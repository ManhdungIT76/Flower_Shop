<?php
session_start();
include 'include/db_connect.php';

$token = trim($_GET['token'] ?? '');
$status = "";

if ($token === "") {
    $status = "bad";
} else {
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT reset_token_expires 
        FROM users 
        WHERE reset_token_hash = ? 
        LIMIT 1
    ");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $rs = $stmt->get_result();

    if ($rs && $rs->num_rows > 0) {
        $u = $rs->fetch_assoc();
        if ($u['reset_token_expires'] && strtotime($u['reset_token_expires']) >= time()) {
            $status = "ok";
        } else {
            $status = "expired";
        }
    } else {
        $status = "bad";
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
<title>Đặt lại mật khẩu | Blossomy Bliss</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root{
  --pink:#f1a791;
  --pink-hover:#e38e78;
  --bg:#fffdfb;
  --text:#4b3f36;
  --muted:#8a7f78;
  --err:#b00020;
}

*{box-sizing:border-box}

body{
  margin:0;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  font-family:"Poppins",sans-serif;
  background:
    radial-gradient(circle at top, #fff3ee, var(--bg) 55%);
  padding:24px;
}

/* CARD */
.auth-card{
  width:420px;
  max-width:92vw;
  background:#fffaf5;
  border-radius:22px;
  padding:28px 26px 26px;
  box-shadow:
    0 24px 60px rgba(241,167,145,0.30),
    inset 0 0 0 1px rgba(241,167,145,0.18);
  animation:fadeUp .28s ease-out;
}

@keyframes fadeUp{
  from{opacity:0; transform:translateY(12px)}
  to{opacity:1; transform:none}
}

/* TITLE */
.auth-card h2{
  margin:0 0 18px;
  text-align:center;
  font-size:20px;
  font-weight:600;
  color:var(--text);
}

/* INPUT */
.input-group{
  margin-bottom:14px;
}
.input-group input{
  width:100%;
  padding:13px 16px;
  border-radius:999px;
  border:1.5px solid #f2c1d1;
  font-size:15px;
  outline:none;
}
.input-group input:focus{
  border-color:var(--pink-hover);
  box-shadow:0 0 0 3px rgba(241,167,145,0.25);
}

/* BUTTON */
.btn-main{
  width:100%;
  margin-top:6px;
  padding:13px 16px;
  border-radius:999px;
  border:none;
  background:linear-gradient(135deg,var(--pink),var(--pink-hover));
  color:#fff;
  font-size:15px;
  font-weight:500;
  cursor:pointer;
  transition:.25s;
}
.btn-main:hover{
  transform:translateY(-1px);
  filter:brightness(1.05);
}

/* MESSAGE */
.msg{
  text-align:center;
  font-size:15px;
  line-height:1.5;
}
.msg.err{color:var(--err)}

.link{
  display:block;
  text-align:center;
  margin-top:16px;
  font-size:14px;
  color:#c59a86;
  text-decoration:none;
}
.link:hover{text-decoration:underline}
</style>
</head>

<body>

<div class="auth-card">

<?php if ($status === "ok"): ?>
  <h2>Đặt lại mật khẩu</h2>

  <form method="POST" action="reset_password_submit.php">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="input-group">
      <input type="password" name="new_password" required placeholder="Mật khẩu mới">
    </div>

    <div class="input-group">
      <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới">
    </div>

    <button type="submit" class="btn-main">Cập nhật mật khẩu</button>
  </form>

<?php elseif ($status === "expired"): ?>
  <h2>Liên kết hết hạn</h2>
  <p class="msg err">Liên kết đặt lại mật khẩu đã hết hạn.<br>Vui lòng thực hiện lại.</p>
  <a class="link" href="login.php">Quay lại đăng nhập</a>

<?php else: ?>
  <h2>Liên kết không hợp lệ</h2>
  <p class="msg err">Liên kết đặt lại mật khẩu không đúng hoặc đã bị sử dụng.</p>
  <a class="link" href="login.php">Quay lại đăng nhập</a>
<?php endif; ?>

</div>

</body>
</html>
