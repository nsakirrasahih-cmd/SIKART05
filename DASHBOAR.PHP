<?php
session_start();
if (!isset($_SESSION['admin'])) { 
    header("Location: ../login.php"); 
    exit; 
}
include '../koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - SIKART</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>Dashboard Pengurus RT</h2>
    <hr>
    <div class="mb-4">
        <a href="transaksi.php" class="btn btn-success">Kelola / Input Transaksi Kas</a>
        <a href="../index.php" target="_blank" class="btn btn-info text-white">Lihat Website Publik</a>
        <a href="logout.php" class="btn btn-danger float-end">Logout</a>
    </div>
    <div class="alert alert-success">Selamat datang, Pengurus RT! Silakan gunakan menu di atas untuk mengelola data kas warga.</div>
</body>
</html>