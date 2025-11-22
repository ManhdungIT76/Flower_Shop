<?php
session_start();

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: ../cart.php");
    exit;
}

$id = $_GET['id'];
$action = $_GET['action'];

if (!isset($_SESSION['cart'][$id])) {
    header("Location: ../cart.php");
    exit;
}

if ($action == "increase") {
    $_SESSION['cart'][$id]['qty']++;
}

if ($action == "decrease") {
    if ($_SESSION['cart'][$id]['qty'] > 1) {
        $_SESSION['cart'][$id]['qty']--;
    } else {
        unset($_SESSION['cart'][$id]);
    }
}

header("Location: ../cart.php");
exit;
