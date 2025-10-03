<?php require_once 'db.php'; include 'layout.php'; ?>
<h5 class="mb-3">Input Saldo Awal</h5>
<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
$tgl = $_POST['tgl']; // biasanya set ke tanggal 1
$ket = trim($_POST['keterangan'])!==''? $_POST['keterangan'] : 'Kas Awal';
$coa = (int)$_POST['coa_id'];
$nom = (float)$_POST['nominal'];
$stmt=$conn->prepare("INSERT INTO transaksi(tgl,keterangan,coa_id,jenis,nominal) VALUES(?,?,?,?,?)");
$jenis='OPENING';
$stmt->bind_param('ssisd',$tgl,$ket,$coa,$jenis,$nom);
$stmt->execute();
echo '<div class="alert alert-success">Saldo awal disimpan.</div>';
}
$coa = $conn->query("SELECT id,nama FROM coa WHERE is_active=1 ORDER BY nama");
?>
<form method="post" class="card p-3 shadow-sm bg-white" onsubmit="return confirm('Simpan saldo awal?')">
<div class="row g-2">
<div class="col-md-2">
<label class="form-label">Tanggal</label>
<input type="date" name="tgl" class="form-control" value="<?= date('Y-m-01') ?>" required>
</div>
<div class="col-md-4">
<label class="form-label">Keterangan</label>
<input name="keterangan" class="form-control" placeholder="Kas Awal Januari 2025">
</div>
<div class="col-md-3">
<label class="form-label">COA</label>
<select name="coa_id" class="form-select">
<?php while($r=$coa->fetch_assoc()): ?>
<option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nama']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="col-md-3">
<label class="form-label">Nominal (Rp)</label>
<input name="nominal" class="form-control" required onfocus="onlyNumber(this)">
</div>
</div>
<div class="mt-3"><button class="btn btn-primary">Simpan</button></div>
</form>
<?php include 'layout_end.php'; ?>