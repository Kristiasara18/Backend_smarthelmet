<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
require_once __DIR__ . '/../lib/koneksi.php';

date_default_timezone_set("Asia/Jakarta");
$today = date("Y-m-d");

if ($row["count"] == 0) {
    $karyawan = $conn->query("SELECT id_pekerja FROM karyawan");
$values = [];
while ($k = $karyawan->fetch_assoc()) {
    $values[] = "('$today', '{$k['id_pekerja']}', 'Tidak Hadir')";
}

if (!empty($values)) {
    $insertSQL = "INSERT IGNORE INTO absensi (tanggal, id_pekerja, kehadiran) VALUES " . implode(",", $values);
    $conn->query($insertSQL);
}
}

// Ambil absensi 7 hari terakhir
$query = "
    SELECT a.id_absensi AS id, a.tanggal, k.nama, k.id_pekerja, a.kehadiran, k.kode_helmet
    FROM absensi a
    JOIN karyawan k ON a.id_pekerja = k.id_pekerja
    WHERE a.tanggal BETWEEN DATE_SUB('$today', INTERVAL 6 DAY) AND '$today'
    ORDER BY a.tanggal DESC, a.id_pekerja ASC
";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
