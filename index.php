<?php
session_start();
include 'include/db_connect.php';     // Kết nối database
include 'config.php';                  // Đọc hình ảnh từ Drive hoặc thư mục

// ===============================
// LẤY 4 DANH MỤC BÁN CHẠY NHẤT
// ===============================
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

// ===============================
// LẤY 10 SẢN PHẨM NỔI BẬT
// ===============================
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

$best_products = [];

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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blossomy Bliss - Cửa hàng hoa tươi</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link rel="stylesheet" href="assets/css/index.css"/>
  <link rel="stylesheet" href="assets/css/global.css"/>
</head>

<body>

  <!-- HEADER -->
  <?php include 'components/header.php'; ?>

  <!-- BANNER -->
  <div class="banner">
    <h2>Trao gửi yêu thương qua từng đóa hoa 💐</h2>
  </div>

  <!-- DANH MỤC BÁN CHẠY -->
  <section>
    <h2 class="section-title">Danh mục bán chạy</h2>

    <div class="categories">
      <?php while ($bc = mysqli_fetch_assoc($best_category_result)) : ?>
        <a href="products.php?category=<?= $bc['category_id'] ?>" class="category">
          <img src="<?= getImagePath($bc['best_image']) ?>" 
            alt="<?= $bc['category_name'] ?>" />
            <h3><?= $bc['category_name'] ?></h3>
        </a>

      <?php endwhile; ?>
    </div>
  </section>

  <!-- SẢN PHẨM NỔI BẬT -->
  <section>
  <h2 class="section-title">Sản phẩm bán chạy</h2>

  <div class="products">
    <?php foreach ($best_products as $row): ?>
      <div class="product">

        <img src="<?= getImagePath($row['image_url']) ?>"  
             alt="<?= $row['product_name'] ?>">

        <h3><?= $row['product_name'] ?></h3>

        <p><?= number_format($row['price'], 0, ',', '.') ?> đ</p>

        <a href="product_details.php?id=<?= $row['product_id'] ?>" class="btn">
            Xem chi tiết
        </a>

      </div>
    <?php endforeach; ?>
  </div>

</section>

  <!-- FOOTER -->
  <?php include 'components/footer.php'; ?>

</body>
</html>
