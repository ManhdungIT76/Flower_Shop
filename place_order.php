<?php
session_start();
include 'include/db_connect.php';

if (!isset($_SESSION['user']['id'])) {
    echo "NOT_LOGGED_IN";
    exit;
}

// Hỗ trợ mua ngay: nếu gọi GET kèm product_id => tạo giỏ và chuyển sang giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['product_id'])) {
    $buyProductId = $_GET['product_id'];
    $buyQty = (int)($_GET['quantity'] ?? 1);
    if ($buyQty <= 0) $buyQty = 1;

    $p = $conn->prepare("SELECT product_id, product_name, price, image, stock FROM products WHERE product_id=? LIMIT 1");
    $p->bind_param("s", $buyProductId);
    $p->execute();
    $product = $p->get_result()->fetch_assoc();

    if (!$product) { echo "PRODUCT_NOT_FOUND"; exit; }
    if ($product['stock'] < $buyQty) { echo "OUT_OF_STOCK"; exit; }

    $_SESSION['cart'] = [[
        'id'    => $product['product_id'],
        'name'  => $product['product_name'],
        'price' => (float)$product['price'],
        'qty'   => $buyQty,
        'image' => $product['image'] ?? ''
    ]];

    header("Location: cart.php");
    exit;
}

// Nếu không phải POST thì báo lỗi
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "INVALID_METHOD";
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "INVALID_METHOD";
    exit;
}

// Thông tin người đặt
$user_id = $_SESSION['user']['id'];
$name    = trim($_POST['full_name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$payment = $_POST['payment_method'] ?? 'cod';   // cod / qr
$note    = trim($_POST['note'] ?? "");

// “Mua ngay” gửi kèm product_id + quantity
$buyProductId = $_POST['product_id'] ?? '';
$buyQty       = (int)($_POST['quantity'] ?? 1);
if ($buyQty <= 0) $buyQty = 1;

// Nếu chưa có giỏ hàng và có thông tin mua ngay -> tự tạo giỏ
if ((!isset($_SESSION['cart']) || empty($_SESSION['cart'])) && $buyProductId !== '') {
    $p = $conn->prepare("SELECT product_id, product_name, price, image, stock FROM products WHERE product_id=? LIMIT 1");
    $p->bind_param("s", $buyProductId);
    $p->execute();
    $product = $p->get_result()->fetch_assoc();

    if (!$product) {
        echo "PRODUCT_NOT_FOUND";
        exit;
    }
    if ($product['stock'] < $buyQty) {
        echo "OUT_OF_STOCK";
        exit;
    }

    $_SESSION['cart'] = [
        [
            'id'    => $product['product_id'],
            'name'  => $product['product_name'],
            'price' => (float)$product['price'],
            'qty'   => $buyQty,
            'image' => $product['image'] ?? ''
        ]
    ];
}

// Kiểm tra giỏ
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "EMPTY_CART";
    exit;
}

// Validate input cơ bản
if ($name === '' || $phone === '' || $address === '') {
    echo "MISSING_INFO";
    exit;
}

// Chọn ID phương thức thanh toán
$payment_method_id = ($payment === "qr") ? "TT002" : "TT001";
// Delivery mặc định
$delivery_method_id = "GH002"; // Giao tiêu chuẩn

// Tính tổng tiền và kiểm tra tồn kho/giá hiện tại
$total_amount = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_amount += $item['price'] * $item['qty'];
}

// Bắt đầu transaction để tránh lệch dữ liệu
$conn->begin_transaction();

try {
    // Tạo ORDER
    $sql = "INSERT INTO orders (user_id, total_amount, note, payment_method_id, delivery_method_id)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $user_id, $total_amount, $note, $payment_method_id, $delivery_method_id);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Thêm ORDER DETAILS + trừ kho (có kiểm tra tồn)
    foreach ($_SESSION['cart'] as $item) {
        // kiểm tra tồn kho hiện tại
        $chk = $conn->prepare("SELECT stock, price FROM products WHERE product_id=? LIMIT 1");
        $chk->bind_param("s", $item["id"]);
        $chk->execute();
        $prod = $chk->get_result()->fetch_assoc();

        if (!$prod || $prod['stock'] < $item['qty']) {
            throw new Exception("OUT_OF_STOCK");
        }
        $unit_price = (float)$prod['price']; // dùng giá DB hiện tại

        $d = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price)
                             VALUES (?, ?, ?, ?)");
        $d->bind_param("ssid", $order_id, $item["id"], $item["qty"], $unit_price);
        $d->execute();

        $s = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
        $s->bind_param("is", $item["qty"], $item["id"]);
        $s->execute();

        $total_amount += 0; // giữ chỗ nếu muốn cộng thêm phí khác sau này
    }

    // Lưu địa chỉ/điện thoại vào user nếu có
    $u = $conn->prepare("UPDATE users SET shipping_address=?, phone_number=? WHERE user_id=?");
    $u->bind_param("sss", $address, $phone, $user_id);
    $u->execute();

    $conn->commit();

    // Xóa giỏ hàng
    unset($_SESSION['cart']);

    echo "SUCCESS";
} catch (Exception $e) {
    $conn->rollback();
    $msg = $e->getMessage();
    echo $msg ?: "ERROR";
}
?>
