<?php
include 'include/db_connect.php';

$input = json_decode(file_get_contents("php://input"), true);

file_put_contents("webhook_log.txt", print_r($input, true), FILE_APPEND);

$content = $input["content"] ?? "";
$amount  = $input["transferAmount"] ?? 0;

preg_match("/HD\d{3,}/", $content, $m);
$order_id = $m[0] ?? null;

if (!$order_id) {
    echo json_encode(["status"=>"not_found"]);
    exit;
}

// Cập nhật đúng ENUM
$stmt = $conn->prepare("
    UPDATE orders 
    SET 
        payment_status='Đã thanh toán', 
        status='Đang xử lý'
    WHERE order_id=?
");
$stmt->bind_param("s", $order_id);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "order_id" => $order_id
]);
?>
