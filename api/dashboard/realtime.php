<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once __DIR__ . '/../../lib/koneksi.php';

$query = "
SELECT 
  k.nama,
  s.kode_helmet,
  s.kondisi,
  s.last_seen
FROM status_helmet s
JOIN karyawan k ON s.kode_helmet = k.kode_helmet
WHERE TIMESTAMPDIFF(SECOND, s.last_seen, NOW()) <= 10
ORDER BY s.last_seen DESC
";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
