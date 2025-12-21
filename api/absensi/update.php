<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json");
require_once __DIR__ . '/../../lib/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

$id = $input['id'] ?? null;
$kehadiran = $input['kehadiran'] ?? null;

if (!$id || !$kehadiran) {
    echo json_encode([
        "status" => "error",
        "message" => "ID atau kehadiran tidak lengkap",
        "debug" => $input
    ]);
    exit;
}

$id_safe = mysqli_real_escape_string($conn, $id);
$kehadiran_safe = mysqli_real_escape_string($conn, $kehadiran);

$query = "UPDATE absensi SET kehadiran='$kehadiran_safe' WHERE id_absensi='$id_safe'";

if ($conn->query($query)) {
    echo json_encode([
        "status" => "success",
        "message" => "Kehadiran berhasil diperbarui"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal memperbarui kehadiran"
    ]);
}

$conn->close();
?>
