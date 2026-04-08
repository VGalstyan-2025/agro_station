<?php include("../config/db.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Historical Data Entry</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<?php include("navbar.php"); ?>

<div class="container">
    <h1>Historical Weather Data Entry</h1>

    <form id="historicalForm" class="form-grid">    
        <input type="number" name="year" oninput="loadExisting()" placeholder="Year" required>
        <input type="number" name="month" oninput="loadExisting()" placeholder="Month" required>
        <input type="number" name="day" oninput="loadExisting()" placeholder="Day" required>

        <input type="number" step="0.01" name="avg_temp" placeholder="Avg Temp">
        <input type="number" step="0.01" name="max_temp" placeholder="Max Temp">
        <input type="number" step="0.01" name="min_temp" placeholder="Min Temp">
        <input type="number" step="0.01" name="pressure" placeholder="Pressure">
        <input type="number" step="0.01" name="humidity" placeholder="Humidity">
        <input type="number" step="0.01" name="wind_max" placeholder="Wind Max">
        <input type="number" step="0.01" name="cloudiness" placeholder="Cloudiness">
        <input type="number" step="0.01" name="precipitation" placeholder="Precipitation">
        <input type="number" step="0.01" name="sun_hours" placeholder="Sun Hours">

        <button type="submit">Save Historical Data</button>
    </form>

    <hr>

    <h2>All Historical Records</h2>

    <table id="historicalTable">
        <tr>
            <th>
                ID 
                <span id="sortIcon" style="cursor:pointer;" onclick="sortTableById()">
                    🔽
                </span>
            </th>
            <th>Date</th>
            <th>Avg</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pressure</th>
            <th>Humidity</th>
            <th>Wind</th>
            <th>Cloud</th>
            <th>Rain</th>
            <th>Sun</th>
            <th>Actions</th>
        </tr>

        <?php
        $result = $conn->query("SELECT * FROM historical_weather ORDER BY year DESC, month DESC, day DESC");
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['year']}-{$row['month']}-{$row['day']}</td>
                <td>{$row['avg_temp']}</td>
                <td>{$row['max_temp']}</td>
                <td>{$row['min_temp']}</td>
                <td>{$row['pressure']}</td>
                <td>{$row['humidity']}</td>
                <td>{$row['wind_max']}</td>
                <td>{$row['cloudiness']}</td>
                <td>{$row['precipitation']}</td>
                <td>{$row['sun_hours']}</td>
                <td class='actions'>
                    <a href='../api/delete_historical.php?id={$row['id']}' onclick='return confirm(\"Delete this record?\")'>Delete</a>
                </td>
            </tr>";
        }
        ?>
    </table>
</div>

<script>
let sortAsc = false;

function sortTableById() {
    const table = document.getElementById("historicalTable");
    const rows = Array.from(table.rows).slice(1); 

    sortAsc = !sortAsc;

    rows.sort((a, b) => {
        const idA = parseInt(a.cells[0].innerText);
        const idB = parseInt(b.cells[0].innerText);

        return sortAsc ? idA - idB : idB - idA;
    });

    rows.forEach(row => table.appendChild(row));

    const icon = document.getElementById("sortIcon");
    if (icon) {
        icon.innerText = sortAsc ? "🔼" : "🔽";
    }
}
async function loadExisting() {
    const year = document.querySelector('[name="year"]').value;
    const month = document.querySelector('[name="month"]').value;
    const day = document.querySelector('[name="day"]').value;

    if (!year || !month || !day) return;

    const res = await fetch(`../api/get_historical_by_date.php?year=${year}&month=${month}&day=${day}`);
    const data = await res.json();

    if (data) {
        for (let key in data) {
            if (document.querySelector(`[name="${key}"]`)) {
                document.querySelector(`[name="${key}"]`).value = data[key];
            }
        }

        if (!document.getElementById("record_id")) {
            let input = document.createElement("input");
            input.type = "hidden";
            input.name = "id";
            input.id = "record_id";
            document.querySelector("form").appendChild(input);
        }

        document.getElementById("record_id").value = data.id;
    }
}

document.getElementById("historicalForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const res = await fetch("../api/add_historical.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();

    if (data.status === "success") {
        alert("✅ Saved successfully!");
        location.reload();
    } else {
        alert("❌ Error: " + data.message);
    }
});
</script>

</body>
</html>