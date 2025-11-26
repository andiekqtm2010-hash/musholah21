<?php

// kalau variabel belum ada, kasih nilai awal
if (!isset($selectedYear))   $selectedYear   = date('Y');
if (!isset($totalKasMasuk))  $totalKasMasuk  = 0;
if (!isset($totalKeluar))    $totalKeluar    = 0;
if (!isset($totalTransaksi)) $totalTransaksi = 0;
if (!isset($saldoAkhir))     $saldoAkhir     = 0;

// dashboard.php
// =======================
// FILTER TAHUN
// =======================
$year = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : (int)date('Y');

$conn = getConnection();

// Tahun dari combo di kanan atas (url: index.php?page=dashboard&tahun=2025)
$selectedYear = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

/*
 |-------------------------------------------------------
 | 1. HITUNG RINGKASAN UNTUK 4 KOTAK ATAS
 |-------------------------------------------------------
*/
$sqlSummary = "
    SELECT
        SUM(COALESCE(debet,0))  AS total_debet,
        SUM(COALESCE(kredit,0)) AS total_kredit,
        COUNT(*)                AS total_trx
    FROM bukubesar
    WHERE YEAR(tanggal) = ?
";
$stmtSum = $conn->prepare($sqlSummary);
$stmtSum->bind_param('i', $selectedYear);
$stmtSum->execute();
$rowSum = $stmtSum->get_result()->fetch_assoc();

$totalKasMasuk  = (float)($rowSum['total_debet']  ?? 0);
$totalKeluar    = (float)($rowSum['total_kredit'] ?? 0);
$totalTransaksi = (int)  ($rowSum['total_trx']    ?? 0);

// SALDO AKHIR (kalau belum dihitung di tempat lain)
$sqlSaldo = "
    SELECT SUM(COALESCE(debet,0) - COALESCE(kredit,0)) AS saldo
    FROM bukubesar
    WHERE tanggal <= CONCAT(?, '-12-31')
";
$stmtSaldo = $conn->prepare($sqlSaldo);
$stmtSaldo->bind_param('i', $selectedYear);
$stmtSaldo->execute();
$rowSaldo   = $stmtSaldo->get_result()->fetch_assoc();
$saldoAkhir = (float)($rowSaldo['saldo'] ?? 0);

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

<!--<div class="dashboard-wrapper">-->
<div class="small-dashboard">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Dashboard Keuangan Mushollah Darush Sholihin</h3>
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
            <div class="summary-card bg-kasmasuk">
                <div class="label">Total Kas Masuk</div>
                <div class="value"><?= number_format($totalKasMasuk,0,',','.') ?></div>
                <div class="label">Debet sepanjang tahun <?= $selectedYear ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-pengeluaran">
                <div class="label">Total Pengeluaran</div>
                <div class="value"><?= number_format($totalKeluar,0,',','.') ?></div>
                <div class="label">Kredit sepanjang tahun <?= $selectedYear ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-saldoakhir">
                <div class="label">Saldo Akhir (DB)</div>
                <div class="value"><?= number_format($saldoAkhir,0,',','.') ?></div>
                <div class="label">Saldo transaksi terakhir</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="summary-card bg-transaksi">
                <div class="label">Jumlah Transaksi</div>
                <div class="value"><?= $totalTransaksi ?></div>
                <div class="label">Transaksi tercatat <?= $selectedYear ?></div>
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
                                        <td><?php echo htmlspecialchars(date('d-m-y',strtotime($r['tanggal']))); ?></td>
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

    <div class="text-center mb-4">
        <a href="index.php?page=report" class="btn btn-primary me-2">Lihat Laporan Detail</a>
        <!--<a href="index.php" class="btn btn-outline-secondary btn-sm">Menu Utama</a>-->
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
</div>
