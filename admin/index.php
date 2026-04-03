<?php include("../config/db.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container">
    <h1>Admin Dashboard</h1>

    <?php
    $histCount = $conn->query("SELECT COUNT(*) as c FROM historical_weather")->fetch_assoc()['c'];
    $measCount = $conn->query("SELECT COUNT(*) as c FROM measurements")->fetch_assoc()['c'];
    $latestMeas = $conn->query("SELECT * FROM measurements ORDER BY measure_date DESC, measure_time DESC LIMIT 1");
    $latest = $latestMeas->num_rows ? $latestMeas->fetch_assoc() : null;
    ?>

    <div class="grid">
        <div class="card">
            <h2>Historical Records</h2>
            <p><?php echo $histCount; ?></p>
        </div>

        <div class="card">
            <h2>Measurement Records</h2>
            <p><?php echo $measCount; ?></p>
        </div>

        <div class="card">
            <h2>Latest Air Temp</h2>
            <p><?php echo $latest ? $latest['air_temp'] . " °C" : "-"; ?></p>
        </div>

        <div class="card">
            <h2>Latest ETc</h2>
            <p><?php echo $latest ? $latest['etc_value'] . " mm/day" : "-"; ?></p>
        </div>
    </div>
</div>
</body>
</html>