<?php

$host = "sql307.infinityfree.com";
$username = "if0_42974676";
$password = "Ww6R5owEi7VnwmL";
$database = "if0_42974676_bukukas";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>