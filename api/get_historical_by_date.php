<?php
include("../config/db.php");

$year = $_GET['year'];
$month = $_GET['month'];
$day = $_GET['day'];

$result = $conn->query("
    SELECT * FROM historical_weather 
    WHERE year=$year AND month=$month AND day=$day
    LIMIT 1
");

if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(null);
}