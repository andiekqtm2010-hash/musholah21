<?php
// =======================================================
// import.php
// Halaman untuk upload & import data dari file Excel
// =======================================================

require 'vendor/autoload.php';      // PhpSpreadsheet
require 'functions.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Untuk contoh, kita hardcode created_by = 1
// Nanti bisa diubah jika sudah ada fitur login
$default_user_id = 1;

// Proses jika form dikirim
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    // ------------------------------------------
    // Bagian ini memproses file Excel yang diupload
    // ------------------------------------------

    $file_tmp  = $_FILES['excel_file']['tmp_name'];
    $file_name = $_FILES['excel_file']['name'];

    try {
        // Load file Excel menggunakan PhpSpreadsheet
        $spreadsheet = IOFactory::load($file_tmp);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow    = $sheet->getHighestRow();    // Baris terakhir
        $highestColumn = $sheet->getHighestColumn(); // Kolom terakhir

        // Mulai dari baris ke-2 (baris 1 adalah header)
        for ($row = 2; $row <= $highestRow; $row++) {

            // Baca nilai dari tiap kolom sesuai struktur contoh
            $no_urut    = $sheet->getCell('A' . $row)->getValue();
            $tanggalRaw = $sheet->getCell('B' . $row)->getValue();
            $keterangan = $sheet->getCell('C' . $row)->getValue();
            $coa_name   = $sheet->getCell('D' . $row)->getValue();
            $debet      = $sheet->getCell('E' . $row)->getValue();
            $kredit     = $sheet->getCell('F' . $row)->getValue();
            $saldo      = $sheet->getCell('G' . $row)->getValue();

            // Jika baris kosong (tidak ada No & Tanggal), skip
            if (empty($no_urut) && empty($tanggalRaw) && empty($keterangan)) {
                continue;
            }

            // Konversi tanggal Excel ke format Y-m-d
            // Catatan: jika di file sudah string '2025-01-01' maka ini langsung jalan.
            // Jika berupa nomor serial Excel, IOFactory akan mengkonversi ke DateTime.
            if ($tanggalRaw instanceof \PhpOffice\PhpSpreadsheet\Shared\Date) {
                $tanggal = date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($tanggalRaw));
            } else {
                // Coba langsung parse string
                $tanggal = date('Y-m-d', strtotime($tanggalRaw));
            }

            // Pastikan nilai numeric
            $debet  = (float)$debet;
            $kredit = (float)$kredit;
            $saldo  = (float)$saldo;

            // Cari atau buat COA di mastercoa
            $coa_id = getOrCreateCoaId(trim($coa_name));

            // Data untuk disimpan
            $data = [
                'no_urut'    => !empty($no_urut) ? (int)$no_urut : null,
                'tanggal'    => $tanggal,
                'keterangan' => $keterangan,
                'coa_id'     => $coa_id,
                'debet'      => $debet,
                'kredit'     => $kredit,
                'saldo'      => $saldo,
                'created_by' => $default_user_id
            ];

            // Simpan ke tabel bukubesar
            insertBukuBesarRow($data);
        }

        $message = 'Import data selesai. File: ' . htmlspecialchars($file_name);
    } catch (Exception $e) {
        $message = 'Terjadi error saat import: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Import Buku Besar Musholla</title>
</head>
<body>
    <h1>Import Buku Besar Musholla</h1>

    <?php if (!empty($message)): ?>
        <p><strong><?php echo $message; ?></strong></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Pilih File Excel (.xlsx):</label><br>
        <input type="file" name="excel_file" accept=".xls,.xlsx" required><br><br>
        <button type="submit">Import</button>
    </form>

    <p><a href="report.php">Lihat Laporan Keuangan</a></p>
    <p><a href="index.php">Kembali ke Menu Utama</a></p>
</body>
</html>
