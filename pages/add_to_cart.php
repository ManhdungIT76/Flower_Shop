<?php
// add_to_cart.php (TRẢ JSON + CHẶN VƯỢT TỒN KHO)
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../include/admin_gate.php';
forbid_admin_buying(); // nếu hàm này đang echo/redirect thì sẽ làm hỏng JSON (xem ghi chú dưới)

include '../include/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(["ok" => false, "message" => "Thiếu id sản phẩm."]);
    exit;
}

$product_id = $_GET['id'];
$qty = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
if ($qty < 1) $qty = 1;

// Lấy sản phẩm + tồn kho
$p = $conn->prepare("SELECT product_id, product_name, price, image_url, stock FROM products WHERE product_id=? LIMIT 1");
$p->bind_param("s", $product_id);
$p->execute();
$product = $p->get_result()->fetch_assoc();
$p->close();

if (!$product) {
    echo json_encode(["ok" => false, "message" => "Sản phẩm không tồn tại."]);
    exit;
}

$stock = (int)($product['stock'] ?? 0);
if ($stock <= 0) {
    echo json_encode(["ok" => false, "message" => "Sản phẩm đã hết hàng."]);
    exit;
}

// Tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$current_qty = isset($_SESSION['cart'][$product_id]) ? (int)$_SESSION['cart'][$product_id]['qty'] : 0;
$new_qty = $current_qty + $qty;

// Chặn vượt tồn kho (tính theo tổng trong giỏ)
if ($new_qty > $stock) {
    echo json_encode([
        "ok" => false,
        "message" => "Không đủ số lượng trong kho. Hiện còn {$stock} sản phẩm."
    ]);
    exit;
}

// Ghi vào giỏ
if ($current_qty > 0) {
    $_SESSION['cart'][$product_id]['qty'] = $new_qty;
} else {
    $_SESSION['cart'][$product_id] = [
        "id"    => $product['product_id'],
        "name"  => $product['product_name'],
        "price" => (float)$product['price'],
        "image" => $product['image_url'],
        "qty"   => $qty
    ];
}

echo json_encode(["ok" => true, "message" => "Đã thêm vào giỏ hàng."]);
exit;
?>
