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
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("s", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) { die("Sản phẩm không tồn tại!"); }

$category_id = $product['category_id'];
$current_id = $product['product_id'];

// ==============================
// LẤY ĐÁNH GIÁ SẢN PHẨM
// ==============================

// Lấy điểm trung bình + tổng số đánh giá
$sql_avg = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews 
            FROM feedback 
            WHERE product_id = ?";
$stmt_avg = $conn->prepare($sql_avg);
$stmt_avg->bind_param("s", $id);
$stmt_avg->execute();
$ratingData = $stmt_avg->get_result()->fetch_assoc();

$avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
$totalReviews = $ratingData['total_reviews'];

// Lấy danh sách đánh giá
$sql_reviews = "SELECT f.*, u.full_name AS username 
                FROM feedback f
                JOIN users u ON f.user_id = u.user_id
                WHERE f.product_id = ?
                ORDER BY f.feedback_id DESC";

$stmt_rev = $conn->prepare($sql_reviews);
$stmt_rev->bind_param("s", $id);
$stmt_rev->execute();
$reviews = $stmt_rev->get_result();

// Lấy 4 sản phẩm bán chạy nhất cùng danh mục
$related_query = "
    SELECT p.product_id, p.product_name, p.price, p.image_url,
           IFNULL(SUM(od.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN order_details od ON od.product_id = p.product_id
    WHERE p.category_id = ? AND p.product_id != ?
    GROUP BY p.product_id
    ORDER BY total_sold DESC
    LIMIT 4";
$stmtRel = $conn->prepare($related_query);
$stmtRel->bind_param("ss", $category_id, $current_id);
$stmtRel->execute();
$related_result = $stmtRel->get_result();

// ==============================
// GỌI API FLASK
// ==============================
$product_id = $product['product_id'];
$userId = $_SESSION['user']['id'] ?? '';

$api_url = "http://localhost:5000/api/recommend/auto?product_id={$product_id}";
if (!empty($userId)) {
    $api_url .= "&user_id={$userId}";
}

$related_products = [];
$bundle_products  = [];

$api_response = @file_get_contents($api_url);

if ($api_response !== false) {
    $recommend_data = json_decode($api_response, true);
    $related_data = $recommend_data['related'] ?? [];
    $bundle_data  = $recommend_data['bundle'] ?? [];

    $fetchProducts = function(array $items) use ($conn) {
        $out = [];
        $seen = [];
        foreach ($items as $rec) {
            if (empty($rec['product_id'])) continue;
            $pid = $rec['product_id'];
            if (isset($seen[$pid])) continue;
            $seen[$pid] = true;

            $pid_safe = mysqli_real_escape_string($conn, $pid);
            $sql = "SELECT * FROM products WHERE product_id = '$pid_safe' AND stock > 0";
            $res = mysqli_query($conn, $sql);
            if ($row = mysqli_fetch_assoc($res)) {
                $out[] = $row;
            }
        }
        return $out;
    };

    $related_products = $fetchProducts(is_array($related_data) ? $related_data : []);
    $bundle_products  = $fetchProducts(is_array($bundle_data) ? $bundle_data : []);
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

    <!-- ⭐ XẾP HẠNG -->
      <div class="product-rating">
          <div class="stars">
              <?php 
                  $fullStars = floor($avgRating);
                  $halfStar = ($avgRating - $fullStars) >= 0.5;
              ?>

              <?php for ($i = 1; $i <= 5; $i++): ?>
                  <?php if ($i <= $fullStars): ?>
                      <i class="fa-solid fa-star" style="color:#ffca28;"></i>
                  <?php elseif ($halfStar && $i == $fullStars + 1): ?>
                      <i class="fa-solid fa-star-half-stroke" style="color:#ffca28;"></i>
                  <?php else: ?>
                      <i class="fa-regular fa-star" style="color:#ccc;"></i>
                  <?php endif; ?>
              <?php endfor; ?>
          </div>

          <span class="rating-number">
              <?= $avgRating ?> / 5 (<?= $totalReviews ?> đánh giá)
          </span>
      </div>

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

<!-- SẢN PHẨM GỢI Ý (LIÊN QUAN) -->
<section class="recommend">
    <h3>Gợi ý sản phẩm liên quan</h3>
    <div class="recommend-products">
        <?php if (empty($related_products)): ?>
            <p>Chưa có gợi ý.</p>
        <?php endif; ?>
        <?php foreach ($related_products as $rec): ?>
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

<!-- SẢN PHẨM MUA KÈM -->
<section class="recommend">
    <h3>Gợi ý mua kèm</h3>
    <div class="recommend-products">
        <?php if (empty($bundle_products)): ?>
            <p>Chưa có gợi ý.</p>
        <?php endif; ?>
        <?php foreach ($bundle_products as $rec): ?>
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

<!-- ============================ -->
<!--     ĐÁNH GIÁ KHÁCH HÀNG      -->
<!-- ============================ -->
<section class="product-reviews">
    <h3>Đánh giá của khách hàng</h3>

    <?php if ($totalReviews == 0): ?>
        <p class="no-review">Chưa có đánh giá nào cho sản phẩm này.</p>
    <?php else: ?>

        <?php while ($rv = $reviews->fetch_assoc()): ?>
            <div class="review-item">

                <div class="review-user">
                    <i class="fa-solid fa-user-circle"></i>
                    <strong><?= $rv['username'] ?></strong>
                </div>

                <div class="review-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= $rv['rating']): ?>
                            <i class="fa-solid fa-star" style="color:#ffca28;"></i>
                        <?php else: ?>
                            <i class="fa-regular fa-star" style="color:#ccc;"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <p class="review-content"><?= nl2br($rv['feedback_content']) ?></p>
            </div>
        <?php endwhile; ?>

    <?php endif; ?>
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
