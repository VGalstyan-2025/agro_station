<?php
include("../config/db.php");

header('Content-Type: application/json');

$sqlDates = "SELECT DISTINCT target_date
             FROM forecast_history
             WHERE actual_eto IS NOT NULL
             AND target_date <= CURDATE()
             ORDER BY target_date DESC
             LIMIT 4";

$resultDates = mysqli_query($conn, $sqlDates);

$targetDates = [];
while ($row = mysqli_fetch_assoc($resultDates)) {
    $targetDates[] = $row['target_date'];
}

$targetDates = array_reverse($targetDates);

$output = [];

foreach ($targetDates as $date) {

    $sql = "SELECT 
                target_date,
                predicted_on,
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
            WHERE target_date = '$date'
            ORDER BY predicted_on ASC";

    $result = mysqli_query($conn, $sql);

    $forecasts = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $forecasts[] = [
            "predicted_on" => $row["predicted_on"],
            "forecast_eto" => (float)$row["forecast_eto"],
            "actual_eto" => (float)$row["actual_eto"],
            "abs_error_eto" => (float)$row["abs_error_eto"],
            "error_percent_eto" => (float)$row["error_percent_eto"],
            "accuracy_percent_eto" => (float)$row["accuracy_percent_eto"],

            "forecast_etc" => (float)$row["forecast_etc"],
            "actual_etc" => (float)$row["actual_etc"],
            "abs_error_etc" => (float)$row["abs_error_etc"],
            "error_percent_etc" => (float)$row["error_percent_etc"],
            "accuracy_percent_etc" => (float)$row["accuracy_percent_etc"]
        ];
    }

    $output[] = [
        "target_date" => $date,
        "forecasts" => $forecasts
    ];
}

echo json_encode($output, JSON_PRETTY_PRINT);
?>