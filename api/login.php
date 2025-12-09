<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json");

include "../koneksi.php";

// 🟢 Tambahkan debug ini PERSIS DI SINI
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode([
    "status" => "error",
    "message" => "Gunakan metode POST, bukan GET",
    "method" => $_SERVER['REQUEST_METHOD']
  ]);
  exit;
}

// 🟢 Handle preflight (penting untuk React)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// 🟢 Baca raw input JSON
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

// 🧠 Debug fallback — kirim ke frontend biar kita tahu apa yang diterima
if (!$input) {
  echo json_encode([
    "status" => "error",
    "message" => "Data tidak lengkap",
    "debug_raw" => $raw,
    "debug_server" => $_SERVER,
  ]);
  exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if ($email && $password) {
  $email_safe = mysqli_real_escape_string($conn, $email);
  $pass_safe = mysqli_real_escape_string($conn, $password);

  $query = mysqli_query($conn, "SELECT * FROM user_login WHERE email='$email_safe' AND password='$pass_safe'");
  if (mysqli_num_rows($query) > 0) {
    $user = mysqli_fetch_assoc($query);
    echo json_encode(["status" => "success", "user" => $user]);
  } else {
    echo json_encode(["status" => "error", "message" => "Email atau password salah!"]);
  }
} else {
  echo json_encode([
    "status" => "error",
    "message" => "Data tidak lengkap (tidak terbaca email/password)",
    "debug" => $input,
  ]);
}
?>
