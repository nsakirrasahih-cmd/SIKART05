<?php

require_once "../config/auth.php";

/*
|--------------------------------------------------------------------------
| HAK AKSES
|--------------------------------------------------------------------------
| Bendahara : bisa upload + melihat
| Sekretaris: bisa upload + melihat
| Ketua RT  : hanya melihat
|--------------------------------------------------------------------------
*/

wajibRole(["bendahara", "sekretaris", "ketua_rt"]);

require_once "../config/database.php";

$pesan = "";
$error = "";


/*
|--------------------------------------------------------------------------
| CEK ROLE
|--------------------------------------------------------------------------
*/

$role = $_SESSION["role"] ?? "";

$isKetuaRT = ($role === "ketua_rt");
$isUploader = in_array($role, ["bendahara", "sekretaris"]);


/*
|--------------------------------------------------------------------------
| PROSES UPLOAD
|--------------------------------------------------------------------------
| Ketua RT tidak boleh melakukan upload.
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$isUploader) {

        $error = "Ketua RT hanya memiliki akses untuk melihat bukti transaksi.";

    } else {

        $transaksi_id = isset($_POST["transaksi_id"])
            ? (int) $_POST["transaksi_id"]
            : 0;

        $keterangan = isset($_POST["keterangan"])
            ? trim($_POST["keterangan"])
            : "";


        /* Cek transaksi */

        if ($transaksi_id <= 0) {

            $error = "ID transaksi tidak valid.";

        } elseif (!isset($_FILES["bukti"])) {

            $error = "File bukti belum dipilih.";

        } else {

            $file = $_FILES["bukti"];


            /* Cek error upload */

            if ($file["error"] !== UPLOAD_ERR_OK) {

                $error = "Upload file gagal.";

            } else {


                /* Batas ukuran file */

                $maksimal = 5 * 1024 * 1024;

                if ($file["size"] > $maksimal) {

                    $error = "Ukuran file maksimal 5 MB.";

                } else {


                    /* Cek jenis file */

                    $nama_asli = $file["name"];

                    $ekstensi = strtolower(
                        pathinfo(
                            $nama_asli,
                            PATHINFO_EXTENSION
                        )
                    );

                    $ekstensi_diizinkan = [
                        "jpg",
                        "jpeg",
                        "png",
                        "pdf"
                    ];

                    if (!in_array(
                        $ekstensi,
                        $ekstensi_diizinkan
                    )) {

                        $error =
                            "File harus berupa JPG, JPEG, PNG, atau PDF.";

                    } else {


                        /* Cek transaksi */

                        $stmtCek = $conn->prepare("
                            SELECT id
                            FROM buku_kas.transaksi_kas
                            WHERE id = ?
                            LIMIT 1
                        ");

                        $stmtCek->bind_param(
                            "i",
                            $transaksi_id
                        );

                        $stmtCek->execute();

                        $hasilCek =
                            $stmtCek->get_result();


                        if ($hasilCek->num_rows === 0) {

                            $error =
                                "Transaksi tidak ditemukan.";

                        } else {


                            /* Nama file baru */

                            $nama_baru =
                                "bukti_" .
                                $transaksi_id .
                                "_" .
                                time() .
                                "_" .
                                bin2hex(
                                    random_bytes(4)
                                ) .
                                "." .
                                $ekstensi;


                            $folder =
                                "../uploads/bukti/";


                            $tujuan =
                                $folder .
                                $nama_baru;


                            /* Pindahkan file */

                            if (move_uploaded_file(
                                $file["tmp_name"],
                                $tujuan
                            )) {


                                /* Simpan ke database */

                                $path_database =
                                    "uploads/bukti/" .
                                    $nama_baru;


                                $stmtSimpan =
                                    $conn->prepare("
                                        INSERT INTO buku_kas.bukti_transaksi
                                        (
                                            transaksi_id,
                                            nama_file,
                                            file_path,
                                            keterangan
                                        )
                                        VALUES (?, ?, ?, ?)
                                    ");


                                $stmtSimpan->bind_param(
                                    "isss",
                                    $transaksi_id,
                                    $nama_asli,
                                    $path_database,
                                    $keterangan
                                );


                                if ($stmtSimpan->execute()) {

                                    $pesan =
                                        "Bukti transaksi berhasil diupload.";

                                } else {

                                    if (
                                        file_exists($tujuan)
                                    ) {

                                        unlink($tujuan);

                                    }

                                    $error =
                                        "File berhasil diupload tetapi gagal disimpan ke database.";

                                }

                            } else {

                                $error =
                                    "File gagal dipindahkan ke folder bukti.";

                            }

                        }

                    }

                }

            }

        }

    }

}


/*
|--------------------------------------------------------------------------
| AMBIL DATA TRANSAKSI
|--------------------------------------------------------------------------
*/

$queryTransaksi = "
    SELECT
        id,
        tanggal,
        keterangan,
        debit,
        kredit,
        saldo
    FROM buku_kas.transaksi_kas
    ORDER BY tanggal DESC, id DESC
