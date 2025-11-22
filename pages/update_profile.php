<?php
session_start();
include '../include/db_connect.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION["user"])) {
    echo "<script>window.location='login.php';</script>";
    exit;
}

$user_id = $_SESSION["user"]["id"];

// Lấy dữ liệu từ form
$full_name = $_POST['full_name'];
$username = $_POST['username'];
$email = $_POST['email'];
$phone_number = $_POST['phone_number'];
$shipping_address = $_POST['shipping_address'];

// Cập nhật thông tin vào database
$sql = "UPDATE users 
        SET full_name = ?, username = ?, email = ?, phone_number = ?, shipping_address = ?, updated_at = NOW()
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $full_name, $username, $email, $phone_number, $shipping_address, $user_id);

$success = false;

if ($stmt->execute()) {

    $_SESSION["user"]["username"] = $username;
    $_SESSION["user"]["full_name"] = $full_name;
    $_SESSION["user"]["email"] = $email;

    $success = true;

}
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

/* Nút quay lại */
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
      <p>Cập nhật thông tin thành công ✨</p>
      <a class="back-btn" href="../index.php">Quay lại trang chủ</a>
  </div>
</div>
<?php else: ?>
<div class="popup-overlay">
  <div class="popup-box">
      <div class="popup-icon" style="color:red;">✖</div>
      <p>Có lỗi xảy ra. Vui lòng thử lại 😢</p>
      <a class="back-btn" href="../profile.php">Quay lại hồ sơ</a>
  </div>
</div>
<?php endif; ?>

</body>
</html>
