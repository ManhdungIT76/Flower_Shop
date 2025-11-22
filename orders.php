<?php
session_start();
include 'include/db_connect.php';
include 'config.php'; // để dùng getImagePath()
// KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập trước!'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user']['id'];  // ✔ LẤY USER ID TỪ SESSION

// LẤY DANH SÁCH ĐƠN HÀNG
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đơn hàng của tôi</title>

  <link rel="stylesheet" href="assets/css/global.css" />
  <link rel="stylesheet" href="assets/css/orders.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<!-- HEADER -->
<?php include 'components/header.php'; ?>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a> › 
  <span>Đơn hàng của tôi</span>
</div>

<!-- DANH SÁCH ĐƠN -->
<section class="orders-section">
    <h2>Đơn hàng của tôi</h2>

    <?php if ($orders->num_rows > 0): ?>
        <?php while ($row = $orders->fetch_assoc()): ?>

            <?php
                // CLASS TRẠNG THÁI
                $statusClass = "status-processing";
                if ($row["status"] == "Đã giao") $statusClass = "status-delivered";
                elseif ($row["status"] == "Đã hủy") $statusClass = "status-cancelled";

                // LẤY ẢNH SẢN PHẨM ĐẦU TIÊN
                $sqlImg = "SELECT p.image_url 
                           FROM order_details od
                           JOIN products p ON od.product_id = p.product_id
                           WHERE od.order_id = ?
                           LIMIT 1";

                $stmtImg = $conn->prepare($sqlImg);
                $stmtImg->bind_param("s", $row['order_id']);
                $stmtImg->execute();
                $imgRow = $stmtImg->get_result()->fetch_assoc();

                $img = $imgRow ? getImagePath($imgRow['image_url']) : "assets/img/no-image.png";
            ?>

            <div class="order-card">
                <div class="order-header">
                    <h3>Mã đơn: #<?= $row['order_id'] ?></h3>
                    <span>Ngày đặt: <?= date("d/m/Y", strtotime($row['order_date'])) ?></span>
                </div>

                <div class="order-body">
                    <!-- Ảnh đại diện đơn hàng -->
                    <img src="<?= $img ?>" class="order-thumb" alt="Ảnh sản phẩm">

                    <div>
                        <p>Tổng tiền: 
                            <strong><?= number_format($row['total_amount'], 0, ',', '.') ?> đ</strong>
                        </p>
                        <p class="order-status <?= $statusClass ?>">
                            Trạng thái: <?= $row['status'] ?>
                        </p>
                    </div>

                    <!-- NÚT XEM CHI TIẾT -->
                    <button class="btn" onclick="openPopup('<?= $row['order_id'] ?>')">
                        Xem chi tiết
                    </button>
                </div>
            </div>

        <?php endwhile; ?>
    <?php else: ?>
        <p class="empty">Bạn chưa có đơn hàng nào.</p>
    <?php endif; ?>

</section>

<!-- POPUP CHI TIẾT -->
<div class="overlay" id="detailOverlay">
    <div class="popup">
        <span class="close-btn" onclick="closePopup()">&times;</span>
        <h3 id="popup-title">Chi tiết đơn hàng</h3>
        <div id="order-detail-content" class="detail-box">Đang tải...</div>
    </div>
</div>

<script>
// ===============================
// MỞ POPUP
// ===============================
function openPopup(orderId) {
    const overlay = document.getElementById("detailOverlay");
    const content = document.getElementById("order-detail-content");

    overlay.style.display = "flex";
    document.body.classList.add("no-scroll");   // 🚫 KHÔNG CHO SCROLL

    content.innerHTML = "Đang tải...";

    fetch("get_orders_detail.php?id=" + orderId)
        .then(res => res.text())
        .then(html => content.innerHTML = html)
        .catch(() => content.innerHTML = "Lỗi tải dữ liệu.");
}

// ===============================
// ĐÓNG POPUP
// ===============================
function closePopup() {
    document.getElementById("detailOverlay").style.display = "none";
    document.body.classList.remove("no-scroll");  // ✔ SCROLL LẠI
}

// ===============================
// BẤM RA NGOÀI ĐỂ ĐÓNG POPUP
// ===============================
document.getElementById("detailOverlay").addEventListener("click", function(e) {
    if (e.target === this) {   // click vào nền đen
        closePopup();
    }
});
</script>

</body>
</html>
