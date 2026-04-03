<?php include("../config/db.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Measurements Entry</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container">
    <h1>Measurements Entry</h1>

    <form method="POST" action="../api/add_measurement.php" class="form-grid">
        <input type="date" name="measure_date" required>
        <input type="time" name="measure_time" required>
        <input type="number" step="0.01" name="air_temp" placeholder="Air Temp">
        <input type="number" step="0.01" name="humidity" placeholder="Humidity">
        <input type="number" step="0.01" name="water_temp" placeholder="Water Temp">
        <input type="number" step="0.01" name="light_lux" placeholder="Light Lux">
        <input type="number" step="0.01" name="distance_cm" placeholder="Distance CM">
        <input type="number" step="0.01" name="wind_speed" placeholder="Wind Speed">
        <input type="number" step="0.001" name="eto" placeholder="ETo">
        <input type="number" step="0.001" name="etc_value" placeholder="ETc">

        <button type="submit">Save Measurement</button>
    </form>

    <hr>

    <h2>All Measurements</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Time</th>
            <th>Air Temp</th>
            <th>Humidity</th>
            <th>Water Temp</th>
            <th>Light</th>
            <th>Distance</th>
            <th>Wind</th>
            <th>ETo</th>
            <th>ETc</th>
            <th>Actions</th>
        </tr>

        <?php
        $result = $conn->query("SELECT * FROM measurements ORDER BY measure_date DESC, measure_time DESC");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['measure_date']}</td>
                <td>{$row['measure_time']}</td>
                <td>{$row['air_temp']}</td>
                <td>{$row['humidity']}</td>
                <td>{$row['water_temp']}</td>
                <td>{$row['light_lux']}</td>
                <td>{$row['distance_cm']}</td>
                <td>{$row['wind_speed']}</td>
                <td>{$row['eto']}</td>
                <td>{$row['etc_value']}</td>
                <td class='actions'>
                    <a href='../api/delete_measurement.php?id={$row['id']}' onclick='return confirm(\"Delete this record?\")'>Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>
</body>
</html>