";

$resultTransaksi =
    $conn->query($queryTransaksi);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Bukti Transaksi - SIKART</title>

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
    max-width: 1200px;
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
}

.header p {
    margin: 0;
    color: #666;
}

.btn-dashboard {
    display: inline-block;
    margin-bottom: 15px;
    padding: 10px 16px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 7px;
}

.form-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-weight: bold;
    margin-bottom: 6px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 11px;
    border: 1px solid #ccc;
    border-radius: 7px;
}

.form-group textarea {
    min-height: 90px;
    resize: vertical;
}

.btn-upload {
    background: #16a34a;
    color: white;
    border: none;
    padding: 11px 18px;
    border-radius: 7px;
    cursor: pointer;
}

.pesan {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}

.info {
    background: #dbeafe;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}

.lihat-info {
    background: #eff6ff;
    color: #1e40af;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    line-height: 1.6;
}

.table-box {
    background: white;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

th {
    background: #111827;
    color: white;
    padding: 11px;
}

td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

tbody tr:hover {
    background: #f3f4f6;
}

.nominal {
    text-align: right;
    white-space: nowrap;
}

@media (max-width: 700px) {

    body {
        padding: 10px;
    }

}

</style>

</head>

<body>

<div class="container">


    <a
        href="../dashboard.php"
        class="btn-dashboard"
    >
        ← Kembali ke Dashboard
    </a>


    <div class="header">

        <h1>📎 Bukti Transaksi</h1>

        <p>
            SIKART — Sistem Informasi Kas RT
        </p>

    </div>


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


    <?php if ($isKetuaRT): ?>

        <!-- =====================================================
             KETUA RT
             HANYA MELIHAT
        ====================================================== -->

        <div class="lihat-info">

            👁️ <strong>Mode Lihat — Ketua RT</strong><br>

            Anda dapat melihat daftar transaksi sebagai
            bahan transparansi kas RT.

            <br><br>

            ❌ Tidak dapat upload bukti transaksi.

        </div>


    <?php else: ?>

        <!-- =====================================================
             BENDAHARA / SEKRETARIS
             BISA UPLOAD
        ====================================================== -->

        <div class="form-box">

            <h2>Upload Bukti Transaksi</h2>

            <div class="info">

                Format yang diperbolehkan:
                <strong>JPG, JPEG, PNG, PDF</strong>.

                Maksimal ukuran:
                <strong>5 MB</strong>.

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-group">

                    <label>
                        Pilih Transaksi
                    </label>

                    <select
                        name="transaksi_id"
                        required
                    >

                        <option value="">
                            -- Pilih transaksi --
                        </option>


                        <?php if ($resultTransaksi): ?>

                            <?php while (
                                $trx =
                                $resultTransaksi->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?= $trx["id"] ?>"
                                >

                                    ID <?= $trx["id"] ?>
                                    —
                                    <?= date(
                                        "d-m-Y",
                                        strtotime(
                                            $trx["tanggal"]
                                        )
                                    ) ?>
                                    —
                                    <?= htmlspecialchars(
                                        $trx["keterangan"]
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        File Bukti
                    </label>

                    <input
                        type="file"
                        name="bukti"
                        accept=".jpg,.jpeg,.png,.pdf"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Keterangan Bukti
                    </label>

                    <textarea
                        name="keterangan"
                        placeholder="Contoh: Nota pembelian atau bukti transfer"
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="btn-upload"
                >

                    📤 Upload Bukti

                </button>

            </form>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         DAFTAR TRANSAKSI
    ====================================================== -->

    <div class="table-box">

        <h2>Daftar Transaksi</h2>


        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Tanggal</th>

                    <th>Keterangan</th>

                    <th>Debit</th>

                    <th>Kredit</th>

                    <th>Saldo</th>

                </tr>

            </thead>


            <tbody>


            <?php

            $queryDaftar = "
                SELECT
                    id,
                    tanggal,
                    keterangan,
                    debit,
                    kredit,
                    saldo
                FROM buku_kas.transaksi_kas
                ORDER BY tanggal DESC, id DESC
            ";

            $resultDaftar =
                $conn->query($queryDaftar);

            ?>


            <?php if ($resultDaftar): ?>

                <?php while (
                    $row =
                    $resultDaftar->fetch_assoc()
                ): ?>

                    <tr>

                        <td>
                            <?= $row["id"] ?>
                        </td>

                        <td>

                            <?= date(
                                "d-m-Y",
                                strtotime(
                                    $row["tanggal"]
                                )
                            ) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars(
                                $row["keterangan"]
                            ) ?>

                        </td>

                        <td class="nominal">

                            <?= rupiah(
                                $row["debit"]
                            ) ?>

                        </td>

                        <td class="nominal">

                            <?= rupiah(
                                $row["kredit"]
                            ) ?>

                        </td>

                        <td class="nominal">

                            <?= rupiah(
                                $row["saldo"]
                            ) ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php endif; ?>


            </tbody>

        </table>

    </div>


</div>

</body>

</html>