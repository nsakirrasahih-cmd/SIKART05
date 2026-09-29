<?php
require_once "../config/auth.php";
wajibLogin();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Ambil Riwayat Perubahan
|--------------------------------------------------------------------------
| Halaman ini hanya untuk MELIHAT riwayat.
| Tidak ada fungsi edit atau hapus.
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        r.id,
        r.transaksi_id,
        r.aksi,

        r.tanggal_lama,
        r.tanggal_baru,

        r.keterangan_lama,
        r.keterangan_baru,

        r.debit_lama,
        r.debit_baru,

        r.kredit_lama,
        r.kredit_baru,

        r.saldo_lama,
        r.saldo_baru,

        r.alasan,
        r.diubah_oleh,
        r.waktu_perubahan

    FROM buku_kas.riwayat_perubahan r

    ORDER BY r.waktu_perubahan DESC, r.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Query riwayat gagal: " . $conn->error);
}

function rupiah($angka)
{
    $angka = (float) $angka;

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

    <title>Riwayat Perubahan - SIKART</title>

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
            background: #1e3a8a;
            color: white;
            padding: 25px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .container {
            width: 95%;
            max-width: 1500px;
            margin: 25px auto;
        }

        .info {
            background: white;
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .info h2 {
            margin-top: 0;
            font-size: 20px;
        }

        .info p {
            margin-bottom: 0;
            color: #475569;
        }

        .table-box {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
        }

        th {
            background: #1e3a8a;
            color: white;
            padding: 12px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        tr:hover {
            background: #f8fafc;
        }

        .aksi {
            font-weight: bold;
            color: #b45309;
        }

        .nominal {
            white-space: nowrap;
            font-weight: bold;
        }

        .lama {
            color: #64748b;
        }

        .baru {
            color: #166534;
            font-weight: bold;
        }

        .alasan {
            max-width: 250px;
        }

        .waktu {
            white-space: nowrap;
            color: #475569;
        }

        .kosong {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }

        .btn-kembali {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 16px;
            background: #334155;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        .btn-kembali:hover {
            background: #1e293b;
        }

        .footer {
            text-align: center;
            color: #64748b;
            padding: 25px;
        }

    </style>
</head>

<body>

<div class="header">
    <div class="container">

        <h1>🕒 Riwayat Perubahan</h1>

        <p>
            Catatan perubahan data transaksi SIKART
        </p>

    </div>
</div>


<div class="container">

    <a href="../dashboard.php" class="btn-kembali">
        ← Kembali ke Dashboard
    </a>


    <div class="info">

        <h2>🔎 Transparansi Perubahan Data</h2>

        <p>
            Halaman ini digunakan untuk melihat seluruh perubahan
            yang pernah dilakukan terhadap transaksi kas.
            Data lama, data baru, alasan perubahan, pengguna yang
            melakukan perubahan, dan waktu perubahan ditampilkan
            sebagai bagian dari jejak audit SIKART.
        </p>

    </div>


    <div class="table-box">

        <?php if ($result->num_rows > 0): ?>

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Transaksi</th>

                        <th>Aksi</th>

                        <th>Tanggal</th>

                        <th>Keterangan</th>

                        <th>Debit</th>

                        <th>Kredit</th>

                        <th>Saldo</th>

                        <th>Alasan</th>

                        <th>Diubah Oleh</th>

                        <th>Waktu Perubahan</th>

                    </tr>

                </thead>

                <tbody>

                <?php
                $no = 1;

                while ($row = $result->fetch_assoc()):
                ?>

                    <tr>

                        <td>
                            <?php echo $no++; ?>
                        </td>


                        <td>
                            #<?php echo (int) $row["transaksi_id"]; ?>
                        </td>


                        <td class="aksi">
                            <?php
                            echo htmlspecialchars(
                                $row["aksi"]
                            );
                            ?>
                        </td>


                        <td>

                            <div class="lama">
                                Lama:
                                <?php
                                echo $row["tanggal_lama"]
                                    ? htmlspecialchars($row["tanggal_lama"])
                                    : "-";
                                ?>
                            </div>

                            <div class="baru">
                                Baru:
                                <?php
                                echo $row["tanggal_baru"]
                                    ? htmlspecialchars($row["tanggal_baru"])
                                    : "-";
                                ?>
                            </div>

                        </td>


                        <td>

                            <div class="lama">

                                <strong>Lama:</strong><br>

                                <?php
                                echo $row["keterangan_lama"]
                                    ? nl2br(
                                        htmlspecialchars(
                                            $row["keterangan_lama"]
                                        )
                                    )
                                    : "-";
                                ?>

                            </div>

                            <br>

                            <div class="baru">

                                <strong>Baru:</strong><br>

                                <?php
                                echo $row["keterangan_baru"]
                                    ? nl2br(
                                        htmlspecialchars(
                                            $row["keterangan_baru"]
                                        )
                                    )
                                    : "-";
                                ?>

                            </div>

                        </td>


                        <td class="nominal">

                            <div class="lama">
                                Lama:<br>
                                <?php
                                echo rupiah($row["debit_lama"]);
                                ?>
                            </div>

                            <br>

                            <div class="baru">
                                Baru:<br>
                                <?php
                                echo rupiah($row["debit_baru"]);
                                ?>
                            </div>

                        </td>


                        <td class="nominal">

                            <div class="lama">
                                Lama:<br>
                                <?php
                                echo rupiah($row["kredit_lama"]);
                                ?>
                            </div>

                            <br>

                            <div class="baru">
                                Baru:<br>
                                <?php
                                echo rupiah($row["kredit_baru"]);
                                ?>
                            </div>

                        </td>


                        <td class="nominal">

                            <div class="lama">
                                Lama:<br>
                                <?php
                                echo rupiah($row["saldo_lama"]);
                                ?>
                            </div>

                            <br>

                            <div class="baru">
                                Baru:<br>
                                <?php
                                echo rupiah($row["saldo_baru"]);
                                ?>
                            </div>

                        </td>


                        <td class="alasan">

                            <?php
                            echo $row["alasan"]
                                ? nl2br(
                                    htmlspecialchars(
                                        $row["alasan"]
                                    )
                                )
                                : "-";
                            ?>

                        </td>


                        <td>

                            <?php
                            echo $row["diubah_oleh"]
                                ? htmlspecialchars(
                                    $row["diubah_oleh"]
                                )
                                : "-";
                            ?>

                        </td>


                        <td class="waktu">

                            <?php
                            echo htmlspecialchars(
                                $row["waktu_perubahan"]
                            );
                            ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="kosong">

                🕒 Belum ada perubahan transaksi.

                <br><br>

                Setiap perubahan transaksi yang dilakukan
                akan tercatat di halaman ini.

            </div>

        <?php endif; ?>

    </div>

</div>


<div class="footer">

    SIKART — Sistem Informasi Kas RT

</div>

</body>
</html>