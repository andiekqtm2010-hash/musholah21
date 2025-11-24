<?php
//    require __DIR__ . '/functions.php';

/*    DEBUG: cek apakah fungsi isLoggedIn terdefinisi
echo "<pre>LOGIN loaded: " . __FILE__ . "</pre>";
echo "<pre>functions.php loaded? isLoggedIn exists? "
    . (function_exists('isLoggedIn') ? 'YES' : 'NO')
    . "</pre>";
die('STOP DEBUG DI SINI');
*/

    require 'functions.php';
    requireLogin();   // memastikan user sudah login
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aplikasi Keuangan Musholla</title>
</head>
<body>
    <h1>Aplikasi Keuangan Musholla</h1>
    <ul>
        <li><a href="input_transaksi.php">Input Transaksi Manual</a></li>
        <li><a href="import.php">Import Buku Besar dari Excel</a></li>
        <li><a href="report.php">Laporan Keuangan</a></li>
    </ul>
</body>
</html>
