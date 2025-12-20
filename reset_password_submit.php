<?php
session_start();
include 'include/db_connect.php';

$token = trim($_POST['token'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

$status = ""; // success | mismatch | expired | bad | empty

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $status = "bad";
} elseif ($token === "" || $new_password === "" || $confirm_password === "") {
    $status = "empty";
} elseif ($new_password !== $confirm_password) {
    $status = "mismatch";
} else {
    $token_hash = hash('sha256', $token);

    $stmt = $conn->prepare("SELECT user_id, reset_token_expires FROM users WHERE reset_token_hash=? LIMIT 1");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $rs = $stmt->get_result();

    if ($rs && $rs->num_rows > 0) {
        $u = $rs->fetch_assoc();
        $exp = $u['reset_token_expires'];

        if ($exp !== null && strtotime($exp) >= time()) {
            $upd = $conn->prepare("UPDATE users SET password=?, reset_token_hash=NULL, reset_token_expires=NULL WHERE user_id=?");
            $upd->bind_param("ss", $new_password, $u['user_id']);
            $upd->execute();
            $upd->close();

            $status = "success";
        } else {
            $status = "expired";
        }
    } else {
        $status = "bad";
    }

    $stmt->close();
}

$conn->close();

// URL quay về
$back_url = "login.php";
// nếu lỗi mismatch/empty thì quay lại trang reset để sửa
if ($status === "mismatch" || $status === "empty") {
    $back_url = "javascript:history.back()";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thông báo</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
:root{
  --pink:#f1a791;
  --pink-hover:#e38e78;
  --text:#4b3f36;
  --muted:#8a7f78;
  --ok:#0a7a3f;
  --err:#b00020;
}
*{box-sizing:border-box}
body{margin:0;font-family:"Poppins",sans-serif;background:#fffdfb}

/* Overlay */
.popup-overlay{
  position:fixed; inset:0;
  background:rgba(0,0,0,0.35);
  display:flex; align-items:center; justify-content:center;
  z-index:99999;
}

/* Box */
.popup-box{
  width:420px; max-width:92vw;
  background:#ffffff;
  padding:26px 24px 22px;
  border-radius:22px;
  text-align:center;
  box-shadow:0 18px 40px rgba(0,0,0,0.16);
  animation:fadeUp .25s ease-out;
}
@keyframes fadeUp{
  from{opacity:0; transform:translateY(10px)}
  to{opacity:1; transform:none}
}

/* Icon */
.icon{
  width:64px;height:64px;border-radius:50%;
  margin:0 auto 12px;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:30px;
}
.icon.ok{
  background:linear-gradient(135deg,#66c28f,#2fb36c);
}
.icon.err{
  background:linear-gradient(135deg,#ff8a8a,#e57373);
}

.title{
  margin:0 0 8px;
  font-size:18px; font-weight:600;
  color:var(--text);
}
.msg{
  margin:0 0 18px;
  font-size:14.5px; line-height:1.55;
  color:var(--muted);
}
.msg.ok{color:var(--ok)}
.msg.err{color:var(--err)}

/* Button */
.btn{
  display:inline-block;
  padding:12px 22px;
  border-radius:999px;
  text-decoration:none;
  font-size:15px; font-weight:500;
  background:linear-gradient(135deg,var(--pink),var(--pink-hover));
  color:#fff;
  transition:.25s;
}
.btn:hover{transform:translateY(-1px);filter:brightness(1.05)}
</style>
</head>

<body>
<div class="popup-overlay">
  <div class="popup-box">

    <?php if ($status === "success"): ?>
      <div class="icon ok">✓</div>
      <h3 class="title">Cập nhật mật khẩu thành công</h3>
      <p class="msg ok">Bạn sẽ được chuyển về trang đăng nhập.</p>

      <script>
        setTimeout(() => { window.location.href = "login.php"; }, 1400);
      </script>

    <?php elseif ($status === "mismatch"): ?>
      <div class="icon err">!</div>
      <h3 class="title">Mật khẩu không khớp</h3>
      <p class="msg err">Vui lòng nhập lại 2 mật khẩu giống nhau.</p>
      <a class="btn" href="javascript:history.back()">Quay lại</a>

    <?php elseif ($status === "empty"): ?>
      <div class="icon err">!</div>
      <h3 class="title">Thiếu thông tin</h3>
      <p class="msg err">Vui lòng nhập đầy đủ dữ liệu.</p>
      <a class="btn" href="javascript:history.back()">Quay lại</a>

    <?php elseif ($status === "expired"): ?>
      <div class="icon err">!</div>
      <h3 class="title">Liên kết đã hết hạn</h3>
      <p class="msg err">Vui lòng thực hiện quên mật khẩu lại.</p>
      <a class="btn" href="login.php">Về đăng nhập</a>

    <?php else: ?>
      <div class="icon err">!</div>
      <h3 class="title">Liên kết không hợp lệ</h3>
      <p class="msg err">Vui lòng thực hiện lại từ trang đăng nhập.</p>
      <a class="btn" href="login.php">Về đăng nhập</a>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
