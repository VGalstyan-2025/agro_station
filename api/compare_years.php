<?php
include("../config/db.php");

header('Content-Type: application/json');

$year1 = intval($_GET['year1'] ?? date("Y") - 1);
$year2 = intval($_GET['year2'] ?? date("Y"));

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

$treesJson = $_GET['trees'] ?? '[]';
$trees = json_decode($treesJson, true);
if (!is_array($trees)) $trees = [];

$totalFactor = 0;      
$totalTreeCount = 0;   
$weightedKc = 0;       

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

function calculateHistoricalETo($tmax, $tmin, $sun_hours = 0, $humidity = 50, $wind = 2.0) {
    if ($tmax === null || $tmin === null) return null;

    $Tmean = ($tmax + $tmin) / 2.0;
    $delta = 4098 * (0.6108 * exp(17.27 * $Tmean / ($Tmean + 237.3))) / pow($Tmean + 237.3, 2);
    $gamma = 0.066;
    $Rn = 8 + ($sun_hours * 0.5);

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

function getYearData($conn, $year, $weightedKc) {
    $sql = "SELECT 
                month,
                day,
                avg_temp,
                max_temp,
                min_temp,
                humidity,
                sun_hours,
                wind_max
            FROM historical_weather
            WHERE year = ?
            ORDER BY month, day";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $eto = calculateHistoricalETo(
            $row['max_temp'] !== null ? floatval($row['max_temp']) : null,
            $row['min_temp'] !== null ? floatval($row['min_temp']) : null,
            $row['sun_hours'] !== null ? floatval($row['sun_hours']) : 0,
            $row['humidity'] !== null ? floatval($row['humidity']) : 50,
            $row['wind_max'] !== null ? floatval($row['wind_max']) : 2.0
        );

        $data[] = [
            "label" => str_pad($row['month'], 2, "0", STR_PAD_LEFT) . "-" . str_pad($row['day'], 2, "0", STR_PAD_LEFT),
            "avg_temp" => $row['avg_temp'],
            "eto" => $eto,
            "etc" => $eto !== null ? round($eto * $weightedKc, 3) : null
        ];
    }

    return $data;
}

$year1Data = getYearData($conn, $year1, $weightedKc);
$year2Data = getYearData($conn, $year2, $weightedKc);

echo json_encode([
    "year1_data" => $year1Data,
    "year2_data" => $year2Data,
    "tree_factor" => $totalFactor,
    "weighted_kc" => $weightedKc,
    "tree_count" => $totalTreeCount
]);
?>