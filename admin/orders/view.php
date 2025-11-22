<?php
include '../../include/db_connect.php';
include '../includes/admin_header.php';

$orderId = $_GET['id'] ?? '';
if ($orderId === '') {
    echo "<p>Thiếu mã đơn.</p>";
    include '../includes/admin_footer.php';
    exit;
}

$stmt = $conn->prepare("
    SELECT o.order_id, o.order_date, o.total_amount, o.status, o.payment_status,
           o.delivery_method_id, o.note,
           u.full_name, u.phone_number, u.shipping_address
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    echo "<p>Không tìm thấy đơn.</p>";
    include '../includes/admin_footer.php';
    exit;
}

$detailsStmt = $conn->prepare("
    SELECT od.product_id, od.quantity, od.unit_price, p.product_name
    FROM order_details od
    LEFT JOIN products p ON od.product_id = p.product_id
    WHERE od.order_id = ?
");
$detailsStmt->bind_param("s", $orderId);
$detailsStmt->execute();
$details = $detailsStmt->get_result();
?>
<h1>Chi tiết đơn #<?php echo htmlspecialchars($order['order_id']); ?></h1>

<p><a href="list.php" style="text-decoration:none;color:#d7a78c;">&larr; Quay lại danh sách</a></p>

<div style="background:#fff;padding:15px;border-radius:10px;width:90%;margin-bottom:15px;">
  <h3>Thông tin khách hàng</h3>
  <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
  <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order['phone_number']); ?></p>
  <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
</div>

<div style="background:#fff;padding:15px;border-radius:10px;width:90%;margin-bottom:15px;">
  <h3>Thông tin đơn</h3>
  <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
  <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
  <p><strong>Thanh toán:</strong> <?php echo htmlspecialchars($order['payment_status']); ?></p>
  <p><strong>Phương thức giao:</strong> <?php echo htmlspecialchars($order['delivery_method_id']); ?></p>
  <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
  <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</p>
</div>

<div style="background:#fff;padding:15px;border-radius:10px;width:90%;margin-bottom:15px;">
  <h3>Sản phẩm</h3>
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="background:#f8eae5;">
        <th style="padding:8px;border:1px solid #f1dfd6;">Mã SP</th>
        <th style="padding:8px;border:1px solid #f1dfd6;">Tên sản phẩm</th>
        <th style="padding:8px;border:1px solid #f1dfd6;">Số lượng</th>
        <th style="padding:8px;border:1px solid #f1dfd6;">Đơn giá</th>
        <th style="padding:8px;border:1px solid #f1dfd6;">Thành tiền</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($details->num_rows > 0): ?>
        <?php while ($d = $details->fetch_assoc()): ?>
          <tr style="text-align:center;border-bottom:1px solid #f1dfd6;">
            <td style="padding:8px;border:1px solid #f1dfd6;"><?php echo htmlspecialchars($d['product_id']); ?></td>
            <td style="padding:8px;border:1px solid #f1dfd6;"><?php echo htmlspecialchars($d['product_name']); ?></td>
            <td style="padding:8px;border:1px solid #f1dfd6;"><?php echo (int)$d['quantity']; ?></td>
            <td style="padding:8px;border:1px solid #f1dfd6;"><?php echo number_format($d['unit_price'], 0, ',', '.'); ?> đ</td>
            <td style="padding:8px;border:1px solid #f1dfd6;"><?php echo number_format($d['unit_price'] * $d['quantity'], 0, ',', '.'); ?> đ</td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" style="padding:10px;text-align:center;">Không có sản phẩm</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include '../includes/admin_footer.php'; ?>
