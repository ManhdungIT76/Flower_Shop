<?php
session_start();
include 'include/db_connect.php';  
include 'config.php';              

// ===== LẤY DANH MỤC =====
$category_query = "SELECT * FROM categories ORDER BY category_name ASC";
$category_result = mysqli_query($conn, $category_query);

// ===== XỬ LÝ FILTER / SORT / SEARCH =====
$conditions = [];
$orderBy = "ORDER BY created_at DESC";  // mặc định: mới nhất

// --- Lọc theo danh mục
if (!empty($_GET['category'])) {
    $category_id = $_GET['category'];
    $conditions[] = "p.category_id = '$category_id'";
}

// --- Tìm kiếm theo tên
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $conditions[] = "p.product_name LIKE '%$search%'";
}

// --- Sắp xếp theo giá
if (!empty($_GET['sort'])) {
    if ($_GET['sort'] == "asc") $orderBy = "ORDER BY price ASC";
    if ($_GET['sort'] == "desc") $orderBy = "ORDER BY price DESC";
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

    <!-- Giữ giá trị sort & search khi đổi danh mục -->
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?? '' ?>">
    <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
  </form>


  <!-- SẮP XẾP -->
  <form method="GET">
    <label for="sort">Sắp xếp theo:</label>
    <select name="sort" onchange="this.form.submit()">
        <option value="new">Mới nhất</option>
        <option value="asc"  <?= (isset($_GET['sort']) && $_GET['sort']=='asc') ? 'selected' : '' ?>>Giá tăng dần</option>
        <option value="desc" <?= (isset($_GET['sort']) && $_GET['sort']=='desc') ? 'selected' : '' ?>>Giá giảm dần</option>
    </select>

    <!-- Giữ nguyên danh mục & tìm kiếm khi đổi sort -->
    <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
    <input type="hidden" name="search" value="<?= $_GET['search'] ?? '' ?>">
  </form>


  <!-- TÌM KIẾM -->
  <form method="GET">
    <input type="text" name="search" placeholder="Tìm sản phẩm..." 
           value="<?= $_GET['search'] ?? '' ?>" 
           style="padding:8px; border-radius:6px; border:1px solid #ccc;">
    <button class="btn">Tìm</button>

    <!-- Giữ category & sort khi tìm -->
    <input type="hidden" name="category" value="<?= $_GET['category'] ?? '' ?>">
    <input type="hidden" name="sort" value="<?= $_GET['sort'] ?? '' ?>">
  </form>

</div>


<!-- DANH SÁCH SẢN PHẨM -->
<section>
  <div class="products">

    <?php 
    if ($product_result->num_rows == 0) {
        echo "<p style='text-align:center; width:100%; color:#888;'>Không tìm thấy sản phẩm</p>";
    }

    while ($row = mysqli_fetch_assoc($product_result)) : 
        $img = getImagePath($row['image_url']);
    ?>

    <div class="product">
      <img src="<?= $img ?>" alt="<?= $row['product_name'] ?>">
      <h3><?= $row['product_name'] ?></h3>
      <p><?= number_format($row['price'], 0, ',', '.') ?> đ</p>
      <a href="product_details.php?id=<?= $row['product_id'] ?>" class="btn">Xem chi tiết</a>
    </div>

    <?php endwhile; ?>

  </div>
</section>

<!-- FOOTER -->
<?php include 'components/footer.php'; ?>

</body>
</html>
