<?php
include 'include/db_connect.php';
include 'config.php';
session_start();

if (!isset($_SESSION['user'])) { exit("Bạn cần đăng nhập."); }
$user_id = $_SESSION['user']['id'];

$order_id = $_GET['id'];

// LẤY THÔNG TIN ĐƠN
$sql = "SELECT o.*, pm.method_name AS payment_method, dm.method_name AS delivery_method
        FROM orders o
        LEFT JOIN payment_methods pm ON o.payment_method_id = pm.payment_method_id
        LEFT JOIN delivery_methods dm ON o.delivery_method_id = dm.delivery_method_id
        WHERE o.order_id = ? AND o.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// LẤY SẢN PHẨM TRONG ĐƠN
$sql_items = "SELECT od.*, p.product_name, p.image_url
              FROM order_details od
              JOIN products p ON od.product_id = p.product_id
              WHERE od.order_id = ?";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("s", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();

$order_date = date("d/m/Y H:i", strtotime($order["order_date"]));
$ship_date  = date("d/m/Y H:i", strtotime($order["ship_date"]));

$paymentStatus = $order['payment_status'];
if (!empty($order['status']) && $order['status'] === 'Đã giao') {
    $paymentStatus = 'Đã thanh toán';
}
?>

<link rel="stylesheet" href="/Flower_Shop/assets/css/get_orders_detail.css">

<div class="order-detail-wrapper">

    <!-- ========================= -->
    <!--        CỘT TRÁI           -->
    <!-- ========================= -->
    <div class="order-left">

        <h2 class="detail-title">
            Chi tiết đơn hàng <strong>#<?= $order_id ?></strong>
        </h2>

        <table class="order-table">
            <tr>
                <th>Sản phẩm</th>
                <th>Tên</th>
                <th>SL</th>
                <th>Giá</th>
            </tr>

            <?php while ($item = $items->fetch_assoc()): ?>
                <tr>
                    <td>
                        <?php $img = getImagePath($item['image_url']); ?>
                        <img src="<?= $img ?>" class="product-thumb">
                    </td>
                    <td><?= $item['product_name'] ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['unit_price'], 0, ',', '.') ?> đ</td>
                </tr>
            <?php endwhile; ?>
        </table>

    </div>

    <!-- ========================= -->
    <!--        CỘT PHẢI           -->
    <!-- ========================= -->
    <div class="order-right">

        <p><span class="label">Ngày đặt:</span> <?= $order_date ?></p>

        <p><span class="label">Ghi chú:</span>
            <?= $order['note'] ? htmlspecialchars($order['note']) : '—' ?>
        </p>

        <p><span class="label">Thanh toán:</span>
            <?= $order['payment_method'] ?> (<?= $paymentStatus ?>)
        </p>

        <p><span class="label">Giao hàng:</span> <?= $order['delivery_method'] ?></p>

        <p><span class="label">Ngày giao:</span> <?= $ship_date ?></p>

        <p><span class="label">Tổng tiền:</span>
            <strong><?= number_format($order['total_amount'], 0, ',', '.') ?> đ</strong>
        </p>

    </div>

</div>
