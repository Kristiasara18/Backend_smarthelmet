<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_karyawan";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi gagal: " . $conn->connect_error]));
}

// ===========================
//  TOTAL KEJADIAN PER TANGGAL
// ===========================
$sqlHarian = "
    SELECT tanggal, SUM(jumlah_kejadian) AS total
    FROM kejadian
    WHERE aktif = 1
    GROUP BY tanggal
    ORDER BY tanggal ASC
";

$resultHarian = $conn->query($sqlHarian);
$dataHarian = [];

if ($resultHarian) {
    while ($row = $resultHarian->fetch_assoc()) {
        $dataHarian[] = [
            "tanggal" => $row["tanggal"],
            "total" => (int)$row["total"],
        ];
    }
}

// ===========================
//  TOTAL KEJADIAN PER BULAN
// ===========================
$sqlBulanan = "
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan, SUM(jumlah_kejadian) AS total
    FROM kejadian
    WHERE aktif = 1
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan ASC
";

$resultBulanan = $conn->query($sqlBulanan);
$dataBulanan = [];

if ($resultBulanan) {
    while ($row = $resultBulanan->fetch_assoc()) {
        $dataBulanan[] = [
            "bulan" => $row["bulan"],
            "total" => (int)$row["total"],
        ];
    }
}

// ===========================
//  OUTPUT JSON
// ===========================
echo json_encode([
    "harian" => $dataHarian,
    "bulanan" => $dataBulanan
]);

$conn->close();
?>
