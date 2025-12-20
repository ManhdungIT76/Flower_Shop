<?php
session_start();
include 'include/db_connect.php';
require 'include/send_mail.php';

$input = trim($_POST['email'] ?? '');
$status = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($input === "") {
        $status = "empty";
    } else {
        $stmt = $conn->prepare("SELECT user_id, email, full_name FROM users WHERE email=? OR phone_number=? LIMIT 1");
        $stmt->bind_param("ss", $input, $input);
        $stmt->execute();
        $rs = $stmt->get_result();

        if ($rs && $rs->num_rows > 0) {
            $user = $rs->fetch_assoc();

            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 15 * 60);

            $upd = $conn->prepare("UPDATE users SET reset_token_hash=?, reset_token_expires=? WHERE user_id=?");
            $upd->bind_param("sss", $token_hash, $expires, $user['user_id']);
            $upd->execute();
            $upd->close();

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $reset_link = $scheme . '://' . $host . $path . '/reset_password.php?token=' . urlencode($token);

            $toEmail = $user['email'];
            $toName  = $user['full_name'] ?: $user['email'];

            $ok = send_reset_mail($toEmail, $toName, $reset_link);
            $status = $ok ? "sent" : "send_fail";
        } else {
            $status = "not_found";
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quên mật khẩu</title>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root{
      --pink:#f1a791;
      --pink-hover:#e38e78;
      --bg1:#fffaf5;
      --bg2:#fffdfb;
      --text:#4b3f36;
      --muted:#8a7f78;
      --err:#b00020;
      --ok:#0a7a3f;
    }

    *{box-sizing:border-box}

    body{
      margin:0;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      font-family:"Poppins", sans-serif;
      background:
        radial-gradient(circle at top, #fff3ee, var(--bg2) 55%);
      padding:24px;
    }

    .card{
      width:520px;
      max-width:92vw;
      background:rgba(255,255,255,0.92);
      border-radius:22px;
      padding:26px 26px 22px;
      box-shadow:
        0 24px 60px rgba(241,167,145,0.25),
        0 10px 22px rgba(0,0,0,0.08);
      border:1px solid rgba(241,167,145,0.20);
      animation:fadeUp .28s ease-out;
      text-align:center;
    }

    @keyframes fadeUp{
      from{opacity:0; transform:translateY(10px)}
      to{opacity:1; transform:none}
    }

    .icon{
      width:64px;
      height:64px;
      border-radius:50%;
      margin:0 auto 14px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:30px;
      background:linear-gradient(135deg,var(--pink),var(--pink-hover));
      box-shadow:0 10px 20px rgba(241,167,145,0.35);
    }
    .icon.err{
      background:linear-gradient(135deg,#ff8a8a,#e57373);
      box-shadow:0 10px 20px rgba(229,115,115,0.35);
    }

    h2{
      margin:0 0 8px;
      font-size:20px;
      font-weight:600;
      color:var(--text);
    }
    p{
      margin:0 0 18px;
      font-size:15px;
      line-height:1.5;
      color:var(--muted);
    }

    .msg-ok{color:var(--ok)}
    .msg-err{color:var(--err)}

    .actions{
      display:flex;
      justify-content:center;
      gap:12px;
      flex-wrap:wrap;
      margin-top:6px;
    }

    .btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:12px 18px;
      border-radius:999px;
      text-decoration:none;
      font-size:15px;
      font-weight:500;
      border:none;
      cursor:pointer;
      transition:.25s;
      user-select:none;
    }

    .btn-primary{
      background:linear-gradient(135deg,var(--pink),var(--pink-hover));
      color:#fff;
      box-shadow:0 10px 20px rgba(241,167,145,0.30);
    }
    .btn-primary:hover{
      transform:translateY(-1px);
      filter:brightness(1.05);
    }

    .btn-ghost{
      background:#e4e6eb;
      color:#111;
    }
    .btn-ghost:hover{
      filter:brightness(0.98);
      transform:translateY(-1px);
    }
  </style>
</head>

<body>
  <div class="card">
    <?php if ($status === "sent"): ?>
      <div class="icon">✓</div>
      <h2>Email đã được gửi</h2>
      <p class="msg-ok">Đã gửi email đặt lại mật khẩu. Kiểm tra hộp thư (kể cả Spam).</p>
      <div class="actions">
        <a class="btn btn-primary" href="login.php">Quay lại đăng nhập</a>
      </div>

    <?php elseif ($status === "send_fail"): ?>
      <div class="icon err">!</div>
      <h2>Gửi email thất bại</h2>
      <p class="msg-err">Không gửi được email. Kiểm tra cấu hình SMTP (host/port/app password).</p>
      <div class="actions">
        <a class="btn btn-primary" href="login.php">Quay lại</a>
      </div>

    <?php elseif ($status === "not_found"): ?>
      <div class="icon err">!</div>
      <h2>Không tìm thấy tài khoản</h2>
      <p class="msg-err">Không tìm thấy tài khoản theo email hoặc số di động đã nhập.</p>
      <div class="actions">
        <a class="btn btn-primary" href="login.php">Quay lại</a>
      </div>

    <?php elseif ($status === "empty"): ?>
      <div class="icon err">!</div>
      <h2>Thiếu thông tin</h2>
      <p class="msg-err">Vui lòng nhập email hoặc số di động.</p>
      <div class="actions">
        <a class="btn btn-primary" href="login.php">Quay lại</a>
      </div>

    <?php else: ?>
      <div class="icon err">!</div>
      <h2>Yêu cầu không hợp lệ</h2>
      <p class="msg-err">Vui lòng thao tác lại từ trang đăng nhập.</p>
      <div class="actions">
        <a class="btn btn-primary" href="login.php">Quay lại</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
