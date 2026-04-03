<?php
include("../config/db.php");

// Օրվա սկիզբ
$day = date('Y-m-d');

// Հաշվել միջինները
$sql = "SELECT 
            AVG(air_temp) as air_temp_avg,
            AVG(humidity) as humidity_avg,
            AVG(water_temp) as water_temp_avg,
            AVG(light_lux) as light_avg,
            AVG(distance_cm) as distance_avg,
            AVG(wind_speed) as wind_avg,
            AVG(eto) as eto_avg,
            AVG(etc_value) as etc_avg
        FROM measurements
        WHERE measure_date='$day'";

$result = $conn->query($sql);
$summary = $result->fetch_assoc();

// Ստուգել, որ տվյալներ կան
if ($summary && !empty($summary['air_temp_avg'])) {
    $stmt = $conn->prepare("INSERT INTO measurements_summary 
        (measure_date, air_temp_avg, humidity_avg, water_temp_avg, light_avg, distance_avg, wind_avg, eto_avg, etc_avg) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            air_temp_avg=VALUES(air_temp_avg),
            humidity_avg=VALUES(humidity_avg),
            water_temp_avg=VALUES(water_temp_avg),
            light_avg=VALUES(light_avg),
            distance_avg=VALUES(distance_avg),
            wind_avg=VALUES(wind_avg),
            eto_avg=VALUES(eto_avg),
            etc_avg=VALUES(etc_avg)
    ");
    $stmt->bind_param(
        "sdddddddd",
        $day,
        $summary['air_temp_avg'],
        $summary['humidity_avg'],
        $summary['water_temp_avg'],
        $summary['light_avg'],
        $summary['distance_avg'],
        $summary['wind_avg'],
        $summary['eto_avg'],
        $summary['etc_avg']
    );
    $stmt->execute();
    echo "Daily summary saved!";
} else {
    echo "No measurements today.";
}
?>