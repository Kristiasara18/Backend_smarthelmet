<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$koneksi = new mysqli("localhost", "root", "", "db_karyawan");
if ($koneksi->connect_error) {
    die(json_encode(["error" => "Koneksi gagal: " . $koneksi->connect_error]));
}

// Generate daftar tanggal (7 hari terakhir)
$dates_query = "
    SELECT CURDATE() - INTERVAL 6 DAY AS tanggal UNION ALL
    SELECT CURDATE() - INTERVAL 5 DAY UNION ALL
    SELECT CURDATE() - INTERVAL 4 DAY UNION ALL
    SELECT CURDATE() - INTERVAL 3 DAY UNION ALL
    SELECT CURDATE() - INTERVAL 2 DAY UNION ALL
    SELECT CURDATE() - INTERVAL 1 DAY UNION ALL
    SELECT CURDATE()
";

// Query utama (pakai tabel kejadian)
$query = "
SELECT 
    dates.tanggal,
    status_list.status AS tipe_kejadian,
    IFNULL(COUNT(k.id), 0) AS jumlah_insiden
FROM 
    ($dates_query) AS dates
CROSS JOIN
    (SELECT DISTINCT status FROM kejadian WHERE aktif = 1) AS status_list
LEFT JOIN kejadian k
    ON DATE(k.waktu) = dates.tanggal
    AND k.status = status_list.status
    AND k.aktif = 1
GROUP BY dates.tanggal, status_list.status
ORDER BY dates.tanggal DESC, status_list.status ASC
";

$result = $koneksi->query($query);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo json_encode(["error" => $koneksi->error]);
    exit;
}

echo json_encode($data);
?>
