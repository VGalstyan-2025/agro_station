<?php
include("../config/db.php");

if (
    isset($_POST['target_date']) &&
    isset($_POST['predicted_on']) &&
    isset($_POST['forecast_eto']) &&
    isset($_POST['forecast_etc'])
) {
    $target_date = $_POST['target_date'];
    $predicted_on = $_POST['predicted_on'];
    $forecast_eto = floatval($_POST['forecast_eto']);
    $forecast_etc = floatval($_POST['forecast_etc']);

    $sql = "INSERT INTO forecast_history 
            (target_date, predicted_on, forecast_eto, forecast_etc)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            forecast_eto = VALUES(forecast_eto),
            forecast_etc = VALUES(forecast_etc)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdd", $target_date, $predicted_on, $forecast_eto, $forecast_etc);

    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "DB Error";
    }

    $stmt->close();
} else {
    echo "Missing parameters";
}

$conn->close();
?>