<?php
session_start();
include "include/db_connect.php";
include "config.php";

// KIỂM TRA ID
if (!isset($_GET['id'])) {
    die("Không tìm thấy sản phẩm!");
}
//$isLoggedIn = isset($_SESSION['user']);
$id = $_GET['id'];

// LẤY THÔNG TIN SẢN PHẨM
// LẤY THÔNG TIN SẢN PHẨM (SAFE)
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE product_id = ?");
mysqli_stmt_bind_param($stmt, "s", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    die("Sản phẩm không tồn tại!");
}

$category_id = $product['category_id'];
$current_id  = $product['product_id'];
$stock = (int)($product['stock'] ?? 0);

// ====== USER_ID + CHECK USER THƯỜNG XUYÊN ======
$user_id = $_SESSION['user']['id'] ?? null;

$isFrequentUser = false;
if ($user_id) {
    $sqlFrequent = "
        SELECT 
            COUNT(DISTINCT o.order_id) AS total_orders,
            COUNT(DISTINCT od.product_id) AS distinct_products
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        WHERE o.user_id = ?
    ";
    $st = mysqli_prepare($conn, $sqlFrequent);
    mysqli_stmt_bind_param($st, "s", $user_id);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $r  = mysqli_fetch_assoc($rs) ?: ['total_orders'=>0,'distinct_products'=>0];

    if ((int)$r['total_orders'] >= 5 || (int)$r['distinct_products'] >= 10) {
        $isFrequentUser = true;
    }
}

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

$avgRating    = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
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
// SẢN PHẨM LIÊN QUAN: cùng loại + ngang tầm giá
// ==============================
$current_price = (float)($product['price'] ?? 0);
$min_price = $current_price * 0.85;
$max_price = $current_price * 1.15;

