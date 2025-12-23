<?php
require_once __DIR__ . '/../lib/koneksi.php';
require_once '../phpMQTT.php';
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil insiden paling terbaru dari tabel kejadian
$query = "SELECT * FROM kejadian WHERE aktif = 1 ORDER BY id DESC LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'data' => $row
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tidak ada data insiden'
    ]);
}

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

$conn->close();
?>
