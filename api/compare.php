<?php
include("../config/db.php");

header('Content-Type: application/json');

$year1 = intval($_GET['year1'] ?? date("Y") - 1); // historical
$year2 = intval($_GET['year2'] ?? date("Y"));     // measured
$month = intval($_GET['month'] ?? date("n"));     // month

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

// ===== TOTAL KC*COUNT =====
$totalFactor = 0;
foreach ($trees as $tree) {
    $type = $tree['type'] ?? '';
    $stage = $tree['stage'] ?? '';
    $count = intval($tree['count'] ?? 0);

    if (isset($kcTable[$type][$stage]) && $count > 0) {
        $kc = $kcTable[$type][$stage];
        $totalFactor += ($kc * $count);
    }
}

// ===== SIMPLE HISTORICAL ETo FUNCTION =====
// Քանի որ historical table-ում լրիվ FAO Penman-Monteith-ի բոլոր տվյալները չկան,
// այստեղ օգտագործում ենք simplified գնահատում՝ Tmax/Tmin հիմքով
function calculateHistoricalETo($tmax, $tmin, $sun_hours = 0, $humidity = 50, $wind = 2.0) {
    if ($tmax === null || $tmin === null) return null;

    $Tmean = ($tmax + $tmin) / 2.0;
    $delta = 4098 * (0.6108 * exp(17.27 * $Tmean / ($Tmean + 237.3))) / pow($Tmean + 237.3, 2);
    $gamma = 0.066;

    // approximate net radiation using sunshine hours
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

// ===== HISTORICAL DATA =====
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
$hist_stmt->bind_param("ii", $year1, $month);
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
    $row['etc_hist'] = $eto_hist !== null ? round($eto_hist * $totalFactor, 3) : null;

    $historical[] = $row;
}

// ===== MEASURED DATA =====
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
$meas_stmt->bind_param("ii", $year2, $month);
$meas_stmt->execute();
$meas_result = $meas_stmt->get_result();

$measurements = [];
while ($row = $meas_result->fetch_assoc()) {
    $eto_meas = $row['eto'] !== null ? floatval($row['eto']) : null;
    $row['etc_calc'] = $eto_meas !== null ? round($eto_meas * $totalFactor, 3) : null;
    $measurements[] = $row;
}

echo json_encode([
    "historical" => $historical,
    "measurements" => $measurements,
    "tree_factor" => $totalFactor
]);
?>