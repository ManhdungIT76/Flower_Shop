<?php
header("Content-Type: application/json; charset=utf-8");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../include/db_connect.php";

// Chưa đăng nhập
if (
    !isset($_SESSION['user']) ||
    !is_array($_SESSION['user']) ||
    empty($_SESSION['user']['id'])
) {
    echo json_encode([
        "logged_in" => false,
        "name" => ""
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (string)$_SESSION['user']['id'];
$name = "";

try {
    // ⚠️ Đổi full_name nếu DB bạn dùng tên cột khác
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $name = (string)($row['full_name'] ?? "");
    }
} catch (Throwable $e) {
    // không echo lỗi ra client
}

echo json_encode([
    "logged_in" => true,
    "name" => $name
], JSON_UNESCAPED_UNICODE);
