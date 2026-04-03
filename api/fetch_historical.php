<?php
include("../config/db.php");

set_time_limit(0);

// ===== CONFIG =====
$lat = 40.878;   // Ijevan
$lon = 45.148;

// ===== cURL FETCH FUNCTION =====
function fetchWeatherRange($start, $end, $lat, $lon) {

    $url = "https://archive-api.open-meteo.com/v1/archive?latitude=$lat&longitude=$lon&start_date=$start&end_date=$end&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,sunshine_duration&timezone=auto";

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // SSL FIX (XAMPP)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "❌ cURL Error: " . curl_error($ch) . "<br>";
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    return json_decode($response, true);
}

// ===== DATE RANGE =====
$startDate = new DateTime("2025-01-01");
$endDate   = new DateTime("2026-04-01");

// ===== LOOP BY MONTH =====
while ($startDate <= $endDate) {

    $monthStart = $startDate->format("Y-m-01");
    $monthEnd   = $startDate->format("Y-m-t");

    // եթե վերջը անցնում է վերջնական date-ից
    if ($monthEnd > $endDate->format("Y-m-d")) {
        $monthEnd = $endDate->format("Y-m-d");
    }

    echo "<h3>📅 Processing $monthStart → $monthEnd</h3>";

    $data = fetchWeatherRange($monthStart, $monthEnd, $lat, $lon);

    if (!$data || !isset($data['daily']['time'])) {
        echo "⚠️ No data<br>";
        $startDate->modify("+1 month");
        continue;
    }

    $days = $data['daily']['time'];

    for ($i = 0; $i < count($days); $i++) {

        $date = $days[$i];
        $d = new DateTime($date);

        $year  = $d->format("Y");
        $month = $d->format("m");
        $day   = $d->format("d");

        $max = $data['daily']['temperature_2m_max'][$i] ?? null;
        $min = $data['daily']['temperature_2m_min'][$i] ?? null;
        $rain = $data['daily']['precipitation_sum'][$i] ?? null;
        $sun  = isset($data['daily']['sunshine_duration'][$i])
            ? $data['daily']['sunshine_duration'][$i] / 3600
            : null;

        $avg = ($max !== null && $min !== null) ? ($max + $min) / 2 : null;

        // ===== CHECK DUPLICATE =====
        $check = $conn->query("
            SELECT id FROM historical_weather 
            WHERE year=$year AND month=$month AND day=$day
        ");

        if ($check->num_rows == 0) {

            $stmt = $conn->prepare("
                INSERT INTO historical_weather 
                (year, month, day, avg_temp, max_temp, min_temp, precipitation, sun_hours)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "iiiddddd",
                $year, $month, $day,
                $avg, $max, $min,
                $rain, $sun
            );

            $stmt->execute();

            echo "✅ Inserted $date<br>";
        } else {
            echo "⏭️ Skipped (exists) $date<br>";
        }
    }

    // հաջորդ ամիս
    $startDate->modify("+1 month");

    // փոքր delay (API safe)
    usleep(200000);
}

echo "<h2>🎉 DONE</h2>";