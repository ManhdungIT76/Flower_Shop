<?php
session_start();
include "include/db_connect.php";
include "config.php";

// KIỂM TRA ID
if (!isset($_GET['id'])) {
    die("Không tìm thấy sản phẩm!");
}
$isLoggedIn = isset($_SESSION['user']);
$id = $_GET['id'];

// LẤY THÔNG TIN SẢN PHẨM
$sql = "SELECT * FROM products WHERE product_id = '$id'";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

$category_id = $product['category_id'];
$current_id = $product['product_id'];

// Lấy 4 sản phẩm bán chạy nhất cùng danh mục
$related_query = "
    SELECT 
        p.product_id,
        p.product_name,
        p.price,
        p.image_url,
        IFNULL(SUM(od.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN order_details od ON od.product_id = p.product_id
    WHERE p.category_id = '$category_id' 
      AND p.product_id != '$current_id'
    GROUP BY p.product_id
    ORDER BY total_sold DESC
    LIMIT 4
";
$related_result = mysqli_query($conn, $related_query);

// ==============================
// GỌI API FLASK — APRIORI RECOMMEND
// ==============================
$product_id = $product['product_id'];

$api_url = "http://localhost:5000/api/recommend?product_id=" . $product_id;
$api_response = file_get_contents($api_url);
$recommend_data = json_decode($api_response, true);

$recommend_products = [];

if (!empty($recommend_data)) {
    foreach ($recommend_data as $rec) {
        $rid = $rec['product_id'];
        $sql = "SELECT * FROM products WHERE product_id = '$rid'";
        $res = mysqli_query($conn, $sql);

        if ($row = mysqli_fetch_assoc($res)) {
            $recommend_products[] = $row;
        }
    }
}

if (!$product) {
    die("Sản phẩm không tồn tại!");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title><?= $product['product_name'] ?></title>

  <link rel="stylesheet" href="assets/css/product_detail.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body>

<!-- HEADER -->
<?php include "components/header.php"; ?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
    <a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a>
    <i class="fa-solid fa-chevron-right"></i>

    <a href="products.php">Sản phẩm</a>
    <i class="fa-solid fa-chevron-right"></i>

    <span><?= $product['product_name'] ?></span>
</div>

<!-- CHI TIẾT SẢN PHẨM -->
<section class="product-detail">
    
    <!-- ẢNH SẢN PHẨM -->
    <img src="<?= getImagePath($product['image_url']) ?>" 
         alt="<?= $product['product_name'] ?>">

    <div class="product-info">
      
      <h2><?= $product['product_name'] ?></h2>

      <p class="price">
        <?= number_format($product['price'], 0, ',', '.') ?> đ
      </p>

      <!-- ⭐ MÔ TẢ SẢN PHẨM -->
      <p>
        <?= $product['description'] ?? "Sản phẩm đang cập nhật mô tả..." ?>
      </p>

      <div class="quantity">
        <label>Số lượng:</label>
        <input type="number" min="1" value="1">
      </div>

      <!-- NÚT THÊM + MUA NGAY -->
      <div class="button-group">
          <button class="btn add-to-cart-btn">
              <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
          </button>

            <button type="button" class="btn buy-now-btn">
                Mua ngay
            </button>
      </div>
        <!-- form ẩn gửi vào place_order -->
        <form id="buyNowForm" method="POST" action="place_order.php" style="display:none;">
            <input type="hidden" name="full_name" value="<?= $_SESSION['user']['name'] ?? '' ?>">
            <input type="hidden" name="phone" value="<?= $_SESSION['user']['phone'] ?? '' ?>">
            <input type="hidden" name="address" value="<?= $_SESSION['user']['address'] ?? '' ?>">
            <input type="hidden" name="payment_method" value="cod">
            <input type="hidden" name="note" value="">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="quantity" id="buyNowQty" value="1">
        </form>
    </div>
</section>

<!-- SẢN PHẨM LIÊN QUAN -->
<section class="related">
    <h3>Sản phẩm liên quan</h3>

    <div class="related-products">
        <?php 
        if (mysqli_num_rows($related_result) == 0) {
            echo "<p>Không có sản phẩm liên quan.</p>";
        }

        while ($rel = mysqli_fetch_assoc($related_result)) : 
            $img = getImagePath($rel['image_url']);
        ?>
        
        <div class="related-item">
            <a href="product_details.php?id=<?= $rel['product_id'] ?>">
                <img src="<?= $img ?>" alt="<?= $rel['product_name'] ?>">
            </a>
            <h4><?= $rel['product_name'] ?></h4>
            <p><?= number_format($rel['price'], 0, ',', '.') ?> đ</p>
        </div>

        <?php endwhile; ?>
    </div>
</section>

<!-- SẢN PHẨM GỢI Ý CHO BẠN -->
<section class="recommend">
    <h3>Sản phẩm gợi ý cho bạn</h3>

    <div class="recommend-products">
        <?php if (empty($recommend_products)): ?>
            <p>Chưa có dữ liệu gợi ý.</p>
        <?php endif; ?>

        <?php foreach ($recommend_products as $rec): ?>
            <div class="recommend-item">
                <a href="product_details.php?id=<?= $rec['product_id'] ?>">
                    <img src="<?= getImagePath($rec['image_url']) ?>">
                </a>
                <h4><?= $rec['product_name'] ?></h4>
                <p><?= number_format($rec['price'], 0, ',', '.') ?> đ</p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- FOOTER -->
<?php include "components/footer.php"; ?>

<!-- POPUP XÁC NHẬN -->
<div id="confirm-popup" class="popup">
    <div class="popup-content">
        <p id="confirm-text"></p>
        <div class="popup-buttons">
            <button id="confirm-yes" class="popup-btn yes">Xác nhận</button>
            <button id="confirm-no" class="popup-btn no">Hủy</button>
        </div>
    </div>
</div>

<!-- POPUP THÀNH CÔNG -->
<div id="success-popup" class="popup">
    <div class="popup-content">
        <p>Đã thêm vào giỏ hàng thành công! 🎉</p>
        <button id="success-ok" class="popup-btn yes">OK</button>
    </div>
</div>

<script>
    const addBtn = document.querySelector(".add-to-cart-btn");
    const quantityInput = document.querySelector(".quantity input");

    const productName = "<?= $product['product_name'] ?>";
    const productId = "<?= $product['product_id'] ?>";

    const confirmPopup = document.getElementById("confirm-popup");
    const confirmText = document.getElementById("confirm-text");
    const confirmYes = document.getElementById("confirm-yes");
    const confirmNo = document.getElementById("confirm-no");

    const successPopup = document.getElementById("success-popup");
    const successOk = document.getElementById("success-ok");

    const isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;

    // ================================
    // 1️⃣ Nếu chưa đăng nhập → chuyển login
    // ================================
    addBtn.addEventListener("click", () => {
        const quantity = quantityInput.value;
        confirmText.innerHTML =
            `Bạn có chắc muốn thêm <b>${productName}</b> (với số lượng :${quantity}) vào giỏ hàng không?`;

        confirmPopup.style.display = "flex";
    });

    // ================================
    // 2) Nút mua ngay: thêm vào giỏ rồi sang giỏ hàng
    // ================================
    document.querySelector(".buy-now-btn").addEventListener("click", (e) => {
        e.preventDefault();
        if (!isLoggedIn) {
            window.location.href = "login.php?redirect=product_details.php?id=<?= $product['product_id'] ?>";
            return;
        }

        const quantity = quantityInput.value || 1;
        fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
            .then(response => response.text())
            .then(() => {
                window.location.href = "cart.php";
            });
    });

    // ================================
    // 3️⃣ Xử lý popup thêm giỏ
    // ================================
    confirmNo.addEventListener("click", () => {
        confirmPopup.style.display = "none";
    });

    confirmYes.addEventListener("click", () => {
        const quantity = quantityInput.value;

        confirmPopup.style.display = "none";

        fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
            .then(response => response.text())
            .then(data => {
                successPopup.style.display = "flex";
            });
    });

    successOk.addEventListener("click", () => {
        successPopup.style.display = "none";
    });
</script>

</body>
</html>
