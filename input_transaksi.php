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
        // Ambil saldo sebelumnya
        $last_saldo = getLastSaldo();

        // Hitung saldo baru (running balance)
        if ($debet > 0) {
            // debet menambah saldo
            $saldo = $last_saldo + $debet;
        } else {
            // kredit mengurangi saldo
            $saldo = $last_saldo - $kredit;
}

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
    <title>Input Transaksi Musholah21</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #2ecc71; /* hijau seperti contoh */
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .wrapper {
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }

        .card {
            background: #ffffff;
            margin: 0 auto;
            max-width: 450px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            padding: 25px 30px 30px 30px;
        }

        .card-header {
            margin-bottom: 20px;
        }

        .card-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .card-header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #777;
        }

        .alert-success {
            background: #e8f9f1;
            border-left: 4px solid #2ecc71;
            padding: 10px 12px;
            font-size: 13px;
            color: #2b7a4b;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        .alert-error {
            background: #fdecea;
            border-left: 4px solid #e74c3c;
            padding: 10px 12px;
            font-size: 13px;
            color: #a94442;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            color: #555;
        }

        .form-group {
            margin-bottom: 12px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 13px;
            outline: none;
        }

        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            border-color: #2ecc71;
        }

        textarea {
            resize: vertical;
            min-height: 60px;
        }

        .btn-submit {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 3px;
            background: #2ecc71;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            margin-top: 5px;
        }

        .btn-submit:hover {
            background: #27ae60;
        }

        .links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }

        .links a {
            color: #2ecc71;
            text-decoration: none;
            margin: 0 5px;
        }

        .links a:hover {
            text-decoration: underline;
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

<div class="wrapper">
    <div class="card">
        <div class="card-header">
            <h1>Input Transaksi Musholah21</h1>
            <p>Isi data transaksi keuangan musholla dengan lengkap.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="return validateForm();">

            <div class="form-group">
                <label>No. Urut</label>
                <input type="number" name="no_urut"
                       value="<?php echo (int)$next_no_urut; ?>" readonly>
            </div>

            <div class="form-group">
                <label>Tanggal Transaksi</label>
                <input type="date" name="tanggal" required>
            </div>

            <div class="form-group">
                <label>Pilih COA</label>
                <select name="coa_id" required>
                    <option value="">-- Pilih COA --</option>
                    <?php foreach ($coa_list as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['nama_coa']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="3"
                          placeholder="Tuliskan keterangan transaksi"></textarea>
            </div>

            <div class="form-group">
                <label>Debet (isi angka atau 0)</label>
                <input type="number" step="0.01" name="debet" id="debet"
                       value="0" oninput="onDebetChange();" required>
            </div>

            <div class="form-group">
                <label>Kredit (isi angka atau 0)</label>
                <input type="number" step="0.01" name="kredit" id="kredit"
                       value="0" oninput="onKreditChange();" required>
            </div>

            <button type="submit" class="btn-submit">Simpan Transaksi</button>
        </form>

        <div class="links">
            <a href="report.php">Lihat Laporan</a> |
            <a href="import.php">Import Data Buku Besar</a> |
            <a href="index.php">Menu Utama</a>
        </div>
    </div>
</div>

</body>
</html>