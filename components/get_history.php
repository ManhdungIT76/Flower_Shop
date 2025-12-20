<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

// ✅ user_id của bạn là dạng "KH003" (VARCHAR) => KHÔNG ép int
$userId = (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id']))
    ? (string)$_SESSION['user']['id']
    : "";

// chưa login -> trả rỗng
if ($userId === "") {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "flowershopdb";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed"], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT role, message, created_at
        FROM chat_history
        WHERE user_id = ?
        ORDER BY id ASC
        LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "SQL prepare failed: ".$conn->error], JSON_UNESCAPED_UNICODE);
    $conn->close();
    exit;
}

// ✅ bind string
$stmt->bind_param("s", $userId);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;

$stmt->close();
$conn->close();

echo json_encode($out, JSON_UNESCAPED_UNICODE);
