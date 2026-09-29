<?php

require_once "config/database.php";

$result = $conn->query("SELECT id, username, nama_lengkap FROM users");

if (!$result) {
    die("Query gagal: " . $conn->error);
}

echo "<h2>Data User dari Database</h2>";

while ($row = $result->fetch_assoc()) {
    echo "ID: " . htmlspecialchars($row["id"]) . "<br>";
    echo "Username: " . htmlspecialchars($row["username"]) . "<br>";
    echo "Nama: " . htmlspecialchars($row["nama_lengkap"]) . "<hr>";
}

?>