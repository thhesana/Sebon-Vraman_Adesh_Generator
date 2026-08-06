<?php
// session_start() is handled inside header.php — do NOT call it here
include 'header.php';

$username = $_SESSION['username'] ?? 'User';

/* ── DB connection ── */
include 'db.php';


/* ── helpers (prefixed to avoid collision with any function in header.php) ── */
function db_scalar($conn, $sql) {
    $r = sqlsrv_query($conn, $sql);
    if (!$r) return 0;
    $row = sqlsrv_fetch_array($r, SQLSRV_FETCH_NUMERIC);
    return $row ? (int)$row[0] : 0;
}

function db_month_map($conn, $sql) {
    $result = sqlsrv_query($conn, $sql);
    $map = [];
    if ($result) {
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $key = sprintf('%04d-%02d', $row['y'], $row['m']);
            $map[$key] = (int)$row['cnt'];
        }
    }
    return $map;
}

/* ── summary counts ── */
$totalDom    = db_scalar($conn, "SELECT COUNT(*) FROM DomesticTada WHERE domestic_travelDateStart >= DATEADD(MONTH,-12,GETDATE())");
$totalInt    = db_scalar($conn, "SELECT COUNT(*) FROM International_tada WHERE travelDateStart >= DATEADD(MONTH,-12,GETDATE())");
$pendingDom  = db_scalar($conn, "SELECT COUNT(*) FROM DomesticTada WHERE tadaverifier_id IS NULL AND domestic_travelDateStart >= DATEADD(MONTH,-12,GETDATE())");
$pendingInt  = db_scalar($conn, "SELECT COUNT(*) FROM International_tada WHERE tadaverifier_id IS NULL AND travelDateStart >= DATEADD(MONTH,-12,GETDATE())");

