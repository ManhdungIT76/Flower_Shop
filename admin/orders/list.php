<?php
session_start();
include '../../include/db_connect.php';
include '../includes/admin_header.php';

/* Danh sách trạng thái dùng chung */
$statusOptions = [
    "Chờ xác nhận",
    "Đang xử lý",
    "Đang giao hàng",
    "Đã giao",
    "Đã hủy"
];

/* Xử lý cập nhật trạng thái */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {

    $orderId   = $_POST['order_id'] ?? '';
    $newStatus = $_POST['new_status'] ?? '';

    if ($orderId !== '' && $newStatus !== '') {

        // Nếu trạng thái mới là "Đã giao" → ghi ngày nhận
        if ($newStatus === 'Đã giao') {
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = ?, ship_date = NOW(), payment_status = 'Đã thanh toán'
                WHERE order_id = ?
            ");
        } 
        // Nếu đổi từ 'Đã giao' sang trạng thái khác → reset ngày nhận
        else {
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = ?, ship_date = NULL, payment_status = 'Chưa thanh toán'
                WHERE order_id = ?
            ");
        }

        $stmt->bind_param("ss", $newStatus, $orderId);
        $stmt->execute();
    }

    header("Location: list.php");
    exit;
}


/* Tìm kiếm & lọc */
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT 
            o.order_id,
            u.full_name AS customer_name,
            o.order_date,
            o.total_amount,
            o.status
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE 1";

if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $sql .= " AND (o.order_id LIKE '%$safe%' OR u.full_name LIKE '%$safe%')";
}

if ($status_filter !== '') {
    $safeStatus = $conn->real_escape_string($status_filter);
    $sql .= " AND o.status = '$safeStatus'";
}

// Sắp xếp theo ngày đặt mới nhất
$sql .= " ORDER BY o.order_date DESC ";


$result = $conn->query($sql);

/* Màu trạng thái */
function statusColor($status) {
    switch ($status) {
        case 'Chờ xác nhận':  return ['#999', '⏳'];
        case 'Đang xử lý':    return ['#3498db', '🔧'];
        case 'Đang giao hàng':return ['#e67e22', '🚚'];
        case 'Đã giao':       return ['#27ae60', '✔'];
        case 'Đã hủy':        return ['#e74c3c', '✖'];
        default:              return ['black', ''];
    }
}
?>
<h1>Quản lý đơn hàng</h1>

<form method="GET"
      style="margin-bottom:25px; display:flex; justify-content:space-between;
             align-items:center; background:#fff2ec; padding:15px;
             border-radius:10px; width:90%;">
  <div style="flex:1;">
    <input type="text" name="search" placeholder="Tìm mã đơn hoặc tên khách hàng"
           value="<?php echo htmlspecialchars($search); ?>"
           style="width:60%; padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
    <button type="submit"
            style="padding:8px 15px; background:#d7a78c; color:#fff;
                   border:none; border-radius:8px; cursor:pointer;">
      Tìm kiếm
    </button>
  </div>

  <div>
    <select name="status" onchange="this.form.submit()"
            style="padding:8px; border:1px solid #e0c7b7; border-radius:8px;">
      <option value="">-- Lọc theo trạng thái --</option>
      <?php 
      foreach ($statusOptions as $s) {
          $sel = ($status_filter == $s) ? "selected" : "";
          echo "<option value='$s' $sel>$s</option>";
      }
      ?>
    </select>
  </div>
</form>

<table style="width:90%; border-collapse:collapse; background:white;
               border-radius:10px; overflow:hidden;">
  <thead>
    <tr style="background:#f8eae5;">
      <th style="padding:10px;">Mã đơn</th>
      <th style="padding:10px;">Tên khách hàng</th>
      <th style="padding:10px;">Ngày đặt</th>
      <th style="padding:10px;">Tổng tiền</th>
      <th style="padding:10px;">Trạng thái</th>
      <th style="padding:10px;">Hành động</th>
    </tr>
  </thead>
  <tbody>
  <?php
  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          list($color, $icon) = statusColor($row['status']);

          $statusSelect = "";
          foreach ($statusOptions as $s) {
              $sel = ($s == $row['status']) ? "selected" : "";
              $statusSelect .= "<option value=\"$s\" $sel>$s</option>";
          }

          echo "
          <tr style='text-align:center; border-bottom:1px solid #f1dfd6;'>
            <td>{$row['order_id']}</td>
            <td>{$row['customer_name']}</td>
            <td>" . date('d/m/Y', strtotime($row['order_date'])) . "</td>
            <td>" . number_format($row['total_amount'], 0, ',', '.') . " đ</td>
            <td style='color: {$color}; font-weight: bold;'>
                {$icon} {$row['status']}
            </td>
            <td>
              <form method='POST' style='display:inline-block; margin-right:6px;'>
                <input type='hidden' name='update_status' value='1'>
                <input type='hidden' name='order_id' value='{$row['order_id']}'>
                <select name='new_status' onchange='this.form.submit()'
                        style='padding:5px 8px; border-radius:6px;'>
                  {$statusSelect}
                </select>
                <noscript><button type='submit'>Cập nhật</button></noscript>
              </form>
              <a href='view.php?id={$row['order_id']}'
                 style='padding:5px 10px; background:#d7a78c;
                        color:white; border-radius:6px; text-decoration:none;'>
                Xem
              </a>
            </td>
          </tr>";
      }
  } else {
      echo "<tr><td colspan='6' style='text-align:center; padding:15px;'>
                Không có đơn hàng nào
            </td></tr>";
  }
  ?>
  </tbody>
</table>

<?php include '../includes/admin_footer.php'; ?>
