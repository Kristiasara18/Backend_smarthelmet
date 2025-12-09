<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include "../koneksi.php";

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

$conn->close();
?>
