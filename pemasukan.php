<?php
require_once "../config/auth.php";

/*
|--------------------------------------------------------------------------
| CEK LOGIN & ROLE
|--------------------------------------------------------------------------
| Bendahara  : bisa melihat + menambah pemasukan
| Ketua RT   : hanya bisa melihat pemasukan
|--------------------------------------------------------------------------
*/

wajibRole(["bendahara", "ketua_rt"]);

require_once "../config/database.php";

$pesan = "";
$error = "";

/*
|--------------------------------------------------------------------------
| PROSES TAMBAH PEMASUKAN
|--------------------------------------------------------------------------
| Hanya bendahara yang boleh menjalankan proses POST.
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "bendahara") {

        $error = "Anda tidak memiliki izin untuk menambah pemasukan.";

    } else {

        $tanggal = $_POST["tanggal"] ?? "";
        $keterangan = trim($_POST["keterangan"] ?? "");
        $jumlah = $_POST["jumlah"] ?? "";
        $kategori = trim($_POST["kategori"] ?? "");

        if (
            $tanggal === "" ||
            $keterangan === "" ||
            $jumlah === "" ||
            $kategori === ""
        ) {

            $error = "Semua data wajib diisi.";

        } elseif (!is_numeric($jumlah) || $jumlah <= 0) {

            $error = "Jumlah harus berupa angka lebih dari 0.";

        } else {

            $jumlah = (float) $jumlah;
            $jenis = "masuk";

            $stmt = $conn->prepare("
                INSERT INTO transaksi
                (tanggal, keterangan, jenis, jumlah, kategori)
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "sssds",
                $tanggal,
                $keterangan,
                $jenis,
                $jumlah,
                $kategori
            );

            if ($stmt->execute()) {

                $pesan = "Pemasukan berhasil disimpan.";

            } else {

                $error = "Data gagal disimpan: " . $conn->error;

            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA PEMASUKAN
|--------------------------------------------------------------------------
*/

$data_pemasukan = [];

$query = "
    SELECT
        id,
        tanggal,
        keterangan,
        jumlah,
        kategori
    FROM transaksi
    WHERE jenis = 'masuk'
    ORDER BY tanggal DESC, id DESC
";

$result = $conn->query($query);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $data_pemasukan[] = $row;

    }
}


/*
|--------------------------------------------------------------------------
| ROLE SAAT INI
|--------------------------------------------------------------------------
*/

$role = $_SESSION["role"] ?? "";

$isBendahara = ($role === "bendahara");
$isKetuaRT = ($role === "ketua_rt");

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pemasukan Kas - SIKART</title>

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
            background: #1e293b;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0 0;
            color: #cbd5e1;
        }

        .logout {
            color: white;
            background: #ef4444;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 7px;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #2563eb;
        }

        .box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            margin-bottom: 25px;
        }

        .box h2 {
            margin-top: 0;
        }

        .box p {
            color: #64748b;
        }

        label {
            display: block;
            margin-top: 20px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input,
        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info {
            background: #eff6ff;
            color: #1e40af;
            padding: 13px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }

        .role-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }

        th {
            background: #f8fafc;
            font-size: 14px;
        }

        td {
            font-size: 14px;
        }

        .jumlah {
            font-weight: bold;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-lihat {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .badge-kelola {
            background: #dcfce7;
            color: #166534;
        }

        .empty {
            text-align: center;
            color: #64748b;
            padding: 30px;
        }

        @media (max-width: 700px) {

            .header {
                padding: 15px;
            }

            .header h1 {
                font-size: 20px;
            }

            .container {
                margin-top: 20px;
                padding: 0 12px;
            }

            .box {
                padding: 20px;
            }

            th,
            td {
                white-space: nowrap;
            }

        }

    </style>

</head>

<body>


    <div class="header">

        <div>

            <h1>SIKART</h1>

            <p>
                Sistem Informasi Kas RT
            </p>

        </div>

        <a href="../logout.php" class="logout">
            Keluar
        </a>

    </div>


    <div class="container">


        <a href="../dashboard.php" class="back">
            ← Kembali ke Dashboard
        </a>


        <?php if ($isKetuaRT): ?>

            <!-- =====================================================
                 TAMPILAN KHUSUS KETUA RT
                 HANYA MELIHAT DATA
            ====================================================== -->

            <div class="box">

                <h2>💰 Pemasukan Kas RT</h2>

                <div class="role-info">

                    👁️ <strong>Mode Lihat</strong><br>

                    Anda login sebagai <strong>Ketua RT</strong>.
                    Anda dapat melihat seluruh data pemasukan kas RT,
                    tetapi tidak dapat menambah, mengedit, atau menghapus
                    transaksi.

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Tanggal</th>

                                <th>Keterangan</th>

                                <th>Kategori</th>

                                <th>Jumlah</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if (count($data_pemasukan) > 0): ?>

                                <?php $no = 1; ?>

                                <?php foreach ($data_pemasukan as $row): ?>

                                    <tr>

                                        <td>
                                            <?= $no++ ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row["tanggal"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row["keterangan"]) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($row["kategori"]) ?>
                                        </td>

                                        <td class="jumlah">

                                            Rp <?= number_format(
                                                $row["jumlah"],
                                                0,
                                                ",",
                                                "."
                                            ) ?>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="5" class="empty">

                                        Belum ada data pemasukan.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


        <?php else: ?>

            <!-- =====================================================
                 TAMPILAN BENDAHARA
                 BISA MENAMBAH PEMASUKAN
            ====================================================== -->

            <div class="box">

                <h2>💰 Tambah Pemasukan</h2>

                <p>
                    Catat setiap uang yang masuk ke kas RT.
                </p>


                <?php if ($pesan !== ""): ?>

                    <div class="success">
                        <?= htmlspecialchars($pesan) ?>
                    </div>

                <?php endif; ?>


                <?php if ($error !== ""): ?>

                    <div class="error">
                        <?= htmlspecialchars($error) ?>
                    </div>

                <?php endif; ?>


                <form method="POST">


                    <label for="tanggal">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="<?= date('Y-m-d') ?>"
                        required
                    >


                    <label for="kategori">
                        Kategori
                    </label>

                    <select
                        id="kategori"
                        name="kategori"
                        required
                    >

                        <option value="">
                            -- Pilih Kategori --
                        </option>

                        <option value="Iuran Warga">
                            Iuran Warga
                        </option>

                        <option value="Donasi">
                            Donasi
                        </option>

                        <option value="Bantuan">
                            Bantuan
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>


                    <label for="jumlah">
                        Jumlah (Rp)
                    </label>

                    <input
                        type="number"
                        id="jumlah"
                        name="jumlah"
                        min="1"
                        step="1"
                        placeholder="Contoh: 50000"
                        required
                    >


                    <label for="keterangan">
                        Keterangan
                    </label>

                    <input
                        type="text"
                        id="keterangan"
                        name="keterangan"
                        placeholder="Contoh: Iuran warga bulan September"
                        required
                    >


                    <button type="submit">
                        Simpan Pemasukan
                    </button>

                </form>


                <div class="info">

                    <strong>Catatan:</strong><br>

                    Setiap pemasukan yang disimpan akan otomatis masuk
                    ke perhitungan saldo kas pada dashboard.

                </div>

            </div>


        <?php endif; ?>


    </div>


</body>

</html>