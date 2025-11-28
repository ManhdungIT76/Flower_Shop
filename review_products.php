<?php
include 'include/db_connect.php';
include 'config.php';

if (!isset($_GET['order_id'])) {
    echo "<p>Lỗi: Không tìm thấy mã đơn.</p>";
    exit;
}

$order_id = $_GET['order_id'];

// Lấy danh sách sản phẩm trong đơn
$sql = "SELECT od.*, p.product_name, p.image_url
        FROM order_details od
        JOIN products p ON od.product_id = p.product_id
        WHERE od.order_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p>Không tìm thấy sản phẩm trong đơn hàng.</p>";
    exit;
}

?>

<style>
.review-item {
    display: flex;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.review-item:last-child {
    border-bottom: none;
}

.review-thumb {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    object-fit: cover;
}

.review-content {
    flex: 1;
}

.review-content h4 {
    font-size: 15px;
    margin-bottom: 5px;
}

.stars i {
    font-size: 20px;
    color: #ccc;
    cursor: pointer;
}

.stars i.active {
    color: #ffbf00;
}

textarea {
    width: 100%;
    height: 60px;
    margin-top: 6px;
    padding: 8px;
    border-radius: 8px;
    border: 1px solid #ddd;
}
</style>

<?php while ($row = $result->fetch_assoc()): ?>
<?php $img = getImagePath($row['image_url']); ?>

<div class="review-item" data-product="<?= $row['product_id'] ?>">
    <img src="<?= $img ?>" class="review-thumb">

    <div class="review-content">
        <h4><?= $row['product_name'] ?> (x<?= $row['quantity'] ?>)</h4>

        <!-- Sao -->
        <div class="stars" data-product="<?= $row['product_id'] ?>">
            <i class="fa-solid fa-star" data-star="1"></i>
            <i class="fa-solid fa-star" data-star="2"></i>
            <i class="fa-solid fa-star" data-star="3"></i>
            <i class="fa-solid fa-star" data-star="4"></i>
            <i class="fa-solid fa-star" data-star="5"></i>
        </div>

        <!-- Comment -->
        <textarea placeholder="Viết đánh giá..." 
                  data-product="<?= $row['product_id'] ?>"></textarea>
    </div>
</div>

<?php endwhile; ?>

<script>
// Xử lý click chọn sao
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
</script>