<?php
session_start();
include '../include/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) { http_response_code(401); echo json_encode(["status"=>"unauth"]); exit; }

$order_id = trim($_POST['order_id'] ?? '');
$user_id  = $_SESSION['user']['id'];

if ($order_id === '') { echo json_encode(["status"=>"missing"]); exit; }

$stmt = $conn->prepare("SELECT status, payment_status FROM orders WHERE order_id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ss", $order_id, $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { echo json_encode(["status"=>"not_found"]); exit; }
// Chỉ cho hủy khi đang chờ xác nhận và chưa thanh toán
if ($row['status'] !== 'Chờ xác nhận' || $row['payment_status'] !== 'Chưa thanh toán') {
    echo json_encode(["status"=>"not_allowed"]); exit;
}

// Hoàn kho, nhưng giữ order_details
$items = $conn->prepare("SELECT product_id, quantity FROM order_details WHERE order_id=?");
$items->bind_param("s", $order_id);
$items->execute();
$res = $items->get_result();
while ($r = $res->fetch_assoc()) {
    $up = $conn->prepare("UPDATE products SET stock = stock + ? WHERE product_id=?");
    $up->bind_param("is", $r['quantity'], $r['product_id']);
    $up->execute();
}

// Cập nhật trạng thái đơn + thanh toán, không xóa chi tiết
$upd = $conn->prepare("UPDATE orders SET status='Đã hủy', payment_status='Chưa thanh toán' WHERE order_id=? AND user_id=?");
$upd->bind_param("ss", $order_id, $user_id);
$upd->execute();

echo json_encode(["status"=>"ok"]);
