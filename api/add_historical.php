<?php
include("../config/db.php");

$year = $_POST['year'] ?? null;
$month = $_POST['month'] ?? null;
$day = $_POST['day'] ?? null;

$avg_temp = $_POST['avg_temp'] ?? null;
$max_temp = $_POST['max_temp'] ?? null;
$min_temp = $_POST['min_temp'] ?? null;
$pressure = $_POST['pressure'] ?? null;
$humidity = $_POST['humidity'] ?? null;
$wind_max = $_POST['wind_max'] ?? null;
$cloudiness = $_POST['cloudiness'] ?? null;
$precipitation = $_POST['precipitation'] ?? null;
$sun_hours = $_POST['sun_hours'] ?? null;

$id = $_POST['id'] ?? null;

header('Content-Type: application/json');

if ($id) {
    $stmt = $conn->prepare("UPDATE historical_weather SET
        avg_temp=?, max_temp=?, min_temp=?, pressure=?, humidity=?, wind_max=?, cloudiness=?, precipitation=?, sun_hours=?
        WHERE id=?");
    $stmt->bind_param(
        "dddddddddi",
        $avg_temp, $max_temp, $min_temp,
        $pressure, $humidity, $wind_max,
        $cloudiness, $precipitation, $sun_hours,
        $id
    );
} else {
    $stmt = $conn->prepare("INSERT INTO historical_weather 
        (year, month, day, avg_temp, max_temp, min_temp, pressure, humidity, wind_max, cloudiness, precipitation, sun_hours)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "iiiddddddddd",
        $year, $month, $day,
        $avg_temp, $max_temp, $min_temp,
        $pressure, $humidity, $wind_max,
        $cloudiness, $precipitation, $sun_hours
    );
}

if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}
?>