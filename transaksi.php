<?php
// REMARK: Tarik koneksi DB & layout (header + helper) yang sudah Bapak punya.
// - db.php biasanya berisi $conn (mysqli) & helper (mis. rupiah()).
// - layout.php biasanya berisi HTML <head>, CSS/JS (Bootstrap), dan buka <body>.

require_once 'db.php';
include 'layout.php';

// letakkan di awal file utama (setelah require db/layout pun boleh),
// yang penting sebelum kita memanggil kelas PhpSpreadsheet:
$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    // fallback aman: tetap izinkan CSV, tapi berikan pesan jika user memilih XLSX
    // (opsional) echo '<div class="alert alert-warning">Autoload Composer tidak ditemukan. Impor XLSX mungkin tidak bisa.</div>';
}

?>


<?php
if (isset($_GET['download']) && $_GET['download'] === 'template_csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_import_musholah21.csv"');
    $out = fopen('php://output', 'w');
    // Header CSV wajib:
    fputcsv($out, ['tgl','keterangan','coa_id','jenis','nominal']);
    // Contoh baris (boleh dihapus):
    fputcsv($out, ['2025-10-01','Infaq Jumat','INFAQ', 'IN', '150000']);
    fputcsv($out, ['01/10/2025','Beli sapu','PERLALAT', 'OUT', '35000']);
    fclose($out);
    exit;
}
?>


<h5 class="mb-3">Transaksi Kas Musholah 21</h5>

<?php
// REMARK: Handler form POST untuk insert transaksi satuan (bukan impor).
// - Cek method POST → ambil field → validasi ringan → simpan pakai prepared statement.
// - Menggunakan bind_param untuk mencegah SQL Injection di nilai variabel.
// - Tipe 'ssisd' artinya: string, string, integer, string, double.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') !== 'import') {
    // REMARK: Ambil nilai dari form. Gunakan null coalescing/ternary agar tidak notice error.
    $tgl  = $_POST['tgl'] ?? date('Y-m-d');              // default ke hari ini jika kosong
    $ket  = trim($_POST['keterangan'] ?? '');            // trim spasi
    $coa  = (int)($_POST['coa_id'] ?? 0);                // paksa integer
    // REMARK: Validasi nilai 'jenis' agar hanya IN/OUT.
    $jenis = (($_POST['jenis'] ?? '') === 'IN') ? 'IN' : 'OUT';

    // REMARK: Nominal: paksa ke float. Jika Bapak memakai input dengan pemisah ribuan,
    // pastikan dibersihkan dulu (hapus titik/koma) sebelum cast agar akurat.
    $rawNom = $_POST['nominal'] ?? '0';
    $rawNom = str_replace(['.', ','], ['', '.'], $rawNom); // contoh normalisasi sederhana
    $nom    = (float)$rawNom;

    // REMARK: Validasi minimal: keterangan tidak boleh kosong, COA valid (>0), nominal > 0.
    if ($ket === '' || $coa <= 0 || $nom <= 0) {
        echo '<div class="alert alert-warning">Mohon lengkapi data: keterangan/coa/nominal.</div>';
    } else {
        // REMARK: Simpan transaksi pakai prepared statement.
        $stmt = $conn->prepare("INSERT INTO transaksi(tgl,keterangan,coa_id,jenis,nominal) VALUES(?,?,?,?,?)");
        $stmt->bind_param('ssisd', $tgl, $ket, $coa, $jenis, $nom);
        $ok = $stmt->execute();
        if ($ok) {
            echo '<div class="alert alert-success">Transaksi tersimpan.</div>';
        } else {
            echo '<div class="alert alert-danger">Gagal menyimpan: ' . htmlspecialchars($stmt->error) . '</div>';
        }
        $stmt->close();
    }
}

// REMARK: Ambil daftar COA aktif untuk <select>. Urutkan agar mudah dipilih user.
$coa = $conn->query("SELECT id,nama FROM coa WHERE is_active=1 ORDER BY nama");
?>

<!-- REMARK: Baris tombol aksi di atas form.
     - d-flex + justify-content-end → tombol menjorok ke kanan.
     - gap-2 → jarak antar tombol.
     - Border di-nolkan hanya untuk debug/rapi tampilan. -->

     <div class="d-flex justify-content-end gap-2 mb-3" style="border:0px solid #0f0f0f">
  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
    Impor Excel/CSV
  </button>
  <!-- REMARK: Opsi link template CSV agar user menyesuaikan format impor. Aktifkan bila sudah ada file. -->
  <!-- <a href="template_import.csv" class="btn btn-outline-secondary">Download Template</a> -->
