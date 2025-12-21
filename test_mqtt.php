<?php
require "phpMQTT.php";

$mqtt = new phpMQTT("test.mosquitto.org", 1883, "TestPHP_".uniqid());

if ($mqtt->connect(true)) {
    echo "MQTT CONNECT OK\n";
    $mqtt->close();
} else {
    echo "MQTT FAILED\n";
}
