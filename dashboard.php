<?php
// dashboard.php
require 'functions.php';
requireLogin(); // kalau mau bisa dibuka semua orang, baris ini bisa di-comment

// =======================
// FILTER TAHUN
// =======================
$year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : (int)date('Y');

$conn = getConnection();

// =======================
// RINGKASAN (KARTU ATAS)
// =======================
$sqlSummary = "
    SELECT 
        COALESCE(SUM(debet),0)  AS total_debet,
        COALESCE(SUM(kredit),0) AS total_kredit,
        COUNT(*)                AS total_trx
    FROM bukubesar
    WHERE YEAR(tanggal) = ?
";
$stmt = $conn->prepare($sqlSummary);
$stmt->bind_param("i", $year);
$stmt->execute();
$res  = $stmt->get_result();
$summary = $res->fetch_assoc() ?: ['total_debet'=>0,'total_kredit'=>0,'total_trx'=>0];
$stmt->close();

$totalDebet  = (float)$summary['total_debet'];   // Kas masuk
$totalKredit = (float)$summary['total_kredit'];  // Pengeluaran
$saldoTahun  = $totalDebet - $totalKredit;

// saldo akhir (running saldo terakhir di tahun tsb)
$sqlSaldoAkhir = "
    SELECT saldo 
    FROM bukubesar 
    WHERE YEAR(tanggal) = ?
    ORDER BY tanggal DESC, id DESC
    LIMIT 1
";
$stmt = $conn->prepare($sqlSaldoAkhir);
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();
$rowSaldo = $res->fetch_assoc();
$stmt->close();

$saldoAkhir = $rowSaldo ? (float)$rowSaldo['saldo'] : 0;

// =======================
// GRAFIK BAR PENDAPATAN / PENGELUARAN PER BULAN
// =======================
$sqlMonthly = "
    SELECT DATE_FORMAT(tanggal, '%Y-%m') AS ym,
           SUM(debet)  AS total_debet,
           SUM(kredit) AS total_kredit
    FROM bukubesar
    WHERE YEAR(tanggal) = ?
    GROUP BY ym
    ORDER BY ym
";
$stmt = $conn->prepare($sqlMonthly);
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();

$labelsMonths   = [];
$incomeMonths   = [];
$expenseMonths  = [];

while ($row = $res->fetch_assoc()) {
    $ym = $row['ym'];
    // ubah ke format "Jan", "Feb", dst
    $timestamp = strtotime($ym . '-01');
    $labelsMonths[]  = date('M', $timestamp);
    $incomeMonths[]  = (float)$row['total_debet'];
    $expenseMonths[] = (float)$row['total_kredit'];
}
$stmt->close();

// =======================
// GRAFIK GARIS SALDO PER TANGGAL
// =======================
$sqlSaldoTrend = "
    SELECT tanggal, MAX(saldo) AS saldo_harian
    FROM bukubesar
    WHERE YEAR(tanggal) = ?
    GROUP BY tanggal
    ORDER BY tanggal
";
$stmt = $conn->prepare($sqlSaldoTrend);
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();

$saldoLabels = [];
$saldoValues = [];

while ($row = $res->fetch_assoc()) {
    $tgl = $row['tanggal'];
    $saldoLabels[] = date('d M', strtotime($tgl));
    $saldoValues[] = (float)$row['saldo_harian'];
}
$stmt->close();

// =======================
// DONUT: KOMPOSISI PER COA
// =======================
$sqlCoa = "
    SELECT 
        COALESCE(c.nama_coa, 'Tanpa COA') AS nama_coa,
        SUM(b.debet)  AS total_debet,
        SUM(b.kredit) AS total_kredit
    FROM bukubesar b
    LEFT JOIN mastercoa c ON b.coa_id = c.id
    WHERE YEAR(b.tanggal) = ?
    GROUP BY b.coa_id, c.nama_coa
    ORDER BY (SUM(b.debet) + SUM(b.kredit)) DESC
    LIMIT 10
";
$stmt = $conn->prepare($sqlCoa);
$stmt->bind_param("i", $year);
$stmt->execute();
$res = $stmt->get_result();

$coaLabels = [];
$coaValues = [];

while ($row = $res->fetch_assoc()) {
    $coaLabels[] = $row['nama_coa'];
    $coaValues[] = (float)$row['total_kredit'] + (float)$row['total_debet'];
}
$stmt->close();

// ambil 10 transaksi terakhir untuk mini table
$sqlLast = "
    SELECT b.tanggal, c.nama_coa, b.keterangan, b.debet, b.kredit, b.saldo
    FROM bukubesar b
    LEFT JOIN mastercoa c ON b.coa_id = c.id
    WHERE YEAR(b.tanggal) = ?
    ORDER BY b.tanggal DESC, b.id DESC
    LIMIT 10
";
$stmt = $conn->prepare($sqlLast);
$stmt->bind_param("i", $year);
$stmt->execute();
$lastResult = $stmt->get_result();
$lastRows   = [];
while ($row = $lastResult->fetch_assoc()) {
    $lastRows[] = $row;
}
$stmt->close();

$conn->close();

