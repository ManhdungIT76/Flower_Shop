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
/* ========== SEARCH BOX ========== */
.search-box{ position:relative; }

/* ========== DROPDOWN ========== */
.search-suggest{
  position:absolute;
  top:48px;
  left:0;
  width:100%;
  display:none;
  z-index:2000;

  background:#fff;
  border:1px solid #f2c1d1;
  border-radius:16px;
  max-height:320px;
  overflow-y:auto;
  box-shadow:0 12px 30px rgba(233,30,99,.18);
}

/* ========== ITEM (PHẢI ĐÁNH ĐÚNG VÀO THẺ <a>) ========== */
.search-suggest > a.suggest-item{
  /* thắng .right-nav a */
  display:grid !important;
  grid-template-columns: 64px 1fr !important;
  justify-content:start !important;
  align-items:center !important;
  text-align:left !important;

  column-gap:12px;
  padding:12px 14px;
  border-bottom:1px solid #fde4ec;
  text-decoration:none;
  color:#5a2a3c;
  background:#fff;
}

.search-suggest > a.suggest-item:last-child{ border-bottom:none; }

.search-suggest > a.suggest-item:hover{
  background:#fde4ec;
  box-shadow: inset 4px 0 0 #e91e63;
}

/* ========== IMAGE ========== */
.search-suggest > a.suggest-item img{
  width:56px !important;
  height:56px !important;
  object-fit:cover;
  border-radius:12px;
  border:1px solid #f2c1d1;
  display:block;
}

/* ========== TEXT WRAP (KHÓA VỊ TRÍ CHỮ + GIÁ) ========== */
.search-suggest > a.suggest-item .suggest-info{
  /* khóa layout 2 hàng: tên (2 dòng) + giá (1 dòng) */
  display:grid !important;
  grid-template-rows: 36px 18px !important;
  align-content:start !important;
  row-gap:2px !important;
  min-width:0; /* cực quan trọng để ellipsis hoạt động */
}

/* tên luôn 2 dòng => giá không bao giờ nhảy */
.search-suggest > a.suggest-item .p-name{
  font-size:14px;
  font-weight:700;
  color:#5a2a3c;

  line-height:18px !important;
  height:36px !important;
  margin:0 !important;

  overflow:hidden;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
}

/* giá luôn nằm đúng hàng thứ 2 */
.search-suggest > a.suggest-item .p-price{
  font-size:13px;
  font-weight:700;
  color:#e91e63;

  line-height:18px !important;
  height:18px !important;
  margin:0 !important;
  white-space:nowrap;
}

/* ========== SCROLLBAR ========== */
.search-suggest::-webkit-scrollbar{ width:6px; }
.search-suggest::-webkit-scrollbar-track{
  background:#fde4ec;
  border-radius:10px;
}
.search-suggest::-webkit-scrollbar-thumb{
  background:#f8bbd0;
  border-radius:10px;
}
.search-suggest::-webkit-scrollbar-thumb:hover{ background:#e91e63; }

/* ===== FIX MÀU CHỮ SEARCH SUGGEST ===== */

/* Tên sản phẩm */
.search-suggest > a.suggest-item .p-name{
  color: #4b1630 !important;   /* tím nâu đậm – rất dễ đọc */
}

/* Giá */
.search-suggest > a.suggest-item .p-price{
  color: #e91e63 !important;   /* hồng đậm (primary) */
}

/* Hover */
.search-suggest > a.suggest-item:hover .p-name{
  color: #2d0a1a !important;
}

.search-suggest > a.suggest-item:hover .p-price{
  color: #c2185b !important;
}

/* Active (nếu có dùng phím ↑ ↓ hoặc click) */
.search-suggest > a.suggest-item.active{
  background: #fde4ec;
}

.search-suggest > a.suggest-item.active .p-name,
.search-suggest > a.suggest-item.active .p-price{
  color: #2d0a1a !important;
}

/* ===== TOAST SUCCESS ===== */
.toast-success{
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;

  background: #e8f5e9;
  color: #1b5e20;
  border: 1px solid #a5d6a7;
  border-radius: 12px;
  padding: 12px 16px;
  font-weight: 600;

  opacity: 0;
  transform: translateY(-10px);
  transition: all .3s ease;
  pointer-events: none;
}

.toast-success.show{
  opacity: 1;
  transform: translateY(0);
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
          <a href="#" id="openChangePass">🔒 Đổi mật khẩu</a>
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

<!-- TOAST -->
<div id="toastSuccess" class="toast-success">
  🔒 Đổi mật khẩu thành công
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="cp-modal" id="cpModal" aria-hidden="true">
  <div class="cp-backdrop" id="cpBackdrop"></div>

  <div class="cp-dialog" role="dialog" aria-modal="true" aria-labelledby="cpTitle">
    <div class="cp-head">
      <h3 id="cpTitle">Đổi mật khẩu</h3>
      <button type="button" class="cp-close" id="cpCloseBtn">✕</button>
    </div>

    <div class="cp-body">
      <div class="cp-alert" id="cpAlert" style="display:none;"></div>

      <form id="cpForm" autocomplete="off">
        <div class="cp-row">
          <label>Mật khẩu hiện tại</label>
          <input type="password" name="old_password" required>
        </div>

        <div class="cp-row">
          <label>Mật khẩu mới</label>
          <input type="password" name="new_password" required>
        </div>

        <div class="cp-row">
          <label>Nhập lại mật khẩu mới</label>
          <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="cp-submit">Cập nhật</button>
      </form>
    </div>
  </div>
</div>

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

<!-- CHANGE PASSWORD MODAL JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const openBtn = document.getElementById("openChangePass");
  const modal = document.getElementById("cpModal");
  const backdrop = document.getElementById("cpBackdrop");
  const closeBtn = document.getElementById("cpCloseBtn");
  const form = document.getElementById("cpForm");
  const alertBox = document.getElementById("cpAlert");

  function showAlert(type, text){
    alertBox.className = "cp-alert " + (type === "ok" ? "ok" : "bad");
    alertBox.textContent = text;
    alertBox.style.display = "block";
  }

  function openModal(){
    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
    alertBox.style.display = "none";
    form.reset();
  }

  function closeModal(){
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
  }

  if (openBtn) {
    openBtn.addEventListener("click", function(e){
      e.preventDefault();
      openModal();
    });
  }

  backdrop.addEventListener("click", closeModal);
  closeBtn.addEventListener("click", closeModal);

  document.addEventListener("keydown", function(e){
    if (e.key === "Escape" && modal.classList.contains("show")) closeModal();
  });

  form.addEventListener("submit", async function(e){
    e.preventDefault();

    const fd = new FormData(form);

    try {
      const res = await fetch("components/change_password_action.php", {
        method: "POST",
        body: fd
      });
      const data = await res.json();

      if (data.ok) {
        showAlert("ok", data.message || "Đổi mật khẩu thành công.");
        form.reset();

        const toast = document.getElementById("toastSuccess");

        // đóng popup
        setTimeout(() => {
          modal.classList.remove("show");
          modal.setAttribute("aria-hidden", "true");
        }, 800);

        // hiện toast
        setTimeout(() => {
          toast.classList.add("show");
        }, 900);

        // ẩn toast sau 2.5s
        setTimeout(() => {
          toast.classList.remove("show");
        }, 3500);

      } else {
        showAlert("bad", data.message || "Đổi mật khẩu thất bại.");
      }

    } catch (err) {
      showAlert("bad", "Lỗi kết nối.");
    }
  });
});
</script>

</body>
</html>
