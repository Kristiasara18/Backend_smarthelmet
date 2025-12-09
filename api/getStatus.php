<?php
header('Content-Type: application/json');
$koneksi = new mysqli("localhost", "root", "", "db_karyawan");

if ($koneksi->connect_error) {
    die(json_encode(["error" => "Koneksi gagal: " . $koneksi->connect_error]));
}

// Ambil semua kejadian aktif (helmet + non-helmet)
$query = "
    SELECT 
        k.nama_pekerja,
        j.lokasi,
        j.status,
        j.waktu,
        j.catatan,
        j.id_tipe,
        j.jumlah_kejadian
    FROM kejadian j
    LEFT JOIN karyawan k ON j.id_pekerja = k.id
    WHERE j.aktif = 1
    ORDER BY j.waktu DESC
";

$result = $koneksi->query($query);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
