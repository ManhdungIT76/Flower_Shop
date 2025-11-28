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
$sql = "SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY order_date DESC";
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
                
                // chỉ hiện nút khi đã giao và chưa đánh giá
                $canReview = ($row["status"] == "Đã giao" && empty($row["is_reviewed"]));
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

                    <?php if ($canReview): ?>
                        <button class="btn-received"
                                data-order-id="<?= $row['order_id'] ?>"
                                onclick="openReviewPopup('<?= $row['order_id'] ?>')">
                            Đã nhận hàng
                        </button>
                    <?php endif; ?>
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

<!-- POPUP ĐÁNH GIÁ -->
<div class="overlay" id="reviewOverlay" style="display:none;">
    <div class="popup review-popup">
        <span class="close-btn" onclick="closeReviewPopup()">&times;</span>

        <h3>Đánh giá sản phẩm</h3>

        <div id="reviewContent">Đang tải đánh giá...</div>

        <button class="btn submit-review" onclick="submitReview()">Gửi đánh giá</button>
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

/* --- GẮN SAO CLICK --- */
function attachStarEvents() {
    document.querySelectorAll(".stars i").forEach(star => {
        star.addEventListener("click", function () {
            let parent = this.parentElement;
            let val = this.dataset.star;

            parent.querySelectorAll("i").forEach(s => {
                s.classList.remove("active");
                if (s.dataset.star <= val) s.classList.add("active");
            });
        });
    });
}

/* --- POPUP ĐÁNH GIÁ --- */
function openReviewPopup(orderId) {
    const overlay = document.getElementById("reviewOverlay");
    const content = document.getElementById("reviewContent");

    overlay.style.display = "flex";
    content.innerHTML = "Đang tải...";

    // lưu lại orderId để gửi và ẩn nút sau khi đánh giá
    window.currentOrderId = orderId;

    fetch("review_products.php?order_id=" + orderId)
        .then(res => res.text())
        .then(html => {
            content.innerHTML = html;
            attachStarEvents();
        });

    window.currentOrderId = orderId; 
}

function closeReviewPopup() {
    document.getElementById("reviewOverlay").style.display = "none";
}

function submitReview() {
    let reviews = [];

    document.querySelectorAll(".review-item").forEach(item => {
        let productId = item.dataset.product;
        let rating = item.querySelectorAll(".stars i.active").length;
        let comment = item.querySelector("textarea").value.trim();

        if (rating === 0 && comment === "") return;

        reviews.push({ product_id: productId, rating: rating, comment: comment });
    });

    if (reviews.length === 0) {
        alert("Bạn chưa đánh giá sản phẩm nào!");
        return;
    }

    let orderId = window.currentOrderId;

    fetch("submit_feedback_multi.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ order_id: orderId, reviews: reviews })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            alert("Cảm ơn bạn đã đánh giá ❤️");

            closeReviewPopup();

            // ẩn nút “Đã nhận hàng” cho đơn vừa đánh giá, không ẩn lịch sử
            const btn = document.querySelector(`.btn-received[data-order-id="${orderId}"]`);
            if (btn) btn.remove();
        } else {
            alert("Lỗi: " + data.message);
        }
    });
}

// ===============================
// BẤM RA NGOÀI ĐỂ ĐÓNG POPUP
// ===============================
document.getElementById("detailOverlay").addEventListener("click", function(e) {
    if (e.target === this) {   // click vào nền đen
        closePopup();
    }
});

document.getElementById("reviewOverlay").addEventListener("click", e => {
    if (e.target === e.currentTarget) closeReviewPopup();
});
</script>

</body>
</html>
