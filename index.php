<?php
// REMARK: Tarik koneksi DB & layout (header, CSS/JS Bootstrap, helper seperti rupiah()).
require_once 'db.php';
include 'layout.php';
?>

<h5 class="mb-3">Ringkasan Cepat</h5>

<?php
// REMARK: Siapkan tanggal acuan.
// - $today: tanggal hari ini (jika suatu saat dibutuhkan).
// - $bulan_ini_awal: tanggal hari pertama bulan berjalan → dipakai sebagai batas bawah perhitungan bulan ini.
$today = date('Y-m-d');
$bulan_ini_awal = date('Y-m-01');

// REMARK: Hitung "saldo awal" per awal bulan berjalan.
// - Logika: total IN + OPENING dikurangi total OUT untuk semua transaksi SEBELUM awal bulan ini.
// - COALESCE untuk antisipasi NULL saat SUM tanpa baris.
$stmt = $conn->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN jenis IN('IN','OPENING') THEN nominal ELSE 0 END),0)
      -
      COALESCE(SUM(CASE WHEN jenis='OUT' THEN nominal ELSE 0 END),0)
      AS saldo
    FROM transaksi
    WHERE tgl < ?
");
$stmt->bind_param('s', $bulan_ini_awal);
$stmt->execute();

// REMARK: Ambil nilai saldo dari result; gunakan null coalescing untuk antisipasi jika fetch gagal.
$saldo_awal = $stmt->get_result()->fetch_assoc()['saldo'] ?? 0;
$stmt->close();

// REMARK: Hitung total IN dan OUT untuk rentang bulan berjalan.
// - BETWEEN ? AND LAST_DAY(?) → param yang sama ($bulan_ini_awal) dipakai untuk periode bulan ini (1 s.d. hari terakhir).
// - Hasil disimpan pada array $r dengan key 'inx' (total IN) dan 'outx' (total OUT).
$stmt = $conn->prepare("
    SELECT
      COALESCE(SUM(CASE WHEN jenis IN('IN','OPENING') THEN nominal ELSE 0 END),0) AS inx,
      COALESCE(SUM(CASE WHEN jenis='OUT' THEN nominal ELSE 0 END),0) AS outx
    FROM transaksi
    WHERE tgl BETWEEN ? AND LAST_DAY(?)
");
$stmt->bind_param('ss', $bulan_ini_awal, $bulan_ini_awal);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc() ?: ['inx' => 0, 'outx' => 0];
$stmt->close();

// REMARK: Saldo akhir = saldo awal (s/d akhir bulan lalu) + total IN bulan ini - total OUT bulan ini.
// - $r['inx'] dan $r['outx'] sudah dipastikan ada nilainya (0 jika tidak ada baris).
$saldo_akhir = (float)$saldo_awal + (float)$r['inx'] - (float)$r['outx'];
?>

<!-- REMARK: Grid ringkas 3 kartu + 1 kartu saldo akhir.
     - Gunakan .row.g-3 untuk spasi antar kolom.
     - Kartu menampilkan nilai yang sudah di-format Rupiah oleh helper rupiah(). -->
<div class="row g-3">
  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Saldo Awal Bulan Ini</div>
        <div class="fs-4 fw-semibold">Rp <?= rupiah($saldo_awal) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Masuk Bulan Ini</div>
        <!-- REMARK: Class text-success untuk penekanan positif -->
        <div class="fs-4 fw-semibold text-success">Rp <?= rupiah($r['inx']) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Keluar Bulan Ini</div>
        <!-- REMARK: Class text-danger untuk penekanan pengeluaran -->
        <div class="fs-4 fw-semibold text-danger">Rp <?= rupiah($r['outx']) ?></div>
      </div>
    </div>
  </div>

  <div class="col-md-12">
    <!-- REMARK: border-primary untuk menonjolkan kartu saldo akhir -->
    <div class="card border-primary shadow-sm">
      <div class="card-body">
        <div class="small text-muted">Saldo Akhir</div>
        <div class="fs-3 fw-bold">Rp <?= rupiah($saldo_akhir) ?></div>
      </div>
    </div>
  </div>
</div>

<?php
// REMARK: Tutup layout (biasanya berisi script bundle Bootstrap juga).
// - Jika layout_end.php sudah memuat <script> Bootstrap, sebaiknya HINDARI memuat ulang di bawah agar tidak duplikat.
include 'layout_end.php';
?>

</div> <!-- REMARK: Periksa: kemungkinan ini adalah extra penutup </div>. Pastikan pasangan <div> sebelumnya memang ada. -->

<!-- REMARK: Jika Bootstrap bundle sudah dimuat di layout_end.php, baris berikut bisa dihapus untuk mencegah double-load. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// REMARK: Utility input angka sederhana: menghapus semua karakter non-digit saat user mengetik.
// - Cocok untuk input nominal polos tanpa format ribuan/decimal.
// - Jika Bapak butuh format ribuan (1.000.000) atau desimal, pertimbangkan masker khusus (mis. Cleave.js/AutoNumeric).
function onlyNumber(el) {
  // Pasang listener input untuk setiap perubahan nilai.
  el.addEventListener('input', () => {
    // Hapus semua selain digit 0-9. (Tidak mendukung koma/titik desimal pada versi sederhana ini.)
    el.value = el.value.replace(/[^\d]/g, '');
  });
}

// REMARK: Catatan tambahan:
// - Pastikan timezone konsisten (mis. di awal aplikasi set: date_default_timezone_set('Asia/Jakarta');),
//   agar perhitungan tanggal & tampilan hari/bulan sesuai WIB.
// - Jika suatu saat jenis "OPENING" hanya boleh dihitung sebagai saldo awal (bukan “Masuk Bulan Ini”),
//   maka di query kedua ganti kondisi jenis IN('IN') saja (tanpa 'OPENING').
</script>
