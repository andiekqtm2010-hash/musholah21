<?php require_once 'db.php'; include 'layout.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-2">
<h5>Master COA</h5>
<a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mCOA">Tambah</a>
</div>

<?php
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['add'])){
    $stmt=$conn->prepare("INSERT INTO coa(kode,nama,tipe,is_active) VALUES(?,?,?,1)");
    $stmt->bind_param('sss', $_POST['kode'], $_POST['nama'], $_POST['tipe']);
    $stmt->execute();
    }elseif(isset($_POST['edit'])){
    $stmt=$conn->prepare("UPDATE coa SET kode=?, nama=?, tipe=?, is_active=? WHERE id=?");
    $stmt->bind_param('sssii', $_POST['kode'], $_POST['nama'], $_POST['tipe'], $_POST['is_active'], $_POST['id']);
    $stmt->execute();
    }
}
$rows = $conn->query("SELECT * FROM coa ORDER BY id DESC");
?>
<table class="table table-sm table-bordered bg-white">
<thead class="table-light"><tr><th>#</th><th>Kode</th><th>Nama</th><th>Tipe</th><th>Aktif</th><th></th></tr></thead>
<tbody>
<?php $no=1; while($r=$rows->fetch_assoc()): ?>
<tr>
<td><?= $no++ ?></td>
<td><?= htmlspecialchars($r['kode']) ?></td>
<td><?= htmlspecialchars($r['nama']) ?></td>
<td><?= htmlspecialchars($r['tipe']) ?></td>
<td><?= $r['is_active']? 'Ya':'Tidak' ?></td>
<td>
<form method="post" class="d-inline">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<input type="hidden" name="kode" value="<?= htmlspecialchars($r['kode']) ?>">
<input type="hidden" name="nama" value="<?= htmlspecialchars($r['nama']) ?>">
<input type="hidden" name="tipe" value="<?= htmlspecialchars($r['tipe']) ?>">
<input type="hidden" name="is_active" value="<?= (int)$r['is_active'] ?>">
<button name="prefill" value="1" class="btn btn-sm btn-outline-secondary" formaction="#mCOA" data-bs-toggle="modal" data-bs-target="#mCOA">Edit</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>


<!-- Modal Tambah/Edit -->
<div class="modal fade" id="mCOA" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<form method="post">
<div class="modal-header"><h6 class="modal-title">COA</h6></div>
<div class="modal-body">
<input type="hidden" name="id" id="coa_id">
<div class="mb-2"><label class="form-label">Kode</label><input class="form-control" name="kode" id="coa_kode" required></div>
<div class="mb-2"><label class="form-label">Nama</label><input class="form-control" name="nama" id="coa_nama" required></div>
<div class="mb-2"><label class="form-label">Tipe</label>
<select class="form-select" name="tipe" id="coa_tipe">
<option>Material</option><option>Konsumsi</option><option>Jasa Tukang</option>
<option>Operasional</option><option>Donasi Masuk</option><option>Kas</option><option>Lainnya</option>
</select>
</div>
<div class="form-check">
<input class="form-check-input" type="checkbox" id="coa_active" checked>
<label class="form-check-label" for="coa_active">Aktif</label>
</div>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Tutup</button>
<button class="btn btn-primary" name="add" value="1">Simpan</button>
</div>
</form>
</div></div></div>


<script>
// (Sederhana) ketika tombol Edit dipencet, isi modal manual via dataset bisa ditambah—
// Untuk pendek, form prefill di atas hanya kirim hidden lalu Anda isi ulang saat "Simpan".
</script>
<?php include 'layout_end.php'; ?>