// siapkan data untuk JS
$jsMonths      = json_encode($labelsMonths);
$jsIncome      = json_encode($incomeMonths);
$jsExpense     = json_encode($expenseMonths);
$jsSaldoLabels = json_encode($saldoLabels);
$jsSaldoValues = json_encode($saldoValues);
$jsCoaLabels   = json_encode($coaLabels);
$jsCoaValues   = json_encode($coaValues);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard Keuangan Mushollah21</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background:#f5f6fa;
        }
        .dashboard-wrapper {
            max-width: 1200px;
            margin: 20px auto;
        }
        .card-summary {
            border-radius: 12px;
            color:#fff;
            box-shadow:0 2px 8px rgba(0,0,0,0.1);
        }
        .card-summary .label {
            font-size: 13px;
            opacity: .9;
        }
        .card-summary .value {
            font-size: 22px;
            font-weight: bold;
        }
        .card-summary small {
            font-size: 12px;
        }
        .bg-income   { background:linear-gradient(135deg,#ffb347,#ffcc33); }
        .bg-expense  { background:linear-gradient(135deg,#ff6b6b,#f06595); }
        .bg-balance  { background:linear-gradient(135deg,#3498db,#2ecc71); }
        .bg-trx      { background:linear-gradient(135deg,#9b59b6,#e056fd); }
        .card {
            border-radius: 12px;
            box-shadow:0 2px 6px rgba(0,0,0,0.05);
        }
        .table-sm td, .table-sm th {
            padding: .25rem .5rem;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Dashboard Keuangan Mushollah21</h3>
        <form method="get" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0">Tahun</label>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for($y = $year-2; $y <= $year+1; $y++): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y==$year?'selected':''; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <!-- Kartu ringkasan -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-summary bg-income">
                <div class="card-body">
                    <div class="label">Total Kas Masuk</div>
                    <div class="value"><?php echo number_format($totalDebet,0,',','.'); ?></div>
                    <small>Debet sepanjang tahun <?php echo $year; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-summary bg-expense">
                <div class="card-body">
                    <div class="label">Total Pengeluaran</div>
                    <div class="value"><?php echo number_format($totalKredit,0,',','.'); ?></div>
                    <small>Kredit sepanjang tahun <?php echo $year; ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-summary bg-balance">
                <div class="card-body">
                    <div class="label">Saldo Akhir (DB)</div>
                    <div class="value"><?php echo number_format($saldoAkhir,0,',','.'); ?></div>
                    <small>Running saldo transaksi terakhir</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-summary bg-trx">
                <div class="card-body">
                    <div class="label">Jumlah Transaksi</div>
                    <div class="value"><?php echo number_format($summary['total_trx']); ?></div>
                    <small>Transaksi tercatat tahun <?php echo $year; ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bar + Line -->
    <div class="row g-3 mb-3">
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Pendapatan & Pengeluaran per Bulan</strong>
                </div>
                <div class="card-body">
                    <canvas id="barMonthly"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Perkembangan Saldo</strong>
                </div>
                <div class="card-body">
                    <canvas id="lineSaldo"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Donut + last transactions -->
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Komposisi Per COA (Top 10)</strong>
                </div>
                <div class="card-body">
                    <canvas id="donutCoa"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>10 Transaksi Terakhir</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                            <tr>
                                <th>Tgl</th>
                                <th>COA</th>
                                <th>Keterangan</th>
                                <th class="text-end">Debet</th>
                                <th class="text-end">Kredit</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($lastRows)): ?>
                                <tr><td colspan="6" class="text-center">Belum ada transaksi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lastRows as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                        <td><?php echo htmlspecialchars($r['nama_coa']); ?></td>
                                        <td><?php echo htmlspecialchars($r['keterangan']); ?></td>
                                        <td class="text-end"><?php echo number_format($r['debet'],0,',','.'); ?></td>
                                        <td class="text-end"><?php echo number_format($r['kredit'],0,',','.'); ?></td>
                                        <td class="text-end"><?php echo number_format($r['saldo'],0,',','.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <a href="report.php" class="btn btn-outline-secondary btn-sm">Lihat Laporan Detail</a>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Menu Utama</a>
    </div>
</div>

<script>
    const months      = <?php echo $jsMonths; ?>;
    const income      = <?php echo $jsIncome; ?>;
    const expense     = <?php echo $jsExpense; ?>;
    const saldoLabels = <?php echo $jsSaldoLabels; ?>;
    const saldoValues = <?php echo $jsSaldoValues; ?>;
    const coaLabels   = <?php echo $jsCoaLabels; ?>;
    const coaValues   = <?php echo $jsCoaValues; ?>;

    // Bar Monthly
    new Chart(document.getElementById('barMonthly'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Kas Masuk (Debet)',
                    data: income
                },
                {
                    label: 'Pengeluaran (Kredit)',
                    data: expense
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Line Saldo
    new Chart(document.getElementById('lineSaldo'), {
        type: 'line',
        data: {
            labels: saldoLabels,
            datasets: [{
                label: 'Saldo',
                data: saldoValues,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: false }
            }
        }
    });

    // Donut COA
    new Chart(document.getElementById('donutCoa'), {
        type: 'doughnut',
        data: {
            labels: coaLabels,
            datasets: [{
                data: coaValues
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>
</body>
</html>
