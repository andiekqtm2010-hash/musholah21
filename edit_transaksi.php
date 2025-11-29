<?php
// =======================================================
// edit_transaksi.php
// Form edit transaksi buku besar
// =======================================================

require_once 'functions.php'; 
// Memuat file functions.php yang berisi fungsi-fungsi helper
// seperti getTransaksiById(), getCoaList(), dan updateBukuBesarRow().

// Ambil ID transaksi dari URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
// Jika ada parameter 'id' di URL, di-casting jadi integer.
// Jika tidak ada, default 0.

// Validasi awal ID
if ($id <= 0) {
    // Jika ID tidak valid (0 atau negatif), hentikan eksekusi.
    die("ID transaksi tidak valid.");
}

// Ambil data transaksi berdasarkan ID dari database
$transaksi = getTransaksiById($id); 
// Fungsi ini seharusnya melakukan query ke tabel buku_besar
// dan mengembalikan 1 baris transaksi dalam bentuk array asosiatif.

if (!$transaksi) {
    // Jika data tidak ditemukan (false / null), hentikan eksekusi.
    die("Data transaksi tidak ditemukan.");
}

// Ambil daftar COA untuk dropdown
$coa_list = getCoaList(); 
// Fungsi ini mengambil semua akun COA dari tabel master COA
// dan mengembalikannya dalam bentuk array (untuk di-loop di <select>).

// Variabel untuk pesan sukses / error
$message = "";
$error   = "";

