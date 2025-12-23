<?php
require_once __DIR__ . '/../lib/koneksi.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

set_time_limit(0);
date_default_timezone_set("Asia/Jakarta");

// Simpan ID terakhir yang sudah dikirim
$lastSentId = 0;

while (true) {
    echo ": ping\n\n";
    flush();

    // Ambil 1 insiden terbaru yang belum handled & aktif
    $stmt = $conn->prepare("
        SELECT 
            k.id,
            k.waktu,
            k.status,
            k.lokasi,
            k.catatan,
            k.id_pekerja,
            p.nama AS nama_pekerja,
            p.kode_helmet
        FROM kejadian k
        JOIN karyawan p ON k.id_pekerja = p.id_pekerja
        WHERE k.handled = 0
          AND k.aktif = 1
          AND k.status = 'SOS'
          AND k.id > ?
        ORDER BY k.id ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $lastSentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $lastSentId = (int)$row['id'];

        echo "id: {$row['id']}\n";
        echo "data: " . json_encode([
            "id"            => $row['id'],
            "nama_pekerja"  => $row['nama_pekerja'],
            "kode_helmet"   => $row['kode_helmet'],
            "status"        => $row['status'],
            "waktu"         => $row['waktu'],
            "lokasi"        => $row['lokasi'],
            "catatan"       => $row['catatan'],
        ]) . "\n\n";

        ob_flush();
        flush();
    }

    // Jeda kecil agar tidak membebani server
    sleep(1);
}
