<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
<header>

  <!-- LEFT NAV -->
  <div class="left-nav">
    <div class="logo">
      <img src="assets/images/z7128943872304_7000db2b5f7c476efb8c375bf165f8e8.jpg" alt="Logo">
      <h1>Blossomy Bliss</h1>
    </div>

    <nav>
      <ul>
        <li><a href="index.php">Trang chủ</a></li>
        <li><a href="products.php">Sản phẩm</a></li>
        <li><a href="about.php">Giới thiệu</a></li>
        <li><a href="contact.php">Liên hệ</a></li>
      </ul>
    </nav>
  </div>

  <!-- RIGHT NAV -->
  <div class="right-nav">

    <!-- SEARCH BOX -->
    <form action="products.php" method="GET" class="search-box">
      <input type="text" name="search" placeholder="Tìm kiếm hoa...">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    <!-- USER ACCOUNT / LOGIN -->
    <?php if (isset($_SESSION['user'])): ?>

      <div class="user-menu">
        <button class="user-btn" id="userBtn">
          <i class="fa-regular fa-user"></i>
          <?= htmlspecialchars($_SESSION['user']['name']) ?> ▼
        </button>

        <div class="dropdown" id="dropdownMenu">
          <a href="profile.php">👤 Thông tin tài khoản</a>
          <a href="orders.php">🛍️ Lịch sử mua hàng</a>

          <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin/dashboard.php">⚙️ Quản lý Admin</a>
          <?php endif; ?>

          <a href="logout.php">🚪 Đăng xuất</a>
        </div>
      </div>

    <?php else: ?>

      <a href="login.php" class="icon-link" title="Tài khoản">
        <i class="fa-regular fa-user"></i>
      </a>

    <?php endif; ?>

    <!-- CART ICON -->
    <a href="cart.php" class="icon-link" title="Giỏ hàng">
      <i class="fa-solid fa-cart-shopping"></i>
    </a>

  </div>

</header>


<!-- DROPDOWN JS -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const userBtn = document.getElementById("userBtn");
  const dropdown = document.getElementById("dropdownMenu");

  if (userBtn && dropdown) {
    userBtn.addEventListener("click", function(e) {
      e.stopPropagation();
      dropdown.classList.toggle("show");
    });

    document.addEventListener("click", function(e) {
      if (!userBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove("show");
      }
    });
  }
});
</script>

</body>
</html>
