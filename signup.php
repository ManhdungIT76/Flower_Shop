<?php
include 'include/db_connect.php';

$signup_status = "";
$message = "";
$redirect_url = "";

// ===== XỬ LÝ KHI SUBMIT =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $fullname = trim($_POST["fullname"]);
  $username = trim($_POST["username"]);
  $email = trim($_POST["email"]);
  $phone = trim($_POST["phone"]);
  $password = trim($_POST["password"]);
  $confirm = trim($_POST["confirm_password"]);

  // KIỂM TRA MẬT KHẨU
  if ($password !== $confirm) {
      $signup_status = "confirm_fail";
      $message = "Mật khẩu không khớp 😢";
  } else {

      // KIỂM TRA TRÙNG USERNAME / EMAIL
      $check = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
      $check->bind_param("ss", $username, $email);
      $check->execute();
      $check->store_result();

      if ($check->num_rows > 0) {
          $signup_status = "exists";
          $message = "Tên đăng nhập hoặc email đã tồn tại ❌";
      } else {

          // Thêm user
          $created_at = date("Y-m-d H:i:s");
          $updated_at = $created_at;
          $role = "Khách hàng";
          $shipping_address = "";

          // ⚠ HASH MẬT KHẨU (nếu muốn admin bảo mật)
          $hashed_pass = $password;

          $stmt = $conn->prepare("
              INSERT INTO users (username, password, full_name, email, phone_number, created_at, updated_at, role, shipping_address)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
          ");
          $stmt->bind_param("sssssssss", $username, $hashed_pass, $fullname, $email, $phone, $created_at, $updated_at, $role, $shipping_address);

          if ($stmt->execute()) {
              $signup_status = "success";
              $message = "Đăng ký thành công ✨";
              $redirect_url = "login.php";
          } else {
              $signup_status = "error";
              $message = "Lỗi server. Vui lòng thử lại 😢";
          }

          $stmt->close();
      }

      $check->close();
  }

  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký | Blossomy Bliss</title>

  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/login_signup.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ==== POPUP ==== */
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
    margin-bottom: 12px;
}

@keyframes zoomIn {
    from { transform: scale(0.6); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

.popup-btn {
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

<!-- ===== POPUP ===== -->
<?php if ($signup_status !== ""): ?>
<div class="popup-overlay">
  <div class="popup-box">

    <?php if ($signup_status === "success"): ?>
        <div class="popup-icon" style="color:#4CAF50;">✔</div>
        <p><?= $message ?></p>

        <script>
            setTimeout(() => {
                window.location.href = "<?= $redirect_url ?>";
            }, 1500);
        </script>

    <?php else: ?>
        <div class="popup-icon" style="color:red;">✖</div>
        <p><?= $message ?></p>
        <a class="popup-btn" href="signup.php">Thử lại</a>
    <?php endif; ?>

  </div>
</div>
<?php endif; ?>


<!-- ===== FORM ĐĂNG KÝ ===== -->
<main class="auth-page">
  <div class="auth-container">
    <h2>Đăng ký tài khoản</h2>

    <form id="signup-form" action="" method="POST">

      <div class="input-group">
        <i class="fa-solid fa-id-card"></i>
        <input type="text" name="fullname" placeholder="Họ và tên" required>
      </div>

      <div class="input-group">
        <i class="fa-solid fa-user"></i>
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
      </div>

      <div class="input-group">
        <i class="fa-solid fa-envelope"></i>
        <input type="email" name="email" placeholder="Email" required>
      </div>

      <div class="input-group">
        <i class="fa-solid fa-phone"></i>
        <input type="tel" name="phone" placeholder="Số điện thoại" required pattern="[0-9]{10}" maxlength="10">
      </div>

      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" placeholder="Mật khẩu" required minlength="6">
      </div>

      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
      </div>

      <button type="submit" class="btn-auth">Đăng ký</button>

      <p class="auth-text">
        Đã có tài khoản? <a href="login.php">Đăng nhập</a>
      </p>
    </form>
  </div>
</main>

</body>
</html>
