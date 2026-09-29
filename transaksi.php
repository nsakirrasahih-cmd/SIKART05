<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

/* =========================
   FILTER
========================= */

$cari = isset($_GET["cari"]) ? trim($_GET["cari"]) : "";
$tahun = isset($_GET["tahun"]) ? $_GET["tahun"] : "";
$bulan = isset($_GET["bulan"]) ? $_GET["bulan"] : "";

/* =========================
   QUERY DASAR
========================= */

$sql = "
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
    WHERE 1=1
";

$params = [];
$types = "";

/* Pencarian */
if ($cari !== "") {
    $sql .= " AND keterangan LIKE ?";
    $params[] = "%" . $cari . "%";
    $types .= "s";
}

/* Filter tahun */
if ($tahun !== "") {
    $sql .= " AND YEAR(tanggal) = ?";
    $params[] = $tahun;
    $types .= "i";
}

/* Filter bulan */
if ($bulan !== "") {
    $sql .= " AND MONTH(tanggal) = ?";
    $params[] = $bulan;
    $types .= "i";
}

$sql .= " ORDER BY tanggal ASC, id ASC";

/* =========================
   JALANKAN QUERY
========================= */

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query gagal: " . $conn->error);
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

/* =========================
   TOTAL DATA
========================= */

$total_data = $result->num_rows;

/* =========================
   FORMAT RUPIAH
========================= */

function rupiah($angka)
{
    if ($angka < 0) {
        return "-Rp " . number_format(abs($angka), 0, ",", ".");
    }

    return "Rp " . number_format($angka, 0, ",", ".");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Riwayat Transaksi - SIKART</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 20px;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f8;
    color: #222;
}

.container {
    max-width: 1400px;
    margin: auto;
}

