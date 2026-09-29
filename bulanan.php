<?php
include "../config/database.php";

$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
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

/* Total Debit */
$queryDebit = "
    SELECT COALESCE(SUM(debit), 0) AS total
    FROM buku_kas.transaksi_kas
    WHERE MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun
";
$resultDebit = $conn->query($queryDebit);
$totalDebit = $resultDebit->fetch_assoc()['total'];

/* Total Kredit */
$queryKredit = "
    SELECT COALESCE(SUM(kredit), 0) AS total
    FROM buku_kas.transaksi_kas
    WHERE MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun
";
$resultKredit = $conn->query($queryKredit);
$totalKredit = $resultKredit->fetch_assoc()['total'];

/* Jumlah transaksi */
$queryJumlah = "
    SELECT COUNT(*) AS total
    FROM buku_kas.transaksi_kas
    WHERE MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun
";
$resultJumlah = $conn->query($queryJumlah);
$jumlahTransaksi = $resultJumlah->fetch_assoc()['total'];

/* Saldo awal bulan */
$querySaldoAwal = "
    SELECT saldo
    FROM buku_kas.transaksi_kas
    WHERE tanggal < '$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01'
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";

$resultSaldoAwal = $conn->query($querySaldoAwal);

if ($resultSaldoAwal && $resultSaldoAwal->num_rows > 0) {
    $saldoAwal = $resultSaldoAwal->fetch_assoc()['saldo'];
} else {
    $saldoAwal = 0;
}

/* Saldo akhir bulan */
$querySaldoAkhir = "
    SELECT saldo
    FROM buku_kas.transaksi_kas
    WHERE MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";

$resultSaldoAkhir = $conn->query($querySaldoAkhir);

if ($resultSaldoAkhir && $resultSaldoAkhir->num_rows > 0) {
    $saldoAkhir = $resultSaldoAkhir->fetch_assoc()['saldo'];
} else {
    $saldoAkhir = $saldoAwal;
}

/* Transaksi bulan tersebut */
$queryData = "
    SELECT id, tanggal, keterangan, debit, kredit, saldo
    FROM buku_kas.transaksi_kas
    WHERE MONTH(tanggal) = $bulan
    AND YEAR(tanggal) = $tahun
    ORDER BY tanggal ASC, id ASC
";

$resultData = $conn->query($queryData);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - SIKART</title>

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
            flex-wrap: wrap;
            align-items: end;
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
    margin-bottom: 15px;
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
            min-width: 900px;
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

        .saldo {
            text-align: right;
            white-space: nowrap;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin: 30px 0;
            color: #777;
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
        grid-template-columns: repeat(2, 1fr);
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

        @media (max-width: 700px) {
            .cards {
                grid-template-columns: 1fr;
            }

            .container {
                width: 92%;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <h1>SIKART</h1>
    <p>Sistem Informasi Kas RT — Laporan Keuangan Bulanan</p>
</div>

<div class="container">

    <a href="../dashboard.php" class="btn btn-kembali">
        ← Kembali ke Dashboard
    </a>

    <div class="filter">
        <form method="GET">

            <div>
                <label>Bulan</label>
                <select name="bulan">
                    <?php foreach ($namaBulan as $nomor => $nama): ?>
                        <option value="<?= $nomor ?>"
                            <?= ($bulan == $nomor) ? 'selected' : '' ?>>
                            <?= $nama ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

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

            <button type="submit" class="btn btn-cari">
                Tampilkan Laporan
            </button>

        </form>
    </div>

<div class="cards">

        <div class="card">
            <h3>Saldo Awal Bulan</h3>
            <div class="angka">
                Rp <?= number_format($saldoAwal, 0, ',', '.') ?>
            </div>
        </div>

        <div class="card">
            <h3>Saldo Akhir Bulan</h3>
            <div class="angka">
                Rp <?= number_format($saldoAkhir, 0, ',', '.') ?>
            </div>
        </div>

    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Debit</h3>
            <div class="angka">
                Rp <?= number_format($totalDebit, 0, ',', '.') ?>
            </div>
        </div>

        <div class="card">
            <h3>Total Kredit</h3>
            <div class="angka">
                Rp <?= number_format($totalKredit, 0, ',', '.') ?>
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

<button onclick="window.print()" class="btn btn-cetak">
    🖨️ Cetak Laporan
</button>

        <h2>
            Laporan <?= $namaBulan[$bulan] ?> <?= $tahun ?>
        </h2>

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>

            <tbody>

            <?php
            $no = 1;

            if ($resultData->num_rows > 0):

                while ($row = $resultData->fetch_assoc()):
            ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td>
                        <?= date('d-m-Y', strtotime($row['tanggal'])) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($row['keterangan']) ?>
                    </td>

                    <td class="nominal">
                        Rp <?= number_format($row['debit'], 0, ',', '.') ?>
                    </td>

                    <td class="nominal">
                        Rp <?= number_format($row['kredit'], 0, ',', '.') ?>
                    </td>

                    <td class="saldo">
                        Rp <?= number_format($row['saldo'], 0, ',', '.') ?>
                    </td>

                </tr>

            <?php
                endwhile;

            else:
            ?>

                <tr>
                    <td colspan="6" style="text-align:center;">
                        Tidak ada transaksi pada bulan ini.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div class="footer">
        SIKART — Sistem Informasi Kas RT
    </div>

</div>

</body>
</html>