<?php
include("../config/db.php");

$sql = "SELECT 
            target_date,
            forecast_eto,
            actual_eto,
            abs_error_eto,
            error_percent_eto,
            accuracy_percent_eto,
            forecast_etc,
            actual_etc,
            abs_error_etc,
            error_percent_etc,
            accuracy_percent_etc
        FROM forecast_history
        WHERE actual_eto IS NOT NULL AND actual_eto > 0
        GROUP BY target_date
        ORDER BY target_date ASC
        LIMIT 4";

$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(array_reverse($data));
$conn->close();
?>