.header {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.header h1 {
    margin: 0 0 8px;
    font-size: 30px;
}

.header p {
    margin: 0;
    color: #666;
}

/* Tombol dashboard */

.btn-dashboard {
    display: inline-block;
    margin-bottom: 15px;
    padding: 10px 16px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 7px;
}

.btn-dashboard:hover {
    background: #1d4ed8;
}

/* Filter */

.filter-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.filter-box h3 {
    margin-top: 0;
}

.filter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-form input,
.filter-form select {
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 7px;
    font-size: 14px;
}

.filter-form input {
    min-width: 250px;
}

.btn-search {
    background: #16a34a;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
}

.btn-reset {
    background: #6b7280;
    color: white;
    text-decoration: none;
    padding: 11px 18px;
    border-radius: 7px;
}

.btn-cetak {
    background: #16a34a;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
    margin-bottom: 15px;
}

/* Info */

.info {
    background: #dbeafe;
    padding: 13px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
}

/* Tabel */

.table-box {
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

thead {
    background: #111827;
    color: white;
}

th,
td {
    padding: 11px 10px;
    border-bottom: 1px solid #ddd;
}

th {
    text-align: center;
}

td {
    vertical-align: top;
}

tbody tr:hover {
    background: #f3f4f6;
}

.no {
    text-align: center;
}

.tanggal {
    white-space: nowrap;
}

.angka {
    text-align: right;
    white-space: nowrap;
}

.saldo {
    text-align: right;
    white-space: nowrap;
    font-weight: bold;
}

.positif {
    color: #15803d;
}

.negatif {
    color: #dc2626;
}

@media print {

    body {
        background: white;
        padding: 0;
    }

    .btn-dashboard,
    .filter-box,
    .btn-cetak {
        display: none !important;
    }

    .header {
        box-shadow: none;
        padding: 10px 0;
    }

    .table-box {
        box-shadow: none;
        padding: 0;
    }

    table {
        min-width: 0;
    }

    thead {
        background: #ddd !important;
        color: black !important;
    }

    th,
    td {
        border: 1px solid #999;
    }
}

/* Mobile */

@media (max-width: 700px) {

    body {
        padding: 10px;
    }

    .header h1 {
        font-size: 24px;
    }

    .filter-form {
        flex-direction: column;
    }

    .filter-form input,
    .filter-form select,
    .btn-search,
    .btn-reset {
        width: 100%;
    }

}

</style>

</head>

<body>

<div class="container">

    <a href="../dashboard.php" class="btn-dashboard">
        ← Kembali ke Dashboard
    </a>

    <div class="header">

        <h1>Riwayat Semua Transaksi</h1>

        <p>
            SIKART — Sistem Informasi Kas RT
        </p>

    </div>


    <!-- FILTER -->

    <div class="filter-box">

        <h3>🔎 Cari dan Filter Transaksi</h3>

        <form method="GET" class="filter-form">

            <input
                type="text"
                name="cari"
                placeholder="Cari keterangan transaksi..."
                value="<?php echo htmlspecialchars($cari); ?>"
            >

            <select name="tahun">

                <option value="">Semua Tahun</option>

                <?php
                for ($t = 2023; $t <= 2025; $t++) {
                    $selected = ($tahun == $t) ? "selected" : "";
                    echo "<option value='$t' $selected>$t</option>";
                }
                ?>

            </select>


            <select name="bulan">

                <option value="">Semua Bulan</option>

                <?php

                $nama_bulan = [
                    1 => "Januari",
                    2 => "Februari",
                    3 => "Maret",
                    4 => "April",
                    5 => "Mei",
                    6 => "Juni",
                    7 => "Juli",
                    8 => "Agustus",
                    9 => "September",
                    10 => "Oktober",
                    11 => "November",
                    12 => "Desember"
                ];

                foreach ($nama_bulan as $nomor => $nama) {

                    $selected = ($bulan == $nomor)
                        ? "selected"
                        : "";

                    echo "<option value='$nomor' $selected>$nama</option>";
                }

                ?>

            </select>


            <button type="submit" class="btn-search">
                🔎 Cari
            </button>


            <a href="transaksi.php" class="btn-reset">
                Reset
            </a>

        </form>

    </div>


    <div class="info">

        Menampilkan
        <strong><?php echo $total_data; ?></strong>
        transaksi dari database
        <strong>buku_kas</strong>,
        tabel
        <strong>transaksi_kas</strong>.

    </div>


    <!-- TABEL -->

    <div class="table-box">

<button onclick="window.print()" class="btn-cetak">
        🖨️ Cetak Riwayat Transaksi
    </button>

        <table>

            <thead>

                <tr>

                    <th>No.</th>

                    <th>Tanggal</th>

                    <th>Keterangan</th>

                    <th>Debit</th>

                    <th>Kredit</th>

                    <th>Saldo</th>

		<th>Bukti</th>

		<th>aksi</th>

                </tr>

            </thead>


            <tbody>

            <?php

            $no = 1;

            if ($result->num_rows > 0):

                while ($row = $result->fetch_assoc()):

                    $saldo_class =
                        ($row["saldo"] < 0)
                        ? "negatif"
                        : "positif";

            ?>

                <tr>

                    <td class="no">
                        <?php echo $no++; ?>
                    </td>

                    <td class="tanggal">
                        <?php
                        echo date(
                            "d-m-Y",
                            strtotime($row["tanggal"])
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $row["keterangan"]
                        );
                        ?>
                    </td>

                    <td class="angka">
                        <?php
                        echo rupiah($row["debit"]);
                        ?>
                    </td>

                    <td class="angka">
                        <?php
                        echo rupiah($row["kredit"]);
                        ?>
                    </td>

                    <td class="saldo <?php echo $saldo_class; ?>">
                        <?php
                        echo rupiah($row["saldo"]);
                        ?>
                    </td>
			<td>
    <?php if (!empty($row["bukti_id"])): ?>
        <a href="<?php echo htmlspecialchars($row["file_path"]); ?>" target="_blank">
            📎 Lihat Bukti
        </a>
    <?php else: ?>
        <span style="color:#999;">Belum ada</span>
    <?php endif; ?>
</td>

<td>
    <?php if (
        isset($_SESSION["role"]) &&
        in_array($_SESSION["role"], ["bendahara", "sekretaris"])
    ): ?>

        <a href="edit_transaksi.php?id=<?php echo $row["id"]; ?>"
           style="text-decoration:none; font-weight:bold;">
            ✏️ Edit
        </a>

        &nbsp; | &nbsp;

    <?php endif; ?>

    <a href="riwayat.php"
       style="text-decoration:none; font-weight:bold;">
        📜 Riwayat
    </a>
</td>
                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td colspan="7"
                        style="text-align:center;padding:30px;">

                        Tidak ada transaksi yang ditemukan.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>