<?php
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    $measure_date = $_POST['measure_date'];
    $measure_time = $_POST['measure_time'];
    $air_temp = $_POST['air_temp'];
    $humidity = $_POST['humidity'];
    $water_temp = $_POST['water_temp'];
    $light_lux = $_POST['light_lux'];
    $distance_cm = $_POST['distance_cm'];
    $wind_speed = $_POST['wind_speed'];
    $eto = $_POST['eto'];
    $etc_value = $_POST['etc_value'];

    $sql = "UPDATE measurements SET
            measure_date=?, measure_time=?, air_temp=?, humidity=?, water_temp=?, light_lux=?, distance_cm=?, wind_speed=?, eto=?, etc_value=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssddddddddi",
        $measure_date, $measure_time, $air_temp, $humidity, $water_temp,
        $light_lux, $distance_cm, $wind_speed, $eto, $etc_value, $id
    );

    $stmt->execute();
}

header("Location: ../admin/measurements_form.php");
exit;
?>