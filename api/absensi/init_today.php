<?php
require_once __DIR__ . '/../../lib/koneksi.php';
date_default_timezone_set("Asia/Jakarta");

$today = date("Y-m-d");

/*
  INSERT IGNORE + UNIQUE (tanggal, id_pekerja)
  = Aman dijalankan berkali-kali
  = Tidak double
  = Tidak error
  = Tidak menimpa data manual
*/
$sql = "
INSERT IGNORE INTO absensi (tanggal, id_pekerja, kehadiran)
SELECT '$today', k.id_pekerja, 'Tidak Hadir'
FROM karyawan k
";

$conn->query($sql);

echo json_encode([
    "status" => "success",
    "message" => "Init absensi harian aman (cron-ready)"
]);
