<?php
session_start();
include 'include/db_connect.php';
include 'config.php';

// KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user']['id'];

// LẤY TẤT CẢ ĐÁNH GIÁ CỦA USER
$sql = "SELECT fb.*, p.product_name, p.image_url
        FROM feedback fb
        JOIN products p ON fb.product_id = p.product_id
        WHERE fb.user_id = ?
        ORDER BY fb.feedback_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đánh giá</title>

    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/review_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'components/header.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a> › 
    <span>Lịch sử đánh giá</span>
</div>

<div class="history-container">
    <h2>Lịch sử đánh giá của bạn</h2>

    <?php if ($reviews->num_rows > 0): ?>
        <?php while ($row = $reviews->fetch_assoc()): ?>
            <?php $img = getImagePath($row['image_url']); ?>
            
            <div class="review-item">
                <img src="<?= $img ?>" class="review-img">

                <div class="review-content">
                    <h3><?= $row['product_name'] ?></h3>

                    <!-- SAO -->
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-solid fa-star <?= $i <= $row['rating'] ? 'active' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>

                    <?php if ($row['feedback_content'] != ""): ?>
                        <div class="comment-box">
                            <?= nl2br($row['feedback_content']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endwhile; ?>

    <?php else: ?>
        <p style="text-align:center; color:#777; font-size:16px;">Bạn chưa có đánh giá nào.</p>
    <?php endif; ?>
</div>

</body>
</html>
