<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once __DIR__ . '/../../lib/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!is_array($data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Body JSON tidak valid",
        "raw" => $raw
    ]);
    exit;
}

$nama = trim($data['nama'] ?? '');
$jabatan = trim($data['jabatan'] ?? '');
$kode_helmet = trim($data['kode_helmet'] ?? '');

if ($nama === '' || $jabatan === '' || $kode_helmet === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Data tidak lengkap",
        "data" => $data
    ]);
    exit;
}

// escape (minimal safety)
$nama = mysqli_real_escape_string($conn, $nama);
$jabatan = mysqli_real_escape_string($conn, $jabatan);
$kode_helmet = mysqli_real_escape_string($conn, $kode_helmet);

$query = "
INSERT INTO karyawan (nama, jabatan, kode_helmet)
VALUES ('$nama', '$jabatan', '$kode_helmet')
";

if ($conn->query($query)) {
    echo json_encode([
        "status" => "success",
        "message" => "Data karyawan berhasil ditambahkan"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
}