$stmtRel = mysqli_prepare($conn, "
    SELECT p.product_id, p.product_name, p.price, p.image_url
    FROM products p
    WHERE p.category_id = ?
      AND p.product_id <> ?
      AND p.price BETWEEN ? AND ?
    ORDER BY RAND()
    LIMIT 10
");
mysqli_stmt_bind_param($stmtRel, "ssdd", $category_id, $current_id, $min_price, $max_price);
mysqli_stmt_execute($stmtRel);
$related_result = mysqli_stmt_get_result($stmtRel);

// ==============================
// GỢI Ý (user mới: item-item, user thường xuyên: user-item + fallback item-item)
// ==============================
$limit = 10;
$recommend_products = [];

// chống trùng
$seen = [];
$seen[$current_id] = true;

// (A) USER THƯỜNG XUYÊN -> ưu tiên CF user-item
if ($isFrequentUser && $user_id) {
    $api_url = "http://localhost:5000/api/recommend/user"
             . "?user_id=" . urlencode($user_id)
             . "&exclude=" . urlencode($current_id)
             . "&limit=" . $limit;

    $api_response = @file_get_contents($api_url);
    $recommend_products = $api_response ? json_decode($api_response, true) : [];
    if (!is_array($recommend_products)) $recommend_products = [];

    foreach ($recommend_products as $p) {
        if (!empty($p['product_id'])) $seen[$p['product_id']] = true;
    }

} else {
    // (B) USER MỚI / CHƯA ĐĂNG NHẬP -> CF item-item
    $api_url = "http://localhost:5000/api/recommend"
             . "?product_id=" . urlencode($current_id);

    $api_response = @file_get_contents($api_url);
    $recommend_products = $api_response ? json_decode($api_response, true) : [];
    if (!is_array($recommend_products)) $recommend_products = [];

    foreach ($recommend_products as $p) {
        if (!empty($p['product_id'])) $seen[$p['product_id']] = true;
    }
}

// (C) Nếu USER THƯỜNG XUYÊN mà CHƯA ĐỦ -> bù thêm bằng item-item
if (count($recommend_products) < $limit && $isFrequentUser && $user_id) {
    $api2 = "http://localhost:5000/api/recommend"
          . "?product_id=" . urlencode($current_id);

    $r2 = @file_get_contents($api2);
    $more = $r2 ? json_decode($r2, true) : [];
    if (!is_array($more)) $more = [];

    foreach ($more as $p) {
        if (count($recommend_products) >= $limit) break;
        $pid = $p['product_id'] ?? null;
        if ($pid && empty($seen[$pid])) {
            $recommend_products[] = $p;
            $seen[$pid] = true;
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

        <div class="quantity" style="display:flex;align-items:center;gap:12px;">
            <label>Số lượng:</label>
            <input
                type="number"
                min="1"
                max="<?= $stock ?>"
                value="1"
                id="qtyInput"
            >

            <span class="stock-info">
                Còn lại:
                <b id="stockLeft"><?= $stock ?></b>
                sản phẩm
            </span>
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
const buyNowBtn = document.querySelector(".buy-now-btn");

const productName = "<?= $product['product_name'] ?>";
const productId = "<?= $product['product_id'] ?>";

const confirmPopup = document.getElementById("confirm-popup");
const confirmText = document.getElementById("confirm-text");
const confirmYes = document.getElementById("confirm-yes");
const confirmNo = document.getElementById("confirm-no");

const successPopup = document.getElementById("success-popup");
const successOk = document.getElementById("success-ok");

const isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;

const qtyInput = document.getElementById("qtyInput");
const stockLeft = parseInt(document.getElementById("stockLeft").innerText || "0", 10);

// Nếu hết hàng => khóa nút
if (stockLeft <= 0) {
  qtyInput.value = 0;
  addBtn.disabled = true;
  buyNowBtn.disabled = true;
}

// Cho phép xóa để nhập (không tự set lại khi đang gõ)
qtyInput.addEventListener("input", () => {
  // không xử lý gì để user xóa nhập tự do
});

// Chỉ chuẩn hóa khi rời ô
qtyInput.addEventListener("blur", () => {
  let v = parseInt(qtyInput.value, 10);

  if (isNaN(v) || v < 1) v = 1;
  if (stockLeft > 0 && v > stockLeft) v = stockLeft;
  if (stockLeft <= 0) v = 0;

  qtyInput.value = v;
});

addBtn.addEventListener("click", () => {
  const quantity = parseInt(qtyInput.value, 10) || 1;

  if (stockLeft <= 0) {
    alert("Sản phẩm đã hết hàng.");
    return;
  }
  if (quantity > stockLeft) {
    alert("Không đủ số lượng trong kho. Hiện còn " + stockLeft + " sản phẩm.");
    return;
  }

  confirmText.innerHTML =
    `Bạn có chắc muốn thêm <b>${productName}</b> (với số lượng :${quantity}) vào giỏ hàng không?`;

  confirmPopup.style.display = "flex";
});
buyNowBtn.addEventListener("click", (e) => {
  e.preventDefault();

  if (!isLoggedIn) {
    window.location.href = "login.php?redirect=product_details.php?id=<?= $product['product_id'] ?>";
    return;
  }

  const quantity = parseInt(qtyInput.value, 10) || 1;

  if (stockLeft <= 0) {
    alert("Sản phẩm đã hết hàng.");
    return;
  }
  if (quantity > stockLeft) {
    alert("Không đủ số lượng trong kho. Hiện còn " + stockLeft + " sản phẩm.");
    return;
  }

  fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
    .then(async (res) => {
      if (res.status === 403) {
        alert('Tài khoản admin không được phép mua hàng.');
        return null;
      }
      return await res.json();
    })
    .then(data => {
      if (data === null) return;

      if (!data.ok) {
        alert(data.message || "Không thêm được vào giỏ hàng.");
        return;
      }

      window.location.href = "cart.php";
    })
    .catch(() => alert("Vui lòng kiểm tra lại."));
});

confirmNo.addEventListener("click", () => {
  confirmPopup.style.display = "none";
});

confirmYes.addEventListener("click", () => {
  const quantity = parseInt(qtyInput.value, 10) || 1;
  confirmPopup.style.display = "none";

  if (stockLeft <= 0) {
    alert("Sản phẩm đã hết hàng.");
    return;
  }
  if (quantity > stockLeft) {
    alert("Không đủ số lượng trong kho. Hiện còn " + stockLeft + " sản phẩm.");
    return;
  }

  fetch(`pages/add_to_cart.php?id=${productId}&quantity=${quantity}`)
    .then(async (res) => {
      if (res.status === 403) {
        alert('Tài khoản admin không được phép mua hàng.');
        return null;
      }
      return await res.json();
    })
    .then(data => {
      if (data === null) return;

      if (!data.ok) {
        alert(data.message || "Không thêm được vào giỏ hàng.");
        return;
      }

      successPopup.style.display = "flex";
    })
    .catch(() => alert("Vui lòng kiểm tra lại."));
});

successOk.addEventListener("click", () => {
  successPopup.style.display = "none";
});

</script>

</body>
</html>