/* ── monthly data ── */
$domMap = db_month_map($conn, "
    SELECT MONTH(domestic_travelDateStart) m, YEAR(domestic_travelDateStart) y, COUNT(*) cnt
    FROM DomesticTada
    WHERE domestic_travelDateStart >= DATEADD(MONTH,-12,GETDATE())
    GROUP BY YEAR(domestic_travelDateStart), MONTH(domestic_travelDateStart)
    ORDER BY y, m
");
$intMap = db_month_map($conn, "
    SELECT MONTH(travelDateStart) m, YEAR(travelDateStart) y, COUNT(*) cnt
    FROM International_tada
    WHERE travelDateStart >= DATEADD(MONTH,-12,GETDATE())
    GROUP BY YEAR(travelDateStart), MONTH(travelDateStart)
    ORDER BY y, m
");

/* ── build ordered 12-month arrays ── */
$monthKeys = $monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $ts           = strtotime("-$i months");
    $monthKeys[]  = date('Y-m', $ts);
    $monthLabels[] = date('M Y', $ts);
}
$domData  = array_map(fn($k) => $domMap[$k]  ?? 0, $monthKeys);
$intData  = array_map(fn($k) => $intMap[$k]  ?? 0, $monthKeys);
$combined = array_map(fn($d, $i) => $d + $i, $domData, $intData);
$maxIdx   = array_keys($combined, max($combined))[0] ?? 0;

$total    = $totalDom + $totalInt;
$intPct   = $total > 0 ? round($totalInt / $total * 100) : 0;

/* ── JSON for JS ── */
$labelsJson = json_encode($monthLabels);
$domJson    = json_encode($domData);
$intJson    = json_encode($intData);
$totalDomJ  = (int)$totalDom;
$totalIntJ  = (int)$totalInt;
?>

<!-- ── extra styles (dashboard only) ── -->
<style>
.db-section { padding: 2rem 1rem; }

/* metric cards */
.metric-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 1.1rem 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    height: 100%;
}
.metric-label {
    font-size: .72rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin: 0 0 4px;
}
.metric-value {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #111827;
}
.metric-sub {
    font-size: .75rem;
    border-radius: 20px;
    padding: 2px 9px;
    display: inline-block;
    margin-top: 6px;
    font-weight: 500;
}
.badge-dom  { background:#dbeafe; color:#1d4ed8; }
.badge-int  { background:#fef3c7; color:#b45309; }
.badge-pend { background:#fee2e2; color:#b91c1c; }
.badge-ok   { background:#d1fae5; color:#065f46; }

/* chart cards */
.chart-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
    height: 100%;
}
.chart-card h6 {
    font-size: .9rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 2px;
}
.chart-card p.sub {
    font-size: .75rem;
    color: #9ca3af;
    margin-bottom: 1rem;
}
.legend { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:10px; }
.legend-item { font-size:.75rem; color:#6b7280; display:flex; align-items:center; gap:5px; }
.legend-box  { width:10px; height:10px; border-radius:2px; display:inline-block; }

/* welcome strip */
.welcome-strip {
    background: linear-gradient(135deg,#0056b3,#007bff);
    color: #fff;
    border-radius: 12px;
    padding: 1.4rem 1.75rem;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,123,255,.25);
}
.welcome-avatar {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.25);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 700; flex-shrink: 0;
}
.welcome-strip h4 { margin:0; font-size:1.1rem; font-weight:700; }
.welcome-strip p  { margin:0; font-size:.82rem; opacity:.85; }
</style>

<div class="db-section container-fluid px-4">

    <!-- Welcome strip -->
    <div class="welcome-strip">
        <div class="welcome-avatar"><?php echo strtoupper(substr($username,0,1)); ?></div>
        <div>
            <h4>Welcome back, <?php echo htmlspecialchars($username); ?>!</h4>
            <p>SEBON Vraman Adesh Generator &mdash; Travel Allowance Dashboard &mdash; Last 12 months</p>
        </div>
    </div>

    <!-- Metric cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="metric-card">
                <p class="metric-label">Total Domestic</p>
                <p class="metric-value"><?= number_format($totalDom) ?></p>
                <span class="metric-sub badge-dom">Last 12 months</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="metric-card">
                <p class="metric-label">Total International</p>
                <p class="metric-value"><?= number_format($totalInt) ?></p>
                <span class="metric-sub badge-int">Last 12 months</span>
            </div>
        </div>
        
    </div>

    <!-- Main grouped bar chart -->
    <div class="chart-card mb-4">
        <h6>Monthly records — Domestic vs International</h6>
        <p class="sub">Grouped bar chart &middot; last 12 months</p>
        <div class="legend">
            <span class="legend-item"><span class="legend-box" style="background:#2563eb"></span>Domestic</span>
            <span class="legend-item"><span class="legend-box" style="background:#f59e0b"></span>International</span>
        </div>
        <div style="position:relative;height:290px;">
            <canvas id="barChart"
                role="img"
                aria-label="Grouped bar chart: monthly domestic and international TADA records">
            </canvas>
        </div>
    </div>

   
</div><!-- /container -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const months  = <?= $labelsJson ?>;
const domData = <?= $domJson ?>;
const intData = <?= $intJson ?>;

/* grouped bar */
new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label:'Domestic',      data:domData, backgroundColor:'#2563eb', borderRadius:5, borderSkipped:false },
            { label:'International', data:intData, backgroundColor:'#f59e0b', borderRadius:5, borderSkipped:false }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{
            x:{ ticks:{ autoSkip:false, maxRotation:45, font:{size:11} }, grid:{ display:false } },
            y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,0.05)' } }
        }
    }
});

/* cumulative line */
function cumSum(arr){ let s=0; return arr.map(v=>s+=v); }
new Chart(document.getElementById('lineChart'), {
    type:'line',
    data:{
        labels:months,
        datasets:[
            { label:'Domestic',      data:cumSum(domData), borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,0.08)',  fill:true, tension:0.4, borderDash:[],    pointRadius:3 },
            { label:'International', data:cumSum(intData), borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,0.08)', fill:true, tension:0.4, borderDash:[5,3], pointRadius:3 }
        ]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ display:false } },
        scales:{
            x:{ ticks:{ font:{size:10}, maxRotation:45 }, grid:{ display:false } },
            y:{ beginAtZero:true }
        }
    }
});

/* doughnut */
new Chart(document.getElementById('doughnut'), {
    type:'doughnut',
    data:{
        labels:['Domestic','International'],
        datasets:[{ data:[<?= $totalDomJ ?>,<?= $totalIntJ ?>], backgroundColor:['#2563eb','#f59e0b'], borderWidth:2, borderColor:'#fff', hoverOffset:8 }]
    },
    options:{
        responsive:true, maintainAspectRatio:false, cutout:'68%',
        plugins:{ legend:{ position:'bottom', labels:{ font:{size:12}, boxWidth:11, padding:14 } } }
    }
});
</script>