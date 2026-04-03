<?php
include("../config/db.php");

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;

$sql = "SELECT * FROM measurements WHERE 1=1";

if ($year) {
    $sql .= " AND YEAR(measure_date)=" . intval($year);
}
if ($month) {
    $sql .= " AND MONTH(measure_date)=" . intval($month);
}

$sql .= " ORDER BY measure_date, measure_time";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>