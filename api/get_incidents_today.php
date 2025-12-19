<?php
include "../koneksi.php";
require_once '../phpMQTT.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Tanggal hari ini
$today = date('Y-m-d');

// Hitung total insiden hari ini dari tabel kejadian
$q = $conn->query("
    SELECT SUM(jumlah_kejadian) AS total_insiden
    FROM kejadian
    WHERE tanggal = '$today' AND aktif = 1
");
$r = $q->fetch_assoc();

// Jika null, ubah menjadi 0
$total_insiden = $r['total_insiden'] ? intval($r['total_insiden']) : 0;

$mqtt = new phpMQTT($server, $port, $client_id);

if ($mqtt->connect(true, NULL, $username, $password)) {

    $topic = "helmet/incident";

    $payload = json_encode([
        "device_id" => $device_id,
        "id_pekerja" => $id_pekerja,
        "lokasi" => $lokasi,
        "status" => $status,
        "catatan" => $catatan,
        "waktu" => date("Y-m-d H:i:s"),
        "incident_id" => $incident_id
    ]);

    $mqtt->publish($topic, $payload, 0);
    $mqtt->close();
}

// Kirim JSON ke frontend
echo json_encode([
    "status" => "success",
    "data" => [
        "total_insiden" => $total_insiden
    ]
]);
?>
