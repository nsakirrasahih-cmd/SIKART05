<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/database.php";

$pesan = "";
$error = "";

/* =========================
   TAMBAH USULAN
   ========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama_pengusul = trim($_POST["nama_pengusul"] ?? "");
    $judul = trim($_POST["judul"] ?? "");
    $isi_usulan = trim($_POST["isi_usulan"] ?? "");
    $tanggal_usulan = $_POST["tanggal_usulan"] ?? "";

    if ($nama_pengusul === "" || $judul === "" || $isi_usulan === "" || $tanggal_usulan === "") {

        $error = "Semua data usulan wajib diisi.";

    } else {

        $sql = "
            INSERT INTO buku_kas.usulan_warga
            (
                nama_pengusul,
                judul,
                isi_usulan,
                tanggal_usulan
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error = "Query gagal: " . $conn->error;

        } else {

            $stmt->bind_param(
                "ssss",
                $nama_pengusul,
                $judul,
                $isi_usulan,
                $tanggal_usulan
            );

            if ($stmt->execute()) {

                $pesan = "Usulan warga berhasil diajukan.";

            } else {

                $error = "Usulan gagal disimpan: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}


/* =========================
   AMBIL SEMUA USULAN
   ========================= */

$resultUsulan = $conn->query("
    SELECT *
    FROM buku_kas.usulan_warga
    ORDER BY tanggal_usulan DESC, id DESC
");


/* =========================
   JUMLAH USULAN
   ========================= */

$totalUsulan = $conn->query("
    SELECT COUNT(*) AS total
    FROM buku_kas.usulan_warga
")->fetch_assoc()["total"];


$totalDiajukan = $conn->query("
    SELECT COUNT(*) AS total
    FROM buku_kas.usulan_warga
    WHERE status = 'Diajukan'
")->fetch_assoc()["total"];


$totalDiproses = $conn->query("
    SELECT COUNT(*) AS total
    FROM buku_kas.usulan_warga
    WHERE status = 'Diproses'
")->fetch_assoc()["total"];


$totalSelesai = $conn->query("
    SELECT COUNT(*) AS total
    FROM buku_kas.usulan_warga
    WHERE status = 'Selesai'
")->fetch_assoc()["total"];

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Usulan Warga - SIKART</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
}

.header {
    background: #0f172a;
    color: white;
    padding: 25px;
}

.header h1 {
    margin: 0 0 8px 0;
}

.header p {
    margin: 0;
    color: #cbd5e1;
}

.container {
    width: 95%;
    max-width: 1400px;
    margin: 25px auto;
}

.menu {
    margin-bottom: 20px;
}

.menu a {
    display: inline-block;
    background: white;
    color: #0f172a;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 8px;
    margin-right: 8px;
    margin-bottom: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.menu a:hover {
    background: #e2e8f0;
}

.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.cards .card {
    margin-bottom: 0;
}

.card h2 {
    margin-top: 0;
}

.card h3 {
    margin-top: 0;
    color: #475569;
}

.angka {
    font-size: 28px;
    font-weight: bold;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

input,
textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

.btn {
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
}

.btn-tambah {
    background: #16a34a;
    color: white;
}

.btn-tambah:hover {
    background: #15803d;
}

.pesan {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1100px;
}

th,
td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
    vertical-align: top;
}

th {
    background: #0f172a;
    color: white;
}

tr:hover {
    background: #f8fafc;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.diajukan {
    background: #dbeafe;
    color: #1d4ed8;
}

.diproses {
    background: #fef3c7;
    color: #92400e;
}

.diterima {
    background: #dcfce7;
    color: #166534;
}

.ditolak {
    background: #fee2e2;
    color: #991b1b;
}

.selesai {
    background: #e0e7ff;
    color: #3730a3;
}

@media (max-width: 800px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 500px) {

    .cards {
        grid-template-columns: 1fr;
    }

    .container {
        width: 94%;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>💡 Usulan Warga</h1>

    <p>
        SIKART — Penyampaian aspirasi dan usulan warga
    </p>

</div>


<div class="container">


    <!-- MENU -->

    <div class="menu">

        <a href="../dashboard.php">
            🏠 Dashboard
        </a>

        <a href="../warga/data.php">
            👥 Data Warga
        </a>

        <a href="../kas/transaksi.php">
            📋 Riwayat Transaksi
        </a>

    </div>


    <!-- PESAN -->

    <?php if ($pesan !== ""): ?>

        <div class="pesan">
            <?= htmlspecialchars($pesan) ?>
        </div>

    <?php endif; ?>


    <?php if ($error !== ""): ?>

        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- RINGKASAN -->

    <div class="cards">

        <div class="card">

            <h3>Total Usulan</h3>

            <div class="angka">
                <?= number_format($totalUsulan, 0, ',', '.') ?>
            </div>

        </div>


        <div class="card">

            <h3>Diajukan</h3>

            <div class="angka">
                <?= number_format($totalDiajukan, 0, ',', '.') ?>
            </div>

        </div>


        <div class="card">

            <h3>Diproses</h3>

            <div class="angka">
                <?= number_format($totalDiproses, 0, ',', '.') ?>
            </div>

        </div>


        <div class="card">

            <h3>Selesai</h3>

            <div class="angka">
                <?= number_format($totalSelesai, 0, ',', '.') ?>
            </div>

        </div>

    </div>


    <!-- FORM USULAN -->

    <div class="card">

        <h2>➕ Ajukan Usulan</h2>

        <form method="POST">

            <div class="form-group">

                <label>Nama Pengusul *</label>

                <input
                    type="text"
                    name="nama_pengusul"
                    maxlength="100"
                    placeholder="Nama warga"
                    required
                >

            </div>


            <div class="form-group">

                <label>Judul Usulan *</label>

                <input
                    type="text"
                    name="judul"
                    maxlength="150"
                    placeholder="Contoh: Perbaikan lampu jalan"
                    required
                >

            </div>


            <div class="form-group">

                <label>Isi Usulan *</label>

                <textarea
                    name="isi_usulan"
                    placeholder="Jelaskan usulan yang ingin disampaikan..."
                    required
                ></textarea>

            </div>


            <div class="form-group">

                <label>Tanggal Usulan *</label>

                <input
                    type="date"
                    name="tanggal_usulan"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-tambah"
            >
                📤 Kirim Usulan
            </button>

        </form>

    </div>


    <!-- DAFTAR USULAN -->

    <div class="card">

        <h2>📋 Daftar Usulan Warga</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Pengusul</th>
                        <th>Judul</th>
                        <th>Isi Usulan</th>
                        <th>Status</th>
                        <th>Tanggapan RT</th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($resultUsulan && $resultUsulan->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $resultUsulan->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= $no++ ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["tanggal_usulan"]) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row["nama_pengusul"]) ?>
                                </strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($row["judul"]) ?>
                            </td>

                            <td>
                                <?= nl2br(htmlspecialchars($row["isi_usulan"])) ?>
                            </td>

                            <td>

                                <?php

                                $kelasStatus = strtolower($row["status"]);

                                ?>

                                <span class="badge <?= htmlspecialchars($kelasStatus) ?>">

                                    <?= htmlspecialchars($row["status"]) ?>

                                </span>

                            </td>

                            <td>

                                <?php if (!empty($row["tanggapan"])): ?>

                                    <?= nl2br(htmlspecialchars($row["tanggapan"])) ?>

                                    <?php if (!empty($row["ditanggapi_oleh"])): ?>

                                        <br><br>

                                        <small>
                                            Ditanggapi oleh:
                                            <strong>
                                                <?= htmlspecialchars($row["ditanggapi_oleh"]) ?>
                                            </strong>
                                        </small>

                                    <?php endif; ?>

                                <?php else: ?>

                                    <span style="color:#94a3b8;">
                                        Belum ada tanggapan
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td
                            colspan="7"
                            style="text-align:center; color:#64748b;"
                        >
                            Belum ada usulan warga.
                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


</div>

</body>

</html>