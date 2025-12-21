<?php
header("Content-Type: application/json");
require_once "../config/database.php";
require_once __DIR__ . '/../lib/koneksi.php';
require_once __DIR__ . '/../lib/phpMQTT.php';


$query = "SELECT * FROM incidents ORDER BY time DESC";
$result = mysqli_query($conn, $query);

$incidents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $incidents[] = $row;
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

echo json_encode([
    "status" => "success",
    "data" => $incidents
]);
?>
