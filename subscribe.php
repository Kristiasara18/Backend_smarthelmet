<?php
/**********************************************************
 * Smart Helmet MQTT Subscriber
 * - Menerima data dari MQTT
 * - Update status_helmet
 * - Insert INSIDEN hanya jika KONDISI BAHAYA
 **********************************************************/

require_once __DIR__ . '/lib/koneksi.php';
require_once __DIR__ . '/lib/phpMQTT.php';

use Bluerhinos\phpMQTT;

/* =======================
   KONFIGURASI MQTT
   ======================= */
$server   = "test.mosquitto.org";
$port     = 1883;
$clientID = "SmartHelmetSub_" . uniqid();

/* =======================
   INISIALISASI MQTT
   ======================= */
$mqtt = new phpMQTT($server, $port, $clientID);

if (!$mqtt->connect(true, NULL, "", "")) {
    file_put_contents("/var/log/smarthelmet-mqtt.log", "MQTT connect failed\n", FILE_APPEND);
    exit;
}

file_put_contents("/var/log/smarthelmet-mqtt.log", "Subscriber MQTT aktif\n", FILE_APPEND);

/* =======================
   SUBSCRIBE TOPIC
   ======================= */
$topics = [
    "smarthelmet/data" => [
        "qos" => 0,
        "function" => "handleMessage"
    ]
];

$mqtt->subscribe($topics, 0);

/* =======================
   LOOP MQTT
   ======================= */
while ($mqtt->proc()) {
    usleep(100000); // 0.1 detik
}

$mqtt->close();

/* ======================================================
   CALLBACK HANDLER MQTT
   ====================================================== */
function handleMessage($topic, $msg) {
    global $conn;

    /* ---------- DEBUG LOG ---------- */
    file_put_contents(
        __DIR__ . "/mqtt_debug.log",
        date("Y-m-d H:i:s") . " | $topic | $msg\n",
        FILE_APPEND
    );

    /* ---------- PARSE JSON ---------- */
    $data = json_decode($msg, true);
    if (!$data || !is_array($data)) {
        return;
    }

    /* ---------- DATA WAJIB ---------- */
    $kode_helmet = $data['kode_helmet'] ?? null;
    if (!$kode_helmet) {
        return;
    }

    /* ---------- DATA STATUS HELMET ---------- */
    $status  = $data['status'] ?? 'ON';            // ON / OFF (bukan insiden)
    $kondisi = strtoupper($data['kondisi'] ?? 'NORMAL'); // NORMAL / DANGER / FALL / SOS

    $lat = $data['gps']['lat'] ?? null;
    $lng = $data['gps']['lng'] ?? null;

    /* ==================================================
       SIMPAN / UPDATE STATUS HELMET
       ================================================== */
    $stmt = $conn->prepare("
        INSERT INTO status_helmet
            (kode_helmet, last_seen, kondisi, status, latitude, longitude)
        VALUES (?, NOW(), ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            last_seen = NOW(),
            kondisi   = VALUES(kondisi),
            status    = VALUES(status),
            latitude  = VALUES(latitude),
            longitude = VALUES(longitude)
    ");

    if (!$stmt) return;

    $stmt->bind_param(
        "sssdd",
        $kode_helmet,
        $kondisi,
        $status,
        $lat,
        $lng
    );

    $stmt->execute();
    $stmt->close();

    /* ==================================================
       LOGIKA INSIDEN (INTI SISTEM)
       ================================================== */

    // DEFINISI KONDISI BERBAHAYA
    $INSIDEN_KONDISI = ['SOS'];

    // Jika bukan bahaya → STOP
    if (!in_array($kondisi, $INSIDEN_KONDISI)) {
        return;
    }

    /* ---------- CARI ID PEKERJA ---------- */
    $qPekerja = $conn->prepare("
        SELECT id_pekerja
        FROM karyawan
        WHERE kode_helmet = ?
        LIMIT 1
    ");
    if (!$qPekerja) return;

    $qPekerja->bind_param("s", $kode_helmet);
    $qPekerja->execute();
    $resPekerja = $qPekerja->get_result();
    $pekerja = $resPekerja->fetch_assoc();
    $qPekerja->close();

    if (!$pekerja) {
        // helmet belum terdaftar
        return;
    }

    $id_pekerja = $pekerja['id_pekerja'];

    /* ---------- CEK INSIDEN AKTIF ---------- */
    $qCek = $conn->prepare("
        SELECT id
        FROM kejadian
        WHERE id_pekerja = ?
          AND status = ?
          AND handled = 0
          AND aktif = 1
        LIMIT 1
    ");
    if (!$qCek) return;

    $qCek->bind_param("is", $id_pekerja, $kondisi);
    $qCek->execute();
    $resCek = $qCek->get_result();

    if ($resCek->num_rows > 0) {
        // Masih ada insiden aktif → jangan insert lagi
        $qCek->close();
        return;
    }
    $qCek->close();

    /* ---------- INSERT INSIDEN BARU ---------- */
    $qInsert = $conn->prepare("
        INSERT INTO kejadian
            (id_pekerja, waktu, tanggal, status, handled, aktif)
        VALUES (?, NOW(), CURDATE(), ?, 0, 1)
    ");
    if (!$qInsert) return;

    $qInsert->bind_param("is", $id_pekerja, $kondisi);
    $qInsert->execute();
    $qInsert->close();
}
