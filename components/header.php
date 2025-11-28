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

  <style>
    /* === GỢI Ý TÌM KIẾM === */
    .search-box {
      position: relative;
    }

    .search-suggest {
      position: absolute;
      top: 40px;
      left: 0;
      width: 100%;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 6px;
      max-height: 300px;
      overflow-y: auto;
      display: none;
      z-index: 2000;
    }

    .suggest-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px;
      cursor: pointer;
      border-bottom: 1px solid #eee;
      text-decoration: none;
      color: #333;
    }

    .suggest-item:hover {
      background: #f9f9f9;
    }

    .suggest-item img {
      width: 45px;
      height: 45px;
      object-fit: cover;
      border-radius: 6px;
    }

    .suggest-info {
      display: flex;
      flex-direction: column;
    }

    .suggest-info .p-name {
      font-size: 14px;
      font-weight: 500;
    }

    .suggest-info .p-price {
      font-size: 13px;
      color: #d2691e;
      font-weight: 600;
    }
  </style>

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
    <form action="products.php" method="GET" class="search-box" autocomplete="off">
      <input type="text" name="search" id="searchInput" placeholder="Tìm kiếm hoa...">
      <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>

      <div class="search-suggest" id="searchSuggest"></div>
    </form>

    <!-- USER -->
    <?php if (isset($_SESSION['user'])): ?>
      <div class="user-menu">
        <button class="user-btn" id="userBtn">
          <i class="fa-regular fa-user"></i>
          <?= htmlspecialchars($_SESSION['user']['name']) ?> ▼
        </button>

        <div class="dropdown" id="dropdownMenu">
          <a href="profile.php">👤 Thông tin tài khoản</a>
          <a href="orders.php">🛍️ Đơn hàng của bạn</a>

          <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="admin/dashboard.php">⚙️ Quản lý Admin</a>
          <?php endif; ?>

          <a href="review_history.php">⭐ Lịch sử đánh giá</a>
          <a href="logout.php">🚪 Đăng xuất</a>
        </div>
      </div>

    <?php else: ?>
      <a href="login.php" class="icon-link" title="Tài khoản">
        <i class="fa-regular fa-user"></i>
      </a>
    <?php endif; ?>

    <!-- CART -->
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

<!-- AUTOCOMPLETE JS -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const suggestBox = document.getElementById("searchSuggest");

  searchInput.addEventListener("keyup", function() {
    let keyword = this.value.trim();

    if (keyword.length < 2) {
      suggestBox.style.display = "none";
      return;
    }

    fetch("components/search_suggest.php?keyword=" + encodeURIComponent(keyword))
      .then(res => res.json())
      .then(data => {
        if (data.length > 0) {
          suggestBox.innerHTML = data.map(item => `
            <a class="suggest-item" href="product_details.php?id=${item.id}">
                <img src="${item.image}">
                <div class="suggest-info">
                    <span class="p-name">${item.name}</span>
                    <span class="p-price">${Number(item.price).toLocaleString()} đ</span>
                </div>
            </a>
          `).join("");

          suggestBox.style.display = "block";
        } else {
          suggestBox.style.display = "none";
        }
      });
  });
});
</script>

</body>
</html>