</div>

<!-- REMARK: Form input transaksi satuan -->
<form method="post" class="card p-3 shadow-sm bg-white mb-3" autocomplete="off">
  <div class="row g-2">
    <!-- REMARK: Input tanggal. Default hari ini, required untuk mencegah kosong. -->
    <div class="col-md-2">
      <label class="form-label">Tanggal</label>
      <input type="date" name="tgl" class="form-control" value="<?= date('Y-m-d') ?>" required>
    </div>

    <!-- REMARK: Keterangan transaksi (deskripsi bebas). -->
    <div class="col-md-4">
      <label class="form-label">Keterangan</label>
      <input name="keterangan" class="form-control" required>
    </div>

    <!-- REMARK: Pilih COA dari tabel coa. Pastikan selaras dengan mapping akun. -->
    <div class="col-md-3">
      <label class="form-label">COA</label>
      <select name="coa_id" class="form-select">
        <?php while ($r = $coa->fetch_assoc()): ?>
          <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nama']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <!-- REMARK: Jenis transaksi: IN (debet) atau OUT (kredit). -->
    <div class="col-md-1">
      <label class="form-label">Jenis</label>
      <select name="jenis" class="form-select">
        <option value="IN">IN</option>
        <option value="OUT">OUT</option>
      </select>
    </div>

    <!-- REMARK: Nominal rupiah. onfocus memanggil onlyNumber(this) → kita sediakan fungsinya di bawah. -->
    <div class="col-md-2">
      <label class="form-label">Nominal (Rp)</label>
      <input name="nominal" class="form-control" required onfocus="onlyNumber(this)">
    </div>
  </div>

  <!-- REMARK: Hidden 'mode' kosong → menandai ini bukan impor. Dipakai pada handler POST di atas. -->
  <input type="hidden" name="mode" value="">

  <div class="mt-2">
    <button class="btn btn-primary">Simpan</button>
  </div>
</form>

<?php
// REMARK: Menampilkan 20 transaksi terakhir.
// - JOIN ke COA untuk tampilkan nama akun.
// - Urut terbaru berdasarkan tgl lalu id.

