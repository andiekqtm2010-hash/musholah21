<?php
// =======================================================
// input_transaksi.php
// Form input transaksi buku besar Musholah21
// Setiap transaksi akan masuk ke tabel bukubesar
// =======================================================

// NOTE: Login dan functions.php sebenarnya harus di-include
// tetapi untuk versi embed (dipanggil via index.php) biasanya
// sudah dimasukkan di index.php
// require 'functions.php';
// requireLogin();

// ------------------------------
// Default user (sementara)
// Karena belum ada login session
// ------------------------------
$default_user_id = 1;

// ------------------------------------------------------
// Ambil daftar COA dari database
// COA akan digunakan untuk dropdown pemilihan akun transaksi
// ------------------------------------------------------
$coa_list = getCoaList();

// ------------------------------------------------------
// Ambil nomor urut transaksi berikutnya
// No urut digunakan untuk menjaga urutan transaksi
// ------------------------------------------------------
$next_no_urut = getNextNoUrut();

// Variabel untuk menampung pesan error / sukses
$message = "";
$error   = "";

// ======================================================
// PROSES SAAT FORM DISUBMIT (method POST)
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------------------------------------------------
    // AMBIL NILAI DARI FORM POST
    // --------------------------------------------------
    $tanggal    = $_POST['tanggal'];              // tanggal transaksi
    //$tahun      = $_POST['tahun'] ?? date('Y');   // tahun transaksi (fallback tahun sekarang)
    $tahun      = date('Y',strtotime($_POST['tanggal'])); // tahun dari dtpicker date
    $keterangan = $_POST['keterangan'];           // keterangan transaksi
    $coa_id     = (int)$_POST['coa_id'];          // id COA dipilih user
    $debet      = (float)$_POST['debet'];         // nilai debet
    $kredit     = (float)$_POST['kredit'];        // nilai kredit
    $no_urut    = (int)$_POST['no_urut'];         // nomor urut transaksi

    // ===================================================
    // VALIDASI NILAI DEBET / KREDIT
    // - kedua-duanya 0 → salah
    // - kedua-duanya >0 → salah
    // Hanya boleh salah satu ada isi
    // ===================================================
    if (($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
        $error = "Isi hanya salah satu: Debet ATAU Kredit (dan nilainya harus > 0).";
    }

    // ---------------------------------------------------
    // VALIDASI TANGGAL
    // ---------------------------------------------------
    if (empty($tanggal)) {
        $error = "Tanggal transaksi wajib diisi.";
    }

    // ---------------------------------------------------
    // VALIDASI COA DIPILIH
    // ---------------------------------------------------
    if (empty($coa_id)) {
        $error = "COA wajib dipilih.";
    }

    // ===================================================
    // JIKA TIDAK ADA ERROR → PROSES SIMPAN TRANSAKSI
    // ===================================================
    if (empty($error)) {

        // --------------------------------------------------
        // Ambil saldo terakhir untuk hitung saldo berikutnya
        // --------------------------------------------------
        $last_saldo = getLastSaldo();

        // --------------------------------------------------
        // Hitung saldo baru (running balance)
        // Debet menambah saldo, Kredit mengurangi saldo
        // --------------------------------------------------
        if ($debet > 0) {
            $saldo = $last_saldo + $debet;
        } else {
            $saldo = $last_saldo - $kredit;
        }

        // --------------------------------------------------
        // Siapkan data yang akan di-insert ke database
        // --------------------------------------------------
        $data = [
            'no_urut'    => $no_urut,
            'tahun'      => $tahun,
            'tanggal'    => $tanggal,
            'keterangan' => $keterangan,
            'coa_id'     => $coa_id,
            'debet'      => $debet,
            'kredit'     => $kredit,
            'saldo'      => $saldo,
            'created_by' => $default_user_id
        ];

        // --------------------------------------------------
        // Simpan ke database (fungsi insert ada di functions.php)
        // --------------------------------------------------
        insertBukuBesarRow($data);

        // --------------------------------------------------
        // Tampilkan pesan sukses
        // --------------------------------------------------
        $message = "Transaksi berhasil disimpan!";

        // --------------------------------------------------
        // Ambil nomor urut berikutnya untuk transaksi selanjutnya
        // --------------------------------------------------
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

        /* ============================================================
        Universal selector: semua elemen menggunakan box-sizing border-box
        Agar padding & border tidak menambah ukuran total elemen.
        ============================================================ */
        * {
            box-sizing: border-box;
        }

        /* ============================================================
        Body styling umum untuk halaman input:
        - Hilangkan margin default
        - Gunakan font Arial
        - Gunakan flex untuk menengahkan kartu di layar
        - min-height 100vh: tinggi penuh layar perangkat
        ============================================================ */
        body {
            margin: 0;
            font-family: Arial, sans-serif;

            /* background dicabut agar ikut background layout index.php */
            /* background: #2ecc71; */

            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        /* ============================================================
        Style untuk input yang memiliki class Bootstrap: is-invalid
        Warna border merah gelap custom sesuai preferensi.
        ============================================================ */
        .form-control.is-invalid {
            border-color: #0c0101ff !important;
        }

        /* ============================================================
        Wrapper utama:
        - Memastikan semua konten di dalam halaman memiliki padding
        - Max-width 900px agar tetap rapi di layar besar
        ============================================================ */
        .wrapper {
            width: 100%;
            max-width: 900px;
            padding: 20px;
        }

        /* ============================================================
        Card container:
        - Digunakan untuk membungkus form input
        - Shadow lembut (material design)
        - Lebar max 450px agar fokus pada form
        ============================================================ */
        .card {
            /* background: #ffffff; <- jika ingin card putih aktifkan ini */
            margin: 0 auto;
            max-width: 450px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            padding: 25px 30px 30px 30px;
        }

        /* ============================================================
        Header card: jarak bawah agar judul tidak nempel ke form
        ============================================================ */
        .card-header {
            margin-bottom: 20px;
        }

        /* ============================================================
        Judul form input
        ============================================================ */
        .card-header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        /* Deskripsi kecil di bawah judul */
        .card-header p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #777;
        }

        /* ============================================================
        Alert sukses:
        - Hijau lembut
        - Border hijau sebagai indikator keberhasilan input
        ============================================================ */
        .alert-success {
            background: #e8f9f1;
            border-left: 4px solid #2ecc71;
            padding: 10px 12px;
            font-size: 13px;
            color: #2b7a4b;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        /* ============================================================
        Alert error:
        - Merah lembut
        - Border merah sebagai indikator input salah
        ============================================================ */
        .alert-error {
            background: #fdecea;
            border-left: 4px solid #e74c3c;
            padding: 10px 12px;
            font-size: 13px;
            color: #a94442;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        /* ============================================================
        Label input:
        - Warna abu agar tidak terlalu mencolok
        - Font kecil namun jelas
        ============================================================ */
        label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
            color: #555;
        }

        /* ============================================================
        Spasi vertikal antar input
        ============================================================ */
        .form-group {
            margin-bottom: 12px;
        }

        /* ============================================================
        Style untuk semua komponen input:
        - Full width
        - Border pink lembut
        - Font kecil
        ============================================================ */
        input[type="text"],
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #f06e6eff;   /* warna pink lembut */
            border-radius: 3px;
            font-size: 13px;
            outline: none;
        }

        /* ============================================================
        Style saat input focus:
        - Border warna hijau (warna tema halaman)
        ============================================================ */
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            border-color: #2ecc71;
        }

        /* ============================================================
        Textarea:
        - Bisa di-resize secara vertikal
        - Min height agar tidak terlalu kecil
        ============================================================ */
        textarea {
            resize: vertical;
            min-height: 60px;
        }

        /* ============================================================
        Tombol submit:
        - Full width
        - Warna hijau tema
        - Rounded corner
        - Hover lebih gelap
        ============================================================ */
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

        /* ============================================================
        Link navigasi:
        - Selaras dengan tema hijau
        - Diberi hover underline agar terasa interaktif
        ============================================================ */
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
        // --------------------------------------------------------------
        // Validasi sebelum submit form:
        // - Hanya boleh mengisi salah satu: Debet atau Kredit
        // - Jika dua-duanya 0 atau dua-duanya >0 → invalid
        // --------------------------------------------------------------
        function validateForm() {
            var debet  = parseFloat(document.getElementById('debet').value)  || 0;
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;

            if ((debet <= 0 && kredit <= 0) || (debet > 0 && kredit > 0)) {
                alert('Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).');
                return false;
            }
            return true;
        }

        // --------------------------------------------------------------
        // Jika user mengisi Debet → paksa Kredit jadi 0
        // --------------------------------------------------------------
        function onDebetChange() {
            var debet = parseFloat(document.getElementById('debet').value) || 0;
            if (debet > 0) {
                document.getElementById('kredit').value = 0;
            }
        }

        // --------------------------------------------------------------
        // Jika user mengisi Kredit → paksa Debet jadi 0
        // --------------------------------------------------------------
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
            <a href="index.php?page=report">Lihat Laporan</a> |
            <a href="index.php?page=import">Import Data Buku Besar</a> |
            <a href="index.php">Menu Utama</a>
        </div>
    </div>
</div>

</body>
</html>