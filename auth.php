<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wajibLogin()
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: /SIKART/login.php");
        exit;
    }
}

function wajibRole($roles)
{
    wajibLogin();

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    if (!isset($_SESSION["role"]) || !in_array($_SESSION["role"], $roles)) {
        http_response_code(403);
        die("
            <h2 style='font-family:Arial;'>403 - Akses Ditolak</h2>
            <p style='font-family:Arial;'>
                Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>
            <a href='/SIKART/dashboard.php'>Kembali ke Dashboard</a>
        ");
    }
}