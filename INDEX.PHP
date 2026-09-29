<?php
include 'koneksi.php';
$query_masuk = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='masuk'");
$masuk = mysqli_fetch_assoc($query_masuk)['total'] ?? 0;

$query_keluar = mysqli_query($koneksi, "SELECT SUM(jumlah) as total FROM transaksi WHERE jenis='keluar'");
$keluar = mysqli_fetch_assoc($query_keluar)['total'] ?? 0;
$saldo = $masuk - $keluar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SIKART - Sistem Informasi Kas RT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>SIKART (Sistem Informasi Kas RT)</h2>
        <a href="login.php" class="btn btn-outline-primary btn-sm">Login Pengurus</a>
    </div>

    <div class="row text-white text-center mb-4">
        <div class="col-md-4 mb-2"><div class="p-3 bg-success rounded"><h5>Pemasukan</h5><h3>Rp <?= number_format($masuk, 0, ',', '.'); ?></h3></div></div>
        <div class="col-md-4 mb-2"><div class="p-3 bg-danger rounded"><h5>Pengeluaran</h5><h3>Rp <?= number_format($keluar, 0, ',', '.'); ?></h3></div></div>
        <div class="col-md-4 mb-2"><div class="p-3 bg-primary rounded"><h5>Saldo Kas</h5><h3>Rp <?= number_format($saldo, 0, ',', '.'); ?></h3></div></div>
    </div>

    <h4>Riwayat Kas Warga</h4>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr><th>No</th><th>Tanggal</th><th>Keterangan</th><th>Jenis</th><th>Jumlah</th></tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            $data = mysqli_query($koneksi, "SELECT * FROM transaksi ORDER BY tanggal DESC");
            while ($row = mysqli_fetch_assoc($data)) {
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $row['tanggal']; ?></td>
                <td><?= $row['keterangan']; ?></td>
                <td><span class="badge bg-<?= $row['jenis'] == 'masuk' ? 'success' : 'danger'; ?>"><?= ucfirst($row['jenis']); ?></span></td>
                <td>Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>