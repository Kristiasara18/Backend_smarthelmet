<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../phpMQTT.php';
include '../koneksi.php';

$server = "test.mosquitto.org";   // broker
$port = 1883;                     // non-TLS port
$username = "";                   // isi jika broker butuh user
$password = "";
$client_id = "backend_publisher_" . uniqid();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metode harus POST"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data tidak valid"]);
    exit();
}

$device_id = $data['device_id'] ?? 'HLM-001';
$lokasi = $data['lokasi'] ?? 'Zona A';
$status = $data['status'] ?? 'Jatuh';
$catatan = $data['catatan'] ?? '';
$id_tipe = 1;
$handled = 0;
$aktif = 1;

// --- MAPPING device_id → id_pekerja ---
$q = $conn->prepare("SELECT id_pekerja FROM karyawan WHERE kode_helmet = ?");
$q->bind_param("s", $device_id);
$q->execute();
$id_pekerja = $q->get_result()->fetch_assoc()['id_pekerja'] ?? null;

if (!$id_pekerja) {
    echo json_encode(["status" => "error", "message" => "Pekerja tidak ditemukan untuk device_id"]);
    exit();
}

// --- Cek apakah pekerja sudah punya kejadian hari ini ---
$check = $conn->prepare("
    SELECT id, jumlah_kejadian 
    FROM kejadian 
    WHERE id_pekerja = ? AND tanggal = CURDATE()
");
$check->bind_param("i", $id_pekerja);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // UPDATE jumlah_kejadian
    $row = $res->fetch_assoc();
    $newCount = $row['jumlah_kejadian'] + 1;

    $update = $conn->prepare("
        UPDATE kejadian 
        SET jumlah_kejadian = ?, waktu = NOW(), lokasi = ?, status = ?, catatan = ?
        WHERE id = ?
    ");
    $update->bind_param("isssi", $newCount, $lokasi, $status, $catatan, $row['id']);
    $update->execute();
    $update->close();

    $incident_id = $row['id'];
} else {
    // INSERT kejadian baru
    $insert = $conn->prepare("
        INSERT INTO kejadian 
        (id_pekerja, waktu, tanggal, lokasi, status, catatan, id_tipe, jumlah_kejadian, handled, aktif)
        VALUES (?, NOW(), CURDATE(), ?, ?, ?, ?, 1, ?, ?)
    ");
    $insert->bind_param("isssiii", $id_pekerja, $lokasi, $status, $catatan, $id_tipe, $handled, $aktif);
    $insert->execute();
    $incident_id = $insert->insert_id;
    $insert->close();
}

// --- Ambil data final untuk response ---
$result = $conn->query("SELECT * FROM kejadian WHERE id = $incident_id")->fetch_assoc();

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
    "message" => "Kejadian berhasil diproses",
    "data" => $result
]);

$conn->close();
?>
