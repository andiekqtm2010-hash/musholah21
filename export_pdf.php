<?php
// =======================================================
// export_pdf.php
// Export laporan ke format PDF menggunakan Dompdf
// =======================================================

require 'vendor/autoload.php';
require 'functions.php';

use Dompdf\Dompdf;

// Ambil parameter filter
$today      = date('Y-m-d');
$firstMonth = date('Y-m-01');

$start_date = isset($_GET['start_date']) && $_GET['start_date'] != '' ? $_GET['start_date'] : $firstMonth;
$end_date   = isset($_GET['end_date']) && $_GET['end_date'] != '' ? $_GET['end_date'] : $today;
$coa_id     = isset($_GET['coa_id']) && $_GET['coa_id'] != '' ? (int)$_GET['coa_id'] : null;

$report = getReportBukuBesar($start_date, $end_date, $coa_id);
$rows   = $report['rows'];
$total_debet  = $report['total_debet'];
$total_kredit = $report['total_kredit'];

// Buat HTML sederhana untuk dijadikan PDF
$html = '
<h2 style="text-align:center;">Laporan Keuangan Musholla</h2>
<p>Periode: ' . htmlspecialchars($start_date) . ' s/d ' . htmlspecialchars($end_date) . '</p>
<table border="1" cellspacing="0" cellpadding="4" width="100%">
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>COA</th>
            <th>Keterangan</th>
            <th>Debet</th>
            <th>Kredit</th>
            <th>Saldo</th>
        </tr>
    </thead>
    <tbody>';

if (empty($rows)) {
    $html .= '
        <tr>
            <td colspan="7" align="center">Tidak ada data</td>
        </tr>';
} else {
    $no = 1;
    foreach ($rows as $r) {
        $html .= '
        <tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($r['tanggal']) . '</td>
            <td>' . htmlspecialchars($r['nama_coa']) . '</td>
            <td>' . htmlspecialchars($r['keterangan']) . '</td>
            <td align="right">' . number_format($r['debet'], 0, ',', '.') . '</td>
            <td align="right">' . number_format($r['kredit'], 0, ',', '.') . '</td>
            <td align="right">' . number_format($r['saldo'], 0, ',', '.') . '</td>
        </tr>';
    }

    $html .= '
        <tr>
            <th colspan="4" align="right">TOTAL</th>
            <th align="right">' . number_format($total_debet, 0, ',', '.') . '</th>
            <th align="right">' . number_format($total_kredit, 0, ',', '.') . '</th>
            <th></th>
        </tr>';
}

$html .= '
    </tbody>
</table>
';

// Inisialisasi Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// (Optional) set ukuran kertas & orientasi
$dompdf->setPaper('A4', 'portrait');

// Render HTML ke PDF
$dompdf->render();

// Output ke browser
$filename = 'Laporan_Keuangan_Musholla_' . $start_date . '_sd_' . $end_date . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
