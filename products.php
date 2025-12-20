<?php
session_start();
include 'include/db_connect.php';
include 'config.php';

// ===== LẤY DANH MỤC =====
$category_query  = "SELECT * FROM categories ORDER BY category_name ASC";
$category_result = mysqli_query($conn, $category_query);

// ===== XỬ LÝ FILTER / SORT / SEARCH / PRICE RANGE =====
$conditions = [];
$orderBy = "ORDER BY p.created_at DESC";  // mặc định: mới nhất

// --- Lọc theo danh mục
if (!empty($_GET['category'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category']);
    $conditions[] = "p.category_id = '$category_id'";
}

// --- Tìm kiếm theo tên
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $conditions[] = "p.product_name LIKE '%$search%'";
}

// --- Lọc theo khoảng giá (dropdown option)
$min_price = null;
$max_price = null;

if (!empty($_GET['price_range'])) {
    switch ($_GET['price_range']) {
        case '0-100k':
            $min_price = 0; $max_price = 100000;
            break;
        case '100k-300k':
            $min_price = 100000; $max_price = 300000;
            break;
        case '300k-500k':
            $min_price = 300000; $max_price = 500000;
            break;
        case '500k-1m':
            $min_price = 500000; $max_price = 1000000;
            break;
        case '1m+':
            $min_price = 1000000; $max_price = null;
            break;
    }
}

if ($min_price !== null) $conditions[] = "p.price >= $min_price";
if ($max_price !== null) $conditions[] = "p.price <= $max_price";

// --- Sắp xếp
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] === "asc")  $orderBy = "ORDER BY p.price ASC";
    if ($_GET['sort'] === "desc") $orderBy = "ORDER BY p.price DESC";
    if ($_GET['sort'] === "new")  $orderBy = "ORDER BY p.created_at DESC";
}

// Ghép điều kiện SQL
$whereClause = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Query sản phẩm
$sql = "SELECT p.* FROM products p $whereClause $orderBy";
$product_result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Blossomy Bliss - Sản phẩm</title>

  <link rel="stylesheet" href="assets/css/products.css" />
  <link rel="stylesheet" href="assets/css/global.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body>

<!-- HEADER -->
<?php include 'components/header.php'; ?>

<!-- BANNER -->
<div class="banner">
  <h2>Tất cả sản phẩm</h2>
</div>

<!-- FILTER -->
<div class="filter-bar">

  <!-- LỌC DANH MỤC -->
  <form method="GET">
    <label for="category">Danh mục:</label>
    <select name="category" onchange="this.form.submit()">
        <option value="">Tất cả</option>

        <?php while ($cat = mysqli_fetch_assoc($category_result)) : ?>
            <option
              value="<?= $cat['category_id'] ?>"
              <?= (isset($_GET['category']) && $_GET['category'] == $cat['category_id']) ? 'selected' : '' ?>>
              <?= $cat['category_name'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <!-- Giữ các giá trị khác khi đổi danh mục -->
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?? 'new' ?>">
    <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
    <input type="hidden" name="price_range" value="<?= $_GET['price_range'] ?? '' ?>">
  </form>

  <!-- SẮP XẾP -->
  <form method="GET">
    <label for="sort">Sắp xếp theo:</label>
    <select name="sort" onchange="this.form.submit()">
        <option value="new"  <?= (!isset($_GET['sort']) || $_GET['sort']=='new') ? 'selected' : '' ?>>Mới nhất</option>
        <option value="asc"  <?= (isset($_GET['sort']) && $_GET['sort']=='asc') ? 'selected' : '' ?>>Giá tăng dần</option>
        <option value="desc" <?= (isset($_GET['sort']) && $_GET['sort']=='desc') ? 'selected' : '' ?>>Giá giảm dần</option>
    </select>

    <!-- Giữ các giá trị khác khi đổi sort -->
    <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
    <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
    <input type="hidden" name="price_range" value="<?= $_GET['price_range'] ?? '' ?>">
  </form>

  <!-- KHOẢNG GIÁ (OPTION) -->
  <form method="GET">
    <label for="price_range">Khoảng giá:</label>
    <select name="price_range" onchange="this.form.submit()">
      <option value="" <?= (($_GET['price_range'] ?? '')=='') ? 'selected' : '' ?>>Tất cả</option>
      <option value="0-100k"    <?= (($_GET['price_range'] ?? '')=='0-100k') ? 'selected' : '' ?>>0 - 100k</option>
      <option value="100k-300k" <?= (($_GET['price_range'] ?? '')=='100k-300k') ? 'selected' : '' ?>>100k - 300k</option>
      <option value="300k-500k" <?= (($_GET['price_range'] ?? '')=='300k-500k') ? 'selected' : '' ?>>300k - 500k</option>
      <option value="500k-1m"   <?= (($_GET['price_range'] ?? '')=='500k-1m') ? 'selected' : '' ?>>500k - 1tr</option>
      <option value="1m+"       <?= (($_GET['price_range'] ?? '')=='1m+') ? 'selected' : '' ?>>Trên 1tr</option>
    </select>

    <!-- Giữ các giá trị khác khi đổi khoảng giá -->
    <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?? 'new' ?>">
    <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
  </form>

  <!-- TÌM KIẾM -->
  <form method="GET">
    <input type="text" name="search" placeholder="Tìm sản phẩm..."
           value="<?= $_GET['search'] ?? '' ?>"
           style="padding:8px; border-radius:12px; border:1px solid #f8bbd0; background:#fff1f6;">

    <button class="btn" type="submit" style="padding:8px 14px;">Tìm</button>

    <!-- Giữ các giá trị khác khi tìm -->
    <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?? 'new' ?>">
    <input type="hidden" name="price_range" value="<?= $_GET['price_range'] ?? '' ?>">
  </form>

</div>

<!-- DANH SÁCH SẢN PHẨM -->
<section>
  <div class="products">

    <?php
    if (!$product_result || $product_result->num_rows == 0) {
        echo "<p style='text-align:center; width:100%; color:#888;'>Không tìm thấy sản phẩm</p>";
    } else {
      while ($row = mysqli_fetch_assoc($product_result)) :
        $img = getImagePath($row['image_url']);
    ?>
        <div class="product">
          <img src="<?= $img ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
          <h3><?= htmlspecialchars($row['product_name']) ?></h3>
          <p><?= number_format((float)$row['price'], 0, ',', '.') ?> đ</p>
          <a href="product_details.php?id=<?= urlencode($row['product_id']) ?>" class="btn">Xem chi tiết</a>
        </div>
    <?php
      endwhile;
    }
    ?>

  </div>
</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

</body>
</html>