$res = $conn->query("
  SELECT t.*, c.nama AS coa
  FROM transaksi t
  JOIN coa c ON c.id = t.coa_id
  ORDER BY t.tgl DESC, t.id DESC
  LIMIT 20
");
?>

<!-- REMARK: Tabel daftar transaksi (ringkas). Kolom IN/OUT dipisah agar mudah scanning. -->
<table class="table table-sm table-bordered bg-white">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Tanggal</th>
      <th>Keterangan</th>
      <th>COA</th>
      <th style="text-align:right">Debet (IN)</th>
      <th style="text-align:right">Kredit (OUT)</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; while ($r = $res->fetch_assoc()): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td><?= date('d/M/y', strtotime($r['tgl'])) ?></td>
        <td><?= htmlspecialchars($r['keterangan']) ?></td>
        <td><?= htmlspecialchars($r['coa']) ?></td>
        <!-- REMARK: Tampilkan nominal di kolom sesuai jenis -->
        <td style="text-align:right">
          <?= ($r['jenis'] !== 'OUT') ? rupiah($r['nominal']) : '' ?>
        </td>
        <td style="text-align:right">
          <?= ($r['jenis'] === 'OUT') ? rupiah($r['nominal']) : '' ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<!-- REMARK: Modal Impor (lengkap + tombol submit) 
     - Form enctype multipart agar bisa upload file.
     - name="mode" diset 'import' supaya di handler POST kita bisa bedakan prosesnya. -->

<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <form method="post" enctype="multipart/form-data" class="modal-content" id="importForm">
      <input type="hidden" name="mode" value="import">

      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">Impor Transaksi dari Excel/CSV</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Pilih file (.xlsx / .csv)</label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required id="fileInput">
          <div class="form-text">
            Urutan kolom: <code>tgl</code>, <code>keterangan</code>, <code>coa_id</code>, <code>jenis</code>, <code>nominal</code>
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted">Tips: CSV lebih ringan jika internet/composer bermasalah.</small>
          <a href="?download=template_csv" class="btn btn-outline-secondary btn-sm">
            Download Template CSV
          </a>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">
          Proses Impor
        </button>
      </div>
    </form>
  </div>
</div>



<?php
// ========================= IMPORT EXCEL/CSV HANDLER =========================
// Letakkan blok ini sebelum include 'layout_end.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'import') {

    // --- Validasi upload dasar
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="alert alert-danger">File tidak terunggah atau terjadi error saat upload.</div>';
    } else {
        $tmpPath = $_FILES['file']['tmp_name'];
        $origName = $_FILES['file']['name'] ?? 'upload';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // --- Baca baris data (array of assoc)
        try {
            if ($ext === 'csv') {
                $rows = readCsvRows($tmpPath);
            } elseif ($ext === 'xlsx') {
                // butuh phpoffice/phpspreadsheet
                $rows = readXlsxRows($tmpPath);
            } else {
                throw new RuntimeException("Ekstensi tidak didukung: .$ext (hanya .csv / .xlsx)");
            }

            // --- Validasi header minimum
            $required = ['tgl','keterangan','coa_id','jenis','nominal'];
            $headerOk = validateHeaders($rows['headers'] ?? [], $required);
            if (!$headerOk) {
                throw new RuntimeException(
                    "Header tidak sesuai. Wajib: ".implode(', ', $required).". ".
                    "Header terdeteksi: ".implode(', ', ($rows['headers'] ?? []))
                );
            }

            $data = $rows['data'] ?? [];
            if (!$data) {
                throw new RuntimeException("Tidak ada baris data yang terbaca.");
            }

            // --- Siapkan cache COA dan prepared statements
            $coaCache = []; // coa_id => id
            $maxRows  = 5000; // batas aman
            $inserted = 0;
            $errors   = []; // kumpulkan error per baris
            $lineNo   = 1 + 1; // asumsi baris data mulai setelah header (header=baris 1)

            // Transaction supaya atomic
            $conn->begin_transaction();
            $stmtIns = $conn->prepare("INSERT INTO transaksi(tgl,keterangan,coa_id,jenis,nominal) VALUES(?,?,?,?,?)");
            if (!$stmtIns) { throw new RuntimeException("Prepare insert gagal: ".$conn->error); }

            foreach ($data as $row) {
                if ($inserted + count($errors) >= $maxRows) {
                    $errors[] = "Dibatasi maksimal $maxRows baris per impor.";
                    break;
                }

                // --- Normalisasi & validasi tiap kolom
                $norm = normalizeRow($row);

                // Lewati baris kosong penuh
                if ($norm === null) { $lineNo++; continue; }

                // Resolve COA ID dari coa_id
                $code = $norm['coa_id'];
                $coa_id = resolveCoaIdByCode($conn, $code, $coaCache);

                $errMsg = [];
                if (!$norm['tgl'])      { $errMsg[] = "tgl tidak valid"; }
                if ($norm['keterangan']==='') { $errMsg[] = "keterangan kosong"; }
                if (!$coa_id)           { $errMsg[] = "coa_id [$code] tidak ditemukan di master COA"; }
                if (!in_array($norm['jenis'], ['IN','OUT'], true)) { $errMsg[] = "jenis harus IN/OUT"; }
                if ($norm['nominal'] <= 0) { $errMsg[] = "nominal harus > 0"; }

                if ($errMsg) {
                    $errors[] = "Baris {$lineNo}: ".implode('; ', $errMsg);
                    $lineNo++;
                    continue;
                }

                // --- Eksekusi insert
                $tgl = $norm['tgl']; $ket = $norm['keterangan']; $jenis = $norm['jenis']; $nom = $norm['nominal'];
                $stmtIns->bind_param('ssisd', $tgl, $ket, $coa_id, $jenis, $nom);
                if (!$stmtIns->execute()) {
                    $errors[] = "Baris {$lineNo}: gagal insert (".$stmtIns->error.")";
                } else {
                    $inserted++;
                }
                $lineNo++;
            }

            // Commit/Rollback sesuai kondisi
            if ($inserted > 0) {
                $conn->commit();
            } else {
                $conn->rollback();
            }

            // --- Tampilkan ringkasan
            if ($inserted > 0) {
                echo '<div class="alert alert-success">Impor selesai. Berhasil: <b>'.$inserted.'</b> baris.'
                   . ($errors ? ' Sebagian baris gagal: '.count($errors).'.' : '')
                   . '</div>';
            }
            if ($inserted === 0 && $errors) {
                echo '<div class="alert alert-danger">Tidak ada baris yang berhasil diimpor.</div>';
            }

            // --- Tampilkan error detail (maks 20 baris agar tidak kepanjangan)
            if ($errors) {
                echo '<div class="alert alert-warning"><b>Catatan error ('.count($errors).'):</b><br><ul style="margin:0;padding-left:18px">';
                foreach (array_slice($errors, 0, 20) as $e) {
                    echo '<li>'.htmlspecialchars($e).'</li>';
                }
                if (count($errors) > 20) {
                    echo '<li>…('.(count($errors)-20).' lagi)</li>';
                }
                echo '</ul></div>';
            }

            if (isset($stmtIns) && $stmtIns) { $stmtIns->close(); }
        } catch (Throwable $e) {
            if ($conn->errno) { $conn->rollback(); }
            echo '<div class="alert alert-danger">Gagal memproses impor: '.htmlspecialchars($e->getMessage()).'</div>';
        }
    }
}

