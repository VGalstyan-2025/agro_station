<?php
include("../config/db.php");

header('Content-Type: application/json');

$year1 = intval($_GET['year1'] ?? date("Y") - 1); // historical year
$year2 = intval($_GET['year2'] ?? date("Y"));     // compare year
$month = intval($_GET['month'] ?? date("n"));     // selected month

// ===== KC TABLE =====
$kcTable = [
    "Apple" => ["Initial" => 0.50, "Middle" => 0.95, "End" => 0.72],
    "Apricot" => ["Initial" => 0.50, "Middle" => 0.90, "End" => 0.65],
    "Peach" => ["Initial" => 0.50, "Middle" => 0.90, "End" => 0.65],
    "Cherry" => ["Initial" => 0.50, "Middle" => 0.95, "End" => 0.72],
    "SweetCherry" => ["Initial" => 0.30, "Middle" => 0.55, "End" => 0.65],
    "Pear" => ["Initial" => 0.30, "Middle" => 0.60, "End" => 0.70],
    "Pomegranate" => ["Initial" => 0.35, "Middle" => 0.80, "End" => 0.70],
    "Hazelnut" => ["Initial" => 0.30, "Middle" => 0.55, "End" => 0.70],
    "Forest" => ["Initial" => 0.90, "Middle" => 0.95, "End" => 0.95]
];

// ===== GET TREES FROM REQUEST =====
$treesJson = $_GET['trees'] ?? '[]';
$trees = json_decode($treesJson, true);
if (!is_array($trees)) $trees = [];

// ===== KC FACTORS =====
$totalFactor = 0;      // Kc × Count (for total water need if needed)
$totalTreeCount = 0;   // total trees
$weightedKc = 0;       // average Kc for ETc graph

foreach ($trees as $tree) {
    $type = $tree['type'] ?? '';
    $stage = $tree['stage'] ?? '';
    $count = intval($tree['count'] ?? 0);

    if (isset($kcTable[$type][$stage]) && $count > 0) {
        $kc = $kcTable[$type][$stage];

        $totalFactor += ($kc * $count);
        $totalTreeCount += $count;
    }
}

if ($totalTreeCount > 0) {
    $weightedKc = round($totalFactor / $totalTreeCount, 3);
} else {
    $weightedKc = 0;
}

// ===== SIMPLE HISTORICAL ETo FUNCTION =====
function calculateHistoricalETo($tmax, $tmin, $sun_hours = 0, $humidity = 50, $wind = 2.0) {
    if ($tmax === null || $tmin === null) return null;

    $Tmean = ($tmax + $tmin) / 2.0;
    $delta = 4098 * (0.6108 * exp(17.27 * $Tmean / ($Tmean + 237.3))) / pow($Tmean + 237.3, 2);
    $gamma = 0.066;

    $Rn = 8 + ($sun_hours * 0.5); // simplified radiation estimate

    $es = (
        0.6108 * exp(17.27 * $tmax / ($tmax + 237.3)) +
        0.6108 * exp(17.27 * $tmin / ($tmin + 237.3))
    ) / 2.0;

    $ea = $es * ($humidity / 100.0);
    $u2 = ($wind > 0) ? $wind : 2.0;

    $eto = (0.408 * $delta * $Rn + $gamma * (900 / ($Tmean + 273)) * $u2 * ($es - $ea)) /
           ($delta + $gamma * (1 + 0.34 * $u2));

    return round($eto, 3);
}

// ===== FUNCTION TO LOAD HISTORICAL DATA =====
function getHistoricalData($conn, $year, $month, $weightedKc) {
    $hist_sql = "SELECT 
                    day,
                    avg_temp,
                    max_temp,
                    min_temp,
                    humidity,
                    precipitation,
                    sun_hours,
                    wind_max
                 FROM historical_weather
                 WHERE year = ? AND month = ?
                 ORDER BY day";

    $hist_stmt = $conn->prepare($hist_sql);
    $hist_stmt->bind_param("ii", $year, $month);
    $hist_stmt->execute();
    $hist_result = $hist_stmt->get_result();

    $historical = [];
    while ($row = $hist_result->fetch_assoc()) {
        $eto_hist = calculateHistoricalETo(
            $row['max_temp'] !== null ? floatval($row['max_temp']) : null,
            $row['min_temp'] !== null ? floatval($row['min_temp']) : null,
            $row['sun_hours'] !== null ? floatval($row['sun_hours']) : 0,
            $row['humidity'] !== null ? floatval($row['humidity']) : 50,
            $row['wind_max'] !== null ? floatval($row['wind_max']) : 2.0
        );

        $row['eto_hist'] = $eto_hist;
        $row['etc_hist'] = $eto_hist !== null ? round($eto_hist * $weightedKc, 3) : null;

        $historical[] = $row;
    }

    return $historical;
}

// ===== FUNCTION TO LOAD MEASURED DATA =====
function getMeasuredData($conn, $year, $month, $weightedKc) {
    $meas_sql = "SELECT 
                    DAY(measure_date) as day,
                    AVG(air_temp) as avg_temp,
                    MAX(air_temp) as max_temp,
                    MIN(air_temp) as min_temp,
                    AVG(humidity) as humidity,
                    AVG(wind_speed) as wind_speed,
                    AVG(eto) as eto,
                    AVG(etc_value) as etc_value
                 FROM measurements
                 WHERE YEAR(measure_date) = ? AND MONTH(measure_date) = ?
                 GROUP BY DAY(measure_date)
                 ORDER BY DAY(measure_date)";

    $meas_stmt = $conn->prepare($meas_sql);
    $meas_stmt->bind_param("ii", $year, $month);
    $meas_stmt->execute();
    $meas_result = $meas_stmt->get_result();

    $measurements = [];
    while ($row = $meas_result->fetch_assoc()) {
        $eto_meas = $row['eto'] !== null ? floatval($row['eto']) : null;
        $row['etc_calc'] = $eto_meas !== null ? round($eto_meas * $weightedKc, 3) : null;
        $measurements[] = $row;
    }

    return $measurements;
}

// ===== LEFT SIDE: ALWAYS HISTORICAL YEAR1 =====
$historical = getHistoricalData($conn, $year1, $month, $weightedKc);

// ===== RIGHT SIDE: CHECK IF LAST DAY EXISTS IN MEASUREMENTS =====
$firstDate = sprintf("%04d-%02d-01", $year2, $month);

$check_sql = "SELECT COUNT(*) as cnt FROM measurements WHERE DATE(measure_date) = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $firstDate);

$check_stmt->execute();
$check_result = $check_stmt->get_result();
$check_row = $check_result->fetch_assoc();

$source_used = "historical"; // default

if (intval($check_row['cnt']) > 0) {
    // If last day exists in measured => use whole month from measurements
    $measurements = getMeasuredData($conn, $year2, $month, $weightedKc);
    $fallbackHistorical = [];
    $source_used = "measured";
} else {
    // Otherwise use historical data of year2
    $measurements = [];
    $fallbackHistorical = getHistoricalData($conn, $year2, $month, $weightedKc);
    $source_used = "historical";
}

echo json_encode([
    "historical" => $historical,
    "measurements" => $measurements,
    "fallback_historical" => $fallbackHistorical,
    "tree_factor" => $totalFactor,
    "weighted_kc" => $weightedKc,
    "tree_count" => $totalTreeCount,
    "source_used" => $source_used
]);
?>