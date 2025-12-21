<?php
session_start();
include 'include/db_connect.php';
include 'config.php';

$user_id = $_SESSION['user']['id'] ?? null;
$isLoggedIn = ($user_id !== null);

// luôn khởi tạo để không lỗi khi render
$best_category_rows = [];

/* ===============================
   XÁC ĐỊNH USER THƯỜNG XUYÊN
   >= 5 đơn HOẶC mua >= 10 sản phẩm khác nhau
=============================== */
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
    $stmt = mysqli_prepare($conn, $sqlFrequent);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $freqRow = mysqli_fetch_assoc($res) ?: ['total_orders' => 0, 'distinct_products' => 0];

    if ($freqRow['total_orders'] >= 5 || $freqRow['distinct_products'] >= 10) {
        $isFrequentUser = true;
    }
}

/* ===============================
   DANH MỤC ĐƯỢC YÊU THÍCH
   - User mới: top danh mục bán chạy toàn hệ thống
   - User thường xuyên: top danh mục theo lịch sử mua
=============================== */
if ($user_id && $isFrequentUser) {

    // 1) Lấy danh mục theo lịch sử mua của user
    $best_category_query_user = "
        SELECT 
            c.category_id,
            c.category_name,
            SUM(od.quantity) AS total_sold,
            (
                SELECT p2.image_url
                FROM order_details od2
                JOIN products p2 ON p2.product_id = od2.product_id
                WHERE p2.category_id = c.category_id
                ORDER BY od2.quantity DESC
                LIMIT 1
            ) AS best_image
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON p.product_id = od.product_id
        JOIN categories c ON c.category_id = p.category_id
        WHERE o.user_id = ?
        GROUP BY c.category_id
        ORDER BY total_sold DESC
        LIMIT 4
    ";

    $stmt = mysqli_prepare($conn, $best_category_query_user);
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $rsUser = mysqli_stmt_get_result($stmt);

    $favCats = [];
    while ($row = mysqli_fetch_assoc($rsUser)) {
        $favCats[] = $row;
    }

    // 2) Nếu user < 4 danh mục -> bù thêm từ danh mục bán chạy toàn hệ thống (không trùng)
    if (count($favCats) < 4) {
        $need = 4 - count($favCats);

        $excludeIds = array_column($favCats, 'category_id');
        $excludeSql = "";
        if (count($excludeIds) > 0) {
            // an toàn: escape từng id
            $escaped = array_map(function($x) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $x) . "'";
            }, $excludeIds);
            $excludeSql = " AND c.category_id NOT IN (" . implode(",", $escaped) . ") ";
        }

        $best_category_query_global = "
            SELECT 
                c.category_id,
                c.category_name,
                t.total_sold,
                p.image_url AS best_image
            FROM categories c
            JOIN (
                SELECT 
                    p.category_id,
                    SUM(od.quantity) AS total_sold,
                    (
                        SELECT od2.product_id
                        FROM order_details od2
                        JOIN products p2 ON p2.product_id = od2.product_id
                        WHERE p2.category_id = p.category_id
                        GROUP BY od2.product_id
                        ORDER BY SUM(od2.quantity) DESC
                        LIMIT 1
                    ) AS best_product
                FROM products p
                JOIN order_details od ON od.product_id = p.product_id
                GROUP BY p.category_id
            ) AS t ON t.category_id = c.category_id
            JOIN products p ON p.product_id = t.best_product
            WHERE 1=1
            $excludeSql
            ORDER BY t.total_sold DESC
            LIMIT $need
        ";

        $rsGlobal = mysqli_query($conn, $best_category_query_global);
        while ($row = mysqli_fetch_assoc($rsGlobal)) {
            $favCats[] = $row;
        }
    }

    // 3) Dùng mảng $favCats để render thay vì while trực tiếp trên result
    $best_category_rows = $favCats;

} else {
    // USER MỚI
    $best_category_query = "
        SELECT 
            c.category_id,
            c.category_name,
            t.total_sold,
            p.image_url AS best_image
        FROM categories c
        JOIN (
            SELECT 
                p.category_id,
                SUM(od.quantity) AS total_sold,
                (
                    SELECT od2.product_id
                    FROM order_details od2
                    JOIN products p2 ON p2.product_id = od2.product_id
                    WHERE p2.category_id = p.category_id
                    GROUP BY od2.product_id
                    ORDER BY SUM(od2.quantity) DESC
                    LIMIT 1
                ) AS best_product
            FROM products p
            JOIN order_details od ON od.product_id = p.product_id
            GROUP BY p.category_id
        ) AS t ON t.category_id = c.category_id
        JOIN products p ON p.product_id = t.best_product
        ORDER BY t.total_sold DESC
        LIMIT 4
    ";

    $best_category_result = mysqli_query($conn, $best_category_query);
    $best_category_rows = [];
    while ($row = mysqli_fetch_assoc($best_category_result)) {
        $best_category_rows[] = $row;
    }
}

