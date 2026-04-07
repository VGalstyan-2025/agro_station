<?php
include("../config/db.php");

if (
    isset($_POST['target_date']) &&
    isset($_POST['actual_eto']) &&
    isset($_POST['actual_etc'])
) {
    $target_date = $_POST['target_date'];
    $actual_eto = floatval($_POST['actual_eto']);
    $actual_etc = floatval($_POST['actual_etc']);

    // Բոլոր forecast-ները այս target_date-ի համար
    $sql = "SELECT id, forecast_eto, forecast_etc
            FROM forecast_history
            WHERE target_date = ?
            ORDER BY predicted_on ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $target_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        while ($row = $result->fetch_assoc()) {

            $id = intval($row['id']);
            $forecast_eto = floatval($row['forecast_eto']);
            $forecast_etc = floatval($row['forecast_etc']);

            // ===== ETo =====
            $abs_error_eto = abs($actual_eto - $forecast_eto);

            if ($actual_eto > 0 || $forecast_eto > 0) {
                $base_eto = max($actual_eto, $forecast_eto);

                if ($base_eto > 0) {
                    $error_percent_eto = ($abs_error_eto / $base_eto) * 100.0;
                    $accuracy_percent_eto = 100.0 - $error_percent_eto;

                    if ($accuracy_percent_eto < 0) {
                        $accuracy_percent_eto = 0;
                    }
                } else {
                    $error_percent_eto = 0;
                    $accuracy_percent_eto = 0;
                }
            } else {
                $error_percent_eto = 0;
                $accuracy_percent_eto = 0;
            }

            // ===== ETc =====
            $abs_error_etc = abs($actual_etc - $forecast_etc);

            if ($actual_etc > 0 || $forecast_etc > 0) {
                $base_etc = max($actual_etc, $forecast_etc);

                if ($base_etc > 0) {
                    $error_percent_etc = ($abs_error_etc / $base_etc) * 100.0;
                    $accuracy_percent_etc = 100.0 - $error_percent_etc;

                    if ($accuracy_percent_etc < 0) {
                        $accuracy_percent_etc = 0;
                    }
                } else {
                    $error_percent_etc = 0;
                    $accuracy_percent_etc = 0;
                }
            } else {
                $error_percent_etc = 0;
                $accuracy_percent_etc = 0;
            }

            // ===== UPDATE =====
            $update = "UPDATE forecast_history SET
                        actual_eto = ?,
                        actual_etc = ?,
                        abs_error_eto = ?,
                        error_percent_eto = ?,
                        accuracy_percent_eto = ?,
                        abs_error_etc = ?,
                        error_percent_etc = ?,
                        accuracy_percent_etc = ?
                       WHERE id = ?";

            $stmt2 = $conn->prepare($update);
            $stmt2->bind_param(
                "ddddddddi",
                $actual_eto,
                $actual_etc,
                $abs_error_eto,
                $error_percent_eto,
                $accuracy_percent_eto,
                $abs_error_etc,
                $error_percent_etc,
                $accuracy_percent_etc,
                $id
            );

            $stmt2->execute();
            $stmt2->close();
        }

        echo "✅ Accuracy updated for ALL forecasts (target_date = $target_date)";
    } else {
        echo "❌ No forecasts found for target_date = $target_date";
    }

    $stmt->close();
} else {
    echo "❌ Missing parameters";
}

$conn->close();
?>