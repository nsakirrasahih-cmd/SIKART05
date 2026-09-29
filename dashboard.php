<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| SIKART - DASHBOARD
|--------------------------------------------------------------------------
| Sumber data:
| Database : buku_kas
| Tabel    : transaksi_kas
|--------------------------------------------------------------------------
*/

/* =========================
   TOTAL PEMASUKAN / DEBIT
   ========================= */

$queryPemasukan = "
    SELECT COALESCE(SUM(debit), 0) AS total
    FROM buku_kas.transaksi_kas
";

$resultPemasukan = $conn->query($queryPemasukan);

if (!$resultPemasukan) {
    die("Gagal mengambil data pemasukan: " . $conn->error);
}

$dataPemasukan = $resultPemasukan->fetch_assoc();
$totalPemasukan = (float) $dataPemasukan["total"];


/* =========================
   TOTAL PENGELUARAN / KREDIT
   ========================= */

$queryPengeluaran = "
    SELECT COALESCE(SUM(kredit), 0) AS total
    FROM buku_kas.transaksi_kas
";

$resultPengeluaran = $conn->query($queryPengeluaran);

if (!$resultPengeluaran) {
    die("Gagal mengambil data pengeluaran: " . $conn->error);
}

$dataPengeluaran = $resultPengeluaran->fetch_assoc();
$totalPengeluaran = (float) $dataPengeluaran["total"];


/* =========================
   JUMLAH TRANSAKSI
   ========================= */

$queryJumlah = "
    SELECT COUNT(*) AS jumlah
    FROM buku_kas.transaksi_kas
";

$resultJumlah = $conn->query($queryJumlah);

if (!$resultJumlah) {
    die("Gagal menghitung transaksi: " . $conn->error);
}

$dataJumlah = $resultJumlah->fetch_assoc();
$jumlahTransaksi = (int) $dataJumlah["jumlah"];


/* =========================
   SALDO TERAKHIR
   ========================= */

$querySaldo = "
    SELECT saldo
    FROM buku_kas.transaksi_kas
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";

$resultSaldo = $conn->query($querySaldo);

if (!$resultSaldo) {
    die("Gagal mengambil saldo: " . $conn->error);
}

if ($resultSaldo->num_rows > 0) {
    $dataSaldo = $resultSaldo->fetch_assoc();
    $saldoKas = (float) $dataSaldo["saldo"];
} else {
    $saldoKas = 0;
}


/* =========================
   TRANSAKSI TERBARU
   ========================= */

$queryTerbaru = "
        SELECT 
        tk.id,
        tk.tanggal,
        tk.keterangan,
        tk.debit,
        tk.kredit,
        tk.saldo,
        bt.id AS bukti_id,
        bt.nama_file,
        bt.file_path
    FROM buku_kas.transaksi_kas tk
    LEFT JOIN buku_kas.bukti_transaksi bt
        ON tk.id = bt.transaksi_id
    ORDER BY tk.tanggal DESC, tk.id DESC
    LIMIT 10
";

$resultTerbaru = $conn->query($queryTerbaru);

if (!$resultTerbaru) {
    die("Gagal mengambil transaksi terbaru: " . $conn->error);
}


/* =========================
   FORMAT RUPIAH
   ========================= */

