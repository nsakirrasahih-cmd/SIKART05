<?php
include "../config/database.php";

$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

/* Saldo awal tahun */
$querySaldoAwal = "
    SELECT saldo
    FROM buku_kas.transaksi_kas
    WHERE tanggal < '$tahun-01-01'
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";

$resultSaldoAwal = $conn->query($querySaldoAwal);

if ($resultSaldoAwal && $resultSaldoAwal->num_rows > 0) {
    $saldoAwal = $resultSaldoAwal->fetch_assoc()['saldo'];
} else {
    $saldoAwal = 0;
}

/* Saldo akhir tahun */
$querySaldoAkhir = "
    SELECT saldo
    FROM buku_kas.transaksi_kas
    WHERE YEAR(tanggal) = $tahun
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";

$resultSaldoAkhir = $conn->query($querySaldoAkhir);

if ($resultSaldoAkhir && $resultSaldoAkhir->num_rows > 0) {
    $saldoAkhir = $resultSaldoAkhir->fetch_assoc()['saldo'];
} else {
    $saldoAkhir = 0;
}

/* Total Debit */
$queryDebit = "
    SELECT COALESCE(SUM(debit), 0) AS total
    FROM buku_kas.transaksi_kas
    WHERE YEAR(tanggal) = $tahun
";
$resultDebit = $conn->query($queryDebit);
$totalDebit = $resultDebit->fetch_assoc()['total'];

/* Total Kredit */
$queryKredit = "
    SELECT COALESCE(SUM(kredit), 0) AS total
    FROM buku_kas.transaksi_kas
    WHERE YEAR(tanggal) = $tahun
";
$resultKredit = $conn->query($queryKredit);
$totalKredit = $resultKredit->fetch_assoc()['total'];

/* Jumlah transaksi */
$queryJumlah = "
    SELECT COUNT(*) AS total
    FROM buku_kas.transaksi_kas
    WHERE YEAR(tanggal) = $tahun
";
$resultJumlah = $conn->query($queryJumlah);
$jumlahTransaksi = $resultJumlah->fetch_assoc()['total'];

/* Rekap per bulan */
$queryBulanan = "
    SELECT
        MONTH(tanggal) AS bulan,
        COALESCE(SUM(debit), 0) AS total_debit,
        COALESCE(SUM(kredit), 0) AS total_kredit,
        COUNT(*) AS jumlah_transaksi
    FROM buku_kas.transaksi_kas
    WHERE YEAR(tanggal) = $tahun
    GROUP BY MONTH(tanggal)
    ORDER BY MONTH(tanggal)
";

$resultBulanan = $conn->query($queryBulanan);

$dataBulanan = [];

while ($row = $resultBulanan->fetch_assoc()) {
    $dataBulanan[$row['bulan']] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Laporan Tahunan - SIKART</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    color: #222;
}

.header {
    background: #1f2937;
    color: white;
    padding: 20px;
}

.header h1 {
    margin: 0;
    font-size: 26px;
}

.header p {
    margin: 6px 0 0;
    color: #d1d5db;
}

.container {
    width: 95%;
    max-width: 1300px;
    margin: 25px auto;
}

