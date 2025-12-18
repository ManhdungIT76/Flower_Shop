<?php
session_start();
include '../include/db_connect.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo "not_login";
    exit;
}

$order_id = trim($_POST['order_id'] ?? '');
$user_id  = $_SESSION['user']['id'];

if ($order_id === '') { echo "missing"; exit; }

// Chỉ hủy đơn QR chưa thanh toán của user
$stmt = $conn->prepare("
    SELECT order_id FROM orders
    WHERE order_id=? AND user_id=? 
      AND payment_method_id='TT002'
      AND payment_status='Chưa thanh toán'
    LIMIT 1
");
$stmt->bind_param("ss", $order_id, $user_id);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
if (!$found) { echo "not_found_or_paid"; exit; }

// Hoàn kho (giữ nguyên đoạn hoàn kho hiện có)
$items = $conn->prepare("SELECT product_id, quantity FROM order_details WHERE order_id=?");
$items->bind_param("s", $order_id);
$items->execute();
$res = $items->get_result();
while ($row = $res->fetch_assoc()) {
    $up = $conn->prepare("UPDATE products SET stock = stock + ? WHERE product_id=?");
    $up->bind_param("is", $row['quantity'], $row['product_id']);
    $up->execute();
}

// Xóa chi tiết và set trạng thái
// $delDetails = $conn->prepare("DELETE FROM order_details WHERE order_id=?");
// $delDetails->bind_param("s", $order_id);
// $delDetails->execute();

// Cập nhật trạng thái đơn và trạng thái thanh toán
$upd = $conn->prepare("
    UPDATE orders
    SET status='Đã hủy', payment_status='Chưa thanh toán'
    WHERE order_id=?
");
$upd->bind_param("s", $order_id);
$upd->execute();

// Nếu muốn giữ giỏ hàng, hãy xóa dòng unset($_SESSION['cart']);
echo "ok";
