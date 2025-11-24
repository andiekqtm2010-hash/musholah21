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
$today      = date('Y-m-d');
$firstMonth = date('Y-m-01'); // Tanggal 1 di bulan ini

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
$report       = getReportBukuBesar($start_date, $end_date, $filter_coa_id);
$rowsAll      = $report['rows'];
$total_debet  = $report['total_debet'];
$total_kredit = $report['total_kredit'];

// ----------------------------------------------
// PAGING (min 20 baris per halaman)
// ----------------------------------------------
$perPage   = 20;
$totalRows = count($rowsAll);
$totalPages = max(1, ceil($totalRows / $perPage));

$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;
$rows   = array_slice($rowsAll, $offset, $perPage);

// helper untuk bikin link paging sambil membawa parameter filter
function buildPageUrl($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return 'report.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan Mushollah21</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
        }
        .page-wrapper {
            max-width: 1100px;
            margin: 20px auto;
        }
        .table thead th {
            background: #3498db;
            color: #fff;
            vertical-align: middle;
            text-align: center;
        }
        .btn-refresh { background:#27ae60; color:#fff; }
        .btn-refresh:hover { background:#219150; color:#fff; }
        .btn-tambah { background:#2980b9; color:#fff; }
        .btn-tambah:hover { background:#226699; color:#fff; }
        .btn-import { background:#8e44ad; color:#fff; }
        .btn-import:hover { background:#74368c; color:#fff; }

        .btn-action-edit {
            background: #f39c12;
            color: #fff;
            padding: 4px 8px;
            border-radius: 3px;
            text-decoration:none;
            font-size: 12px;
        }
        .btn-action-delete {
            background: #e74c3c;
            color: #fff;
            padding: 4px 8px;
            border-radius: 3px;
            text-decoration:none;
            font-size: 12px;
        }
        .btn-action-edit:hover { opacity: .9; color:#fff; }
        .btn-action-delete:hover { opacity: .9; color:#fff; }
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <h2 class="mb-3">Laporan Keuangan Musholla</h2>

    <!-- Panel Filter + Tombol -->
    <div class="card mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="get" action="report.php">
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control"
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">COA</label>
                    <select name="coa_id" class="form-select">
                        <option value="">-- Semua COA --</option>
                        <?php foreach ($coa_list as $coa): ?>
                            <option value="<?php echo $coa['id']; ?>"
                                <?php echo ($filter_coa_id == $coa['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($coa['nama_coa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">Filter</button>
                </div>
            </form>

            <div class="mt-3 d-flex flex-wrap gap-2">
                <!-- Refresh -->
                <a href="report.php" class="btn btn-refresh">Refresh</a>
                <!-- Tambah transaksi -->
                <a href="input_transaksi.php" class="btn btn-tambah">Tambah</a>
                <!-- Import -->
                <a href="import.php" class="btn btn-import">Import</a>

                <!-- Export Excel -->
                <form method="get" action="export_excel.php" class="d-inline-block ms-auto">
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <input type="hidden" name="coa_id" value="<?php echo htmlspecialchars($filter_coa_id); ?>">
                    <button type="submit" class="btn btn-success btn-sm">Export Excel</button>
                </form>

                <!-- Export PDF -->
                <form method="get" action="export_pdf.php" class="d-inline-block">
                    <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    <input type="hidden" name="coa_id" value="<?php echo htmlspecialchars($filter_coa_id); ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Export PDF</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Laporan -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-bordered mb-0">
                    <thead>
                    <tr>
                        <th style="width:50px;">No</th>
                        <th style="width:110px;">Tanggal</th>
                        <th style="width:160px;">COA</th>
                        <th>Keterangan</th>
                        <th style="width:120px;">Debet</th>
                        <th style="width:120px;">Kredit</th>
                        <th style="width:140px;">Saldo (dari data)</th>
                        <th style="width:90px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data untuk kriteria ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-center"><?php echo $no++; ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($r['tanggal']); ?></td>
                                <td><?php echo htmlspecialchars($r['nama_coa']); ?></td>
                                <td><?php echo htmlspecialchars($r['keterangan']); ?></td>
                                <td class="text-end"><?php echo number_format($r['debet'], 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo number_format($r['kredit'], 0, ',', '.'); ?></td>
                                <td class="text-end"><?php echo number_format($r['saldo'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <a href="edit_transaksi.php?id=<?php echo $r['id']; ?>"
                                       class="btn-action-edit" title="Edit">✏</a>
                                    <a href="delete_transaksi.php?id=<?php echo $r['id']; ?>"
                                       class="btn-action-delete"
                                       onclick="return confirm('Yakin hapus transaksi ini?');"
                                       title="Hapus">🗑</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTAL</td>
                            <td class="text-end"><?php echo number_format($total_debet, 0, ',', '.'); ?></td>
                            <td class="text-end"><?php echo number_format($total_kredit, 0, ',', '.'); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paging -->
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div>
                Menampilkan
                <strong><?php echo $totalRows ? $offset + 1 : 0; ?></strong>
                -
                <strong><?php echo min($offset + $perPage, $totalRows); ?></strong>
                dari
                <strong><?php echo $totalRows; ?></strong>
                data.
            </div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo buildPageUrl(1); ?>">&laquo;</a>
                    </li>
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo buildPageUrl($page - 1); ?>">&lsaquo;</a>
                    </li>

                    <li class="page-item active">
                        <span class="page-link">
                            Hal <?php echo $page; ?> / <?php echo $totalPages; ?>
                        </span>
                    </li>

                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo buildPageUrl($page + 1); ?>">&rsaquo;</a>
                    </li>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo buildPageUrl($totalPages); ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="mt-3">
        <a href="index.php">Menu Utama</a>
    </div>
</div>
</body>
</html>
