<?php
include 'include/db_connect.php';

$signup_status = "";
$message = "";
$redirect_url = "";

// ===== HÀM HỖ TRỢ =====
function has_whitespace($s) {
    return preg_match('/\s/', $s) === 1;
}
function is_valid_username($u) {
    return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $u) === 1;
}

function is_valid_email_strict($e) {
    if ($e === "") return false;
    if (has_whitespace($e)) return false;

    // reject consecutive dots
    if (strpos($e, '..') !== false) return false;

    // reject starts/ends with dot
    if ($e[0] === '.' || substr($e, -1) === '.') return false;

    // must match basic pattern
    if (!preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $e)) return false;

    // extra dot rules around '@'
    $parts = explode('@', $e);
    if (count($parts) !== 2) return false;

    $local = $parts[0];
    $domain = $parts[1];

    // local/domain cannot start/end with dot
    if ($local === "" || $domain === "") return false;
    if ($local[0] === '.' || substr($local, -1) === '.') return false;
    if ($domain[0] === '.' || substr($domain, -1) === '.') return false;

    return true;
}

function is_valid_phone_vn_basic($p) {
    return preg_match('/^0\d{9}$/', $p) === 1;
}

// ===== XỬ LÝ KHI SUBMIT =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $fullname = trim($_POST["fullname"] ?? "");
  $username = trim($_POST["username"] ?? "");
  $email_raw = trim($_POST["email"] ?? "");
  $email = strtolower($email_raw);
  $phone    = trim($_POST["phone"] ?? "");
  $password = $_POST["password"] ?? "";
  $confirm  = $_POST["confirm_password"] ?? "";

  // ===== 0) FULLNAME =====
  if ($fullname === "") {
      $signup_status = "invalid_fullname";
      $message = "Họ và tên không được để trống.";
  }

  // ===== 1) USERNAME =====
  else if ($username === "" || has_whitespace($username) || !is_valid_username($username)) {
      $signup_status = "invalid_username";
      $message = "Tên đăng nhập không hợp lệ (bắt đầu bằng chữ cái, không dấu, không khoảng trắng, chỉ gồm chữ/số/_).";
  }

  // ===== 2) EMAIL (STRICT) =====
  else if (!is_valid_email_strict($email)) {
      $signup_status = "invalid_email";
      $message = "Email không hợp lệ.";
  }

  // ===== 3) PHONE =====
  else if ($phone === "" || !is_valid_phone_vn_basic($phone)) {
      $signup_status = "invalid_phone";
      $message = "Số điện thoại không hợp lệ (phải đủ 10 số và bắt đầu bằng 0).";
  }

  // ===== 4) PASSWORD =====
  else if (strlen($password) < 6 || has_whitespace($password)) {
      $signup_status = "invalid_password";
      $message = "Mật khẩu không hợp lệ (tối thiểu 6 ký tự và không chứa khoảng trắng).";
  }

  // ===== 5) CONFIRM =====
  else if ($password !== $confirm) {
      $signup_status = "confirm_fail";
      $message = "Mật khẩu không khớp.";
  }

  else {
      // KIỂM TRA TRÙNG USERNAME / EMAIL / PHONE
      $check = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=? OR phone_number=? LIMIT 1");
      $check->bind_param("sss", $username, $email, $phone);
      $check->execute();
      $check->store_result();

      if ($check->num_rows > 0) {
          $signup_status = "exists";
          $message = "Tên đăng nhập / email / số điện thoại đã tồn tại.";
      } else {

          $created_at = date("Y-m-d H:i:s");
          $updated_at = $created_at;
          $role = "Khách hàng";
          $shipping_address = "";

          // HASH MẬT KHẨU
          $hashed_pass = $password;

          $stmt = $conn->prepare("
              INSERT INTO users (username, password, full_name, email, phone_number, created_at, updated_at, role, shipping_address)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
          ");
          $stmt->bind_param("sssssssss", $username, $hashed_pass, $fullname, $email, $phone, $created_at, $updated_at, $role, $shipping_address);

          if ($stmt->execute()) {
              $signup_status = "success";
              $message = "Đăng ký thành công";
              $redirect_url = "login.php";
          } else {
              $signup_status = "error";
              $message = "Lỗi server. Vui lòng thử lại.";
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
        <input type="text" name="username" placeholder="Tên đăng nhập"
                required
                pattern="[A-Za-z][A-Za-z0-9_]*"
                title="Bắt đầu bằng chữ cái, chỉ gồm chữ/số/_ , không khoảng trắng, không dấu, không ký tự đặc biệt">
      </div>

      <div class="input-group">
        <i class="fa-solid fa-envelope"></i>
        <input type="email"
            name="email"
            placeholder="Email"
            required
            pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z.\-]+\.[a-zA-Z]{2,}$"
            title="Email không hợp lệ (ví dụ: name@gmail.com)">
      </div>

      <div class="input-group">
        <i class="fa-solid fa-phone"></i>
        <input type="tel" name="phone" placeholder="Số điện thoại"
                required
                pattern="0[0-9]{9}"
                maxlength="10"
                title="Bắt đầu bằng 0 và đủ 10 số">
      </div>

      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" placeholder="Mật khẩu"
                required
                minlength="6"
                pattern="^\S{6,}$"
                title="Tối thiểu 6 ký tự và không chứa khoảng trắng">
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
