<?php
include '../../include/db_connect.php';
include '../includes/admin_header.php';

// ===== XỬ LÝ THÊM DANH MỤC =====
if (isset($_POST['add'])) {
    $name = trim($_POST['category_name']);
    $desc = trim($_POST['category_description']);

    if (!empty($name)) {

        // 1) KIỂM TRA TRÙNG TÊN DANH MỤC
        $check = $conn->prepare("SELECT 1 FROM categories WHERE category_name = ? LIMIT 1");
        $check->bind_param("s", $name);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Tên danh mục đã tồn tại!');</script>";
        } else {
            // 2) TẠO MÃ TỰ ĐỘNG DMxxx
            $sql = "SELECT MAX(CAST(SUBSTRING(category_id, 3) AS UNSIGNED)) AS max_id FROM categories";
            $res = $conn->query($sql);
            $row = $res->fetch_assoc();
            $next_id = ($row['max_id'] ?? 0) + 1;
            $new_id = "DM" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO categories (category_id, category_name, category_description, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("sss", $new_id, $name, $desc);
            $stmt->execute();

            header("Location: list.php");
            exit();
        }
    }
}


// ===== XỬ LÝ XÓA DANH MỤC =====
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();

    header("Location: list.php");
    exit();
}

// ===== XỬ LÝ SỬA DANH MỤC =====
if (isset($_POST['edit'])) {
    $id   = $_POST['category_id'];
    $name = trim($_POST['category_name_edit']);
    $desc = trim($_POST['category_description_edit']);

    // KIỂM TRA TRÙNG TÊN (TRỪ CHÍNH NÓ)
    $check = $conn->prepare("
        SELECT 1 FROM categories 
        WHERE category_name = ? AND category_id <> ?
        LIMIT 1
    ");
    $check->bind_param("ss", $name, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('Tên danh mục đã tồn tại!');</script>";
    } else {
        $stmt = $conn->prepare("
            UPDATE categories 
            SET category_name = ?, category_description = ?
            WHERE category_id = ?
        ");
        $stmt->bind_param("sss", $name, $desc, $id);
        $stmt->execute();

        header("Location: list.php");
        exit();
    }
}
?>

<h1>Quản lý danh mục sản phẩm</h1>

<!-- Form thêm danh mục -->
<form method="POST" style="background:#fff2ec; padding:15px; border-radius:10px; width:70%; margin-bottom:25px;">
  <h3>Thêm danh mục mới</h3>
  <input type="text" name="category_name" placeholder="Nhập tên danh mục..." required
         style="width:60%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
  <input type="text" name="category_description" placeholder="Mô tả (tùy chọn)" 
         style="width:30%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
  <button type="submit" name="add"
          style="padding:8px 15px; background:#d7a78c; color:#fff; border:none; border-radius:8px; cursor:pointer;">+ Thêm</button>
</form>

<!-- Bảng danh mục -->
<table style="width:90%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden;">
  <thead>
    <tr style="background:#f8eae5;">
      <th style="padding:10px;">Mã danh mục</th>
      <th style="padding:10px;">Tên danh mục</th>
      <th style="padding:10px;">Ngày tạo</th>
      <th style="padding:10px;">Hành động</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $sql = "SELECT * FROM categories ORDER BY created_at DESC";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $catId = htmlspecialchars($row['category_id']);
          $catName = htmlspecialchars($row['category_name']);
          $catDesc = htmlspecialchars($row['category_description'] ?? '');
          $catDate = date('d/m/Y', strtotime($row['created_at']));

          echo "<tr style='text-align:center; border-bottom:1px solid #f1dfd6;'>
                  <td>{$catId}</td>
                  <td>{$catName}</td>
                  <td>{$catDate}</td>
                  <td>
                    <button onclick=\"openEditForm('{$catId}', '{$catName}', '{$catDesc}')\" 
                            style='padding:5px 10px; background:#d7a78c; color:white; border:none; border-radius:6px; cursor:pointer;'>Sửa</button>
                    <a href='list.php?delete={$catId}' 
                       onclick='return confirm(\"Bạn có chắc muốn xóa danh mục này không?\")' 
                       style='padding:5px 10px; background:#c27d60; color:white; border-radius:6px; text-decoration:none;'>Xóa</a>
                  </td>
                </tr>";
      }
  } else {
      echo "<tr><td colspan='4' style='text-align:center; padding:15px;'>Chưa có danh mục nào</td></tr>";
  }
  ?>
  </tbody>
</table>

<!-- Form sửa (ẩn) -->
<div id="editForm" style="display:none; margin-top:30px; background:#fff2ec; padding:20px; border-radius:10px; width:70%;">
  <form method="POST">
    <h3>Sửa danh mục</h3>
    <input type="hidden" name="category_id" id="edit_id">
    <input type="text" name="category_name_edit" id="edit_name" required 
           style="width:60%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
    <input type="text" name="category_description_edit" id="edit_desc"
           style="width:30%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;" placeholder="Mô tả (tùy chọn)">
    <button type="submit" name="edit" 
            style="padding:8px 15px; background:#d7a78c; color:#fff; border:none; border-radius:8px; cursor:pointer;">Lưu</button>
    <button type="button" onclick="document.getElementById('editForm').style.display='none'"
            style="padding:8px 15px; background:#bbb; color:#fff; border:none; border-radius:8px;">Hủy</button>
  </form>
</div>

<script>
function openEditForm(id, name, desc) {
  document.getElementById('editForm').style.display = 'block';
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_desc').value = desc;
}
</script>

<?php include '../includes/admin_footer.php'; ?>