// -------------------------------------------------------
// PROSES SAAT FORM DI-SUBMIT
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil input form dan pastikan tipe data aman
    $no_urut    = (int)$_POST['no_urut'];      // nomor urut transaksi
    $tanggal    = $_POST['tanggal'];           // tanggal transaksi
    $keterangan = $_POST['keterangan'];        // deskripsi transaksi
    $coa_id     = (int)$_POST['coa_id'];       // id COA
    $debet      = (float)$_POST['debet'];      // nilai debet
    $kredit     = (float)$_POST['kredit'];     // nilai kredit

    // ---------------------------------------
    // VALIDASI: Debet dan kredit tidak boleh:
    // - dua-duanya 0
    // - dua-duanya berisi nilai >0
    // ---------------------------------------
    if (($debet <= 0 && $kredit <= 0) || ($debet > 0 && $kredit > 0)) {
        $error = "Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).";
    }

    // Validasi tanggal
    if (empty($tanggal)) {
        $error = "Tanggal wajib diisi.";
    }

    // Validasi COA
    if (empty($coa_id)) {
        $error = "COA wajib dipilih.";
    }

    // Jika tidak ada error, lanjut proses perhitungan saldo
    if (empty($error)) {

        // -------------------------------------------------------
        // HITUNG SALDO BARU BERDASARKAN SALDO ROW SEBELUMNYA
        // -------------------------------------------------------

        // saldo yang digunakan adalah saldo dari row sebelumnya
        // bukan saldo kolom transaksi yang sedang diedit
       
        $saldo_prev=getSaldoSebelumnya($no_urut);

        /*
            Rumus final:

            Jika DEBET diisi:
                saldo_baru = saldo_prev + debet

            Jika KREDIT diisi:
                saldo_baru = saldo_prev - kredit
        */

        // Jika input DEBET
        if ($debet > 0 && $kredit == 0) {
            $saldo_baru = $saldo_prev + $debet;
        }
        // Jika input KREDIT
        elseif ($kredit > 0 && $debet == 0) {
            $saldo_baru = $saldo_prev - $kredit;
        }
        else {
            $error = "Isi salah satu: Debet atau Kredit.";
        }


        // Jika tidak ada error lanjutan
        if (empty($error)) {

            // -------------------------------------------------------
            // SIAPKAN DATA YANG AKAN DIUPDATE KE DATABASE
            // -------------------------------------------------------
            $data = [
                'no_urut'    => $no_urut,
                'tanggal'    => $tanggal,
                'keterangan' => $keterangan,
                'coa_id'     => $coa_id,
                'debet'      => $debet,
                'kredit'     => $kredit,
                'saldo'      => $saldo_baru  // <-- saldo akhir baru
            ];

            // -------------------------------------------------------
            // UPDATE TRANSAKSI KE DALAM TABEL buku_besar
            // -------------------------------------------------------
            updateBukuBesarRow($id, $data);

            // Refresh data untuk menampilkan nilai terbaru di form
            $transaksi = getTransaksiById($id);

            $message = "Transaksi berhasil diupdate.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Edit Transaksi Buku Besar</title>
    <style>
      /*  body { font-family: Arial; }
        label { display:block; margin-top:10px; }
        input, select, textarea {
            padding: 6px;
            width: 300px;
        }
            */
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
        /**
         * validateForm()
         * Fungsi ini dijalankan ketika form di-submit (onsubmit).
         * Tujuannya untuk memvalidasi bahwa hanya salah satu field
         * Debet ATAU Kredit yang diisi dan nilainya harus > 0.
         */
        function validateForm() {
            // Ambil nilai debet dan kredit dari input
            var debet  = parseFloat(document.getElementById('debet').value)  || 0;
            // parseFloat(...) || 0 artinya: jika hasil parse NaN, maka pakai 0
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;

            // Kondisi tidak valid:
            // - Jika dua-duanya <= 0
            // - Jika dua-duanya > 0
            if ((debet <= 0 && kredit <= 0) || (debet > 0 && kredit > 0)) {
                alert('Isi salah satu saja: Debet ATAU Kredit (dan nilainya harus > 0).');
                return false; // Menghentikan submit form
            }
            return true; // Form boleh dikirim
        }

        /**
         * onDebetChange()
         * Dipanggil saat nilai input Debet berubah (oninput).
         * Jika Debet diisi > 0, maka otomatis mengosongkan (set 0) field Kredit,
         * agar user tidak mengisi keduanya.
         */
        function onDebetChange() {
            var debet = parseFloat(document.getElementById('debet').value) || 0;
            if (debet > 0) {
                // Jika debet diisi, set nilai kredit jadi 0
                document.getElementById('kredit').value = 0;
            }
        }

        /**
         * onKreditChange()
         * Dipanggil saat nilai input Kredit berubah (oninput).
         * Jika Kredit diisi > 0, maka otomatis mengosongkan (set 0) field Debet,
         * agar tidak terjadi input ganda.
         */
        function onKreditChange() {
            var kredit = parseFloat(document.getElementById('kredit').value) || 0;
            if (kredit > 0) {
                // Jika kredit diisi, set nilai debet jadi 0
                document.getElementById('debet').value = 0;
            }
        }
    </script>
</head>
<body>
<h1>Edit Transaksi Buku Besar</h1>

<?php if (!empty($message)): ?>
    <!-- Menampilkan pesan sukses jika ada -->
    <p style="color:green; font-weight:bold;"><?php echo $message; ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <!-- Menampilkan pesan error jika ada -->
    <p style="color:red; font-weight:bold;"><?php echo $error; ?></p>
<?php endif; ?>

<!--
    Form edit transaksi.
    onsubmit memanggil validateForm() untuk validasi di sisi client (JavaScript)
-->
<form method="post" onsubmit="return validateForm();">

    <label>No. Urut:</label>
    <!-- Input nomor urut transaksi (boleh diubah jika diperlukan) -->
    <input type="number" name="no_urut" value="<?php echo (int)$transaksi['no_urut']; ?>">

    <label>Tanggal Transaksi:</label>
    <!-- Input tanggal, tipe date agar ada datepicker di browser modern -->
    <input type="date" name="tanggal" value="<?php echo htmlspecialchars($transaksi['tanggal']); ?>" required>

    <label>Pilih COA:</label>
    <!-- Dropdown COA -->
    <select name="coa_id" required>
        <option value="">-- Pilih COA --</option>
        <?php foreach ($coa_list as $c): ?>
            <option value="<?php echo $c['id']; ?>"
                <?php echo ($c['id'] == $transaksi['coa_id']) ? 'selected' : ''; ?>>
                <!-- Menampilkan nama COA, gunakan htmlspecialchars untuk menghindari XSS -->
                <?php echo htmlspecialchars($c['nama_coa']); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Keterangan:</label>
    <!-- Input keterangan transaksi -->
    <textarea name="keterangan" rows="3"><?php echo htmlspecialchars($transaksi['keterangan']); ?></textarea>

    <label>Debet:</label>
    <!-- 
        Input debet: 
        - step="0.01" agar mendukung dua angka di belakang koma
        - value menggunakan number_format dengan titik (.) sebagai desimal
        - oninput memanggil onDebetChange() untuk mengosongkan kredit
     -->
    <input type="number" step="0.01" name="debet" id="debet"
           value="<?php echo number_format($transaksi['debet'], 2, '.', ''); ?>"
           oninput="onDebetChange();" required>

    <label>Kredit:</label>
    <!-- 
        Input kredit:
        - Step 0.01
        - oninput memanggil onKreditChange() untuk mengosongkan debet
     -->
    <input type="number" step="0.01" name="kredit" id="kredit"
           value="<?php echo number_format($transaksi['kredit'], 2, '.', ''); ?>"
           oninput="onKreditChange();" required>

    <br><br>
    <button type="submit">Update Transaksi</button>
</form>

<br>
<p>
    <!-- Link navigasi kembali ke report dan menu utama -->
    <a href="index.php?page=report">Kembali ke Laporan</a> |
    <a href="index.php">Menu Utama</a>
</p>

</body>
</html>
