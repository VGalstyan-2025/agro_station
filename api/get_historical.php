<?php
include("../config/db.php");

$year = $_GET['year'] ?? null;
$month = $_GET['month'] ?? null;

$sql = "SELECT * FROM historical_weather WHERE 1=1";

if ($year) $sql .= " AND year=" . intval($year);
if ($month) $sql .= " AND month=" . intval($month);

$sql .= " ORDER BY year, month, day";

$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>