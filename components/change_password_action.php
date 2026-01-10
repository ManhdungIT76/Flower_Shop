<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user'])) {
  echo json_encode(["ok" => false, "message" => "Chưa đăng nhập."]);
  exit;
}

$userId = $_SESSION['user']['id'] ?? ($_SESSION['user']['user_id'] ?? null);
if (!$userId) {
  echo json_encode(["ok" => false, "message" => "Thiếu user_id trong session."]);
  exit;
}
function has_whitespace($s) { return preg_match('/\s/', $s) === 1; }
$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$cfm = $_POST['confirm_password'] ?? '';

if ($old === '' || $new === '' || $cfm === '') {
  echo json_encode(["ok" => false, "message" => "Vui lòng nhập đủ thông tin."]);
  exit;
}

// ràng buộc mật khẩu mới: >=6 và KHÔNG có khoảng trắng
if (mb_strlen($new) < 6 || has_whitespace($new)) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu mới tối thiểu 6 ký tự và không chứa khoảng trắng."]);
  exit;
}

// xác nhận khớp
if ($new !== $cfm) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu mới không khớp."]);
  exit;
}

// không cho đổi sang đúng mật khẩu cũ (tránh thao tác thừa)
if (hash_equals($old, $new)) {
  echo json_encode(["ok" => false, "message" => "Mật khẩu mới phải khác mật khẩu hiện tại."]);
  exit;
}

/* Kết nối DB (nếu bạn đã có include db_connect.php thì dùng $conn từ đó; ở đây giữ như bạn đang viết) */
$conn = new mysqli("localhost", "root", "", "flowershopdb");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
  echo json_encode(["ok" => false, "message" => "Lỗi DB."]);
  exit;
}

/* Lấy mật khẩu hiện tại */
$stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("s", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$current = $row['password'] ?? null;
if ($current === null) {
  $conn->close();
  echo json_encode(["ok" => false, "message" => "Không tìm thấy user."]);
  exit;
}

/* Nhận diện hash */
$isHash = false;
if (is_string($current)) {
  if (str_starts_with($current, '$2y$') || str_starts_with($current, '$argon2')) $isHash = true;
}

/* Verify mật khẩu cũ */
$okOld = $isHash ? password_verify($old, $current) : hash_equals((string)$current, (string)$old);

if (!$okOld) {
  $conn->close();
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
