<?php
// =======================================================
// export_excel.php
// Export laporan ke format Excel (XLSX)
// =======================================================

require 'vendor/autoload.php';
require 'functions.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ambil parameter filter dari GET
$today      = date('Y-m-d');
$firstMonth = date('Y-m-01');

$start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? $_GET['start_date'] : $firstMonth;
$end_date   = isset($_GET['end_date']) && $_GET['end_date'] != '' ? $_GET['end_date'] : $today;
$coa_id     = isset($_GET['coa_id']) && $_GET['coa_id'] != '' ? (int)$_GET['coa_id'] : null;

// Ambil data laporan
$report = getReportBukuBesar($start_date, $end_date, $coa_id);
$rows   = $report['rows'];

// Buat objek Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->setCellValue('A1', 'Tanggal');
$sheet->setCellValue('B1', 'COA');
$sheet->setCellValue('C1', 'Keterangan');
$sheet->setCellValue('D1', 'Debet');
$sheet->setCellValue('E1', 'Kredit');
$sheet->setCellValue('F1', 'Saldo');

// Isi data
$rowNum = 2;
foreach ($rows as $r) {
    $sheet->setCellValue('A' . $rowNum, $r['tanggal']);
    $sheet->setCellValue('B' . $rowNum, $r['nama_coa']);
    $sheet->setCellValue('C' . $rowNum, $r['keterangan']);
    $sheet->setCellValue('D' . $rowNum, $r['debet']);
    $sheet->setCellValue('E' . $rowNum, $r['kredit']);
    $sheet->setCellValue('F' . $rowNum, $r['saldo']);
    $rowNum++;
}

// Nama file
$filename = 'Laporan_Keuangan_Musholla_' . $start_date . '_sd_' . $end_date . '.xlsx';

// Header untuk download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Output ke browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
