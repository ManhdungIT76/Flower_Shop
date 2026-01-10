<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
  header('Location: /Flower_Shop/login.php?redirect=admin/dashboard.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Blossomy Bliss - Trang quản trị</title>
  <style>
    body {
      margin: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #fffaf8;
      color: #4b2e1e;
    }

    /* Sidebar */
    .sidebar {
      width: 220px;
      height: 100vh;
      background-color: #f8eae5;
      position: fixed;
      top: 0;
      left: 0;
      padding: 20px;
      box-sizing: border-box;
    }

    .sidebar h2 {
      text-align: center;
      margin-bottom: 40px;
      color: #4b2e1e;
      font-weight: 600;
    }

    .sidebar a {
      display: block;
      color: #4b2e1e;
      text-decoration: none;
      padding: 10px;
      margin: 8px 0;
      border-radius: 8px;
      transition: 0.3s;
    }

    .sidebar a:hover {
      background-color: #d3b8ac;
      color: white;
    }

    .logout {
      position: absolute;
      bottom: 20px;
      left: 20px;
      width: 180px;
      background-color: #cfa68c;
      color: #fff;
      border: none;
      padding: 10px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
    }

    .logout:hover {
      background-color: #b68a74;
    }

    /* Nội dung chính */
    .content {
      margin-left: 250px;
      padding: 30px;
    }

    table {
      border-collapse: collapse;
      width: 100%;
    }

    th, td {
      padding: 10px;
      border-bottom: 1px solid #f0d8ce;
      text-align: left;
    }

    th {
      background-color: #f8eae5;
    }
  </style>
</head>
<body>

 <div class="sidebar">
  <h2>🌸 Blossomy Bliss</h2>

  <a href="/Flower_Shop/admin/dashboard.php">Tổng quan</a>
  <a href="/Flower_Shop/admin/category/list.php">Danh mục</a>
  <a href="/Flower_Shop/admin/products/list.php">Sản phẩm</a>
  <a href="/Flower_Shop/admin/orders/list.php">Đơn hàng</a>
  <a href="/Flower_Shop/index.php">Về trang chủ</a>

  <button class="logout" onclick="window.location.href='/Flower_Shop/login.php'">
    Đăng xuất
  </button>
</div>

  <div class="content">
