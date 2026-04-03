<?php
include("../config/db.php");

header('Content-Type: application/json');

$measure_date = $_POST['measure_date'] ?? date("Y-m-d");
$measure_time = $_POST['measure_time'] ?? date("H:i:s");

$air_temp    = $_POST['air_temp'] ?? null;
$humidity    = $_POST['humidity'] ?? null;
$water_temp  = $_POST['water_temp'] ?? null;
$light_lux   = $_POST['light_lux'] ?? null;
$distance_cm = $_POST['distance_cm'] ?? null;
$wind_speed  = $_POST['wind_speed'] ?? null;
$eto         = $_POST['eto'] ?? null;
$etc_value   = $_POST['etc_value'] ?? null;

$sql = "INSERT INTO measurements 
(measure_date, measure_time, air_temp, humidity, water_temp, light_lux, distance_cm, wind_speed, eto, etc_value) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssdddddddd",
    $measure_date,
    $measure_time,
    $air_temp,
    $humidity,
    $water_temp,
    $light_lux,
    $distance_cm,
    $wind_speed,
    $eto,
    $etc_value
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Measurement saved successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}
?>