.filter {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter form {
    display: flex;
    gap: 10px;
    align-items: end;
    flex-wrap: wrap;
}

.filter label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

.filter select {
    padding: 10px;
    min-width: 150px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.btn {
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-cari {
    background: #2563eb;
    color: white;
}

.btn-kembali {
    background: #6b7280;
    color: white;
    margin-bottom: 20px;
}

.btn-cetak {
    background: #16a34a;
    color: white;
    margin-bottom: 20px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card h3 {
    margin: 0 0 10px;
    font-size: 15px;
    color: #666;
}

.card .angka {
    font-size: 22px;
    font-weight: bold;
}

.table-box {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.table-box h2 {
    margin-top: 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #1f2937;
    color: white;
    padding: 12px;
    text-align: left;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

tr:hover {
    background: #f3f4f6;
}

.nominal {
    text-align: right;
    white-space: nowrap;
}

.jumlah {
    text-align: center;
}

.total-row {
    font-weight: bold;
    background: #eeeeee;
}

.footer {
    text-align: center;
    margin: 30px 0;
    color: #777;
}

@media (max-width: 700px) {

    .cards {
        grid-template-columns: 1fr;
    }

    .container {
        width: 92%;
    }

}
@media print {

    body {
        background: white;
    }

    .header {
        background: white;
        color: black;
        padding: 10px 0;
    }

    .header p {
        color: black;
    }

    .filter,
    .btn-kembali,
    .btn-cetak,
    .footer {
        display: none !important;
    }

    .container {
        width: 100%;
        max-width: none;
        margin: 0;
    }

    .cards {
        grid-template-columns: repeat(3, 1fr);
    }

    .card,
    .table-box {
        box-shadow: none;
    }

    table {
        min-width: 0;
    }

    th {
        background: #ddd !important;
        color: black !important;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>SIKART</h1>

    <p>
        Sistem Informasi Kas RT — Laporan Keuangan Tahunan
    </p>

</div>

<div class="container">

<a href="../dashboard.php"
   class="btn btn-kembali">
    ← Kembali ke Dashboard
</a>
<button onclick="window.print()" class="btn btn-cetak">
    🖨️ Cetak Laporan
</button>

<div class="filter">

<form method="GET">

<div>

<label>Tahun</label>

<select name="tahun">

<?php for ($y = 2023; $y <= 2025; $y++): ?>

<option value="<?= $y ?>"
    <?= ($tahun == $y) ? 'selected' : '' ?>>

    <?= $y ?>

</option>

<?php endfor; ?>

</select>

</div>

<button type="submit"
        class="btn btn-cari">

    Tampilkan Laporan

</button>

</form>

</div>

<div class="cards">

    <div class="card">
        <h3>Saldo Awal Tahun <?= $tahun ?></h3>
        <div class="angka">
            Rp <?= number_format($saldoAwal, 0, ',', '.') ?>
        </div>
    </div>

    <div class="card">
        <h3>Saldo Akhir Tahun <?= $tahun ?></h3>
        <div class="angka">
            Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
        </div>
    </div>

</div>

<div class="cards">

<div class="card">

<h3>Total Debit Tahun <?= $tahun ?></h3>

<div class="angka">

Rp <?= number_format(
    $totalDebit,
    0,
    ',',
    '.'
) ?>

</div>

</div>

<div class="card">

<h3>Total Kredit Tahun <?= $tahun ?></h3>

<div class="angka">

Rp <?= number_format(
    $totalKredit,
    0,
    ',',
    '.'
) ?>

</div>

</div>

<div class="card">

<h3>Jumlah Transaksi</h3>

<div class="angka">

<?= $jumlahTransaksi ?>

</div>

</div>

</div>

<div class="table-box">

<h2>
    Rekap Keuangan Tahun <?= $tahun ?>
</h2>

<table>

<thead>

<tr>

<th>No</th>

<th>Bulan</th>

<th>Total Debit</th>

<th>Total Kredit</th>

<th>Jumlah Transaksi</th>

</tr>

</thead>

<tbody>

<?php

$totalDebitTabel = 0;
$totalKreditTabel = 0;
$totalTransaksiTabel = 0;

for ($i = 1; $i <= 12; $i++):

    if (isset($dataBulanan[$i])) {

        $debit = $dataBulanan[$i]['total_debit'];
        $kredit = $dataBulanan[$i]['total_kredit'];
        $jumlah = $dataBulanan[$i]['jumlah_transaksi'];

    } else {

        $debit = 0;
        $kredit = 0;
        $jumlah = 0;

    }

    $totalDebitTabel += $debit;
    $totalKreditTabel += $kredit;
    $totalTransaksiTabel += $jumlah;

?>

<tr>

<td><?= $i ?></td>

<td>
    <?= $namaBulan[$i] ?>
</td>

<td class="nominal">
    Rp <?= number_format(
        $debit,
        0,
        ',',
        '.'
    ) ?>
</td>

<td class="nominal">
    Rp <?= number_format(
        $kredit,
        0,
        ',',
        '.'
    ) ?>
</td>

<td class="jumlah">
    <?= $jumlah ?>
</td>

</tr>

<?php endfor; ?>

<tr class="total-row">

<td colspan="2">
    TOTAL
</td>

<td class="nominal">
    Rp <?= number_format(
        $totalDebitTabel,
        0,
        ',',
        '.'
    ) ?>
</td>

<td class="nominal">
    Rp <?= number_format(
        $totalKreditTabel,
        0,
        ',',
        '.'
    ) ?>
</td>

<td class="jumlah">
    <?= $totalTransaksiTabel ?>
</td>

</tr>

</tbody>

</table>

</div>

<div class="footer">

    SIKART — Sistem Informasi Kas RT

</div>

</div>

</body>

</html>