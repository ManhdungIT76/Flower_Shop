<?php
include '../../include/db_connect.php';     // Kết nối database
include '../../config.php';                  // Đọc hình ảnh từ Drive hoặc thư mục
include '../includes/admin_header.php';      // Giao diện admin

// ===== XỬ LÝ THÊM SẢN PHẨM =====
if (isset($_POST['add'])) {
    $name = trim($_POST['product_name']);
    $price = floatval($_POST['product_price']);
    $stock = intval($_POST['product_stock']);
    $category_id = $_POST['category_id'];
    $image = $_FILES['product_image']['name'];

    if (!empty($name) && $price > 0 && $category_id) {
        // Upload ảnh vào thư mục cục bộ
        $targetDir = "../../assets/images/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $targetFile = $targetDir . basename($image);
        move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFile);

        // 🟢 Trigger MySQL tự tạo product_id (SP001,...)
        $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, price, stock, image_url, created_at, updated_at) 
                                VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssdis", $category_id, $name, $price, $stock, $image);
        $stmt->execute();

        header("Location: list.php");
        exit();
    }
}

// ===== XÓA SẢN PHẨM =====
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    header("Location: list.php");
    exit();
}

// ===== SỬA SẢN PHẨM =====
if (isset($_POST['edit'])) {
    $id = $_POST['product_id'];
    $name = trim($_POST['product_name_edit']);
    $price = floatval($_POST['product_price_edit']);
    $stock = intval($_POST['product_stock_edit']);
    $category_id = $_POST['category_id_edit'];

    $stmt = $conn->prepare("UPDATE products 
                            SET product_name=?, price=?, stock=?, category_id=?, updated_at=NOW() 
                            WHERE product_id=?");
    $stmt->bind_param("sdiss", $name, $price, $stock, $category_id, $id);
    $stmt->execute();
    header("Location: list.php");
    exit();
}
?>

<h1>Quản lý sản phẩm</h1>

<!-- FORM THÊM SẢN PHẨM -->
<form method="POST" enctype="multipart/form-data"
      style="background:#fff2ec; padding:15px; border-radius:10px; width:90%; margin-bottom:25px;">
  <h3>Thêm sản phẩm mới</h3>

  <input type="text" name="product_name" placeholder="Tên sản phẩm..." required
         style="width:20%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
  <input type="number" name="product_price" placeholder="Giá (VND)" required
         style="width:15%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
  <input type="number" name="product_stock" placeholder="Số lượng tồn" required
         style="width:10%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">

  <select name="category_id" required
          style="width:20%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
    <option value="">-- Chọn danh mục --</option>
    <?php
    $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
    while ($c = $cats->fetch_assoc()) {
        echo "<option value='{$c['category_id']}'>{$c['category_name']}</option>";
    }
    ?>
  </select>

  <input type="file" name="product_image" required style="width:20%;">
  <button type="submit" name="add"
          style="padding:8px 15px; background:#d7a78c; color:#fff; border:none; border-radius:8px; cursor:pointer;">+ Thêm sản phẩm</button>
</form>

<form method="GET" 
      style="margin-bottom:20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap;">

    <!-- Tìm theo tên -->
    <input type="text" name="search" placeholder="Tìm theo tên sản phẩm..."
           value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>"
           style="padding:8px; width:20%; border:1px solid #e0c7b7; border-radius:8px;">

    <!-- Lọc danh mục -->
    <select name="filter_category"
            style="padding:8px; width:18%; border:1px solid #e0c7b7; border-radius:8px;">
        <option value="">-- Danh mục --</option>

        <?php
        $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
        while ($c = $cats->fetch_assoc()) {
            $selected = (isset($_GET['filter_category']) && $_GET['filter_category'] == $c['category_id']) ? "selected" : "";
            echo "<option value='{$c['category_id']}' $selected>{$c['category_name']}</option>";
        }
        ?>
    </select>

    <!-- Lọc trạng thái -->
    <select name="filter_status"
            style="padding:8px; width:18%; border:1px solid #e0c7b7; border-radius:8px;">
        <option value="">-- Trạng thái --</option>
        <option value="1" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == "1") ? "selected" : ""; ?>>
            Còn hàng
        </option>
        <option value="2" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == "2") ? "selected" : ""; ?>>
            Sắp hết
        </option>
        <option value="3" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == "3") ? "selected" : ""; ?>>
            Hết hàng
        </option>
    </select>

    <!-- Nút tìm -->
    <button type="submit"
            style="padding:8px 15px; background:#d7a78c; color:white; border:none; border-radius:8px;">
        Lọc
    </button>

    <!-- Reset -->
    <a href="list.php"
       style="padding:8px 15px; background:#bbb; color:white; text-decoration:none; border-radius:8px;">
        Reset
    </a>
