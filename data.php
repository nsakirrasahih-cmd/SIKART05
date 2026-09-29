<?php
require_once "../config/auth.php";
wajibLogin();

require_once "../config/database.php";

$pesan = "";
$error = "";

/* =========================
   TAMBAH DATA WARGA
   ========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "sekretaris") {
        http_response_code(403);
        die("
            <h2 style='font-family:Arial;'>403 - Akses Ditolak</h2>
            <p style='font-family:Arial;'>
                Hanya Sekretaris yang dapat menambahkan data warga.
            </p>
            <a href='data.php'>Kembali ke Data Warga</a>
        ");
    }

    $no_kk = trim($_POST["no_kk"] ?? "");
    $nik = trim($_POST["nik"] ?? "");
    $nama_lengkap = trim($_POST["nama_lengkap"] ?? "");
    $jenis_kelamin = $_POST["jenis_kelamin"] ?? "";
    $tempat_lahir = trim($_POST["tempat_lahir"] ?? "");
    $tanggal_lahir = $_POST["tanggal_lahir"] ?? "";
    $alamat = trim($_POST["alamat"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $status_warga = $_POST["status_warga"] ?? "Tetap";

    if ($nama_lengkap === "") {
        $error = "Nama lengkap wajib diisi.";
    } else {

        $sql = "
            INSERT INTO buku_kas.warga
            (
                no_kk,
                nik,
                nama_lengkap,
                jenis_kelamin,
                tempat_lahir,
                tanggal_lahir,
                alamat,
                no_hp,
                status_warga
            )
            VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $error = "Query gagal: " . $conn->error;
        } else {

            $stmt->bind_param(
                "sssssssss",
                $no_kk,
                $nik,
                $nama_lengkap,
                $jenis_kelamin,
                $tempat_lahir,
                $tanggal_lahir,
                $alamat,
                $no_hp,
                $status_warga
            );

            if ($stmt->execute()) {
                $pesan = "Data warga berhasil ditambahkan.";
            } else {
                $error = "Data gagal disimpan: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}


/* =========================
   PENCARIAN DATA
   ========================= */

$cari = trim($_GET["cari"] ?? "");

if ($cari !== "") {

    $sqlWarga = "
        SELECT *
        FROM buku_kas.warga
        WHERE nama_lengkap LIKE ?
           OR nik LIKE ?
           OR no_kk LIKE ?
           OR no_hp LIKE ?
        ORDER BY nama_lengkap ASC
    ";

    $stmtWarga = $conn->prepare($sqlWarga);

    $keyword = "%" . $cari . "%";

    $stmtWarga->bind_param(
        "ssss",
        $keyword,
        $keyword,
        $keyword,
        $keyword
    );

    $stmtWarga->execute();

    $resultWarga = $stmtWarga->get_result();

} else {

    $sqlWarga = "
        SELECT *
        FROM buku_kas.warga
        ORDER BY nama_lengkap ASC
    ";

    $resultWarga = $conn->query($sqlWarga);
}


/* =========================
   HITUNG DATA
   ========================= */

$totalWarga = $conn->query("
    SELECT COUNT(*) AS total
    FROM buku_kas.warga
    WHERE status_aktif = 'Aktif'
")->fetch_assoc()["total"];

?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Data Warga - SIKART</title>

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

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.08);
}

.card h2 {
    margin-top: 0;
}

.jumlah {
    font-size: 28px;
    font-weight: bold;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    font-weight: bold;
    margin-bottom: 6px;
}

input,
select,
textarea {
    padding: 11px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 14px;
}

textarea {
    min-height: 80px;
    resize: vertical;
}

.btn {
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 10px;
}

.btn-tambah {
    background: #16a34a;
    color: white;
}

.btn-tambah:hover {
    background: #15803d;
}

.btn-cari {
    background: #2563eb;
    color: white;
}

.btn-reset {
    background: #64748b;
    color: white;
    text-decoration: none;
    display: inline-block;
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
    padding: 11px;
    border-bottom: 1px solid #e2e8f0;
    text-align: left;
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
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.aktif {
    background: #dcfce7;
    color: #166534;
}

.tidak-aktif {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 700px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
    }

    .container {
        width: 94%;
    }

}

</style>

</head>

<body>

<div class="header">

    <h1>👥 Data Warga</h1>

    <p>SIKART — Sistem Informasi Kas RT</p>

</div>