/* ===============================
   SẢN PHẨM ĐƯỢC YÊU THÍCH
   - User mới: giữ logic cũ
   - User thường xuyên: (1) sản phẩm đã từng mua (ưu tiên) + (2) sản phẩm tương đồng (bổ sung)
=============================== */
$best_products = [];

if ($user_id && $isFrequentUser) {

    // (1) LẤY LẠI SẢN PHẨM ĐÃ TỪNG MUA (top theo tổng số lượng)
    // số lượng ưu tiên mua lại
    $repurchaseLimit = 6;

    $sqlRepurchase = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.image_url,
            SUM(od.quantity) AS bought_qty
        FROM orders o
        JOIN order_details od ON o.order_id = od.order_id
        JOIN products p ON p.product_id = od.product_id
        WHERE o.user_id = ?
        GROUP BY p.product_id
        ORDER BY bought_qty DESC
        LIMIT $repurchaseLimit
    ";
    $stmt1 = mysqli_prepare($conn, $sqlRepurchase);
    mysqli_stmt_bind_param($stmt1, "s", $user_id);
    mysqli_stmt_execute($stmt1);
    $rs1 = mysqli_stmt_get_result($stmt1);

    $repurchaseIds = [];
    while ($r = mysqli_fetch_assoc($rs1)) {
        $best_products[] = $r;
        $repurchaseIds[] = $r['product_id'];
    }

    // (2) BỔ SUNG SẢN PHẨM TƯƠNG ĐỒNG (cùng loại + cùng tầm giá) dựa trên các sản phẩm đã mua
    // tổng hiển thị tối đa
    $totalLimit = 10;
    $need = $totalLimit - count($best_products);

    if ($need > 0 && count($repurchaseIds) > 0) {

        // tạo placeholders IN (?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($repurchaseIds), '?'));

        // lấy sản phẩm tương đồng: cùng category & giá ±15% so với bất kỳ sản phẩm đã mua
        // loại trừ chính các sản phẩm đã mua lại để tránh trùng card
        $sqlSimilar = "
            SELECT DISTINCT
                p.product_id,
                p.product_name,
                p.price,
                p.image_url
            FROM products p
            JOIN (
                SELECT DISTINCT p0.category_id, p0.price
                FROM products p0
                WHERE p0.product_id IN ($placeholders)
            ) base
              ON p.category_id = base.category_id
             AND p.price BETWEEN base.price * 0.85 AND base.price * 1.15
            WHERE p.product_id NOT IN ($placeholders)
            ORDER BY RAND()
            LIMIT $need
        ";

        // bind: danh sách ids 2 lần (cho IN base và NOT IN)
        $types = str_repeat('s', count($repurchaseIds) * 2);
        $params = array_merge($repurchaseIds, $repurchaseIds);

        $stmt2 = mysqli_prepare($conn, $sqlSimilar);
        mysqli_stmt_bind_param($stmt2, $types, ...$params);
        mysqli_stmt_execute($stmt2);
        $rs2 = mysqli_stmt_get_result($stmt2);

        while ($r = mysqli_fetch_assoc($rs2)) {
            $best_products[] = $r;
        }
    }

} else {
    // ===== USER MỚI: GIỮ LOGIC CŨ (top 5 danh mục bán chạy, mỗi danh mục 2 sp) =====
    $top_categories_query = "
        SELECT 
            p.category_id,
            SUM(od.quantity) AS total_sold
        FROM products p
        JOIN order_details od ON od.product_id = p.product_id
        GROUP BY p.category_id
        ORDER BY total_sold DESC
        LIMIT 5
    ";

    $top_categories_result = mysqli_query($conn, $top_categories_query);

    $top_categories = [];
    while ($row = mysqli_fetch_assoc($top_categories_result)) {
        $top_categories[] = $row['category_id'];
    }

    foreach ($top_categories as $cat_id) {
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                p.price,
                p.image_url,
                SUM(od.quantity) AS sold_qty
            FROM products p
            JOIN order_details od ON od.product_id = p.product_id
            WHERE p.category_id = '$cat_id'
            GROUP BY p.product_id
            ORDER BY sold_qty DESC
            LIMIT 2
        ";
        $result = mysqli_query($conn, $query);
        while ($prod = mysqli_fetch_assoc($result)) {
            $best_products[] = $prod;
        }
    }
}

