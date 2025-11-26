<?php
// =======================================================
// import.php
// Halaman untuk upload & import data dari file Excel
// =======================================================

/*require 'vendor/autoload.php';      // PhpSpreadsheet
require 'functions.php'; */

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;   // <--- TAMBAH INI DI SINI

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

            $no_urut    = $sheet->getCell('A' . $row)->getValue();
            $tanggalCell = $sheet->getCell('B' . $row);
            $tanggalVal  = $tanggalCell->getValue();
            $keterangan = $sheet->getCell('C' . $row)->getValue();
            $coa_name   = $sheet->getCell('D' . $row)->getValue();

            // kolom angka/rupiah
            $debet  = $sheet->getCell('E' . $row)->getCalculatedValue();
            $kredit = $sheet->getCell('F' . $row)->getCalculatedValue();
            $saldo  = $sheet->getCell('G' . $row)->getCalculatedValue();

            // Skip jika baris kosong
            if (empty($no_urut) && empty($tanggalCell->getValue()) && empty($keterangan)) {
                continue;
            }

            // ============================
            // KONVERSI TANGGAL
            // ============================

            // JIKA cell-nya bertipe Date ATAU value numeric (serial Excel)
            if (Date::isDateTime($tanggalCell) || is_numeric($tanggalVal)) {
                // anggap sebagai serial tanggal Excel
                $tanggal = Date::excelToDateTimeObject($tanggalVal)->format('Y-m-d');

            } else {
                // kalau berupa teks, coba parse manual
                $tanggalRaw = trim((string)$tanggalVal);

                // beberapa pola umum tanggal
                $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];

                $tanggal = null;
                foreach ($formats as $f) {
                    $d = DateTime::createFromFormat($f, $tanggalRaw);
                    if ($d) {
                        $tanggal = $d->format('Y-m-d');
                        break;
                    }
                }

                // kalau masih belum kebaca, terakhir pakai strtotime
                if (!$tanggal) {
                    $ts = strtotime($tanggalRaw);
                    $tanggal = $ts ? date('Y-m-d', $ts) : '1970-01-01'; // fallback
                }
            }

            // Pastikan numeric
            $debet  = (float) str_replace(['.', ','], ['', '.'], $debet);
            $kredit = (float) str_replace(['.', ','], ['', '.'], $kredit);
            $saldo  = (float) str_replace(['.', ','], ['', '.'], $saldo);

            // Cari / buat COA
            $coa_id = getOrCreateCoaId(trim($coa_name));

            // Data untuk simpan ke DB
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
        <button type="submit" class="btn btn-secondary">Import</button>
    </form>

    <div class="text-left mb-4">
        <br><a href="index.php?page=report" class="btn btn-primary me-2">Lihat Laporan Detail</a>
    </div>

</body>
</html>
