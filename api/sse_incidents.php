<?php
set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once __DIR__ . '/../lib/koneksi.php';
require_once __DIR__ . '/../lib/phpMQTT.php';

// Helper kirim SSE
function sendSSE($id, $data) {
    echo "id: {$id}\n";
    echo "data: {$data}\n\n";
    @ob_flush();
    @flush();
}

// Ambil last_id awal dari tabel `kejadian`
$last_id = 0;
$res = $conn->query("SELECT MAX(id) AS mx FROM kejadian");
if ($res) {
    $r = $res->fetch_assoc();
    $last_id = intval($r['mx']);
}

// Loop memantau perubahan database
while (true) {

    if (connection_aborted()) break;

    $stmt = $conn->prepare("
        SELECT *
        FROM kejadian
        WHERE id > ? 
          AND handled = 0
          AND aktif = 1
        ORDER BY id ASC
    ");
    $stmt->bind_param("i", $last_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika ada data baru → kirim SSE
    while ($row = $result->fetch_assoc()) {
        $last_id = intval($row['id']);
        sendSSE($last_id, json_encode($row));
    }

    sleep(1);
}

$mqtt = new phpMQTT($server, $port, $client_id);

if ($mqtt->connect(true, NULL, $username, $password)) {

    $topic = "helmet/incident";

    $payload = json_encode([
        "device_id" => $device_id,
        "id_pekerja" => $id_pekerja,
        "lokasi" => $lokasi,
        "status" => $status,
        "catatan" => $catatan,
        "waktu" => date("Y-m-d H:i:s"),
        "incident_id" => $incident_id
    ]);

    $mqtt->publish($topic, $payload, 0);
    $mqtt->close();
}

$conn->close();
?>
