<?php
// =======================================================
// config.php
// Berisi konfigurasi database dan pengaturan umum aplikasi
// =======================================================

// Konfigurasi koneksi database MySQL
define('DB_HOST', 'localhost');        // Host database
define('DB_USER', 'root');             // Username MySQL
define('DB_PASS', 'root');                 // Password MySQL
define('DB_NAME', 'musholla_keuangan'); // Nama database

// Timezone aplikasi
date_default_timezone_set('Asia/Jakarta');

// Base URL (sesuaikan dengan folder di server)
$base_url = 'http://localhost/mushola21/';
