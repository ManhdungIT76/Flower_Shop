<?php
require_once __DIR__ . '/../include/admin_gate.php';
forbid_admin_buying();
session_start();
include '../include/db_connect.php';

/* ===========================================================
   1) HIỂN THỊ QR KHI CÓ order_id
=========================================================== */
if (isset($_GET['order_id'])) {

    $order_id = $_GET['order_id'];

    // Lấy đơn
    $stmt = $conn->prepare("
        SELECT user_id, total_amount 
        FROM orders 
        WHERE order_id=? LIMIT 1
    ");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    // Check quyền sở hữu đơn
    if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
        die("Không tìm thấy đơn hoặc không thuộc quyền sở hữu!");
    }

    /* ============================================================
       AMOUNT — 2 CHẾ ĐỘ (bạn chọn bằng comment)
    ============================================================ */

    // (1) Chế độ DEMO SEPAY – luôn 2000đ
     $amount = 2000;

    // (2) Chế độ thật
    //$amount = (int)$order['total_amount'];

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

    // Kiểm tra xem đã tạo đơn QR chưa
    $check = $conn->prepare("
        SELECT order_id
        FROM orders
        WHERE user_id=? 
        AND payment_status='Chưa thanh toán'
        AND payment_method_id='TT002'
        AND status<>'Đã hủy'
        ORDER BY order_date DESC
        LIMIT 1
    ");
    $check->bind_param("s", $user_id);
    $check->execute();
    $checkRes = $check->get_result();

    if ($checkRes->num_rows > 0) {
    $old_order = $checkRes->fetch_assoc()['order_id'];

    echo json_encode([
        "status"   => "already_created",
        "order_id" => $old_order
    ]);
    exit;
    }

    /* ------------------ Tính tổng -------------------------- */
    $total = 0;
    foreach ($items as $i) {
        $total += ($i['price'] * $i['qty']);
    }
    $total += $fee;


    /* ------------------ Tạo đơn ---------------------------- */
    $conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        INSERT INTO orders
        (user_id, total_amount, payment_method_id, delivery_method_id,
         payment_status, status, note)
        VALUES (?, ?, ?, ?, 'Chưa thanh toán', 'Chờ xác nhận', ?)
    ");
    $stmt->bind_param("sdsss", $user_id, $total, $payment_method_id, $delivery_method_id, $note);
    $stmt->execute();
    // Lấy mã đơn trigger tạo
    $getID = $conn->query("
        SELECT order_id 
        FROM orders
        WHERE user_id = '$user_id' AND status<>'Đã hủy'
        ORDER BY order_date DESC
        LIMIT 1
    ");
    $order_id = $getID->fetch_assoc()['order_id'];

    foreach ($items as $i) {
        // lấy giá từ DB
        $p = $conn->prepare("SELECT price FROM products WHERE product_id=? LIMIT 1");
        $p->bind_param("s", $i['id']);
        $p->execute();
        $db_product = $p->get_result()->fetch_assoc();
        if (!$db_product) throw new Exception("product_not_found");

        $qty = (int)$i['qty'];
        $unit_price = (float)$db_product['price'];

        $d = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $d->bind_param("ssid", $order_id, $i['id'], $qty, $unit_price);
        $d->execute();

        $u = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id=?");
        $u->bind_param("is", $qty, $i['id']);
        $u->execute();
    }

    $conn->commit();
    echo json_encode(["status" => "ok", "order_id" => $order_id]);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}
}

echo json_encode(["status" => "invalid"]);
?>
