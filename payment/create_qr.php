<?php
session_start();
include '../include/db_connect.php';

/* ===========================================================
   1) HIỂN THỊ QR THANH TOÁN
=========================================================== */
if (isset($_GET['order_id'])) {

    $order_id = $_GET['order_id'];

    // Lấy đơn hàng
    $stmt = $conn->prepare("
        SELECT user_id, total_amount 
        FROM orders 
        WHERE order_id=? LIMIT 1
    ");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    // Kiểm tra đơn hàng hợp lệ
    if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
        die("Không tìm thấy đơn hàng hoặc không thuộc quyền sở hữu!");
    }

    // GIỮ amount = 2000 để test SEPay
    $amount = 2000;

    $bank = "MBBank";
    $acc  = "0363074451";
    $desc = urlencode($order_id);

    $qr = "https://qr.sepay.vn/img?bank=$bank&acc=$acc&template=compact&amount=$amount&des=$desc";

    ?>
    <!DOCTYPE html>
    <html>
    <body style="text-align:center;font-family:Arial;">
        <h2>Mã QR thanh toán đơn: <?= htmlspecialchars($order_id) ?></h2>
        <img src="<?= $qr ?>" style="width:300px;border-radius:12px;">
        <p>Vui lòng quét mã để thanh toán</p>
    </body>
    </html>
    <?php
    exit;
}



/* ===========================================================
   2) TẠO ĐƠN HÀNG QR BANKING
=========================================================== */
if (isset($_POST['create_qr'])) {

    if (!isset($_SESSION['user'])) {
        echo json_encode(["status" => "not_login"]);
        exit;
    }

    $user_id  = $_SESSION['user']['id'];
    $fullname = trim($_POST['fullname']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $note     = trim($_POST['note'] ?? "");

    $items = json_decode($_POST['items'], true);

    if (!$items || count($items) == 0) {
        echo json_encode(["status" => "no_items"]);
        exit;
    }

    $payment_method_id  = "TT002"; // QR Banking
    $delivery_method_id = $_POST['delivery_method'];
    $fee = (float)$_POST['delivery_fee'];


    /* ------------------------------------------------------
       2.1 Tính tổng tiền đúng
    --------------------------------------------------------- */
    $total = 0;
    foreach ($items as $i) {
        $total += ($i['price'] * $i['qty']);
    }
    $total += $fee;


    /* ------------------------------------------------------
       2.2 Tạo đơn hàng
    --------------------------------------------------------- */
    $stmt = $conn->prepare("
        INSERT INTO orders
        (user_id, total_amount, payment_method_id, delivery_method_id,
         payment_status, status, note)
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
        echo json_encode(["status" => "error", "msg" => $stmt->error]);
        exit;
    }

    // order_id không thể lấy bằng insert_id vì dùng varchar + trigger
    $result = $conn->query("
        SELECT order_id 
        FROM orders 
        WHERE user_id='$user_id' 
        ORDER BY order_date DESC 
        LIMIT 1
    ");
    $order_id = $result->fetch_assoc()['order_id'];

    if (!$order_id) {
        echo json_encode(["status" => "error", "msg" => "Không lấy được mã đơn hàng!"]);
        exit;
    }


    /* ------------------------------------------------------
       2.3 Insert chi tiết đơn hàng
    --------------------------------------------------------- */
    foreach ($items as $i) {

        $product_id = $i['id'];
        $qty        = (int)$i['qty'];

        // Lấy giá từ DB (đảm bảo chính xác)
        $p = $conn->prepare("SELECT price FROM products WHERE product_id=? LIMIT 1");
        $p->bind_param("s", $product_id);
        $p->execute();
        $db_product = $p->get_result()->fetch_assoc();

        if (!$db_product) continue;

        $unit_price = (float)$db_product['price'];

        // Insert từng dòng
        $d = $conn->prepare("
            INSERT INTO order_details (order_id, product_id, quantity, unit_price)
            VALUES (?, ?, ?, ?)
        ");
        $d->bind_param("ssid", $order_id, $product_id, $qty, $unit_price);
        $d->execute();

        // Trừ kho
        $u = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id=?");
        $u->bind_param("is", $qty, $product_id);
        $u->execute();
    }


    /* ------------------------------------------------------
       2.4 Trả về JSON cho JS hiển thị QR
    --------------------------------------------------------- */
    echo json_encode([
        "status"   => "ok",
        "order_id" => $order_id
    ]);
    exit;
}


/* ===========================================================
   3) REQUEST KHÔNG HỢP LỆ
=========================================================== */
echo json_encode(["status" => "invalid"]);
?>
