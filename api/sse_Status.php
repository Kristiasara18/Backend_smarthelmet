<?php
set_time_limit(0);
ignore_user_abort(true);

header("Access-Control-Allow-Origin: *");
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

require_once __DIR__ . '/../lib/koneksi.php';

function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    @ob_flush();
    @flush();
}

while (true) {

    if (connection_aborted()) break;

    // Ambil daftar device aktif unik dari tabel kejadian (JOIN pekerja)
    $devices = [];
    $resDevices = $conn->query("
        SELECT DISTINCT 
            p.device_id,
            p.nama_pekerja,
            k.lokasi,
            k.id_pekerja,
            k.aktif
        FROM kejadian k
        LEFT JOIN pekerja p ON p.id_pekerja = k.id_pekerja
        WHERE k.aktif = 1
    ");

    while ($d = $resDevices->fetch_assoc()) {
        $devices[] = $d;
    }

    $statusData = [];

    foreach ($devices as $dev) {

        // Ambil kejadian terakhir untuk pekerja/device tersebut
        $resLast = $conn->query("
            SELECT status, waktu 
            FROM kejadian 
            WHERE id_pekerja = '{$dev['id_pekerja']}'
            ORDER BY waktu DESC 
            LIMIT 1
        ");

        $last = $resLast->fetch_assoc();

        $statusData[] = [
            'nama_pekerja' => $dev['nama_pekerja'],
            'device_id'    => $dev['device_id'],
            'lokasi'       => $dev['lokasi'],
            'status'       => $last ? $last['status'] : 'Aman',
            'waktu'        => $last ? $last['waktu'] : date('Y-m-d H:i:s')
        ];
    }

    sendSSE($statusData);

    sleep(3);
}
?>
