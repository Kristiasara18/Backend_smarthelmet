<?php
set_time_limit(0);
ignore_user_abort(true);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

include "../koneksi.php";

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

$conn->close();
?>
