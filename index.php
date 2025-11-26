<?php
require_once 'functions.php';
requireLogin();   // memastikan user sudah login

// tentukan halaman yang akan dimuat
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Aplikasi Keuangan Musholla Darush Sholihin</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js untuk dashboard -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        /* ============================================================
        BODY UTAMA
        - Mengatur font dasar dan ukuran font keseluruhan.
        - Background dicabut karena layout sudah memiliki wrapper sendiri.
        ============================================================ */
        body {
            /*background: #2ecc71;*/
            font-size: 0.9rem;
        }

        /* ============================================================
        WRAPPER UTAMA
        - Menjamin tinggi layar minimal penuh.
        ============================================================ */
        .wrapper {
            min-height: 100vh;
        }

        /* ============================================================
        SIDEBAR KIRI
        - Fixed: tidak ikut scroll
        - Full height: dari atas sampai bawah
        - Lebar tetap 230px
        - overflow-y auto: sidebar bisa scroll sendiri jika menu panjang
        ============================================================ */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 230px;
            background-color: #2c3e50;
            color: #ecf0f1;
            overflow-y: auto;
        }

        /* Header/Brand di dalam sidebar */
        .sidebar .brand {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar .brand h5 {
            font-size: 0.9rem;
            margin: 0;
        }

        /* Judul kategori menu di sidebar (contoh: "Master", "Laporan") */
        .sidebar .menu-title {
            padding: 10px 15px;
            font-size: 0.8rem;
            text-transform: uppercase;
            opacity: 0.7;
        }

        /* Link navigasi sidebar */
        .sidebar .nav-link {
            color: #ecf0f1;
            border-radius: 0;
            padding: 8px 15px;
        }

        /* Efek hover & saat menu aktif */
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background-color: #34495e;
        }

        /* ============================================================
        AREA KANAN (TOPBAR + KONTEN)
        - margin-left 230px agar tidak menabrak sidebar
        - height 100vh agar adea scroll bekerja benar
        - column flex untuk susunan vertikal (topbar lalu konten)
        ============================================================ */
        .main {
            margin-left: 230px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            /*overflow-y: auto;*/ /* kalau mau scroll area kanan saja */
        }

        /* ============================================================
        TOPBAR (Header putih bagian atas layar)
        ============================================================ */
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e1e1e1;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Judul halaman di topbar */
        .topbar .title {
            font-size: 1rem;
            font-weight: 600;
        }

        /* Pembungkus konten utama di area kanan */
        .content-wrapper {
            padding: 20px;
        }

        /* ============================================================
        SUMMARY CARD (Kotak info ringkas)
        ============================================================ */
        .summary-card {
            border-radius: 10px;
            padding: 18px 20px;
            color: white;
            font-weight: 500;
            min-height: 95px;
        }

        .summary-card .value {
            font-size: 1.4rem;
            font-weight: 700;
            margin-top: 3px;
        }

        .summary-card .label {
            opacity: 0.9;
            font-size: 0.85rem;
        }

        /* ============================================================
        WARNA CARD (bisa diganti sesuai tema)
        ============================================================ */
        .bg-kasmasuk   { background: #3498db; }   /* biru */
        .bg-pengeluaran { background: #e74c3c; }  /* merah */
        .bg-saldoakhir  { background: #2ecc71; }  /* hijau */
        .bg-transaksi   { background: #9b59b6; }  /* ungu */

        /* ============================================================
        DASHBOARD MODE RINGKAS
        - Dipakai agar dashboard muat di satu layar (tanpa scroll)
        ============================================================ */
        .small-dashboard {
            font-size: 0.85rem;   /* perkecil font seluruh dashboard */
        }

        /* Card ringkas */
        .small-dashboard .summary-card {
            padding: 10px 14px;
            min-height: 70px;
        }

        /* Padding card body lebih rapat */
        .small-dashboard .card-body {
            padding: 10px 12px;
        }

        /* Header card lebih kecil */
        .small-dashboard .card-header {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        /* Grafik dashboard diperkecil agar muat di layar */
        .small-dashboard canvas {
            max-height: 230px; /* boleh 220–260 sesuai kebutuhan */
        }

        /* ============================================================
        INPUT INVALID (CSS tambahan untuk error input)
        ============================================================ */
        .form-control.is-invalid {
            border-color: #0c0101ff !important;
        }

        /* ============================================================
        Footer copyright di bawah sidebar
        ============================================================*/
        .sidebar-footer {
            position: absolute;
            bottom: 10px;  /* jarak dari bawah */
            left: 1;
            width: 100%;
            text-align: center;
            font-size: 0.90rem;
            padding: 8px 0;
            color: #3ff711ff;
            opacity: 0.8;
        }
    </style>

</head>

<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="brand">
            <h5>NU CARE - LAZISNU<br>MUSHOLLAH DHARUSH SHOLIHIN</h5>
        </div>

        <div class="menu-title">Menu Utama</div>
        <div class="nav flex-column nav-pills">
            <a class="nav-link <?php echo ($page == 'dashboard' ? 'active' : ''); ?>"
               href="index.php?page=dashboard">Dashboard</a>
            <a class="nav-link <?php echo ($page == 'input' ? 'active' : ''); ?>"
               href="index.php?page=input">Input Transaksi</a>
            <a class="nav-link <?php echo ($page == 'import' ? 'active' : ''); ?>"
               href="index.php?page=import">Import Buku Besar</a>
            <a class="nav-link <?php echo ($page == 'report' ? 'active' : ''); ?>"
               href="index.php?page=report">Laporan Keuangan</a>
            <a class="nav-link" href="logout.php">Logout</a>
        </div>
        <div class="sidebar-footer">
            © 2025 MUGNESIA Solution
        </div>
    </nav>

    <!-- Area kanan -->
    <div class="main">
        <!-- Topbar -->
        <div class="topbar">
            <div class="title">Selamat datang, admin</div>
            <div>
                <!-- kalau mau, bisa tampilkan nama dari session -->
                <!-- <?php // echo $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'admin'; ?> -->
            </div>
        </div>

        <!-- Konten -->
        <div class="content-wrapper">
            <?php
            // routing simple berdasarkan ?page=
            switch ($page) {
                case 'input':
                    include 'input_transaksi.php';
                    break;

                case 'import':
                    include 'import.php';
                    break;

                case 'report':
                    include 'report.php';
                    break;

                case 'dashboard':
                default:
                    include 'dashboard.php';  // halaman dashboard utama
                    break;
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
