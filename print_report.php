<?php require_once 'db.php'; ?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Print Report</title>
<style>
body{font-family: Arial, Helvetica, sans-serif}
table{border-collapse: collapse; width:100%}
th,td{border:1px solid #000; padding:6px}
.right{text-align:right}
</style>
</head><body>
<?php
$ym = $_GET['ym'] ?? date('Y-m');
$awal = $ym.'-01';
$akhir = date('Y-m-t', strtotime($awal));


$stmt=$conn->prepare("SELECT COALESCE(SUM(CASE WHEN jenis IN('IN','OPENING') THEN nominal ELSE 0 END),0) -
COALESCE(SUM(CASE WHEN jenis='OUT' THEN nominal ELSE 0 END),0) AS saldo FROM transaksi WHERE tgl < ?");
$stmt->bind_param('s',$awal); $stmt->execute(); $saldo_awal=(float)($stmt->get_result()->fetch_assoc()['saldo']??0); $stmt->close();


$stmt=$conn->prepare("SELECT tgl,keterangan,jenis,nominal,c.nama as coa FROM transaksi t JOIN coa c ON c.id=t.coa_id WHERE tgl BETWEEN ? AND ? ORDER BY tgl, id");
$stmt->bind_param('ss',$awal,$akhir); $stmt->execute(); $rows=$stmt->get_result();


$saldo=$saldo_awal; $tot_in=$tot_out=0; $i=1;
?>
<h3 style="margin:0">Catatan Keuangan Pembangunan</h3>
<div>Musholla Darush Sholiihin</div>
<div>Periode <?= date('F Y', strtotime($awal)) ?></div>
<br>
<table>
<thead>
<tr><th style="width:40px">#</th><th>Tanggal</th><th>Keterangan</th><th>COA</th><th class="right">Kredit</th><th class="right">Debet</th><th class="right">Saldo</th></tr>
</thead>
<tbody>
<tr><td></td><td><?= date('d/M/y', strtotime($awal)) ?></td><td><em>Saldo Awal</em></td><td>Kas</td><td></td><td></td><td class="right"><?= rupiah($saldo_awal) ?></td></tr>
<?php while($r=$rows->fetch_assoc()):
$in = ($r['jenis']!=='OUT')? (float)$r['nominal'] : 0;
$out= ($r['jenis']==='OUT')? (float)$r['nominal'] : 0;
$saldo += $in - $out; $tot_in+=$in; $tot_out+=$out; ?>
<tr>
<td><?= $i++ ?></td>
<td><?= date('d/M/y', strtotime($r['tgl'])) ?></td>
<td><?= htmlspecialchars($r['keterangan']) ?></td>
<td><?= htmlspecialchars($r['coa']) ?></td>
<td class="right"><?= $in? rupiah($in):'' ?></td>
<td class="right"><?= $out? rupiah($out):'' ?></td>
<td class="right"><?= rupiah($saldo) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr><th colspan="4" style="text-align:right">Grand Total</th><th class="right"><?= rupiah($tot_in) ?></th><th class="right"><?= rupiah($tot_out) ?></th><th class="right"><?= rupiah($saldo) ?></th></tr>
</tfoot>
</table>
<script>window.print()</script>
</body></html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Format angka saat ketik
function onlyNumber(el){
el.addEventListener('input', ()=>{
el.value = el.value.replace(/[^\d]/g,'');
});
}
</script>
</body></html>
