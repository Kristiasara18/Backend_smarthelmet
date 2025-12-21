<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require_once __DIR__ . '/../lib/koneksi.php';

// ✅ HANDLE PREFLIGHT DULU
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// ❌ BLOK GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode([
    "status" => "error",
    "message" => "Gunakan metode POST",
    "method" => $_SERVER['REQUEST_METHOD']
  ]);
  exit;
}

// ✅ BACA JSON
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!$input) {
  echo json_encode([
    "status" => "error",
    "message" => "JSON tidak terbaca",
    "raw" => $raw
  ]);
  exit;
}

$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if ($email === '' || $password === '') {
  echo json_encode([
    "status" => "error",
    "message" => "Email dan password wajib diisi"
  ]);
  exit;
}

$email_safe = mysqli_real_escape_string($conn, $email);
$password_safe = mysqli_real_escape_string($conn, $password);

$query = mysqli_query(
  $conn,
  "SELECT id, name, email FROM user_login 
   WHERE email='$email_safe' AND password='$password_safe'"
);

if (mysqli_num_rows($query) > 0) {
  echo json_encode([
    "status" => "success",
    "user" => mysqli_fetch_assoc($query)
  ]);
} else {
  echo json_encode([
    "status" => "error",
    "message" => "Email atau password salah"
  ]);
}
