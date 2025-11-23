<?php
// =======================================================
// edit_transaksi.php
// Form edit transaksi buku besar
// =======================================================

require 'functions.php';

// Ambil ID transaksi dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("ID transaksi tidak valid.");
}

// Ambil data transaksi
$transaksi = getTransaksiById($id);
if (!$transaksi) {
    die("Data transaksi tidak ditemukan.");
}

// Ambil daftar COA
$coa_list = getCoaList();

$message = "";
$error   = "";

// -------------------------------------------------------
// PROSES SAAT FORM DI-SUBMIT
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $no_urut    = (int)$_POST['no_urut'];
    $tanggal    = $_POST['tanggal'];
    $keterangan = $_POST['keterangan'];
    $coa_id     = (int)$_POST['coa_id'];
    $debet      = (float)$_POST['debet'];
    $kredit     = (float)$_POST['kredit'];

    // Validasi Debet/Kredit
    if (($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
        $error = "Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).";
    }

    if (empty($tanggal)) {
        $error = "Tanggal wajib diisi.";
    }

    if (empty($coa_id)) {
        $error = "COA wajib dipilih.";
    }

    if (empty($error)) {
        $saldo = $debet - $kredit;

        $data = [
            'no_urut'    => $no_urut,
            'tanggal'    => $tanggal,
            'keterangan' => $keterangan,
            'coa_id'     => $coa_id,
            'debet'      => $debet,
            'kredit'     => $kredit,
            'saldo'      => $saldo
        ];

        updateBukuBesarRow($id, $data);

        // Refresh data untuk ditampilkan
        $transaksi = getTransaksiById($id);
        $message = "Transaksi berhasil diupdate.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Transaksi Buku Besar</title>
    <style>
        body { font-family: Arial; }
        label { display:block; margin-top:10px; }
        input, select, textarea {
            padding: 6px;
            width: 300px;
        }
    </style>
    <script>
        function validateForm() {
            var debet  = parseFloat(document.getElementById('debet').value)  || 0;
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;

            if ((debet <= 0 && kredit <= 0) || (debet > 0 && kredit > 0)) {
                alert('Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).');
                return false;
            }
            return true;
        }

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
<h1>Edit Transaksi Buku Besar</h1>

<?php if (!empty($message)): ?>
    <p style="color:green; font-weight:bold;"><?php echo $message; ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <p style="color:red; font-weight:bold;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="post" onsubmit="return validateForm();">

    <label>No. Urut:</label>
    <input type="number" name="no_urut" value="<?php echo (int)$transaksi['no_urut']; ?>">

    <label>Tanggal Transaksi:</label>
    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($transaksi['tanggal']); ?>" required>

    <label>Pilih COA:</label>
    <select name="coa_id" required>
        <option value="">-- Pilih COA --</option>
        <?php foreach ($coa_list as $c): ?>
            <option value="<?php echo $c['id']; ?>"
                <?php echo ($c['id'] == $transaksi['coa_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['nama_coa']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Keterangan:</label>
    <textarea name="keterangan" rows="3"><?php echo htmlspecialchars($transaksi['keterangan']); ?></textarea>

    <label>Debet:</label>
    <input type="number" step="0.01" name="debet" id="debet"
           value="<?php echo number_format($transaksi['debet'], 2, '.', ''); ?>"
           oninput="onDebetChange();" required>

    <label>Kredit:</label>
    <input type="number" step="0.01" name="kredit" id="kredit"
           value="<?php echo number_format($transaksi['kredit'], 2, '.', ''); ?>"
           oninput="onKreditChange();" required>

    <br><br>
    <button type="submit">Update Transaksi</button>
</form>

<br>
<p>
    <a href="report.php">Kembali ke Laporan</a> |
    <a href="index.php">Menu Utama</a>
</p>

</body>
</html>
