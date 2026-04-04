<!DOCTYPE html>
<html>
<head>
    <title>Compare Weather Data</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .big-chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .big-chart-card canvas {
            width: 100% !important;
            height: 420px !important;
        }

        .tree-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .tree-item {
            background: #f7fbff;
            border: 1px solid #d7e7f7;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        .summary-box {
            background: #eef6ff;
            padding: 12px 18px;
            border-radius: 10px;
            margin: 15px 0;
            font-weight: bold;
            color: #1f3b57;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Compare Historical vs Measured Data</h1>

    <div class="form-grid">
        <input type="number" id="year1" value="2025" placeholder="Historical Year">
        <input type="number" id="year2" value="2026" placeholder="Measured Year">
        <input type="number" id="month" value="4" min="1" max="12" placeholder="Month">
        <button onclick="loadComparison()">Compare</button>
    </div>

    <hr>

    <h2>Add Tree for ETc Comparison</h2>

    <select id="treeType">
      <option value="Apple">Apple</option>
      <option value="Apricot">Apricot</option>
      <option value="Peach">Peach</option>
      <option value="Cherry">Cherry</option>
      <option value="SweetCherry">SweetCherry</option>
      <option value="Pear">Pear</option>
      <option value="Pomegranate">Pomegranate</option>
      <option value="Hazelnut">Hazelnut</option>
      <option value="Forest">Forest</option>
    </select>

    <select id="treeStage">
      <option value="Initial">Initial</option>
      <option value="Middle">Middle</option>
      <option value="End">End</option>
    </select>

    <input type="number" id="treeCount" placeholder="Count">
    <button onclick="addTree()">Add Tree</button>

    <div id="treeFactorBox" class="summary-box">Total Kc × Count Factor = 0</div>

    <div class="tree-list" id="treeList"></div>

    <hr>

    <div class="big-chart-card">
        <h2>1. Air Temperature Comparison</h2>
        <canvas id="airTempChart"></canvas>
    </div>

    <div class="big-chart-card">
        <h2>2. Historical ETo vs Measured ETo</h2>
        <canvas id="etoChart"></canvas>
    </div>

    <div class="big-chart-card">
        <h2>3. Historical ETc vs Measured ETc</h2>
        <canvas id="etcChart"></canvas>
    </div>
    <hr>

    <h1>Compare Full Years</h1>

    <div class="form-grid">
        <input type="number" id="fullYear1" value="2025" placeholder="Year 1">
        <input type="number" id="fullYear2" value="2026" placeholder="Year 2">
        <button onclick="loadYearComparison()">Compare Full Years</button>
    </div>

    <div class="big-chart-card">
        <h2>4. Full Year Air Temperature Comparison</h2>
        <canvas id="yearAirTempChart"></canvas>
    </div>

    <div class="big-chart-card">
        <h2>5. Full Year ETo Comparison</h2>
        <canvas id="yearEtoChart"></canvas>
    </div>

    <div class="big-chart-card">
        <h2>6. Full Year ETc Comparison</h2>
        <canvas id="yearEtcChart"></canvas>
    </div>
</div>

<script>
let airTempChart, etoChart, etcChart;
let yearAirTempChart, yearEtoChart, yearEtcChart;
let trees = [];

function destroyChart(chart) {
    if (chart) chart.destroy();
}

function getValueByDay(arr, day, key) {
    let item = arr.find(x => parseInt(x.day) === day);
    return item && item[key] !== null ? parseFloat(item[key]) : null;
}

function addTree() {
    const type = document.getElementById('treeType').value;
    const stage = document.getElementById('treeStage').value;
    const count = parseInt(document.getElementById('treeCount').value);

    if (!count || count <= 0) {
        alert("Enter valid tree count");
        return;
    }

    trees.push({ type, stage, count });
    document.getElementById('treeCount').value = "";
    renderTrees();
    loadComparison();
    loadYearComparison(); // ✅ full year charts refresh
}

function deleteTree(index) {
    trees.splice(index, 1);
    renderTrees();
    loadComparison();
    loadYearComparison(); // ✅ full year charts refresh
}

function renderTrees() {
    let html = "";

    trees.forEach((t, i) => {
        html += `
            <div class="tree-item">
                <b>${t.type}</b><br>
                Stage: ${t.stage}<br>
                Count: ${t.count}<br><br>
                <button onclick="deleteTree(${i})">Delete</button>
            </div>
        `;
    });

    document.getElementById("treeList").innerHTML = html || "<p>No trees added yet.</p>";
}

async function loadComparison() {
    const year1 = document.getElementById('year1').value;
    const year2 = document.getElementById('year2').value;
    const month = document.getElementById('month').value;

    const treesEncoded = encodeURIComponent(JSON.stringify(trees));

    const res = await fetch(`api/compare.php?year1=${year1}&year2=${year2}&month=${month}&trees=${treesEncoded}`);
    const data = await res.json();

    const hist = data.historical || [];
    const meas = data.measurements || [];
    const fallbackHist = data.fallback_historical || [];
    const sourceUsed = data.source_used || "historical";
    const factor = data.tree_factor || 0;
    const weightedKc = data.weighted_kc || 0;
    const treeCount = data.tree_count || 0;

    document.getElementById("treeFactorBox").innerHTML =
        `Weighted Kc = ${weightedKc} | Total Trees = ${treeCount} | Total Kc × Count = ${factor}`;

    const compareData = sourceUsed === "measured" ? meas : fallbackHist;
    const days = [...new Set([
        ...hist.map(x => parseInt(x.day)),
        ...compareData.map(x => parseInt(x.day))
    ])].sort((a, b) => a - b);

    if (days.length === 0) {
        alert("⚠️ No data found for selected month/year.");
        return;
    }

    // ===== DATA =====
    const histAvgTemp = days.map(day => getValueByDay(hist, day, 'avg_temp'));
    const compareAvgTemp = sourceUsed === "measured"
        ? days.map(day => getValueByDay(meas, day, 'avg_temp'))
        : days.map(day => getValueByDay(fallbackHist, day, 'avg_temp'));

    const histETo = days.map(day => getValueByDay(hist, day, 'eto_hist'));
    const compareETo = sourceUsed === "measured"
        ? days.map(day => getValueByDay(meas, day, 'eto'))
        : days.map(day => getValueByDay(fallbackHist, day, 'eto_hist'));

    const histETc = days.map(day => getValueByDay(hist, day, 'etc_hist'));
    const compareETc = sourceUsed === "measured"
        ? days.map(day => getValueByDay(meas, day, 'etc_calc'))
        : days.map(day => getValueByDay(fallbackHist, day, 'etc_hist'));
        
    destroyChart(airTempChart);
    destroyChart(etoChart);
    destroyChart(etcChart);

    // const compareLabel = sourceUsed === "measured"
    //     ? `Measured ${year2}`
    //     : `Historical ${year2}`;

    // ===== 1. AIR TEMP =====
    airTempChart = new Chart(document.getElementById('airTempChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                {
                    label: `${year1} Avg Temp`,
                    data: histAvgTemp,
                    borderColor: 'blue',
                    backgroundColor: 'blue',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} Avg Temp`,
                    data: compareAvgTemp,
                    borderColor: 'red',
                    backgroundColor: 'red',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Day of Month' } },
                y: { title: { display: true, text: 'Temperature (°C)' } }
            }
        }
    });

    // ===== 2. ETO =====
    etoChart = new Chart(document.getElementById('etoChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                {
                    label: `${year1} ETo`,
                    data: histETo,
                    borderColor: 'green',
                    backgroundColor: 'green',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} ETo`,
                    data: compareETo,
                    borderColor: 'purple',
                    backgroundColor: 'purple',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Day of Month' } },
                y: { title: { display: true, text: 'ETo (mm/day)' } }
            }
        }
    });

    // ===== 3. ETC =====
    etcChart = new Chart(document.getElementById('etcChart'), {
        type: 'line',
        data: {
            labels: days,
            datasets: [
                {
                    label: `${year1} ETc`,
                    data: histETc,
                    borderColor: 'orange',
                    backgroundColor: 'orange',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} ETc`,
                    data: compareETc,
                    borderColor: 'brown',
                    backgroundColor: 'brown',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Day of Month' } },
                y: { title: { display: true, text: 'ETc (mm/day)' } }
            }
        }
    });
}

async function loadYearComparison() {
    const year1 = document.getElementById('fullYear1').value;
    const year2 = document.getElementById('fullYear2').value;

    const treesEncoded = encodeURIComponent(JSON.stringify(trees));

    const res = await fetch(`api/compare_years.php?year1=${year1}&year2=${year2}&trees=${treesEncoded}`);
    const data = await res.json();

    const year1Data = data.year1_data || [];
    const year2Data = data.year2_data || [];
    const factor = data.tree_factor || 0;

    const labels = [...new Set([
        ...year1Data.map(x => x.label),
        ...year2Data.map(x => x.label)
    ])];

    if (labels.length === 0) {
        alert("⚠️ No yearly data found.");
        return;
    }

    function getValueByLabel(arr, label, key) {
        let item = arr.find(x => x.label === label);
        return item && item[key] !== null ? parseFloat(item[key]) : null;
    }

    const year1AvgTemp = labels.map(label => getValueByLabel(year1Data, label, 'avg_temp'));
    const year2AvgTemp = labels.map(label => getValueByLabel(year2Data, label, 'avg_temp'));

    const year1ETo = labels.map(label => getValueByLabel(year1Data, label, 'eto'));
    const year2ETo = labels.map(label => getValueByLabel(year2Data, label, 'eto'));

    const year1ETc = labels.map(label => getValueByLabel(year1Data, label, 'etc'));
    const year2ETc = labels.map(label => getValueByLabel(year2Data, label, 'etc'));

    destroyChart(yearAirTempChart);
    destroyChart(yearEtoChart);
    destroyChart(yearEtcChart);

    // ===== FULL YEAR AIR TEMP =====
    yearAirTempChart = new Chart(document.getElementById('yearAirTempChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: `${year1} Avg Temp`,
                    data: year1AvgTemp,
                    borderColor: 'blue',
                    backgroundColor: 'blue',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} Avg Temp`,
                    data: year2AvgTemp,
                    borderColor: 'red',
                    backgroundColor: 'red',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Month-Day' } },
                y: { title: { display: true, text: 'Temperature (°C)' } }
            }
        }
    });

    // ===== FULL YEAR ETO =====
    yearEtoChart = new Chart(document.getElementById('yearEtoChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: `${year1} ETo`,
                    data: year1ETo,
                    borderColor: 'green',
                    backgroundColor: 'green',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} ETo`,
                    data: year2ETo,
                    borderColor: 'purple',
                    backgroundColor: 'purple',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Month-Day' } },
                y: { title: { display: true, text: 'ETo (mm/day)' } }
            }
        }
    });

    // ===== FULL YEAR ETC =====
    yearEtcChart = new Chart(document.getElementById('yearEtcChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: `${year1} ETc`,
                    data: year1ETc,
                    borderColor: 'orange',
                    backgroundColor: 'orange',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                },
                {
                    label: `${year2} ETc`,
                    data: year2ETc,
                    borderColor: 'brown',
                    backgroundColor: 'brown',
                    fill: false,
                    tension: 0.3,
                    borderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { title: { display: true, text: 'Month-Day' } },
                y: { title: { display: true, text: 'ETc (mm/day)' } }
            }
        }
    });
}

window.onload = () => {
    renderTrees();
    loadComparison();
    loadYearComparison();
};
</script>

</body>
</html>