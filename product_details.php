<?php
session_start();
include "include/db_connect.php";
include "config.php";

// KIỂM TRA ID SẢN PHẨM
if (!isset($_GET['id'])) {
    die("Không tìm thấy sản phẩm!");
}

$isLoggedIn = isset($_SESSION['user']);
$id = $_GET['id'];

// ==============================
// LẤY THÔNG TIN SẢN PHẨM
// ==============================
$sql = "SELECT * FROM products WHERE product_id = '$id'";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

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


// ==============================
// SẢN PHẨM LIÊN QUAN
// ==============================
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
// GỢI Ý SẢN PHẨM APRIORI
// ==============================
$product_id = $product['product_id'];
$api_url = "http://localhost:5000/api/recommend?product_id=" . $product_id;
$api_response = @file_get_contents($api_url);
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

<!-- ============================ -->
<!--     CHI TIẾT SẢN PHẨM        -->
<!-- ============================ -->
<section class="product-detail">
    
    <!-- ẢNH -->
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

      <!-- MÔ TẢ -->
      <p>
        <?= $product['description'] ?? "Sản phẩm đang cập nhật mô tả..." ?>
      </p>

      <div class="quantity">
        <label>Số lượng:</label>
        <input type="number" min="1" value="1">
      </div>

      <div class="button-group">
          <button class="btn add-to-cart-btn">
              <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ
          </button>

          <button type="button" class="btn buy-now-btn">
              Mua ngay
          </button>
      </div>

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

<!-- ============================ -->
<!--     SẢN PHẨM LIÊN QUAN       -->
<!-- ============================ -->
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

<!-- ============================ -->
<!--       GỢI Ý SẢN PHẨM        -->
<!-- ============================ -->
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


<!-- ============================ -->
<!--     SCRIPT XỬ LÝ GIỎ HÀNG    -->
<!-- ============================ -->
<script>
    const addBtn = document.querySelector(".add-to-cart-btn");
    const quantityInput = document.querySelector(".quantity input");

    const productName = "<?= $product['product_name'] ?>";
    const productId = "<?= $product['product_id'] ?>";

    const confirmPopup = document.getElementById("confirm-popup");
    const confirmText = document.getElementById("confirm-text");
    const successPopup = document.getElementById("success-popup");

    const confirmYes = document.getElementById("confirm-yes");
    const confirmNo = document.getElementById("confirm-no");
    const successOk = document.getElementById("success-ok");

    const isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;


    // ================================
    // 1️⃣ Nếu chưa đăng nhập → chuyển login
    // ================================
    addBtn.addEventListener("click", () => {
        const quantity = quantityInput.value;
        confirmText.innerHTML =
            `Bạn có chắc muốn thêm <b>${productName}</b> (số lượng: ${quantity}) vào giỏ hàng không?`;

        confirmPopup.style.display = "flex";
    });

    confirmNo.addEventListener("click", () => {
        confirmPopup.style.display = "none";
    });

    confirmYes.addEventListener("click", () => {
        const quantity = quantityInput.value;

        confirmPopup.style.display = "none";

        fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
            .then(response => response.text())
            .then(() => {
                successPopup.style.display = "flex";
            });
    });

    successOk.addEventListener("click", () => {
        successPopup.style.display = "none";
    });


    // ================================
    // 2️⃣ Mua ngay
    // ================================
    document.querySelector(".buy-now-btn").addEventListener("click", (e) => {
        e.preventDefault();
        if (!isLoggedIn) {
            window.location.href = "login.php?redirect=product_details.php?id=<?= $product['product_id'] ?>";
            return;
        }

        const quantity = quantityInput.value;
        fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
            .then(() => window.location.href = "cart.php");
    });
</script>

</body>
</html>
