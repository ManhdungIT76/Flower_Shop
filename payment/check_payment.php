<?php
session_start();
include 'include/db_connect.php';

$user_id = $_SESSION['user']['id'];

$sql = "SELECT payment_status FROM orders WHERE user_id=? ORDER BY order_date DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo json_encode($res);
?>
