<?php
// =======================================================
// input_transaksi.php
// Form input transaksi manual untuk tabel bukubesar
// =======================================================

require 'functions.php';
requireLogin();   // memastikan user sudah login

// Untuk contoh, created_by kita isi 1 (belum ada login)
$default_user_id = 1;

// Ambil daftar COA untuk dropdown
$coa_list = getCoaList();

// Ambil next no_urut
$next_no_urut = getNextNoUrut();

$message = "";
$error   = "";

// -------------------------------------------------------
// PROSES SAAT FORM DI-SUBMIT
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil value dari form
    $tanggal    = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $coa_id     = (int)$_POST['coa_id'];
    $debet      = (float)$_POST['debet'];
    $kredit     = (float)$_POST['kredit'];
    $no_urut    = (int)$_POST['no_urut'];

    // ----------------------------------------------
    // VALIDASI: hanya boleh DEBET atau KREDIT
    // - Jika dua-duanya 0  -> error
    // - Jika dua-duanya >0 -> error
    // ----------------------------------------------
    if (($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
        $error = "Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).";
    }

    if (empty($tanggal)) {
        $error = "Tanggal wajib diisi.";
    }

    if (empty($coa_id)) {
        $error = "COA wajib dipilih.";
    }

    // Jika tidak ada error, proses simpan
    if (empty($error)) {

        // Hitung saldo sederhana: debet - kredit
        $saldo = $debet - $kredit;

        // Siapkan array data untuk insert
        $data = [
            'no_urut'    => $no_urut,
            'tanggal'    => $tanggal,
            'keterangan' => $keterangan,
            'coa_id'     => $coa_id,
            'debet'      => $debet,
            'kredit'     => $kredit,
            'saldo'      => $saldo,
            'created_by' => $default_user_id
        ];

        // Simpan ke database
        insertBukuBesarRow($data);

        // Pesan sukses
        $message = "Transaksi berhasil disimpan!";

        // Reset form: ambil nomor urut baru
        $next_no_urut = getNextNoUrut();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Input Transaksi Buku Besar</title>
    <style>
        body { font-family: Arial; }
        label { display:block; margin-top:10px; }
        input, select, textarea {
            padding: 6px;
            width: 300px;
        }
    </style>
    <script>
        // ----------------------------------------------
        // Validasi sederhana di sisi client (JavaScript)
        // untuk memastikan hanya Debet atau Kredit yang diisi
        // ----------------------------------------------
        function validateForm() {
            var debet  = parseFloat(document.getElementById('debet').value)  || 0;
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;

            if ((debet <= 0 && kredit <= 0) || (debet > 0 && kredit > 0)) {
                alert('Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).');
                return false;
            }
            return true;
        }

        // Jika user mengisi Debet > 0, otomatis nol-kan Kredit, dan sebaliknya
        function onDebetChange() {
            var debet = parseFloat(document.getElementById('debet').value) || 0;
            if (debet > 0) {
                document.getElementById('kredit').value = 0;
            }
        }
        function onKreditChange() {
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;
            if (kredit > 0) {
                document.getElementById('debet').value = 0;
            }
        }
    </script>
</head>
<body>

<h1>Input Transaksi Buku Besar</h1>

<?php if (!empty($message)): ?>
    <p style="color:green; font-weight:bold;"><?php echo $message; ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <p style="color:red; font-weight:bold;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post" onsubmit="return validateForm();">

    <!-- No Urut (auto) -->
    <label>No. Urut:</label>
    <input type="number" name="no_urut" value="<?php echo (int)$next_no_urut; ?>" readonly>

    <!-- Tanggal -->
    <label>Tanggal Transaksi:</label>
    <input type="date" name="tanggal" required>

    <!-- COA -->
    <label>Pilih COA:</label>
    <select name="coa_id" required>
        <option value="">-- Pilih COA --</option>
        <?php foreach ($coa_list as $c): ?>
            <option value="<?php echo $c['id']; ?>">
                <?php echo htmlspecialchars($c['nama_coa']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <!-- Keterangan -->
    <label>Keterangan:</label>
    <textarea name="keterangan" rows="3"></textarea>

    <!-- Debet -->
    <label>Debet (isi angka atau 0):</label>
    <input type="number" step="0.01" name="debet" id="debet" value="0" oninput="onDebetChange();" required>

    <!-- Kredit -->
    <label>Kredit (isi angka atau 0):</label>
    <input type="number" step="0.01" name="kredit" id="kredit" value="0" oninput="onKreditChange();" required>

    <br><br>
    <button type="submit">Simpan Transaksi</button>
</form>

<br>
<p>
    <a href="report.php">Lihat Laporan</a> |
    <a href="import.php">Import Excel</a> |
    <a href="index.php">Menu Utama</a>
</p>

</body>
</html>