// ========================= HELPER FUNCTIONS =========================

/**
 * Baca CSV → return ['headers'=>[], 'data'=>[assoc,...]]
 * - Asumsi separator koma/semicolon otomatis.
 * - Trim BOM & spasi.
 */
function readCsvRows(string $path): array {
    $fh = fopen($path, 'r');
    if (!$fh) throw new RuntimeException("Tidak bisa membuka file CSV.");

    // Deteksi delimiter sederhana (',' atau ';')
    $firstLine = fgets($fh);
    if ($firstLine === false) throw new RuntimeException("File CSV kosong.");
    $firstLine = trimBOM($firstLine);
    $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

    // Reset pointer, baca header
    rewind($fh);
    $headers = fgetcsv($fh, 0, $delim);
    if (!$headers) throw new RuntimeException("Header CSV tidak terbaca.");
    $headers = array_map(fn($h)=> strtolower(trim($h ?? '')), $headers);

    $data = [];
    while (($row = fgetcsv($fh, 0, $delim)) !== false) {
        if (count($row) === 1 && trim($row[0]) === '') { continue; } // lewati kosong
        $assoc = [];
        foreach ($headers as $i=>$h) { $assoc[$h] = $row[$i] ?? ''; }
        $data[] = $assoc;
    }
    fclose($fh);
    return ['headers'=>$headers, 'data'=>$data];
}

/**
 * Baca XLSX via PhpSpreadsheet → return ['headers'=>[], 'data'=>[assoc,...]]
 */
function readXlsxRows(string $path): array {
    // Pastikan library tersedia
    if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
        throw new RuntimeException("PhpSpreadsheet belum terpasang. Jalankan: composer require phpoffice/phpspreadsheet");
    }
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getActiveSheet();

    $rows = [];
    foreach ($sheet->toArray(null, true, true, true) as $arr) {
        // $arr = ['A'=>..., 'B'=>...]
        $rows[] = array_values($arr); // jadi numerik
    }
    if (!$rows) throw new RuntimeException("Sheet kosong.");

    // Header
    $headerRow = array_map(fn($h)=> strtolower(trim((string)$h)), $rows[0]);
    $data = [];
    for ($i=1; $i<count($rows); $i++) {
        $r = $rows[$i];
        // lewati baris kosong penuh
        if (count(array_filter($r, fn($v)=> trim((string)$v) !== '')) === 0) { continue; }
        $assoc = [];
        foreach ($headerRow as $idx=>$h) { $assoc[$h] = $r[$idx] ?? ''; }
        $data[] = $assoc;
    }
    return ['headers'=>$headerRow, 'data'=>$data];
}

/** Hilangkan BOM UTF-8 jika ada */
function trimBOM(string $s): string {
    $bom = "\xEF\xBB\xBF";
    if (strncmp($s, $bom, 3) === 0) { $s = substr($s, 3); }
    return $s;
}

/** Cek header wajib ada semua */
function validateHeaders(array $headers, array $required): bool {
    $hset = array_flip($headers);
    foreach ($required as $r) {
        if (!isset($hset[strtolower($r)])) return false;
    }
    return true;
}

/**
 * Normalisasi satu baris impor → array ['tgl','keterangan','coa_id','jenis','nominal']
 * - Tanggal: terima 'Y-m-d', 'd/m/Y', 'd-m-Y'
 * - Jenis: IN/OUT (case-insensitive)
 * - Nominal: buang pemisah ribuan, koma→titik
 * - Baris kosong penuh → return null
 */
