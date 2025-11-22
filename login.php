<?php
session_start();
include 'include/db_connect.php';

$login_status = "";
$redirect_url = "";

// ===== XỬ LÝ ĐĂNG NHẬP =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username_or_email = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("
        SELECT user_id, username, email, phone_number, password, role, full_name, shipping_address
        FROM users 
        WHERE username=? OR email=? 
        LIMIT 1
    ");
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if ($password === $user["password"]) {
            $_SESSION["user"] = [
                "id"    => $user["user_id"],
                "name"  => $user["full_name"],
                "phone" => $user["phone_number"],
                "email" => $user["email"],
                "role"  => $user["role"],
                "address" => $user["shipping_address"]
            ];

            $login_status = "success";

            if ($user["role"] === "admin") {
                $redirect_url = "index.php";
            } else {
                $redirect_url = "index.php";
            }

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
/* POPUP OVERLAY */
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.35);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
}

/* POPUP BOX */
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

/* Animation */
@keyframes smoothZoom {
    from { transform: scale(0.85); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

/* Icon */
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

/* Message */
.popup-message {
    font-size: 17px;
    color: #5b4b43;
    margin-bottom: 22px;
    line-height: 1.45;
}

/* Buttons */
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

.popup-btn:hover {
    background: #e38e78;
}

.popup-btn-grey {
    background: #b8b8b8 !important;
}
.popup-btn-grey:hover {
    background: #9e9e9e !important;
}
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

        <p class="auth-text">
          Chưa có tài khoản? <a href="signup.php">Đăng ký ngay</a>
        </p>
      </form>
    </div>
</main>

</body>
</html>