function rupiah($angka)
{
    return "Rp " . number_format(
        $angka,
        0,
        ",",
        "."
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Dashboard - SIKART</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .topbar {
            background: #1e293b;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
        }

        .logout {
            background: #C44545;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 14px;
        }

        .logout:hover {
            background: #C44545;
        }

        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h1 {
            margin-bottom: 8px;
            font-size: 30px;
        }

        .welcome p {
            margin: 0;
            color: #64748b;
        }

        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }

        .menu a {
            text-decoration: none;
            background: white;
            color: #1e293b;
            padding: 13px 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            font-weight: 600;
        }

        .menu a:hover {
            background: #e2e8f0;
        }

        .menu .riwayat {
            background: #2563eb;
            color: white;
        }

        .menu .riwayat:hover {
            background: #5B7E3C;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .card-title {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 25px;
            font-weight: bold;
            color: #0f172a;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .lihat-semua {
    text-decoration: none;
    background: #C44545;
    color: white;
    padding: 9px 15px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
}

.lihat-semua:hover {
    background: #A93636;
}

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th {
            background: #1e293b;
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 11px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .angka {
            text-align: right;
            white-space: nowrap;
        }

        .masuk {
            color: #15803d;
            font-weight: bold;
        }

        .keluar {
            color: #C44545;
            font-weight: bold;
        }

        .kosong {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        .footer-info {
            margin-top: 20px;
            padding: 15px;
            background: #e0f2fe;
            color: #075985;
            border-radius: 8px;
            font-size: 14px;
        }

        @media (max-width: 900px) {

            .cards {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .cards {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 15px;
            }

            .container {
                margin-top: 20px;
            }

            .welcome h1 {
                font-size: 24px;
            }

        }

</style>


<!-- =====================================================
     SIKART - TEMA HIJAU BARU
     ===================================================== -->

<style>
/* =====================================================
   SIKART - TEMA HIJAU
   Palette:
   #5B7E3C = hijau utama
   #A2CB8B = hijau muda
   #E8F5BD = background
   #C44545 = merah
   ===================================================== */

:root {
    --hijau-utama: #5B7E3C;
    --hijau-muda: #A2CB8B;
    --background: #E8F5BD;
    --merah: #C44545;
    --putih: #ffffff;
    --teks: #26321f;
}

body {
    background: var(--background) !important;
    color: var(--teks) !important;
    font-family: Arial, Helvetica, sans-serif !important;
}

header,
.navbar,
.topbar {
    background: var(--hijau-utama) !important;
}

header h1,
header .logo,
.logo {
    color: #ffffff !important;
}

nav a,
.nav a,
.menu a,
.menu-item,
button {
    background: var(--hijau-muda) !important;
    color: #1f2d19 !important;
    border: none !important;
    border-radius: 10px !important;
    font-weight: bold !important;
}

nav a:hover,
.nav a:hover,
.menu a:hover,
.menu-item:hover,
button:hover {
    background: var(--hijau-utama) !important;
    color: #ffffff !important;
}

.container,
.main,
.content {
    color: var(--teks);
}

.card,
.stat-card,
.summary-card {
    background: rgba(255,255,255,0.88) !important;
    border-radius: 14px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(91,126,60,0.15) !important;
}

.card h2,
.card h3,
.stat-card h2,
.stat-card h3,
.summary-card h2,
.summary-card h3 {
    color: var(--hijau-utama) !important;
}

table {
    border-collapse: collapse !important;
    width: 100% !important;
    background: rgba(255,255,255,0.75) !important;
    border-radius: 10px !important;
    overflow: hidden !important;
}

table thead th,
table th {
    background: var(--hijau-utama) !important;
    color: #ffffff !important;
    font-weight: bold !important;
}

table td {
    color: var(--teks) !important;
    border-bottom: 1px solid #d4e6bd !important;
}

table tbody tr:hover {
    background: #f4f9df !important;
}

.pengeluaran,
.text-danger,
.danger,
.error {
    color: var(--merah) !important;
}

.btn-danger,
button.danger,
a.danger {
    background: var(--merah) !important;
    color: #ffffff !important;
}

a {
    color: var(--hijau-utama);
}

.btn,
.btn-primary {
    background: var(--hijau-utama) !important;
    color: #ffffff !important;
    border: none !important;
    border-radius: 9px !important;
    font-weight: bold !important;
}

@media (max-width: 768px) {

    body {
        font-size: 14px !important;
        overflow-x: hidden;
    }

    .topbar {
        padding: 15px 18px !important;
        display: flex !important;
        align-items: center !important;
    }

    .brand {
        font-size: 20px !important;
    }

    .logout {
        padding: 9px 13px !important;
        font-size: 13px !important;
    }

    .container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 20px auto !important;
        padding: 0 12px !important;
    }

    .welcome {
        margin-bottom: 18px !important;
    }

    .welcome h1 {
        font-size: 23px !important;
        line-height: 1.3 !important;
    }

    .welcome p {
        font-size: 14px !important;
    }

    /* MENU */
    .menu {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 8px !important;
        margin-bottom: 20px !important;
    }

    .menu a {
        width: 100% !important;
        padding: 12px 8px !important;
        text-align: center !important;
        font-size: 13px !important;
        border-radius: 9px !important;
    }

    /* KARTU */
    .cards {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 20px !important;
    }

    .card {
        padding: 15px !important;
        border-radius: 12px !important;
    }

    .card-title {
        font-size: 12px !important;
    }

    .card-value {
        font-size: 18px !important;
        word-break: break-word;
    }

    /* TRANSAKSI */
    .section {
        padding: 15px !important;
        border-radius: 12px !important;
    }

    .section-header {
        align-items: center !important;
        margin-bottom: 15px !important;
    }

    .section-header h2 {
        font-size: 18px !important;
    }

    .lihat-semua {
        padding: 8px 11px !important;
        font-size: 12px !important;
        white-space: nowrap !important;
    }

    .table-wrapper {
        width: 100% !important;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }

    table {
        min-width: 800px !important;
        font-size: 12px !important;
    }

    th,
    td {
        padding: 9px !important;
    }

    .footer-info {
        font-size: 12px !important;
        padding: 12px !important;
    }
}

    </style>

</head>

<body>


<!-- =========================
     TOPBAR
     ========================= -->

<div class="topbar">

    <div class="brand">
        SIKART
    </div>

    <a class="logout"
       href="logout.php">
        Keluar
    </a>

</div>


<!-- =========================
     CONTENT
     ========================= -->

<div class="container">


    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Selamat datang,
            <?php
            echo htmlspecialchars(
                $_SESSION["nama_lengkap"] ?? "Pengguna"
            );
            ?>
        </h1>

        <p>
            Dashboard keuangan RT
        </p>

    </div>


    <!-- =========================
         MENU
         ========================= -->

    <div class="menu">

        <a href="dashboard.php">
            🏠 Dashboard
        </a>

        <a href="kas/pemasukan.php">
            💰 Pemasukan
        </a>

        <a href="kas/pengeluaran.php">
            💸 Pengeluaran
        </a>

        <a href="kas/transaksi.php"
           class="riwayat">
            📋 Riwayat Transaksi
        </a>
	<a href="laporan/bulanan.php" class="menu-button">
    	📅 Laporan Bulanan
	</a>

	<a href="laporan/tahunan.php" class="menu-button">
    	📊 Laporan Tahunan
	</a>

<a href="kas/bukti.php" class="riwayat">
    📎 Bukti Transaksi
</a>

<a href="kas/riwayat.php" class="riwayat">
    🕒 Riwayat Perubahan
</a>
        <a href="warga/data.php">👥 Data Warga</a>

	<a href="usulan/index.php">
    	💡 Usulan Warga
	</a>

    </div>


    <!-- =========================
         RINGKASAN KEUANGAN
         ========================= -->

    <div class="cards">


        <!-- SALDO -->

        <div class="card">

            <div class="card-title">
                Saldo Kas
            </div>

            <div class="card-value">
                <?php
                echo rupiah($saldoKas);
                ?>
            </div>

        </div>


        <!-- PEMASUKAN -->

        <div class="card">

            <div class="card-title">
                Total Pemasukan
            </div>

            <div class="card-value masuk">
                <?php
                echo rupiah($totalPemasukan);
                ?>
            </div>

        </div>


        <!-- PENGELUARAN -->

        <div class="card">

            <div class="card-title">
                Total Pengeluaran
            </div>

            <div class="card-value keluar">
                <?php
                echo rupiah($totalPengeluaran);
                ?>
            </div>

        </div>


        <!-- JUMLAH TRANSAKSI -->

        <div class="card">

            <div class="card-title">
                Jumlah Transaksi
            </div>

            <div class="card-value">
                <?php
                echo $jumlahTransaksi;
                ?>
            </div>

        </div>


    </div>


    <!-- =========================
         TRANSAKSI TERBARU
         ========================= -->

    <div class="section">


        <div class="section-header">

            <h2>
                Transaksi Terbaru
            </h2>

            <a class="lihat-semua"
               href="kas/transaksi.php">
                Lihat Semua
            </a>

        </div>


        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>
                            Tanggal
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th>
                            Jenis
                        </th>

                        <th>
                            Jumlah
                        </th>

                        <th>
                            Saldo
                        </th>
			
                        <th>
                            Bukti
                        </th>

                    </tr>

                </thead>


                <tbody>

                    <?php

                    if ($resultTerbaru->num_rows > 0) {

                        while ($row = $resultTerbaru->fetch_assoc()) {

                            $debit = (float) $row["debit"];
                            $kredit = (float) $row["kredit"];

                            echo "<tr>";

                            echo "<td>";
                            echo htmlspecialchars($row["tanggal"]);
                            echo "</td>";

                            echo "<td>";
                            echo htmlspecialchars($row["keterangan"]);
                            echo "</td>";


                            /*
                             * Menentukan jenis transaksi
                             */

                            if ($debit > 0) {

                                echo "<td class='masuk'>";
                                echo "Pemasukan";
                                echo "</td>";

                                echo "<td class='angka masuk'>";
                                echo rupiah($debit);
                                echo "</td>";

                            } elseif ($kredit > 0) {

                                echo "<td class='keluar'>";
                                echo "Pengeluaran";
                                echo "</td>";

                                echo "<td class='angka keluar'>";
                                echo rupiah($kredit);
                                echo "</td>";

                            } else {

                                echo "<td>";
                                echo "Saldo";
                                echo "</td>";

                                echo "<td class='angka'>";
                                echo rupiah(0);
                                echo "</td>";

                            }


                            echo "<td class='angka'>";
                            echo rupiah(
                                (float) $row["saldo"]
                            );
                            echo "</td>";

/* =========================
   BUKTI TRANSAKSI
   ========================= */

echo "<td>";

if (!empty($row["bukti_id"])) {

    echo "<a href='" . htmlspecialchars($row["file_path"]) . "' 
            target='_blank' 
            style='text-decoration:none; font-weight:bold;'>
            📎 Lihat
          </a>";

} else {

    echo "<span style='color:#94a3b8;'>";
    echo "Belum ada";
    echo "</span>";

}

echo "</td>";

echo "</tr>";

                            echo "</tr>";

                        }

                    } else {

                        echo "<tr>";

                        echo "<td colspan='5' class='kosong'>";
                        echo "Belum ada transaksi kas.";
                        echo "</td>";

                        echo "</tr>";

                    }

                    ?>

                </tbody>

            </table>

        </div>


        <div class="footer-info">

            <b>Informasi:</b>
            Dashboard mengambil data langsung dari
            <b>buku_kas.transaksi_kas</b>.
            Data transaksi tidak diubah oleh halaman dashboard.

        </div>


    </div>


</div>

</body>

</html>