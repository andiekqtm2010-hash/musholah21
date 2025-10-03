<?php 
require_once 'db.php'; 
include 'layout.php'; 

// Fallback rupiah() jika belum ada dari layout.php
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

// --- Parameter bulan ---
$ym   = isset($_GET['ym']) && $_GET['ym'] !== '' ? $_GET['ym'] : date('Y-m');
$awal = $ym . '-01';
$akhir= date('Y-m-t', strtotime($awal));

// --- Saldo awal: akumulasi sebelum awal bulan (IN+OPENING - OUT) ---
$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN jenis IN('IN','OPENING') THEN nominal ELSE 0 END),0)
      - COALESCE(SUM(CASE WHEN jenis='OUT' THEN nominal ELSE 0 END),0) AS saldo
    FROM transaksi
    WHERE tgl < ?
");
$stmt->bind_param('s', $awal);
$stmt->execute();
$saldo_awal = (float)($stmt->get_result()->fetch_assoc()['saldo'] ?? 0);
$stmt->close();

// --- Data bulan berjalan ---
$stmt = $conn->prepare("
    SELECT tgl, keterangan, jenis, nominal, c.nama AS coa
    FROM transaksi t 
    JOIN coa c ON c.id = t.coa_id
    WHERE t.tgl BETWEEN ? AND ?
    ORDER BY t.tgl, t.id
");
$stmt->bind_param('ss', $awal, $akhir);
$stmt->execute();
$rows = $stmt->get_result();

$tot_in = $tot_out = 0; 
$saldo  = $saldo_awal; 
$data   = [];

while ($r = $rows->fetch_assoc()) {
    $in  = ($r['jenis'] !== 'OUT') ? (float)$r['nominal'] : 0;
    $out = ($r['jenis'] === 'OUT') ? (float)$r['nominal'] : 0;
    $saldo += $in - $out;
    $tot_in  += $in; 
    $tot_out += $out;
    $r['saldo'] = $saldo; 
    $r['in']    = $in; 
    $r['out']   = $out;
    $data[] = $r;
}
$stmt->close();
?>

<h5 class="mb-3">Report Keuangan Bulanan</h5>

<form class="row g-2 mb-2">
    <div class="col-auto">
        <input type="month" name="ym" value="<?= htmlspecialchars($ym) ?>" class="form-control">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Terapkan</button>
    </div>
    <div class="col-auto">
        <a class="btn btn-outline-secondary" href="print_report.php?ym=<?= urlencode($ym) ?>" target="_blank">Print/PDF</a>
    </div>
</form>

<!-- Text search realtime (keterangan / COA) -->
<div class="mb-2">
    <input type="text" id="searchInput" class="form-control" placeholder="Cari keterangan / COA...">
</div>

<table id="reportTable" class="table table-sm table-bordered bg-white">
    <thead class="table-light">
    <tr>
        <th style="width:40px">#</th>
        <th>Tanggal</th>
        <th>Keterangan</th>
        <th>COA</th>
        <th style="text-align:right;width:120px">Kredit (IN)</th>
        <th style="text-align:right;width:120px">Debet (OUT)</th>
        <th style="text-align:right;width:140px">Saldo</th>
    </tr>
    </thead>
    <tbody>
        <!-- Baris saldo awal -->
        <tr class="table-warning saldo-awal-row">
            <td></td>
            <td><?= date('d/M/y', strtotime($awal)) ?></td>
            <td><em>Saldo Awal</em></td>
            <td>Kas</td>
            <td style="text-align:right"></td>
            <td style="text-align:right"></td>
            <td style="text-align:right"><?= rupiah($saldo_awal) ?></td>
        </tr>

        <?php $i=1; foreach($data as $r): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= date('d/M/y', strtotime($r['tgl'])) ?></td>
            <td><?= htmlspecialchars($r['keterangan']) ?></td>
            <td><?= htmlspecialchars($r['coa']) ?></td>
            <td style="text-align:right"><?= $r['in']  ? rupiah($r['in'])  : '' ?></td>
            <td style="text-align:right"><?= $r['out'] ? rupiah($r['out']) : '' ?></td>
            <td style="text-align:right"><?= rupiah($r['saldo']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="table-light">
        <tr>
            <th colspan="4" class="text-end">Grand Total</th>
            <th style="text-align:right"><?= rupiah($tot_in) ?></th>
            <th style="text-align:right"><?= rupiah($tot_out) ?></th>
            <th style="text-align:right"><?= rupiah($saldo) ?></th>
        </tr>
    </tfoot>
</table>

<script>
// Realtime filter berdasarkan input (Keterangan & COA)
document.getElementById('searchInput').addEventListener('keyup', function() {
    var keyword = this.value.toLowerCase();
    var rows = document.querySelectorAll('#reportTable tbody tr');

    rows.forEach(function(row) {
        // Jangan sembunyikan baris saldo awal
        if (row.classList.contains('saldo-awal-row')) { 
            row.style.display = '';
            return;
        }
        var keterangan = (row.cells[2]?.innerText || '').toLowerCase();
        var coa        = (row.cells[3]?.innerText || '').toLowerCase();

        if (keterangan.includes(keyword) || coa.includes(keyword)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include 'layout_end.php'; ?>
