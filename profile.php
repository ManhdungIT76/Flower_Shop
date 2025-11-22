<?php
session_start();
include 'include/db_connect.php';

// ===== KIỂM TRA ĐĂNG NHẬP =====
if (!isset($_SESSION["user"])) {
    echo "<script>alert('Bạn cần đăng nhập trước ✨'); window.location='login.php';</script>";
    exit;
}

$user_id = $_SESSION["user"]["id"];

// ===== LẤY THÔNG TIN NGƯỜI DÙNG =====
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Không tìm thấy người dùng");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hồ sơ cá nhân</title>
  
  <!-- Load CSS chung cho toàn bộ website -->
  <link rel="stylesheet" href="assets/css/global.css">

  <!-- CSS riêng của trang hồ sơ -->
  <link rel="stylesheet" href="assets/css/profile.css">
</head>

<body>

<?php include 'components/header.php'; ?>

<section class="profile-section">

    <h2>Hồ sơ cá nhân</h2>

    <form action="pages/update_profile.php" method="POST">

      <label>Họ và tên</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>

      <label>Tên đăng nhập</label>
      <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label>Số điện thoại</label>
      <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number']) ?>">

      <label>Địa chỉ</label>
      <input type="text" name="shipping_address" value="<?= htmlspecialchars($user['shipping_address']) ?>">

      <button class="btn">Cập nhật thông tin</button>

    </form>
</section>

<?php include 'components/footer.php'; ?>


<!-- ====================== POPUP BỰ ====================== -->

<div id="popup" class="popup-overlay">
  <div class="popup-box">
    <div class="popup-icon">✔</div>
    <p id="popup-message"></p>
  </div>
</div>

<style>
/* Mờ nền */
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

/* Box popup */
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

/* Icon xanh */
.popup-icon {
    font-size: 55px;
    color: #4CAF50;
    margin-bottom: 12px;
}

/* Animation zoom */
@keyframes zoomIn {
    from { transform: scale(0.6); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}
</style>

<script>
function showPopup(message) {
    let popup = document.getElementById("popup");
    let msg = document.getElementById("popup-message");

    msg.innerText = message;
    popup.style.display = "flex";

    // Tự tắt sau 2 giây
    setTimeout(() => {
        popup.style.display = "none";
    }, 2000);
}
</script>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
<script>
    showPopup("Cập nhật thông tin thành công ✨");
</script>
<?php endif; ?>

</body>
</html>
