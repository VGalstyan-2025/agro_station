<?php
include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);

    $year = $_POST['year'];
    $month = $_POST['month'];
    $day = $_POST['day'];

    $avg_temp = $_POST['avg_temp'];
    $max_temp = $_POST['max_temp'];
    $min_temp = $_POST['min_temp'];
    $pressure = $_POST['pressure'];
    $humidity = $_POST['humidity'];
    $wind_max = $_POST['wind_max'];
    $cloudiness = $_POST['cloudiness'];
    $precipitation = $_POST['precipitation'];
    $sun_hours = $_POST['sun_hours'];

    $sql = "UPDATE historical_weather SET
            year=?, month=?, day=?, avg_temp=?, max_temp=?, min_temp=?, pressure=?, humidity=?, wind_max=?, cloudiness=?, precipitation=?, sun_hours=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iiidddddddddi",
        $year, $month, $day, $avg_temp, $max_temp, $min_temp, $pressure, $humidity,
        $wind_max, $cloudiness, $precipitation, $sun_hours, $id
    );

    $stmt->execute();
}

header("Location: ../admin/historical_form.php");
exit;
?>