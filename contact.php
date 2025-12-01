<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$isLoggedIn = $user !== null;
include 'components/header.php';
?>
<link rel="stylesheet" href="assets/css/contact.css">

<section class="contact-hero">
  <div class="hero-blob"></div>
  <div class="hero-text">
    <span class="eyebrow">Blossomy Bliss</span>
    <h1>Liên hệ & kết nối</h1>
    <p class="sub">
      Để lại lời nhắn, đội ngũ của chúng tôi sẽ phản hồi trong giờ làm việc hoặc gọi ngay nếu bạn cần gấp.
    </p>

    <div class="hero-stats">
      <div class="stat">
        <p class="label">Thời gian</p>
        <p class="value"><i class="fa-regular fa-clock"></i> 08:00 - 21:00</p>
      </div>
      <div class="stat">
        <p class="label">Giao nhanh</p>
        <p class="value"><i class="fa-solid fa-truck"></i> 2h nội thành</p>
      </div>
      <div class="stat">
        <p class="label">Chất lượng</p>
        <p class="value"><i class="fa-regular fa-gem"></i> Hoa tươi chọn lọc</p>
      </div>
    </div>

    <div class="hero-ctas">
      <a class="ghost-btn" href="tel:0901234567"><i class="fa-solid fa-phone"></i> Gọi ngay</a>
      <a class="ghost-btn" href="mailto:hello@blossomy.vn"><i class="fa-regular fa-envelope"></i> Gửi email</a>
    </div>
  </div>

  <div class="hero-card">
    <div class="cta">
      <div class="pill">Cửa hàng</div>
      <h3>Blossomy Bliss Studio</h3>
      <p><i class="fa-solid fa-location-dot"></i> 155 Chế Lan Viên, Quy Nhơn, Bình Định</p>
      <p><i class="fa-solid fa-phone"></i> 0901 234 567</p>
      <p><i class="fa-regular fa-envelope"></i> nguyenduythinh1112@gmail.com</p>
    </div>
    <div class="map">
      <iframe
        src="https://www.google.com/maps?q=155+Ch%E1%BA%BF+Lan+Vi%C3%AAn,+Quy+Nh%C6%A1n,+B%C3%ACnh+%C4%90%E1%BB%8Bnh&output=embed"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
  </div>
</section>

<section class="contact-grid">
  <div class="card form-card">
    <div class="card-head">
      <h2>Gửi lời nhắn</h2>
      <p>Chia sẻ nhu cầu hoặc vấn đề bạn gặp phải, chúng tôi sẽ phản hồi sớm.</p>
      <?php if (!$isLoggedIn): ?>
        <div class="alert warning">Vui lòng đăng nhập trước khi gửi lời nhắn.</div>
      <?php endif; ?>
    </div>
    <form class="contact-form" action="contact_mail.php" method="POST">
      <div class="field">
        <label>Họ tên</label>
        <input
          type="text"
          name="name"
          placeholder="Nhập tên của bạn"
          value="<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES); ?>"
          required>
      </div>
      <div class="field">
        <label>Email</label>
        <input
          type="email"
          name="email"
          placeholder="Email để nhận phản hồi"
          value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>"
          required>
      </div>
      <div class="field two-col">
        <div>
          <label>Số điện thoại</label>
          <input
            type="text"
            name="phone"
            placeholder="VD: 0901 234 567"
            value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES); ?>">
        </div>
        <div>
          <label>Chủ đề</label>
          <select name="topic">
            <option>Đặt hoa theo yêu cầu</option>
            <option>Hỗ trợ đơn hàng</option>
            <option>Hợp tác & sự kiện</option>
            <option>Khác</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Tin nhắn</label>
        <textarea name="message" rows="4" placeholder="Mô tả nhu cầu hoặc vấn đề bạn gặp phải" required></textarea>
      </div>
      <?php if ($isLoggedIn): ?>
        <button type="submit" class="btn-primary">Gửi ngay</button>
      <?php else: ?>
        <button type="button" class="btn-primary" onclick="window.location.href='login.php?redirect=contact.php'">Đăng nhập để gửi</button>
      <?php endif; ?>
    </form>
  </div>

  <div class="card info-card">
    <div class="block">
      <h3>Kênh liên lạc nhanh</h3>
      <ul>
        <li><i class="fa-brands fa-facebook-messenger"></i> Facebook Messenger</li>
        <li><i class="fa-brands fa-instagram"></i> Instagram @blossomy.vn</li>
        <li><i class="fa-brands fa-tiktok"></i> TikTok @blossomybliss</li>
        <li><i class="fa-brands fa-whatsapp"></i> Zalo/WhatsApp 0901 234 567</li>
      </ul>
    </div>
    <div class="block hours">
      <h3>Giờ hỗ trợ</h3>
      <p>Thứ 2 - CN: 08:00 - 21:00</p>
      <p>Ưu tiên: đơn gấp, giao trong ngày, sự kiện.</p>
    </div>
    <div class="block perks">
      <h3>Cam kết</h3>
      <p><i class="fa-regular fa-heart"></i> Hoa tươi mới mỗi sáng</p>
      <p><i class="fa-solid fa-shield-heart"></i> Bảo hành tươi 48h</p>
      <p><i class="fa-solid fa-leaf"></i> Giao xanh, đóng gói thân thiện</p>
    </div>
  </div>
</section>

<?php
include 'components/footer.php';
?>
