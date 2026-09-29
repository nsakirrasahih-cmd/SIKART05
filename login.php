<?php
session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {

        $stmt = $conn->prepare("
            SELECT id, username, password, nama_lengkap, role
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            /*
             * Sementara masih menggunakan MD5
             * karena akun yang kita buat menggunakan MD5.
             */
            if (md5($password) === $user["password"]) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["nama_lengkap"] = $user["nama_lengkap"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = "Password salah.";
            }

        } else {
            $error = "Username tidak ditemukan.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login SIKART</title>

    <style>
    :root {
        --hijau-utama: #5B7E3C;
        --hijau-muda: #A2CB8B;
        --background: #E8F5BD;
        --merah: #C44545;
        --putih: #ffffff;
        --teks: #26321f;
    }

    * {
        box-sizing: border-box;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        background: var(--background);
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        color: var(--teks);
    }

    .login-box {
        width: 100%;
        max-width: 390px;
        background: var(--putih);
        padding: 32px;
        border-radius: 18px;
        box-shadow: 0 8px 25px rgba(91, 126, 60, 0.20);
        border-top: 7px solid var(--hijau-utama);
    }

    h2 {
        text-align: center;
        margin: 0 0 8px;
        color: var(--hijau-utama);
        font-size: 27px;
    }

    .login-box::before {
        content: "Sistem Informasi Kas RT";
        display: block;
        text-align: center;
        color: #66745d;
        font-size: 14px;
        margin-bottom: 25px;
    }

    label {
        display: block;
        font-weight: bold;
        margin-top: 12px;
        margin-bottom: 7px;
        color: var(--teks);
    }

    input {
        width: 100%;
        padding: 13px;
        margin: 0 0 10px;
        border: 1px solid #cbd8bd;
        border-radius: 9px;
        background: #fbfdf7;
        color: var(--teks);
        font-size: 15px;
        outline: none;
    }

    input:focus {
        border-color: var(--hijau-utama);
        box-shadow: 0 0 0 3px rgba(162, 203, 139, 0.35);
    }

    button {
        width: 100%;
        padding: 13px;
        margin-top: 15px;
        border: none;
        border-radius: 10px;
        background: var(--hijau-utama);
        color: white;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    button:hover {
        background: #49672f;
    }

    .error {
        background: #fdeaea;
        color: var(--merah);
        border: 1px solid #efbcbc;
        padding: 11px;
        border-radius: 9px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    @media (max-width: 480px) {
        body {
            padding: 15px;
        }

        .login-box {
            padding: 25px 20px;
            border-radius: 15px;
        }

        h2 {
            font-size: 24px;
        }

        input,
        button {
            padding: 12px;
        }
    }
	</style>
</head>

<body>

<div class="login-box">

    <h2>🔐 Login SIKART</h2>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            placeholder="Masukkan username"
            required
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            placeholder="Masukkan password"
            required
        >

        <button type="submit">
            Login
        </button>

    </form>

</div>

</body>
</html>