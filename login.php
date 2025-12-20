<?php
session_start();
include 'include/db_connect.php';

$login_status = "";
$redirect_url = "";

// ===== XỬ LÝ ĐĂNG NHẬP =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_or_email = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    $stmt = $conn->prepare("
        SELECT user_id, username, email, phone_number, password, role, full_name, shipping_address
        FROM users 
        WHERE username=? OR email=? 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user["password"]) {
            // ID dùng cho gợi ý, CF, phân loại user
            $_SESSION["user_id"] = $user["user_id"];

            $_SESSION["user"] = [
                "id"      => $user["user_id"],
                "name"    => $user["full_name"],
                "phone"   => $user["phone_number"],
                "email"   => $user["email"],
                "role"    => $user["role"],
                "address" => $user["shipping_address"]
            ];

            $login_status = "success";
            $redirect_url = "index.php";

        } else {
            $login_status = "wrong_pass";
        }

    } else {
        $login_status = "not_found";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập | Blossomy Bliss</title>

  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/login_signup.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* =========================
   POPUP
========================= */
.popup-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.35);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 99999;
}
.popup-box {
  background: #ffffff;
  padding: 32px 38px;
  border-radius: 20px;
  text-align: center;
  max-width: 370px;
  width: 90%;
  animation: smoothZoom 0.25s ease-out;
  box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}
@keyframes smoothZoom {
  from { transform: scale(0.85); opacity: 0; }
  to   { transform: scale(1); opacity: 1; }
}
.icon-success {
  font-size: 48px;
  color: #66c28f;
  margin-bottom: 10px;
}
.icon-error {
  font-size: 48px;
  color: #e57373;
  margin-bottom: 10px;
}
.popup-message {
  font-size: 17px;
  color: #5b4b43;
  margin-bottom: 22px;
  line-height: 1.45;
}
.popup-btn {
  padding: 10px 22px;
  background: #f1a791;
  color: white;
  border-radius: 25px;
  text-decoration: none;
  font-size: 15px;
  display: inline-block;
  min-width: 110px;
  transition: 0.25s;
}
.popup-btn:hover { background: #e38e78; }
.popup-btn-grey { background: #b8b8b8 !important; }
.popup-btn-grey:hover { background: #9e9e9e !important; }

/* =========================
   FORGOT LINK
========================= */
.forgot-link{
  display:block;
  margin-top:12px;
  text-align:center;
  font-size:14px;
  color:#c59a86;
  text-decoration:none;
  transition:0.2s;
}
.forgot-link:hover{
  color:#e38e78;
  text-decoration:underline;
}

/* =========================
   FORGOT MODAL (FB STYLE)
========================= */
.forgot-modal{
  position:fixed;
  inset:0;
  display:none;
  align-items:center;
  justify-content:center;
  background:rgba(0,0,0,0.35);
  z-index:99999;
}
.forgot-modal.show{ display:flex; }

.forgot-box.fb-style{
  width:520px;
  max-width:92vw;
  background:#ffffff;
  border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,0.12);
  overflow:hidden;
  animation:fadeUp 0.25s ease-out;
}
@keyframes fadeUp{
  from{ opacity:0; transform:translateY(10px); }
  to{ opacity:1; transform:none; }
}
.fb-header{
  padding:16px 20px;
  font-size:20px;
  font-weight:600;
  color:#1c1e21;
  border-bottom:1px solid #eee;
}
.fb-body{
  padding:18px 20px 16px;
}
.fb-body p{
  margin:0 0 14px;
  font-size:15px;
  color:#4b4f56;
}
.fb-body input{
  width:100%;
  padding:14px 16px;
  font-size:16px;
  border-radius:8px;
  border:1.5px solid #ccd0d5;
  outline:none;
}
.fb-body input:focus{
  border-color:#f1a791;
  box-shadow:0 0 0 2px rgba(241,167,145,0.25);
}
.fb-footer{
  margin-top:16px;
  display:flex;
  justify-content:flex-end;
  gap:12px;
}
.btn-cancel{
  padding:10px 18px;
  border-radius:8px;
  border:none;
  background:#e4e6eb;
  color:#050505;
  font-size:15px;
  cursor:pointer;
}
.btn-search{
  padding:10px 22px;
  border-radius:8px;
  border:none;
  background:#f1a791;
  color:#ffffff;
  font-size:15px;
  font-weight:500;
  cursor:pointer;
  transition:0.25s;
}
.btn-search:hover{ background:#e38e78; }
</style>

</head>
<body>

<!-- ===== POPUP ===== -->
<?php if ($login_status): ?>
<div class="popup-overlay">
  <div class="popup-box">

    <?php if ($login_status === "success"): ?>
      <div class="icon-success">✔</div>
      <p class="popup-message">Đăng nhập thành công !</p>
      <script>
        setTimeout(() => {
          window.location.href = "<?= $redirect_url ?>";
        }, 1500);
      </script>

    <?php elseif ($login_status === "wrong_pass"): ?>
      <div class="icon-error">✖</div>
      <p class="popup-message">Sai mật khẩu, vui lòng thử lại !</p>
      <a class="popup-btn popup-btn-grey" href="login.php">Thử lại</a>

    <?php elseif ($login_status === "not_found"): ?>
      <div class="icon-error">✖</div>
      <p class="popup-message">Không tìm thấy tài khoản, vui lòng thử lại !</p>
      <div style="display:flex; gap:12px; justify-content:center;">
        <a class="popup-btn popup-btn-grey" href="login.php">Thử lại</a>
        <a class="popup-btn" href="signup.php">Đăng ký</a>
      </div>
    <?php endif; ?>

  </div>
</div>
<?php endif; ?>


<!-- ===== FORM LOGIN ===== -->
<main class="auth-page">
  <div class="auth-container">
    <h2>Đăng nhập</h2>

    <form id="login-form" action="" method="POST">

      <div class="input-group">
        <i class="fa-solid fa-user"></i>
        <input type="text" name="username" placeholder="Tên đăng nhập hoặc Email" required>
      </div>

      <div class="input-group">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" placeholder="Mật khẩu" required>
      </div>

      <button type="submit" class="btn-auth">Đăng nhập</button>

      <a href="#" class="forgot-link" id="openForgot">Quên mật khẩu?</a>

      <p class="auth-text">
        Chưa có tài khoản? <a href="signup.php">Đăng ký ngay</a>
      </p>
    </form>
  </div>
</main>


<!-- ===== MODAL QUÊN MẬT KHẨU (FB STYLE) ===== -->
<div class="forgot-modal" id="forgotModal" aria-hidden="true">
  <div class="forgot-box fb-style">

    <div class="fb-header">
      Tìm tài khoản của bạn
    </div>

    <div class="fb-body">
      <p>Vui lòng nhập email hoặc số di động để tìm kiếm tài khoản của bạn.</p>

      <form method="POST" action="forgot_password_request.php">
        <input type="text" name="email" required placeholder="Email hoặc số di động">

        <div class="fb-footer">
          <button type="button" class="btn-cancel" id="closeForgot">Hủy</button>
          <button type="submit" class="btn-search">Tìm kiếm</button>
        </div>
      </form>
    </div>

  </div>
</div>


<script>
  const openForgot = document.getElementById('openForgot');
  const modal = document.getElementById('forgotModal');
  const closeForgot = document.getElementById('closeForgot');

  openForgot.addEventListener('click', function(e){
    e.preventDefault();
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
  });

  closeForgot.addEventListener('click', function(){
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
  });

  modal.addEventListener('click', function(e){
    if (e.target === modal) {
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
    }
  });
</script>

</body>
</html>
