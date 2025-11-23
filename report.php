<?php
// =======================================================
// report.php
// Halaman laporan keuangan (buku besar) Musholla
// =======================================================

require 'functions.php';
requireLogin();   // memastikan user sudah login
// ----------------------------------------------
// Setup default filter (bulan berjalan)
// ----------------------------------------------
$today       = date('Y-m-d');
$firstMonth  = date('Y-m-01'); // Tanggal 1 di bulan ini

// Ambil nilai filter dari GET, jika ada
$start_date = isset($_GET['start_date']) && $_GET['start_date'] != ''
    ? $_GET['start_date'] : $firstMonth;

$end_date = isset($_GET['end_date']) && $_GET['end_date'] != ''
    ? $_GET['end_date'] : $today;

$filter_coa_id = isset($_GET['coa_id']) && $_GET['coa_id'] != ''
    ? (int)$_GET['coa_id'] : null;

// Ambil daftar COA untuk dropdown
$coa_list = getCoaList();

// Ambil data report berdasarkan filter
$report = getReportBukuBesar($start_date, $end_date, $filter_coa_id);
$rows   = $report['rows'];
$total_debet  = $report['total_debet'];
$total_kredit = $report['total_kredit'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Musholla</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #444;
            padding: 4px 6px;
        }
        th {
            background: #eee;
        }
    </style>
</head>
<body>
    <h1>Laporan Keuangan Musholla</h1>

    <!-- Form Filter -->
    <form method="get" action="report.php">
        <label>Dari Tanggal:</label>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">

        <label>Sampai Tanggal:</label>
        <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">

        <label>COA:</label>
        <select name="coa_id">
            <option value="">-- Semua COA --</option>
            <?php foreach ($coa_list as $coa): ?>
                <option value="<?php echo $coa['id']; ?>"
                    <?php echo ($filter_coa_id == $coa['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($coa['nama_coa']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Filter</button>
    </form>
    <br>

       <!-- Tombol Export -->
    <form method="get" action="export_excel.php" style="display:inline;">
        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        <input type="hidden" name="coa_id" value="<?php echo htmlspecialchars($filter_coa_id); ?>">
        <button type="submit">Export Excel</button>
    </form>

    <form method="get" action="export_pdf.php" style="display:inline;">
        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        <input type="hidden" name="coa_id" value="<?php echo htmlspecialchars($filter_coa_id); ?>">
        <button type="submit">Export PDF</button>
    </form>

    <br>

    <!-- Tabel Laporan -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>COA</th>
                <th>Keterangan</th>
                <th>Debet</th>
                <th>Kredit</th>
                <th>Saldo (dari data)</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;">Tidak ada data untuk kriteria ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($r['tanggal']); ?></td>
                        <td><?php echo htmlspecialchars($r['nama_coa']); ?></td>
                        <td><?php echo htmlspecialchars($r['keterangan']); ?></td>
                        <td style="text-align:right;"><?php echo number_format($r['debet'], 0, ',', '.'); ?></td>
                        <td style="text-align:right;"><?php echo number_format($r['kredit'], 0, ',', '.'); ?></td>
                        <td style="text-align:right;"><?php echo number_format($r['saldo'], 0, ',', '.'); ?></td>
                        <td>
                            <a href="edit_transaksi.php?id=<?php echo $r['id']; ?>">Edit</a> |
                            <a href="delete_transaksi.php?id=<?php echo $r['id']; ?>"
                            onclick="return confirm('Yakin hapus transaksi ini?');">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="4" style="text-align:right;">TOTAL</th>
                    <th style="text-align:right;"><?php echo number_format($total_debet, 0, ',', '.'); ?></th>
                    <th style="text-align:right;"><?php echo number_format($total_kredit, 0, ',', '.'); ?></th>
                    <th></th>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>
    <p><a href="import.php">Import Data Buku Besar</a> | <a href="index.php">Menu Utama</a></p>
</body>
</html>
