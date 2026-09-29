<?php

require_once "../config/auth.php";

/*
|--------------------------------------------------------------------------
| HAK AKSES
|--------------------------------------------------------------------------
| Bendahara : bisa melihat + menambah pengeluaran
| Ketua RT  : hanya bisa melihat pengeluaran
|--------------------------------------------------------------------------
*/

wajibRole(["bendahara", "ketua_rt"]);

require_once "../config/database.php";

$pesan = "";
$error = "";


/*
|--------------------------------------------------------------------------
| PROSES TAMBAH PENGELUARAN
|--------------------------------------------------------------------------
| Hanya Bendahara yang boleh menyimpan data.
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "bendahara") {

        $error = "Anda tidak memiliki izin untuk menambah pengeluaran.";

    } else {

        $tanggal = $_POST["tanggal"] ?? "";
        $kategori = $_POST["kategori"] ?? "";
        $jumlah = $_POST["jumlah"] ?? "";
        $keterangan = trim($_POST["keterangan"] ?? "");

        if (
            $tanggal === "" ||
            $kategori === "" ||
            $jumlah === "" ||
            $keterangan === ""
        ) {

            $error = "Semua data wajib diisi.";

        } elseif (!is_numeric($jumlah) || $jumlah <= 0) {

            $error = "Jumlah pengeluaran harus berupa angka lebih dari 0.";

        } else {

            $jumlah = (float) $jumlah;
            $jenis = "keluar";

            $stmt = $conn->prepare(
                "INSERT INTO transaksi
                (tanggal, keterangan, jenis, jumlah, kategori)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssds",
                $tanggal,
                $keterangan,
                $jenis,
                $jumlah,
                $kategori
            );

            if ($stmt->execute()) {

                $pesan = "Pengeluaran berhasil disimpan.";

            } else {

                $error = "Gagal menyimpan pengeluaran: " . $conn->error;

            }

            $stmt->close();
        }
    }
}


/*
|--------------------------------------------------------------------------
| AMBIL SEMUA DATA PENGELUARAN
|--------------------------------------------------------------------------
*/

$data_pengeluaran = [];

$query = "
    SELECT
        id,
        tanggal,
        keterangan,
        jumlah,
        kategori
    FROM transaksi
    WHERE jenis = 'keluar'
    ORDER BY tanggal DESC, id DESC
";

$result = $conn->query($query);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $data_pengeluaran[] = $row;

    }
}


/*
|--------------------------------------------------------------------------
| CEK ROLE
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

    <title>Pengeluaran Kas - SIKART</title>

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
            font-size: 25px;
        }

        .header p {
            margin: 6px 0 0;
            color: #d1d5db;
        }

        .container {
            width: 90%;
            max-width: 1100px;
            margin: 30px auto;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #2563eb;
            font-weight: bold;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            background: #dc2626;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #b91c1c;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .info {
            margin-top: 20px;
            padding: 15px;
            background: #fff7ed;
            border-radius: 8px;
            color: #9a3412;
            font-size: 14px;
        }

        .role-info {
            background: #eff6ff;
            color: #1e40af;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
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

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 30px;
        }

        @media (max-width: 700px) {

            .container {
                width: 94%;
                margin: 20px auto;
            }

            .card {
                padding: 18px;
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

    <h1>SIKART</h1>

    <p>
        Sistem Informasi Kas RT
    </p>

</div>


<div class="container">


    <a href="../dashboard.php" class="back">
        ← Kembali ke Dashboard
    </a>


    <?php if ($isKetuaRT): ?>

        <!-- =====================================================
             TAMPILAN KETUA RT
             HANYA MELIHAT DATA
        ====================================================== -->

        <div class="card">

            <h2>💸 Pengeluaran Kas RT</h2>

            <div class="role-info">

                👁️ <strong>Mode Lihat</strong><br>

                Anda login sebagai <strong>Ketua RT</strong>.
                Anda dapat melihat seluruh data pengeluaran kas RT,
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

                        <?php if (count($data_pengeluaran) > 0): ?>

                            <?php $no = 1; ?>

                            <?php foreach ($data_pengeluaran as $row): ?>

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

                                    Belum ada data pengeluaran.

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
             BISA MENAMBAH PENGELUARAN
        ====================================================== -->

        <div class="card">

            <h2>Tambah Pengeluaran Kas</h2>


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


                <div class="form-group">

                    <label for="tanggal">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="<?= htmlspecialchars(
                            $_POST["tanggal"] ?? date("Y-m-d")
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="kategori">
                        Kategori Pengeluaran
                    </label>

                    <select
                        id="kategori"
                        name="kategori"
                        required
                    >

                        <option value="">
                            -- Pilih Kategori --
                        </option>

                        <option value="Kebersihan">
                            Kebersihan
                        </option>

                        <option value="Keamanan">
                            Keamanan
                        </option>

                        <option value="Kegiatan Warga">
                            Kegiatan Warga
                        </option>

                        <option value="Perlengkapan RT">
                            Perlengkapan RT
                        </option>

                        <option value="Sosial">
                            Sosial
                        </option>

                        <option value="Lainnya">
                            Lainnya
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="jumlah">
                        Jumlah Pengeluaran
                    </label>

                    <input
                        type="number"
                        id="jumlah"
                        name="jumlah"
                        min="1"
                        step="0.01"
                        placeholder="Contoh: 25000"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="keterangan">
                        Keterangan
                    </label>

                    <textarea
                        id="keterangan"
                        name="keterangan"
                        placeholder="Contoh: Membeli kantong sampah untuk kegiatan kebersihan"
                        required
                    ></textarea>

                </div>


                <button type="submit">
                    Simpan Pengeluaran
                </button>

            </form>


            <div class="info">

                <strong>Catatan:</strong><br>

                Pengeluaran yang disimpan akan otomatis mengurangi
                saldo kas pada dashboard SIKART.

            </div>

        </div>

    <?php endif; ?>


</div>


</body>

</html>