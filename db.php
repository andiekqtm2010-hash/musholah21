<?php
// Ubah kredensial sesuai XAMPP/Hostinger Anda
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'root';
$DB_NAME = 'kas_musholla';


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$conn->set_charset('utf8mb4');


function rupiah($n){
return number_format((float)$n, 0, ',', '.');
}
?>