<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'components/header.php';
?>
<link rel="stylesheet" href="assets/css/about.css">

<main class="about-page">
  <section class="about-hero">
    <div class="hero-text">
      <p class="eyebrow">Blossomy Bliss</p>
      <h1>Chúng tôi tạo nên trải nghiệm hoa đáng nhớ</h1>
      <p class="sub">
        Từ những bó hoa đầu tiên ở Quy Nhơn, chúng tôi luôn tin rằng mỗi cành hoa mang theo một câu chuyện.
        Blossomy Bliss hiện là studio hoa chuyên bespoke, sự kiện và giao nhanh nội thành.
      </p>
      <div class="hero-actions">
        <a class="btn-primary" href="products.php">Xem bộ sưu tập</a>
        <a class="ghost-btn" href="contact.php">Đặt thiết kế riêng</a>
      </div>
      <div class="hero-stats">
        <div class="stat">
          <span class="value">8+</span>
          <span class="label">Năm kinh nghiệm</span>
        </div>
        <div class="stat">
          <span class="value">12k</span>
          <span class="label">Đơn hàng giao nhanh</span>
        </div>
        <div class="stat">
          <span class="value">320+</span>
          <span class="label">Sự kiện đã thực hiện</span>
        </div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="hero-card">
        <p class="pill">Quy Nhơn, Bình Định</p>
        <h3>Studio hoa boutique</h3>
        <ul>
          <li><i class="fa-regular fa-star"></i> Hoa tươi mỗi sáng, chọn tay từ vườn liên kết</li>
          <li><i class="fa-solid fa-truck-fast"></i> Giao 2h nội thành, bảo quản lạnh</li>
          <li><i class="fa-regular fa-heart"></i> Thiết kế bespoke cho tiệc, lễ, doanh nghiệp</li>
        </ul>
        <div class="badge">Tư vấn miễn phí</div>
    </div>
  </section>

  <section class="story">
    <div class="section-head">
      <p class="eyebrow">Hành trình</p>
      <h2>Từ góc phố nhỏ đến studio hoa</h2>
      <p class="sub">Blossomy Bliss lớn lên cùng khách hàng địa phương, học cách lắng nghe và sáng tạo.</p>
    </div>
    <div class="timeline">
      <div class="step">
        <span class="dot"></span>
        <h4>2017</h4>
        <p>Mở cửa hàng đầu tiên, tập trung các bó hoa sinh nhật và cảm ơn.</p>
      </div>
      <div class="step">
        <span class="dot"></span>
        <h4>2020</h4>
        <p>Ra mắt dịch vụ giao nhanh 2h, áp dụng đóng gói lạnh.</p>
      </div>
      <div class="step">
        <span class="dot"></span>
        <h4>2022</h4>
        <p>Thành lập đội ngũ sự kiện chuyên biệt cho tiệc, cưới, và doanh nghiệp.</p>
      </div>
      <div class="step">
        <span class="dot"></span>
        <h4>2024</h4>
        <p>Ra mắt studio mới, nâng cấp kho lạnh và khu vực trải nghiệm khách hàng.</p>
      </div>
    </div>
  </section>

  <section class="values">
    <div class="section-head">
      <p class="eyebrow">Giá trị cốt lõi</p>
      <h2>Chăm chút từng cánh hoa</h2>
    </div>
    <div class="value-grid">
      <div class="value-card">
        <div class="icon-circle"><i class="fa-solid fa-leaf"></i></div>
        <h4>Chọn lọc tươi</h4>
        <p>Hoa lấy mới mỗi sáng từ nông trại liên kết, bảo quản lạnh 18-20°C.</p>
      </div>
      <div class="value-card">
        <div class="icon-circle"><i class="fa-regular fa-gem"></i></div>
        <h4>Thiết kế bespoke</h4>
        <p>Đo ni đóng giày cho từng chủ đề: cưới, sinh nhật, doanh nghiệp, lễ tết.</p>
      </div>
      <div class="value-card">
        <div class="icon-circle"><i class="fa-solid fa-truck-fast"></i></div>
        <h4>Giao nhanh, an tâm</h4>
        <p>Đóng gói chống va đập, giao 2h nội thành, cập nhật trạng thái theo chặng.</p>
      </div>
      <div class="value-card">
        <div class="icon-circle"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h4>Chăm sóc sau bán</h4>
        <p>Hướng dẫn chăm hoa, hỗ trợ đổi mới nếu hoa hỏng trong 48h.</p>
      </div>
    </div>
  </section>

  <section class="team">
    <div class="section-head">
      <p class="eyebrow">Đội ngũ</p>
      <h2>Những người thổi hồn vào hoa</h2>
    </div>
    <div class="team-grid">
      <div class="team-card">
        <div class="avatar">MD</div>
        <h4>Mạnh Dũng</h4>
        <p>Lead Florist · 8 năm kinh nghiệm, sở trường bố cục cưới và boutique.</p>
      </div>
      <div class="team-card">
        <div class="avatar">NH</div>
        <h4>Ngọc Hà</h4>
        <p>Event Manager · Triển khai sự kiện doanh nghiệp, tối ưu timeline và ngân sách.</p>
      </div>
      <div class="team-card">
        <div class="avatar">MD</div>
        <h4>Mỹ Duyên</h4>
        <p>Customer Care · Lắng nghe, đề xuất phối màu và thông điệp phù hợp.</p>
      </div>
    </div>
  </section>

  <section class="cta-banner">
    <div class="cta-text">
      <p class="eyebrow">Bắt đầu ngay</p>
      <h2>Đặt hoa, lên ý tưởng sự kiện, hay thiết kế bespoke?</h2>
      <p class="sub">Gửi yêu cầu, chúng tôi phản hồi trong giờ làm việc hoặc gọi ngay nếu bạn cần gấp.</p>
    </div>
    <div class="cta-actions">
      <a class="btn-primary" href="contact.php">Liên hệ đội ngũ</a>
      <a class="ghost-btn" href="products.php">Xem sản phẩm</a>
    </div>
  </section>
</main>

<?php
include 'components/footer.php';
?>

