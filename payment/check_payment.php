<?php
// ===== FILE 3: payment/check_payment.php =====
require_once __DIR__ . '/../include/admin_gate.php';
forbid_admin_buying();
include '../include/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(["error" => "not_login"]);
    exit;
}

$user_id  = $_SESSION['user']['id'];
$order_id = $_POST['order_id'] ?? ($_GET['order_id'] ?? '');

if ($order_id === '') {
    echo json_encode(["error" => "missing_order_id"]);
    exit;
}

$stmt = $conn->prepare("SELECT payment_status FROM orders WHERE user_id=? AND order_id=? LIMIT 1");
$stmt->bind_param("ss", $user_id, $order_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    echo json_encode(["error" => "not_found"]);
    exit;
}

if (($res['payment_status'] ?? '') === "Đã thanh toán") {
    unset($_SESSION['cart']);
}

echo json_encode($res);
?>
