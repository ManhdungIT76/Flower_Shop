<?php
session_start();
include '../include/db_connect.php';

// Nếu không có ID thì không xử lý
if (!isset($_GET['id'])) {
    echo "error: missing id";
    exit();
}

$product_id = $_GET['id'];
$qty = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;

if ($qty < 1) $qty = 1;

// Lấy thông tin sản phẩm từ DB
$p = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$p->bind_param("s", $product_id);
$p->execute();
$product = $p->get_result()->fetch_assoc();

if (!$product) {
    echo "error: product not found";
    exit();
}

// Tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Nếu sản phẩm đã có trong giỏ → tăng đúng số lượng
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['qty'] += $qty;
}
// Nếu sản phẩm chưa có → thêm mới
else {
    $_SESSION['cart'][$product_id] = [
        "id"    => $product['product_id'],
        "name"  => $product['product_name'],
        "price" => $product['price'],
        "image" => $product['image_url'],
        "qty"   => $qty
    ];
}

// Trả về thông báo cho AJAX
echo "success";
exit();
?>
