<?php
session_start();
include 'include/db_connect.php';
include 'config.php';

/* ======================================================
   XỬ LÝ ĐẶT HÀNG COD
====================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_cod'])) {

    if (!isset($_SESSION['user'])) {
        echo "<script>alert('Vui lòng đăng nhập trước!'); window.location='login.php';</script>";
        exit;
    }

    $user_id  = $_SESSION['user']['id'];
    $fullname = $_POST['fullname'];
    $phone    = $_POST['phone'];
    $address  = $_POST['address'];
    $note     = trim($_POST['note'] ?? '');
    $items    = json_decode($_POST['items'], true);

    if (!$items || count($items) == 0) {
        echo "<script>alert('Bạn chưa chọn sản phẩm!');</script>";
        exit;
    }

    $payment_method_id  = "TT001"; // COD
    $delivery_method_id = $_POST['delivery_method'];

    $total = 0;
    foreach ($items as $i) $total += $i['price'];

    // Tạo đơn hàng
$stmt = $conn->prepare("
    INSERT INTO orders 
    (user_id, total_amount, payment_method_id, delivery_method_id, payment_status, status, note)
    VALUES (?, ?, ?, ?, 'Chưa thanh toán', 'Chờ xác nhận', ?)
");
$stmt->bind_param("sdsss", 
    $user_id, 
    $total, 
    $payment_method_id, 
    $delivery_method_id, 
    $note
);

if (!$stmt->execute()) {
    die("Lỗi tạo đơn hàng: " . $stmt->error);
}

// LẤY order_id vừa được trigger sinh ra
$res = $conn->query("SELECT @last_order_id AS order_id");
$row = $res->fetch_assoc();
$order_id = $row['order_id'];

if (!$order_id) {
    die("Không lấy được mã đơn hàng!");
}

// Insert chi tiết đơn hàng
foreach ($items as $i) {

    // Lấy product_id
    $p = $conn->prepare("SELECT product_id FROM products WHERE product_name=? LIMIT 1");
    $p->bind_param("s", $i['name']);
    $p->execute();
    $pr = $p->get_result()->fetch_assoc();

    if (!$pr) {
        die("Không tìm thấy sản phẩm: " . $i['name']);
    }

    $product_id = $pr['product_id'];
    $qty = $i['qty'];
    $unit_price = $i['price'] / $qty;

    $d = $conn->prepare("
        INSERT INTO order_details (order_id, product_id, quantity, unit_price)
        VALUES (?, ?, ?, ?)
    ");
    $d->bind_param("ssid", $order_id, $product_id, $qty, $unit_price);

    if (!$d->execute()) {
        die("LỖI INSERT order_details — Lý do: " . $d->error);
    }

    // Trừ kho
    $u = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id=?");
    $u->bind_param("is", $qty, $product_id);
    $u->execute();
}


    // Cập nhật địa chỉ người dùng
    $u2 = $conn->prepare("
        UPDATE users 
        SET full_name=?, phone_number=?, shipping_address=? 
        WHERE user_id=?
    ");
    $u2->bind_param("ssss", $fullname, $phone, $address, $user_id);
    $u2->execute();

    // cập nhật lại session
    $_SESSION['user']['name']    = $fullname;
    $_SESSION['user']['phone']   = $phone;
    $_SESSION['user']['address'] = $address;

    unset($_SESSION['cart']);

    echo "<script>alert('Đặt hàng thành công!'); window.location='orders.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Giỏ hàng</title>

<link rel="stylesheet" href="assets/css/global.css">
<link rel="stylesheet" href="assets/css/cart.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<?php include 'components/header.php'; ?>

<div class="breadcrumb"><a href="index.php">Trang chủ</a> › Giỏ hàng</div>

<section class="cart-container">
<h2>Giỏ hàng của bạn</h2>

<table id="cartTable">
<thead>
<tr>
<th>Hình</th>
<th>Tên</th>
<th>Giá</th>
<th>Số lượng</th>
<th>Tổng</th>
<th>Xóa</th>
</tr>
</thead>
<tbody>

<?php
$grand = 0;
if (empty($_SESSION['cart'])) {
    echo "<tr><td colspan='6' style='text-align:center;'>Giỏ hàng trống!</td></tr>";
} else {
foreach ($_SESSION['cart'] as $item):
    $t = $item['qty'] * $item['price'];
    $grand += $t;
?>
<tr>
<td><img src="<?= getImagePath($item['image']) ?>" class="product-img"></td>
<td><?= $item['name'] ?></td>
<td><?= number_format($item['price']) ?> đ</td>

<td>
<div class="qty-box">
<a class="qty-btn" href="pages/update_qty.php?id=<?= $item['id'] ?>&action=decrease">−</a>
<span class="qty-number"><?= $item['qty'] ?></span>
<a class="qty-btn" href="pages/update_qty.php?id=<?= $item['id'] ?>&action=increase">+</a>
</div>
</td>

<td><?= number_format($t) ?> đ</td>

<td><a href="pages/remove_from_cart.php?id=<?= $item['id'] ?>"><i class="fa fa-trash" style="color:red;"></i></a></td>
</tr>
<?php endforeach; } ?>

</tbody>
</table>

<div class="total">Tổng cộng: <b><?= number_format($grand) ?> đ</b></div>

<button class="checkout-btn" onclick="openPopup()">Tiến hành đặt hàng</button>

</section>


<!-- POPUP THANH TOÁN -->
<div class="overlay" id="overlay">
<div class="popup">
<span class="close-btn" onclick="closePopup()">&times;</span>

<h3>Thông tin thanh toán</h3>

<form method="POST" id="checkoutForm">

<input type="hidden" id="orderItems" name="items">
<input type="hidden" id="paymentMethod">
<input type="hidden" id="deliveryFee" name="delivery_fee" value="20000">

<label>Họ tên:</label>
<input type="text" name="fullname" required value="<?= $_SESSION['user']['name'] ?? '' ?>">

<label>Số điện thoại:</label>
<input type="tel" name="phone" required value="<?= $_SESSION['user']['phone'] ?? '' ?>">

<label>Địa chỉ:</label>
<textarea name="address" placeholder="Nhập địa chỉ giao hàng..." required><?= $_SESSION['user']['address'] ?? '' ?></textarea>

<label>Ghi chú cho cửa hàng:</label>
<textarea name="note" placeholder="Ví dụ: giao giờ hành chính, thêm lời chúc..." rows="2"></textarea>

<h4>Phương thức giao hàng</h4>
<select id="deliveryMethod" name="delivery_method" onchange="updateDeliveryFee()" required>
    <option value="GH001" data-fee="50000">Giao nhanh (+50.000đ)</option>
    <option value="GH002" data-fee="20000" selected>Giao tiêu chuẩn (+20.000đ)</option>
    <option value="GH003" data-fee="40000">Giao theo lịch hẹn (+40.000đ)</option>
    <option value="GH004" data-fee="30000">Giao nội thành (+30.000đ)</option>
</select>

<p id="deliveryFeeText">Phí giao hàng: <strong>20.000 đ</strong></p>

<h4>Tổng thanh toán:</h4>
<p id="totalPayText"><strong>0 đ</strong></p>

<h4>Sản phẩm thanh toán</h4>
<table id="selectedProducts"></table>

<h4>Hình thức thanh toán</h4>
<div class="method-options">
    <div id="codCard" class="method-card active" onclick="selectPayment('cod')">
        <i class="fa-solid fa-truck"></i> COD
    </div>
    <div id="qrCard" class="method-card" onclick="selectPayment('qr')">
        <i class="fa-solid fa-qrcode"></i> QR Banking
    </div>
</div>

<div id="qrBox" style="display:none;">
<iframe id="qrFrame" style="width:100%;height:380px;border:none;border-radius:10px"></iframe>
</div>

<button class="btn-submit" type="submit" name="checkout_cod" id="btnCOD">Xác nhận COD</button>

<button class="btn-submit" type="button" id="btnQR" style="display:none;" onclick="submitQR()">Tạo QR thanh toán</button>

</form>

</div>
</div>

<script>
function openPopup() {

    <?php if (!isset($_SESSION['user'])): ?>
        window.location.href = "login.php";
        return;
    <?php endif; ?>

    let items = [];
    let table = document.getElementById("selectedProducts");
    table.innerHTML = "<tr><th>Tên</th><th>SL</th><th>Tiền</th></tr>";

    document.querySelectorAll("#cartTable tbody tr").forEach(row => {
        let name  = row.children[1].innerText;
        let qty   = parseInt(row.querySelector(".qty-number").innerText);
        let total = parseInt(row.children[4].innerText.replace(/\D/g,""));

        items.push({ name, qty, price: total });

        table.innerHTML += `
            <tr>
                <td>${name}</td>
                <td>${qty}</td>
                <td>${row.children[4].innerText}</td>
            </tr>
        `;
    });

    document.getElementById("orderItems").value = JSON.stringify(items);

    overlay.style.display = "flex";
    updateDeliveryFee();
}

function closePopup() { overlay.style.display = "none"; }

function selectPayment(type) {
    document.getElementById("codCard").classList.remove("active");
    document.getElementById("qrCard").classList.remove("active");

    if (type === "cod") {
        document.getElementById("codCard").classList.add("active");
        document.getElementById("paymentMethod").value = "cod";
        document.getElementById("btnCOD").style.display = "block";
        document.getElementById("btnQR").style.display = "none";
        document.getElementById("qrBox").style.display = "none";
    } else {
        document.getElementById("qrCard").classList.add("active");
        document.getElementById("paymentMethod").value = "qr";
        document.getElementById("btnCOD").style.display = "none";
        document.getElementById("btnQR").style.display = "block";
    }
}

function updateDeliveryFee() {
    let fee = parseInt(document.querySelector("#deliveryMethod option:checked").dataset.fee);
    let items = JSON.parse(document.getElementById("orderItems").value || "[]");

    let subtotal = 0;
    items.forEach(i => subtotal += i.price);

    let total = subtotal + fee;

    document.getElementById("deliveryFeeText").innerHTML =
        "Phí giao hàng: <strong>" + fee.toLocaleString() + " đ</strong>";

    document.getElementById("totalPayText").innerHTML =
        "<strong>" + total.toLocaleString() + " đ</strong>";
}
</script>

<?php include 'components/footer.php'; ?>
</body>
</html>
