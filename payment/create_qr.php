<?php
session_start();
include 'include/db_connect.php';

/* ======================================
   HIỂN THỊ QR
====================================== */
if (isset($_GET['order_id'])) {

    $order_id = $_GET['order_id'];

    $stmt = $conn->prepare("SELECT total_amount FROM orders WHERE order_id=?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) die("Không tìm thấy đơn hàng");

    //$amount = intval($order['total_amount']);
    $amount = 2000;

    $bank = "MBBank";
    $acc  = "0363074451";
    $desc = urlencode($order_id);

    $qr = "https://qr.sepay.vn/img?bank=$bank&acc=$acc&template=compact&amount=$amount&des=$desc";

?>
<!DOCTYPE html>
<html>
<body style="text-align:center;font-family:Arial;">
<h2>QR thanh toán đơn <?= $order_id ?></h2>
<img src="<?= $qr ?>" style="width:280px;border-radius:10px;">
<p>Vui lòng quét mã để thanh toán</p>
</body>
</html>
<?php exit; }


/* ======================================
   TẠO ĐƠN HÀNG QR
====================================== */
if (isset($_POST['create_qr'])) {

    if (!isset($_SESSION['user'])) {
        echo json_encode(["status"=>"not_login"]);
        exit;
    }

    $user_id  = $_SESSION['user']['id'];
    $fullname = $_POST['fullname'];
    $phone    = $_POST['phone'];
    $address  = $_POST['address'];
    $items    = json_decode($_POST['items'], true);

    if (!$items) {
        echo json_encode(["status"=>"no_items"]);
        exit;
    }

    $payment_method_id  = "TT002"; // QR Banking
    $delivery_method_id = $_POST['delivery_method']; 

    $total = 0;
    foreach ($items as $i) $total += $i['price'];

    $fee = $_POST['delivery_fee'];
    $total += $fee;


    // TRẠNG THÁI CHUẨN THEO DB
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, payment_method_id, delivery_method_id,
                            payment_status, status, note)
        VALUES (?, ?, ?, ?, 'Chưa thanh toán', 'Chờ xác nhận', '')
    ");
    $stmt->bind_param("sdss", $user_id, $total, $payment_method_id, $delivery_method_id);
    $stmt->execute();

    $res = $conn->query("SELECT order_id FROM orders ORDER BY order_date DESC LIMIT 1");
    $order_id = $res->fetch_assoc()['order_id'];

    foreach ($items as $i) {
        $p = $conn->prepare("SELECT product_id FROM products WHERE product_name=? LIMIT 1");
        $p->bind_param("s", $i['name']);
        $p->execute();
        $pr = $p->get_result()->fetch_assoc();

        $product_id = $pr['product_id'];
        $qty        = $i['qty'];
        $unit_price = $i['price'] / $qty;

        $d = $conn->prepare("
            INSERT INTO order_details (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $d->bind_param("ssid", $order_id, $product_id, $qty, $unit_price);
        $d->execute();
    }

    echo json_encode(["status"=>"ok", "order_id"=>$order_id]);
    exit;
}

echo json_encode(["status"=>"invalid"]);
?>
