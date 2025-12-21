<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../lib/koneksi.php';
require_once __DIR__ . '/../lib/phpMQTT.php';

// Ambil data JSON dari request
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode([
        "status" => "error",
        "message" => "ID required"
    ]);
    exit;
}

// Update tabel kejadian
$stmt = $conn->prepare("UPDATE kejadian SET handled = 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();

if ($ok) {
    echo json_encode([
        "status" => "success",
        "message" => "Marked as handled"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error"
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
