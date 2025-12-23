<?php
require_once __DIR__ . '/../lib/koneksi.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json")

header('Content-Type: application/json');
date_default_timezone_set("Asia/Jakarta");

$sql = "
    SELECT COUNT(*) AS total_insiden
    FROM kejadian
    WHERE tanggal = CURDATE()
      AND status = 'SOS'
";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo json_encode([
    "status" => "success",
    "data" => [
        "total_insiden" => (int)$row['total_insiden']
    ]
]);
