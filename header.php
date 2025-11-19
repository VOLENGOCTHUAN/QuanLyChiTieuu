<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Personal Finance Manager</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="main-header">
<h1>Personal Finance Manager</h1>
<?php if (isset($_SESSION['user_id'])): ?>
<nav class="nav-bar">
<a href="dashboard.php">Dashboard</a>
<a href="categories.php">Danh mục</a>
<a href="actions/action_logout.php">Đăng xuất</a>
</nav>
<?php endif; ?>
</header>
<main class="main-content">