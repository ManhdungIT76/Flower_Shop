<?php
session_start();
include '../include/db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION["user"])) {
    echo "<script>window.location='../login.php';</script>";
    exit;
}

$user_id = $_SESSION["user"]["id"];

// ===== HÀM VALIDATE =====
function has_whitespace($s) { return preg_match('/\s/', $s) === 1; }
function is_valid_email($e) { return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function is_valid_phone($p) { return preg_match('/^0\d{9}$/', $p) === 1; }

$full_name        = trim($_POST['full_name'] ?? '');
$username         = trim($_POST['username'] ?? ''); // không cập nhật username (giữ để không lỗi nếu form có gửi)
$email            = trim($_POST['email'] ?? '');
$phone_number     = trim($_POST['phone_number'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');

$success = false;
$error_message = "";
$skip_update = false;
$is_admin_error = false;

if (($_SESSION["user"]["role"] ?? "") === "admin") {
    $skip_update = true;
    $is_admin_error = true;
    $error_message = "Không được ủy quyền.";
}

// ===== RÀNG BUỘC =====
// Họ tên bắt buộc
if ($skip_update) {
    // no-op
}
else if ($full_name === '') {
    $error_message = "Họ và tên không được để trống";
}
// Email chuẩn
else if ($email === '' || !is_valid_email($email)) {
    $error_message = "Email không hợp lệ";
}
// SĐT: duy nhất, 10 số, bắt đầu 0
else if ($phone_number === '' || !is_valid_phone($phone_number)) {
    $error_message = "Số điện thoại không hợp lệ (bắt đầu bằng 0 và đủ 10 số)";
}
else {
    // Check trùng email / phone với user khác
    $chk = $conn->prepare("SELECT user_id FROM users WHERE (email=? OR phone_number=?) AND user_id<>? LIMIT 1");
    $chk->bind_param("sss", $email, $phone_number, $user_id);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $error_message = "Email hoặc số điện thoại đã tồn tại";
        $chk->close();
    } else {
        $chk->close();

        // Cập nhật thông tin vào database
        $sql = "UPDATE users 
                SET full_name = ?, email = ?, phone_number = ?, shipping_address = ?, updated_at = NOW()
                WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $full_name, $email, $phone_number, $shipping_address, $user_id);

        if ($stmt->execute()) {
            $_SESSION["user"]["full_name"] = $full_name;
            $_SESSION["user"]["email"] = $email;
            $success = true;
        } else {
            $error_message = "Có lỗi xảy ra. Vui lòng thử lại";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Cập nhật hồ sơ</title>

<style>
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}
.popup-box {
    background: white;
    padding: 35px 45px;
    border-radius: 18px;
    text-align: center;
    min-width: 320px;
    max-width: 90%;
    animation: zoomIn 0.35s ease forwards;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.popup-icon {
    font-size: 55px;
    color: #4CAF50;
    margin-bottom: 12px;
}
@keyframes zoomIn {
    from { transform: scale(0.6); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
.back-btn {
    margin-top: 15px;
    padding: 10px 20px;
    background: #4CAF50;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    display: inline-block;
}
</style>

</head>
<body>

<?php if ($success): ?>
<div class="popup-overlay">
  <div class="popup-box">
      <div class="popup-icon">✔</div>
      <p>Cập nhật thông tin thành công</p>
      <a class="back-btn" href="../profile.php">Quay lại hồ sơ</a>
  </div>
</div>
<?php else: ?>
<div class="popup-overlay">
  <div class="popup-box">
      <?php if ($is_admin_error): ?>
          <div class="popup-icon" style="color:red;">✖</div>
      <?php else: ?>
          <div class="popup-icon" style="color:red;">✖</div>
      <?php endif; ?>

      <p><?= htmlspecialchars($error_message !== "" ? $error_message : "Có lỗi xảy ra") ?></p>
      <a class="back-btn" href="../profile.php">Quay lại hồ sơ</a>
  </div>
</div>
<?php endif; ?>

</body>
</html>
