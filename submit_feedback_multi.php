<?php
session_start();
include 'include/db_connect.php';

header("Content-Type: application/json");

// kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Bạn chưa đăng nhập!"
    ]);
    exit;
}

// lấy dữ liệu JSON từ fetch()
$data = json_decode(file_get_contents("php://input"), true);

$order_id = $data['order_id'] ?? null;
$reviews  = $data['reviews'] ?? null;
$user_id  = $_SESSION['user']['id'];

if (!$order_id || !$reviews || !is_array($reviews)) {
    echo json_encode([
        "status" => "error",
        "message" => "Dữ liệu gửi lên không hợp lệ!"
    ]);
    exit;
}

// duyệt từng sản phẩm trong đánh giá
foreach ($reviews as $rv) {

    $product_id = $rv['product_id'];
    $rating     = intval($rv['rating']);
    $comment    = trim($rv['comment']);

    if ($rating == 0 && $comment == "") continue;

    // tạo feedback_id dạng FB001
    $last = $conn->query("SELECT feedback_id FROM feedback ORDER BY feedback_id DESC LIMIT 1");

    if ($last->num_rows > 0) {
        $rowLast = $last->fetch_assoc();
        $num = intval(substr($rowLast['feedback_id'], 2)) + 1;
        $feedback_id = "FB" . str_pad($num, 3, "0", STR_PAD_LEFT);
    } else {
        $feedback_id = "FB001";
    }

    // thêm đánh giá vào DB
    $sql = "INSERT INTO feedback (feedback_id, user_id, product_id, feedback_content, rating)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $feedback_id, $user_id, $product_id, $comment, $rating);
    $stmt->execute();
}

// ⭐⭐⭐ THÊM ĐOẠN NÀY: cập nhật đơn hàng thành đã đánh giá
$update = $conn->prepare("UPDATE orders SET is_reviewed = 1 WHERE order_id = ?");
$update->bind_param("s", $order_id);
$update->execute();

echo json_encode([
    "status" => "success",
    "message" => "Đã lưu đánh giá thành công!"
]);
