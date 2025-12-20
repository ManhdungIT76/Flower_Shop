<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function send_reset_mail(string $toEmail, string $toName, string $resetLink): bool {
    $cfg = require __DIR__ . '/mailer_config.php';

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = $cfg['secure']; // tls
        $mail->Port       = $cfg['port'];   // 587

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Đặt lại mật khẩu - Blossomy Bliss';

        // ===== SAFE DATA =====
        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

        $displayName = trim($toName);
        if ($displayName === '') $displayName = $toEmail;
        $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');

        // ===== LOGO URL (bạn tự thay) =====
        $logoUrl = 'assets/img/z7128943872304_7000db2b5f7c476efb8c375bf165f8e8.jpg'; // VD: https://your-domain.com/assets/img/logo.png
        $safeLogo = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');

        // Nếu chưa set logoUrl thì không render <img>
        $logoHtml = '';
        if (trim($logoUrl) !== '') {
            $logoHtml = '
              <img src="'.$safeLogo.'" width="54" height="54" alt="Blossomy Bliss"
                   style="display:block;margin:0 auto 10px;border-radius:50%;
                          background:#ffffff;padding:4px;">
            ';
        }

        // ===== HTML BODY =====
        $mail->Body = '
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background-color:#fff1f6;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
  <tr>
    <td align="center">

      <table width="520" cellpadding="0" cellspacing="0"
        style="background:#ffffff;border-radius:18px;
               box-shadow:0 12px 30px rgba(233,30,99,0.25);
               overflow:hidden;">

        <!-- HEADER -->
        <tr>
          <td style="background:#e91e63;padding:22px 24px;text-align:center;">
            '.$logoHtml.'
            <h1 style="margin:0;font-size:22px;color:#ffffff;">Blossomy Bliss</h1>
            <p style="margin:6px 0 0;font-size:14px;color:#ffd6e4;">Cửa hàng hoa &amp; quà tặng</p>
          </td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style="padding:28px 26px;color:#4b2c36;">
            <p style="margin:0 0 14px;font-size:15px;">
              Xin chào <strong>'.$safeName.'</strong>,
            </p>

            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">
              Bạn vừa yêu cầu <strong>đặt lại mật khẩu</strong> cho tài khoản tại
              <strong>Blossomy Bliss</strong>.
            </p>

            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#7a5a64;">
              Nhấn vào nút bên dưới để tạo mật khẩu mới.
              <br>
              <span style="font-size:13px;">(Liên kết có hiệu lực trong 15 phút)</span>
            </p>

            <!-- BUTTON -->
            <p style="text-align:center;margin:30px 0;">
              <a href="'.$safeLink.'"
                 style="display:inline-block;
                        padding:14px 28px;
                        background:#f06292;
                        color:#ffffff;
                        text-decoration:none;
                        border-radius:999px;
                        font-size:15px;
                        font-weight:500;
                        box-shadow:0 8px 18px rgba(240,98,146,0.35);">
                Đặt lại mật khẩu
              </a>
            </p>

            <p style="margin:22px 0 0;font-size:14px;line-height:1.6;color:#7a5a64;">
              Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.
              Mật khẩu của bạn sẽ không thay đổi.
            </p>

            <!-- FALLBACK LINK -->
            <p style="margin:16px 0 0;font-size:12.5px;line-height:1.6;color:#9b7b86;">
              Nếu nút không bấm được, copy link này vào trình duyệt:<br>
              <span style="word-break:break-all;color:#e91e63;">'.$safeLink.'</span>
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:18px 24px;background:#fff1f6;text-align:center;font-size:12px;color:#7a5a64;">
            © '.date('Y').' Blossomy Bliss<br>
            Email được gửi tự động, vui lòng không trả lời.
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
';

        // ===== ALT BODY =====
        $mail->AltBody =
"Đặt lại mật khẩu - Blossomy Bliss

Xin chào $displayName,

Bạn vừa yêu cầu đặt lại mật khẩu.
Mở liên kết dưới đây để tạo mật khẩu mới (hiệu lực 15 phút):

$resetLink

Nếu bạn không yêu cầu, hãy bỏ qua email này.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