// ===============================
// HÀM: LẤY SẢN PHẨM THEO category_id (bán chạy nhất)
// ===============================
function getProductsByCategoryId($conn, $categoryId, $limit = 10) {
    $sql = "
        SELECT 
            p.product_id,
            p.product_name,
            p.price,
            p.image_url,
            COALESCE(SUM(od.quantity), 0) AS sold_qty
        FROM products p
        LEFT JOIN order_details od ON od.product_id = p.product_id
        WHERE p.category_id = ?
        GROUP BY p.product_id
        ORDER BY sold_qty DESC, p.product_id DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) return [];

    mysqli_stmt_bind_param($stmt, "si", $categoryId, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $data;
}

// ===============================
// LẤY TẤT CẢ DANH MỤC TỪ CSDL
// ===============================
$all_categories_sql = "SELECT category_id, category_name FROM categories ORDER BY category_name";
$all_categories_result = mysqli_query($conn, $all_categories_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blossomy Bliss - Cửa hàng hoa tươi</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="stylesheet" href="assets/css/global.css">
</head>

<body>

<?php include 'components/header.php'; ?>

<section class="hero-slider">
  <div class="hero-track">
    <div class="hero-slide" style="background-image:url('assets/images/anh1.jpg')"></div>
    <div class="hero-slide" style="background-image:url('assets/images/anh2.jpg')"></div>
    <div class="hero-slide" style="background-image:url('assets/images/anh3.jpg')"></div>
    <div class="hero-slide" style="background-image:url('assets/images/anh4.jpg')"></div>
    <div class="hero-slide" style="background-image:url('assets/images/anh5.jpg')"></div>
  </div>

  <div class="hero-overlay">
    <h2>Trao gửi yêu thương qua từng đóa hoa 💐</h2>
    <a href="products.php" class="hero-btn">Xem sản phẩm</a>
  </div>
</section>

<section>
  <h2 class="section-title">Danh mục được yêu thích</h2>
  <div class="categories">
    <?php foreach ($best_category_rows as $bc) : ?>
      <a href="products.php?category=<?= $bc['category_id'] ?>" class="category">
        <img src="<?= getImagePath($bc['best_image']) ?>" alt="<?= $bc['category_name'] ?>">
        <h3><?= $bc['category_name'] ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section>
  <h2 class="section-title">Sản phẩm được yêu thích</h2>
  <div class="products">
    <?php foreach ($best_products as $row): ?>
      <div class="product">
        <img src="<?= getImagePath($row['image_url']) ?>" alt="<?= $row['product_name'] ?>">
        <h3><?= $row['product_name'] ?></h3>
        <p><?= number_format($row['price'], 0, ',', '.') ?> đ</p>
        <a href="product_details.php?id=<?= $row['product_id'] ?>" class="btn">Xem chi tiết</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===============================
       TỰ ĐỘNG HIỂN THỊ SẢN PHẨM THEO TỪNG DANH MỤC (LẤY TỪ CSDL)
       MỖI DANH MỤC HIỂN THỊ 10 SẢN PHẨM + NÚT XEM TẤT CẢ
       =============================== -->
  <?php while ($cat = mysqli_fetch_assoc($all_categories_result)): ?>

    <?php
      // ✅ mỗi danh mục hiển thị 10 sản phẩm
      $products = getProductsByCategoryId($conn, $cat['category_id'], 10);

      // Nếu danh mục không có sản phẩm thì bỏ qua
      if (count($products) === 0) continue;
    ?>

    <section>
      <h2 class="section-title"><?= htmlspecialchars($cat['category_name']) ?></h2>

      <div class="products">
        <?php foreach ($products as $row): ?>
          <div class="product">
            <img src="<?= getImagePath($row['image_url']) ?>"
                 alt="<?= htmlspecialchars($row['product_name']) ?>">

            <h3><?= htmlspecialchars($row['product_name']) ?></h3>

            <p><?= number_format((float)$row['price'], 0, ',', '.') ?> đ</p>

            <a href="product_details.php?id=<?= urlencode($row['product_id']) ?>" class="btn">
              Xem chi tiết
            </a>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ✅ XEM TẤT CẢ -->
      <div class="view-more">
        <a href="products.php?category=<?= urlencode($cat['category_id']) ?>">
          Xem tất cả <?= htmlspecialchars($cat['category_name']) ?> →
        </a>
      </div>
    </section>

  <?php endwhile; ?>

  <!-- FOOTER -->

<?php include 'components/footer.php'; ?>

</body>
</html>
