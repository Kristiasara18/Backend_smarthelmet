<?php
require_once __DIR__ . '/lib/koneksi.php';      // Koneksi ke MySQL
require_once __DIR__ . '/lib/phpMQTT.php';      // phpMQTT klasik

use Bluerhinos\phpMQTT;

// -------------------- MQTT CONFIG --------------------
$server   = "test.mosquitto.org";
$port     = 1883;

// Generate clientID unik
$clientID = "SmartHelmetSub_" . uniqid();

// -------------------- CREATE MQTT CLIENT --------------------
$mqtt = new phpMQTT($server, $port, $clientID);

if(!$mqtt->connect(true, NULL, "", "")){
    die("Gagal konek MQTT\n");
}

echo "Subscriber MQTT aktif, menunggu data...\n";

// -------------------- AMBIL DAFTAR TOPIC DARI USERS --------------------
$users = [];
$result = $conn->query("SELECT id, device_topic FROM users");
while($row = $result->fetch_assoc()){
    $users[$row['device_topic']] = $row['id']; // mapping topic ke user_id
}

// -------------------- SUBSCRIBE --------------------
$topics = [];
foreach(array_keys($users) as $topic){
    $topics[$topic] = ["qos" => 0, "function" => "handleMessage"];
}

$mqtt->subscribe($topics);

// -------------------- LOOP --------------------
while($mqtt->proc()){}

// -------------------- CLOSE CONNECTION --------------------
$mqtt->close();

// -------------------- CALLBACK FUNCTION --------------------
function handleMessage($topic, $msg){
    global $conn, $users;

    // Cek user_id dari topic
    $user_id = $users[$topic] ?? null;
    if(!$user_id){
        echo "Topic $topic tidak terdaftar di users\n";
        return;
    }

    // LOG FILE
    file_put_contents(
        __DIR__ . "/mqtt_log.txt",
        date("Y-m-d H:i:s") . " | Topic: $topic | $msg" . PHP_EOL,
        FILE_APPEND
    );

    $data = json_decode($msg, true);
    if(!$data){
        echo "JSON error dari topic $topic\n";
        return;
    }

    $status = $data["status"] ?? null;
    $ax = $data["mpu"]["ax"] ?? null;
    $ay = $data["mpu"]["ay"] ?? null;
    $az = $data["mpu"]["az"] ?? null;

    $stmt = $conn->prepare(
        "INSERT INTO kejadian(user_id, status, ax, ay, az) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isiii", $user_id, $status, $ax, $ay, $az);

    if($stmt->execute()){
        echo "Data masuk DB untuk user_id $user_id\n";
    } else {
        echo "DB error: " . $stmt->error . "\n";
    }
}
