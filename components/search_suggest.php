<?php
require_once "../include/db_connect.php";
require_once "../config.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['keyword'])) {
    echo json_encode([]);
    exit;
}

$keyword = "%" . $_GET['keyword'] . "%";

$sql = "SELECT product_id, product_name, price, image_url 
        FROM products 
        WHERE product_name LIKE ? 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $keyword);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];

while ($row = $result->fetch_assoc()) {

    $suggestions[] = [
        "id"    => $row["product_id"],                    // <<< THÊM ID
        "name"  => $row["product_name"],
        "price" => $row["price"],
        "image" => getImagePath($row["image_url"])        // <<< DÙNG HÀM CHUẨN
    ];
}

echo json_encode($suggestions);
exit;
