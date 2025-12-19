<?php
require("phpMQTT.php");
require("koneksi.php");

// Konfigurasi broker MQTT (bisa disesuaikan)
$server   = "test.mosquitto.org";   // broker MQTT
$port     = 1883;                   // port MQTT non-TLS
$username = "";                     // kosong jika tidak pakai auth
$password = "";
$clientID = "SmartHelmetSubskriber_".uniqid();

// Topik IoT
$topic = "smarthelmet/data";

// Membuat instance MQTT
$mqtt = new phpMQTT($server, $port, $clientID);

if(!$mqtt->connect(true, NULL, $username, $password)){
    die("⚠ Gagal terhubung ke MQTT broker...");
}

// Callback saat pesan diterima
$mqtt->subscribe([$topic => ["qos" => 0, "function" => "handleMessage"]]);

while($mqtt->proc()){
    // loop agar subscriber tetap hidup
}

$mqtt->close();


// =========================
// Handler pesan dari IoT
// =========================
function handleMessage($topic, $msg){
    global $conn;

    echo "Pesan diterima dari IoT:\n";
    echo "Topic: $topic\n";
    echo "Payload: $msg\n\n";

    // IoT biasanya kirim JSON, misalnya:
    // {"status":"normal", "ax":123, "ay":456, ...}

    $data = json_decode($msg, true);

    if($data === null){
        echo "❌ ERROR: payload bukan JSON\n";
        return;
    }

    // --- contoh simpan ke database ---
    // Sesuaikan dengan tabel kamu

    $status = $data["status"] ?? null;
    $ax     = $data["mpu"]["ax"] ?? null;
    $ay     = $data["mpu"]["ay"] ?? null;
    $az     = $data["mpu"]["az"] ?? null;

    $sql = "INSERT INTO kejadian_helmet (status, ax, ay, az) 
            VALUES ('$status', '$ax', '$ay', '$az')";

    if ($conn->query($sql)) {
        echo "✔ Data berhasil disimpan ke database\n";
    } else {
        echo "❌ Gagal simpan data: " . $conn->error . "\n";
    }
}
?>
