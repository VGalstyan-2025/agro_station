<?php
include("config/config.php");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("❌ MySQL connection failed: " . $conn->connect_error);
}

echo "<h2>Agro Station Installer</h2>";

$sql_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " 
           CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";

if ($conn->query($sql_db) === TRUE) {
    echo "✅ Database created or already exists<br>";
} else {
    die("❌ Error creating database: " . $conn->error);
}

$conn->select_db(DB_NAME);

$sql_historical = "
CREATE TABLE IF NOT EXISTS historical_weather (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL,
    month INT NOT NULL,
    day INT NOT NULL,

    avg_temp DECIMAL(5,2),
    max_temp DECIMAL(5,2),
    min_temp DECIMAL(5,2),

    pressure DECIMAL(6,2),
    humidity DECIMAL(5,2),
    wind_max DECIMAL(5,2),
    cloudiness DECIMAL(5,2),
    precipitation DECIMAL(6,2),
    sun_hours DECIMAL(5,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_historical) === TRUE) {
    echo "✅ Table 'historical_weather' created<br>";
} else {
    echo "❌ Error creating historical_weather: " . $conn->error . "<br>";
}

$sql_measurements = "
CREATE TABLE IF NOT EXISTS measurements (
    id INT AUTO_INCREMENT PRIMARY KEY,

    measure_date DATE NOT NULL,
    measure_time TIME NOT NULL,

    air_temp DECIMAL(5,2),
    humidity DECIMAL(5,2),
    water_temp DECIMAL(5,2),
    light_lux DECIMAL(10,2),
    distance_cm DECIMAL(10,2),
    wind_speed DECIMAL(5,2),
    eto DECIMAL(6,3),
    etc_value DECIMAL(6,3),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_measurements) === TRUE) {
    echo "✅ Table 'measurements' created<br>";
} else {
    echo "❌ Error creating measurements: " . $conn->error . "<br>";
}

$sql_forecast_history = "
CREATE TABLE IF NOT EXISTS forecast_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_date DATE NOT NULL,
    predicted_on DATE NOT NULL,

    forecast_eto FLOAT DEFAULT 0,
    forecast_etc FLOAT DEFAULT 0,

    actual_eto FLOAT DEFAULT 0,
    actual_etc FLOAT DEFAULT 0,

    abs_error_eto FLOAT DEFAULT 0,
    error_percent_eto FLOAT DEFAULT 0,
    accuracy_percent_eto FLOAT DEFAULT 0,

    abs_error_etc FLOAT DEFAULT 0,
    error_percent_etc FLOAT DEFAULT 0,
    accuracy_percent_etc FLOAT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_forecast (target_date, predicted_on)
)";

if ($conn->query($sql_forecast_history) === TRUE) {
    echo "✅ Table 'forecast_history' created<br>";
} else {
    echo "❌ Error creating forecast_history: " . $conn->error . "<br>";
}

echo "<br><h3>🎉 Installation Completed Successfully!</h3>";
echo "<p><a href='index.php'>➡ Go to Home Page</a></p>";

$conn->close();
?>