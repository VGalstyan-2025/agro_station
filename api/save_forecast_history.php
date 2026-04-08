<?php
include("../config/db.php");

function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (
    isset($_POST['target_date']) &&
    isset($_POST['predicted_on']) &&
    isset($_POST['forecast_eto']) &&
    isset($_POST['forecast_etc'])
) {
    $target_date  = trim($_POST['target_date']);
    $predicted_on = trim($_POST['predicted_on']);
    $forecast_eto = floatval($_POST['forecast_eto']);
    $forecast_etc = floatval($_POST['forecast_etc']);

    error_log("save_forecast_history.php called");
    error_log("target_date = [$target_date]");
    error_log("predicted_on = [$predicted_on]");
    error_log("forecast_eto = [$forecast_eto]");
    error_log("forecast_etc = [$forecast_etc]");

    if (!isValidDate($target_date)) {
        die("❌ Invalid target_date: [$target_date]");
    }

    if (!isValidDate($predicted_on)) {
        die("❌ Invalid predicted_on: [$predicted_on]");
    }

    if ($forecast_eto <= 0) {
        die("❌ Invalid forecast_eto");
    }

    $sql = "INSERT INTO forecast_history 
            (target_date, predicted_on, forecast_eto, forecast_etc)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            forecast_eto = VALUES(forecast_eto),
            forecast_etc = VALUES(forecast_etc)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssdd", $target_date, $predicted_on, $forecast_eto, $forecast_etc);

    if ($stmt->execute()) {
        echo "OK";
    } else {
        echo "❌ DB Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "❌ Missing parameters";
}

$conn->close();
?>