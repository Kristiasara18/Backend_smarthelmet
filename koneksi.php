<?php
$host = "127.0.0.1";
$user = "appuser";
$pass = "app123";
$db   = "db_karyawan";
$port = 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi DB gagal: " . mysqli_connect_error());
}
