<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
  echo json_encode(["ok" => false, "message" => "Chưa đăng nhập."]);
  exit;
}

/*
  Yêu cầu session phải có user_id.
  Nếu bạn đang lưu key khác, sửa lại cho đúng.
*/
$userId = $_SESSION['user']['id'] ?? ($_SESSION['user']['user_id'] ?? null);
if (!$userId) {
  echo json_encode(["ok" => false, "message" => "Thiếu user_id trong session."]);
  exit;
}

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$cfm = $_POST['confirm_password'] ?? '';

if ($old === '' || $new === '' || $cfm === '') {
  echo json_encode(["ok" => false, "message" => "Vui lòng nhập đủ thông tin."]);
  exit;
}
if ($new !== $cfm) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu mới không khớp."]);
  exit;
}
if (mb_strlen($new) < 6) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu mới tối thiểu 6 ký tự."]);
  exit;
}

/* Kết nối DB */
$conn = new mysqli("localhost", "root", "", "flowershopdb");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
  echo json_encode(["ok" => false, "message" => "Lỗi DB."]);
  exit;
}

/* Lấy mật khẩu hiện tại */
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
$stmt->bind_param("s", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$current = $row['password'] ?? null;
if ($current === null) {
  echo json_encode(["ok" => false, "message" => "Không tìm thấy user."]);
  exit;
}

/*
  Tự nhận diện kiểu lưu mật khẩu:
  - Nếu là hash chuẩn PHP: thường bắt đầu bằng "$2y$" (bcrypt) hoặc "$argon2"
  - Nếu không: coi như plain text (phù hợp với dữ liệu đang thấy trên phpMyAdmin: 123/admin...)
*/
$isHash = false;
if (is_string($current)) {
  if (str_starts_with($current, '$2y$') || str_starts_with($current, '$argon2')) $isHash = true;
}

/* Verify mật khẩu cũ */
$okOld = false;
if ($isHash) {
  if (password_verify($old, $current)) $okOld = true;
} else {
  if (hash_equals($current, $old)) $okOld = true;
}

if (!$okOld) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu hiện tại không đúng."]);
  exit;
}

/* Lưu mật khẩu mới: giữ cùng kiểu để không làm hỏng login hiện tại */
$newStore = $isHash ? password_hash($new, PASSWORD_DEFAULT) : $new;

$u = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
$u->bind_param("ss", $newStore, $userId);

if ($u->execute()) {
  echo json_encode(["ok" => true, "message" => "Đổi mật khẩu thành công."]);
} else {
  echo json_encode(["ok" => false, "message" => "Không cập nhật được mật khẩu."]);
}
$u->close();
$conn->close();
