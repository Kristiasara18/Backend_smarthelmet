<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "../../koneksi.php";

date_default_timezone_set("Asia/Jakarta");
$today = date("Y-m-d");

// Periksa apakah absensi hari ini sudah ada
$check = $conn->query("SELECT COUNT(*) AS count FROM absensi WHERE tanggal = '$today'");
$row = $check->fetch_assoc();

if ($row["count"] == 0) {
    // Insert otomatis untuk semua karyawan
    $insert = "
        INSERT INTO absensi (tanggal, id_pekerja, kehadiran)
        SELECT '$today', id_pekerja, 'Tidak Hadir' FROM karyawan
    ";
    $conn->query($insert);
}

// Ambil absensi 7 hari terakhir (SAMA seperti NodeJS)
$query = "
    SELECT a.id_absensi AS id, a.tanggal, k.nama, k.id_pekerja, a.kehadiran, k.kode_helmet
    FROM absensi a
    JOIN karyawan k ON a.id_pekerja = k.id_pekerja
    WHERE a.tanggal >= DATE_SUB('$today', INTERVAL 6 DAY)
    ORDER BY a.tanggal DESC, a.id_pekerja ASC
";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
