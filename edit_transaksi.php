<?php
require_once "../config/auth.php";

wajibRole(["bendahara", "sekretaris"]);

require_once "../config/database.php";

/* =========================
   CEK ID TRANSAKSI
   ========================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("ID transaksi tidak valid.");
}

$id = (int) $_GET["id"];


/* =========================
   AMBIL DATA TRANSAKSI
   ========================= */

$stmt = $conn->prepare("
    SELECT id, tanggal, keterangan, debit, kredit, saldo
    FROM buku_kas.transaksi_kas
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Transaksi tidak ditemukan.");
}

$dataLama = $result->fetch_assoc();


/* =========================
   PROSES PERUBAHAN
   ========================= */

$pesan = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tanggalBaru = $_POST["tanggal"] ?? "";
    $keteranganBaru = trim($_POST["keterangan"] ?? "");
    $debitBaru = (float) ($_POST["debit"] ?? 0);
    $kreditBaru = (float) ($_POST["kredit"] ?? 0);
    $alasan = trim($_POST["alasan"] ?? "");

    /* Validasi */

    if ($tanggalBaru === "" || $keteranganBaru === "") {

        $error = "Tanggal dan keterangan wajib diisi.";

    } elseif ($debitBaru < 0 || $kreditBaru < 0) {

        $error = "Nominal tidak boleh negatif.";

    } elseif ($debitBaru > 0 && $kreditBaru > 0) {

        $error = "Isi Debit atau Kredit saja, jangan keduanya.";

    } elseif ($alasan === "") {

        $error = "Alasan perubahan wajib diisi.";

    } else {

        /*
         * Saldo transaksi dihitung berdasarkan:
         * saldo sebelum transaksi + debit - kredit
         */

        $stmtSaldo = $conn->prepare("
            SELECT saldo
            FROM buku_kas.transaksi_kas
            WHERE tanggal < ?
               OR (tanggal = ? AND id < ?)
            ORDER BY tanggal DESC, id DESC
            LIMIT 1
        ");

        $stmtSaldo->bind_param(
            "ssi",
            $tanggalBaru,
            $tanggalBaru,
            $id
        );

        $stmtSaldo->execute();

        $resultSaldo = $stmtSaldo->get_result();

        if ($resultSaldo->num_rows > 0) {

            $saldoSebelumnya = (float)
                $resultSaldo->fetch_assoc()["saldo"];

        } else {

            $saldoSebelumnya = 0;

        }

        $saldoBaru =
            $saldoSebelumnya +
            $debitBaru -
            $kreditBaru;


        /*
         * Nama pengguna yang sedang login
         */

        $diubahOleh =
            $_SESSION["nama_lengkap"] ??
            $_SESSION["username"] ??
            "Pengguna";


        /*
         * Simpan riwayat terlebih dahulu
         */

        $stmtRiwayat = $conn->prepare("
            INSERT INTO buku_kas.riwayat_perubahan
            (
                transaksi_id,
                aksi,
                tanggal_lama,
                tanggal_baru,
                keterangan_lama,
                keterangan_baru,
                debit_lama,
                debit_baru,
                kredit_lama,
                kredit_baru,
                saldo_lama,
                saldo_baru,
                alasan,
                diubah_oleh
            )
            VALUES
            (
                ?,
                'EDIT',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmtRiwayat->bind_param(
            "issssddddddss",
            $id,
            $dataLama["tanggal"],
            $tanggalBaru,
            $dataLama["keterangan"],
            $keteranganBaru,
            $dataLama["debit"],
            $debitBaru,
            $dataLama["kredit"],
            $kreditBaru,
            $dataLama["saldo"],
            $saldoBaru,
            $alasan,
            $diubahOleh
        );

        if (!$stmtRiwayat->execute()) {

            $error =
                "Gagal menyimpan riwayat perubahan: " .
                $stmtRiwayat->error;

        } else {

            /*
             * Setelah riwayat tersimpan,
             * baru ubah transaksi utama.
             */

            $stmtUpdate = $conn->prepare("
                UPDATE buku_kas.transaksi_kas
                SET
                    tanggal = ?,
                    keterangan = ?,
                    debit = ?,
                    kredit = ?,
                    saldo = ?
                WHERE id = ?
            ");

            $stmtUpdate->bind_param(
                "ssdddi",
                $tanggalBaru,
                $keteranganBaru,
                $debitBaru,
                $kreditBaru,
                $saldoBaru,
                $id
            );

            if ($stmtUpdate->execute()) {

                $pesan =
                    "Transaksi berhasil diubah dan riwayat perubahan telah dicatat.";

                /*
                 * Ambil data terbaru untuk ditampilkan
                 */

                $dataLama["tanggal"] = $tanggalBaru;
                $dataLama["keterangan"] = $keteranganBaru;
                $dataLama["debit"] = $debitBaru;
                $dataLama["kredit"] = $kreditBaru;
                $dataLama["saldo"] = $saldoBaru;

            } else {

                $error =
                    "Riwayat sudah tersimpan, tetapi transaksi gagal diperbarui: " .
                    $stmtUpdate->error;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Transaksi - SIKART</title>

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
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .kembali {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: white;
            padding: 10px 16px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
        }

        h1 {
            margin-top: 0;
        }

        .peringatan {
            background: #fff7ed;
            color: #9a3412;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .sukses {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
        }

        input,
        textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            margin-bottom: 18px;
            font-family: inherit;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .nominal {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            border: none;
            padding: 12px 20px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }

        .btn-simpan {
            background: #16a34a;
            color: white;
        }

        .btn-simpan:hover {
            background: #15803d;
        }

        .btn-batal {
            display: inline-block;
            text-decoration: none;
            background: #64748b;
            color: white;
            padding: 12px 20px;
            border-radius: 7px;
            margin-left: 8px;
        }

        @media (max-width: 600px) {

            .nominal {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="topbar">

    <div class="brand">
        SIKART — Edit Transaksi
    </div>

</div>

<div class="container">

    <a href="transaksi.php" class="kembali">
        ← Kembali ke Riwayat Transaksi
    </a>

    <div class="box">

        <h1>
            Edit Transaksi #<?php echo $id; ?>
        </h1>

        <div class="peringatan">

            <b>⚠️ Perhatian</b><br><br>

            Perubahan data tidak akan menghapus jejak perubahan.
            Nilai sebelum dan sesudah perubahan akan disimpan
            di <b>Riwayat Perubahan</b>.

        </div>

        <?php if ($pesan !== ""): ?>

            <div class="sukses">
                <?php echo htmlspecialchars($pesan); ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <label>
                Tanggal
            </label>

            <input
                type="date"
                name="tanggal"
                value="<?php
                    echo htmlspecialchars(
                        $dataLama["tanggal"]
                    );
                ?>"
                required
            >


            <label>
                Keterangan
            </label>

            <textarea
                name="keterangan"
                required
            ><?php
                echo htmlspecialchars(
                    $dataLama["keterangan"]
                );
            ?></textarea>


            <div class="nominal">

                <div>

                    <label>
                        Debit / Pemasukan
                    </label>

                    <input
                        type="number"
                        name="debit"
                        step="0.01"
                        min="0"
                        value="<?php
                            echo htmlspecialchars(
                                $dataLama["debit"]
                            );
                        ?>"
                    >

                </div>

                <div>

                    <label>
                        Kredit / Pengeluaran
                    </label>

                    <input
                        type="number"
                        name="kredit"
                        step="0.01"
                        min="0"
                        value="<?php
                            echo htmlspecialchars(
                                $dataLama["kredit"]
                            );
                        ?>"
                    >

                </div>

            </div>


            <label>
                Alasan Perubahan
            </label>

            <textarea
                name="alasan"
                placeholder="Contoh: Salah memasukkan nominal transaksi."
                required
            ></textarea>


            <button
                type="submit"
                class="btn btn-simpan"
            >
                💾 Simpan Perubahan
            </button>

            <a
                href="transaksi.php"
                class="btn-batal"
            >
                Batal
            </a>

        </form>

    </div>

</div>

</body>

</html>