function normalizeRow(array $row): ?array {
    // Normalisasi keys
    $map = [];
    foreach ($row as $k=>$v) { $map[strtolower(trim((string)$k))] = $v; }

    $tglStr   = trim((string)($map['tgl'] ?? ''));
    $ket      = trim((string)($map['keterangan'] ?? ''));
    $coa_id = trim((string)($map['coa_id'] ?? ''));
    $jenis    = strtoupper(trim((string)($map['jenis'] ?? '')));
    $nomStr   = trim((string)($map['nominal'] ?? ''));

    // Baris kosong penuh?
    if ($tglStr==='' && $ket==='' && $coa_id==='' && $jenis==='' && $nomStr==='') {
        return null;
    }

    // Tanggal
    $tgl = parseDateFlex($tglStr); // 'Y-m-d' atau null

    // Jenis
    if ($jenis !== 'IN' && $jenis !== 'OUT') {
        // toleransi: debit/credit?
        if (in_array($jenis, ['DEBIT','DEBET','MASUK'], true)) $jenis = 'IN';
        elseif (in_array($jenis, ['KREDIT','KELUAR'], true))    $jenis = 'OUT';
    }

    // Nominal → float (hapus pemisah ribuan)
    $nomStr = str_replace([' ', "\xC2\xA0"], '', $nomStr); // hapus spasi & nbsp
    // Pola umum: kalau mengandung titik dan koma, asumsikan titik=ribuan, koma=desimal
    if (strpos($nomStr, '.') !== false && strpos($nomStr, ',') !== false) {
        $nomStr = str_replace('.', '', $nomStr);
        $nomStr = str_replace(',', '.', $nomStr);
    } else {
        // Kalau hanya koma, ganti ke titik
        if (strpos($nomStr, ',') !== false && strpos($nomStr, '.') === false) {
            $nomStr = str_replace(',', '.', $nomStr);
        }
        // Kalau hanya titik, anggap sudah desimal atau ribuan → biarkan (float_cast akan jalan)
    }
    $nominal = (float)$nomStr;

    return [
        'tgl'        => $tgl,
        'keterangan' => $ket,
        'coa_id'   => $coa_id,
        'jenis'      => $jenis,
        'nominal'    => $nominal,
    ];
}

/** Parse tanggal fleksibel → 'Y-m-d' atau null */
function parseDateFlex(string $s): ?string {
    $s = trim($s);
    if ($s === '') return null;

    // Jika numeric Excel date (serial), coba konversi (Excel 1900-based)
    if (is_numeric($s)) {
        $base = new DateTime('1899-12-30'); // Excel serial day 1 = 1900-01-01 → offset 25569 dari Unix; cara aman: tambah hari
        $clone = clone $base;
        $clone->modify('+'.((int)$s).' days');
        return $clone->format('Y-m-d');
    }

    $formats = ['Y-m-d','d/m/Y','d-m-Y','m/d/Y','d.m.Y'];
    foreach ($formats as $f) {
        $dt = DateTime::createFromFormat($f, $s);
        if ($dt && $dt->format($f) === $s) {
            return $dt->format('Y-m-d');
        }
    }
    // Coba parse bebas
    $ts = strtotime($s);
    if ($ts !== false) {
        return date('Y-m-d', $ts);
    }
    return null;
}

/**
 * Dapatkan coa_id dari coa_id (cache agar hemat query).
 * - Cocokkan ke kolom `code` di tabel `coa` (ubah sesuai skema Bapak).
 * - Hanya terima yang aktif (is_active=1). Sesuaikan jika tak perlu.
 */
function resolveCoaIdByCode(mysqli $conn, string $code, array &$cache): ?int {
    $key = strtoupper(trim($code));
    if ($key === '') return null;
    if (isset($cache[$key])) return $cache[$key];

    $stmt = $conn->prepare("SELECT id FROM coa WHERE UPPER(code)=? AND is_active=1 LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $id = null;
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();

    $cache[$key] = $id ? (int)$id : null;
    return $cache[$key];
}
?>



<?php
// REMARK: Tutup layout (biasanya berisi script bundle Bootstrap juga).
// - Jika layout_end.php sudah memuat <script> Bootstrap, sebaiknya HINDARI memuat ulang di bawah agar tidak duplikat.
include 'layout_end.php';
?>