</form>

<!-- DANH SÁCH SẢN PHẨM -->
<table style="width:90%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden;">
  <thead>
    <tr style="background:#f8eae5;">
      <th style="padding:10px;">Ảnh</th>
      <th style="padding:10px;">Tên sản phẩm</th>
      <th style="padding:10px;">Danh mục</th>
      <th style="padding:10px;">Giá</th>
      <th style="padding:10px;">Tồn kho</th>
      <th style="padding:10px;">Trạng thái</th>
      <th style="padding:10px;">Hành động</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $sql = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          ORDER BY p.created_at DESC";
  $result = $conn->query($sql);
  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $statusText = $row['stock'] <= 0 ? "Hết hàng" : ($row['stock'] <= 10 ? "Sắp hết" : "Còn hàng");
          $statusColor = $row['stock'] <= 0 ? "red" : ($row['stock'] <= 10 ? "orange" : "green");
          $imagePath = getImagePath($row['image_url']); // lấy từ config.php

          echo "
          <tr style='text-align:center; border-bottom:1px solid #f1dfd6;'>
            <td><img src='{$imagePath}' width='60' height='60' style='object-fit:cover; border-radius:8px;'></td>
            <td>{$row['product_name']}</td>
            <td>{$row['category_name']}</td>
            <td>" . number_format($row['price'], 0, ',', '.') . " đ</td>
            <td>{$row['stock']}</td>
            <td><span style='color:$statusColor; font-weight:bold;'>$statusText</span></td>
            <td>
              <button onclick=\"openEditForm('{$row['product_id']}', '{$row['product_name']}', '{$row['price']}', '{$row['stock']}', '{$row['category_id']}')\"
                      style='padding:5px 10px; background:#d7a78c; color:white; border:none; border-radius:6px;'>Sửa</button>
              <a href='list.php?delete={$row['product_id']}'
                 onclick='return confirm(\"Xóa sản phẩm này?\")'
                 style='padding:5px 10px; background:#c27d60; color:white; border-radius:6px; text-decoration:none;'>Xóa</a>
            </td>
          </tr>";
      }
  } else {
      echo "<tr><td colspan='7' style='text-align:center; padding:15px;'>Chưa có sản phẩm nào</td></tr>";
  }
  ?>
  </tbody>
</table>

<!-- ======================= POPUP SỬA SẢN PHẨM ======================= -->
<div id="editModal" 
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.4); backdrop-filter:blur(2px); 
            align-items:center; justify-content:center; z-index:9999;">

    <div style="background:white; padding:25px; border-radius:12px; width:450px;
                box-shadow:0 5px 18px rgba(0,0,0,0.2); animation:showModal .25s ease;">
        
        <h3 style="margin-top:0;">✏️ Sửa sản phẩm</h3>

        <form method="POST">
            <input type="hidden" name="product_id" id="edit_id">

            <label>Tên sản phẩm</label>
            <input type="text" name="product_name_edit" id="edit_name" required
                   style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #d8c3b5; border-radius:8px;">

            <label>Giá (VND)</label>
            <input type="number" name="product_price_edit" id="edit_price" required
                   style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #d8c3b5; border-radius:8px;">

            <label>Số lượng tồn kho</label>
            <input type="number" name="product_stock_edit" id="edit_stock" required
                   style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #d8c3b5; border-radius:8px;">

            <label>Danh mục</label>
            <select name="category_id_edit" id="edit_category" required
                    style="width:100%; padding:8px; margin-bottom:15px; border:1px solid #d8c3b5; border-radius:8px;">
                <?php
                $catsPopup = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
                while ($c = $catsPopup->fetch_assoc()) {
                    echo "<option value='{$c['category_id']}'>{$c['category_name']}</option>";
                }
                ?>
            </select>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeEditForm()"
                        style="padding:8px 15px; background:#aaa; border:none; border-radius:8px; color:white;">
                    Hủy
                </button>

                <button type="submit" name="edit"
                        style="padding:8px 15px; background:#d7a78c; border:none; border-radius:8px; color:white;">
                    Lưu thay đổi
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Hiệu ứng popup -->
<style>
@keyframes showModal {
    from { transform:translateY(-20px); opacity:0; }
    to   { transform:translateY(0); opacity:1; }
}
</style>

<script>
function openEditForm(id, name, price, stock, catId) {
    document.getElementById('editModal').style.display = 'flex';

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_stock').value = stock;
    document.getElementById('edit_category').value = catId;
}

function closeEditForm() {
    document.getElementById('editModal').style.display = 'none';
}
</script>


<?php include '../includes/admin_footer.php'; ?>
