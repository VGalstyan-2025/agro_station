<?php
include("../config/db.php");

header('Content-Type: application/json');

$sql = "SELECT 
            measure_date,
            AVG(eto) as eto,
            AVG(etc_value) as etc_value
        FROM measurements
        GROUP BY measure_date
        ORDER BY measure_date DESC
        LIMIT 4";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$data = array_reverse($data);

echo json_encode($data);
?>