<div class="container">


    <!-- MENU -->

    <div class="menu">

        <a href="../dashboard.php">
            🏠 Dashboard
        </a>

        <a href="../kas/transaksi.php">
            📋 Riwayat Transaksi
        </a>

        <a href="../laporan/bulanan.php">
            📅 Laporan Bulanan
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


    <!-- TOTAL WARGA -->

    <div class="card">

        <h2>Jumlah Warga Aktif</h2>

        <div class="jumlah">
            <?= number_format($totalWarga, 0, ',', '.') ?>
            orang
        </div>

    </div>

<?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "sekretaris"): ?>

    <!-- FORM TAMBAH -->

    <div class="card">

        <h2>➕ Tambah Data Warga</h2>

        <form method="POST">

            <div class="form-grid">

                <div class="form-group">

                    <label>No. KK</label>

                    <input
                        type="text"
                        name="no_kk"
                        maxlength="20"
                        placeholder="Nomor KK"
                    >

                </div>


                <div class="form-group">

                    <label>NIK</label>

                    <input
                        type="text"
                        name="nik"
                        maxlength="20"
                        placeholder="NIK"
                    >

                </div>


                <div class="form-group full">

                    <label>Nama Lengkap *</label>

                    <input
                        type="text"
                        name="nama_lengkap"
                        maxlength="100"
                        placeholder="Nama lengkap warga"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>Jenis Kelamin</label>

                    <select name="jenis_kelamin">

                        <option value="">-- Pilih --</option>

                        <option value="L">
                            Laki-laki
                        </option>

                        <option value="P">
                            Perempuan
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Status Warga</label>

                    <select name="status_warga">

                        <option value="Tetap">
                            Tetap
                        </option>

                        <option value="Kontrak">
                            Kontrak
                        </option>

                        <option value="Pendatang">
                            Pendatang
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Tempat Lahir</label>

                    <input
                        type="text"
                        name="tempat_lahir"
                        maxlength="100"
                    >

                </div>


                <div class="form-group">

                    <label>Tanggal Lahir</label>

                    <input
                        type="date"
                        name="tanggal_lahir"
                    >

                </div>


                <div class="form-group">

                    <label>No. HP</label>

                    <input
                        type="text"
                        name="no_hp"
                        maxlength="20"
                        placeholder="08xxxxxxxxxx"
                    >

                </div>


                <div class="form-group full">

                    <label>Alamat</label>

                    <textarea
                        name="alamat"
                        placeholder="Alamat warga"
                    ></textarea>

                </div>

            </div>


            <button
                type="submit"
                class="btn btn-tambah"
            >
                💾 Simpan Data Warga
            </button>

        </form>

    </div>

<?php endif; ?>

    <!-- PENCARIAN -->

    <div class="card">

        <h2>🔎 Cari Warga</h2>

        <form method="GET">

            <input
                type="text"
                name="cari"
                value="<?= htmlspecialchars($cari) ?>"
                placeholder="Cari nama, NIK, No. KK, atau No. HP"
                style="width:70%;"
            >

            <button
                type="submit"
                class="btn btn-cari"
            >
                🔎 Cari
            </button>

            <a
                href="data.php"
                class="btn btn-reset"
            >
                Reset
            </a>

        </form>

    </div>


    <!-- TABEL DATA -->

    <div class="card">

        <h2>📋 Daftar Warga</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Nama Lengkap</th>

                        <th>NIK</th>

                        <th>No. KK</th>

                        <th>JK</th>

                        <th>Tempat Lahir</th>

                        <th>Tanggal Lahir</th>

                        <th>No. HP</th>

                        <th>Status Warga</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                <?php

                if ($resultWarga && $resultWarga->num_rows > 0):

                    $no = 1;

                    while ($row = $resultWarga->fetch_assoc()):

                ?>

                    <tr>

                        <td>
                            <?= $no++ ?>
                        </td>

                        <td>
                            <strong>
                                <?= htmlspecialchars($row["nama_lengkap"]) ?>
                            </strong>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["nik"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["no_kk"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["jenis_kelamin"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["tempat_lahir"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["tanggal_lahir"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["no_hp"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($row["status_warga"]) ?>
                        </td>

                        <td>

                            <?php if ($row["status_aktif"] === "Aktif"): ?>

                                <span class="badge aktif">
                                    Aktif
                                </span>

                            <?php else: ?>

                                <span class="badge tidak-aktif">
                                    Tidak Aktif
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php

                    endwhile;

                else:

                ?>

                    <tr>

                        <td
                            colspan="10"
                            style="text-align:center; color:#64748b;"
                        >
                            Belum ada data warga.
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