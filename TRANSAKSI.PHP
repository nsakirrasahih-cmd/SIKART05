<?php
session_start();
if (!isset($_SESSION['admin'])) { 
    header("Location: ../login.php"); 
    exit; 
}
include '../koneksi.php';

if (isset($_POST['simpan'])) {
    $tanggal = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $jenis = $_POST['jenis'];
    $jumlah = $_POST['jumlah'];
    $kategori = $_POST['kategori'];

    mysqli_query($koneksi, "INSERT INTO transaksi (tanggal, keterangan, jenis, jumlah, kategori) VALUES ('$tanggal', '$keterangan', '$jenis', '$jumlah', '$kategori')");
    header("Location: transaksi.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Transaksi - SIKART</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <h2>Input & Kelola Transaksi Kas</h2>
    <a href="dashboard.php" class="btn btn-secondary mb-3">Kembali ke Dashboard</a>

    <div class="card p-3 mb-4">
        <h5>Tambah Catatan Baru</h5>
        <form method="POST">
            <div class="row">
                <div class="col-md-3 mb-3"><label>Tanggal</label><input type="date" name="tanggal" class="form-control" required></div>
                <div class="col-md-3 mb-3"><label>Jenis</label><select name="jenis" class="form-control"><option value="masuk">Pemasukan</option><option value="keluar">Pengeluaran</option></select></div>
                <div class="col-md-3 mb-3"><label>Kategori</label><input type="text" name="kategori" class="form-control" placeholder="Cth: Iuran Warga" required></div>
                <div class="col-md-3 mb-3"><label>Jumlah (Rp)</label><input type="number" name="jumlah" class="form-control" required></div>
            </div>
            <div class="mb-3"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="2" required></textarea></div>
            <button type="submit" name="simpan" class="btn btn-success">Simpan Data</button>
        </form>
    </div>
</body>
</html>