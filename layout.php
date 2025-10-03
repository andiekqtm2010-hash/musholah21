<?php // layout.php ?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kas Musholla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body{background:#f7f7fb}
    .table-sm th,.table-sm td{padding:.45rem .5rem}
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Kas Musholla</a>
        <div class="collapse navbar-collapse show">
            <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="saldo_awal.php">Saldo Awal</a></li>
            <li class="nav-item"><a class="nav-link" href="transaksi.php">Transaksi</a></li>
            <li class="nav-item"><a class="nav-link" href="coa.php">Master COA</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Report Bulanan</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-3"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Format angka saat ketik
function onlyNumber(el){
el.addEventListener('input', ()=>{
el.value = el.value.replace(/[^\d]/g,'');
});
}
</script>
</body></html>
