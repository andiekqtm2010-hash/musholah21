<?php
// =======================================================
// functions.php
// Berisi kumpulan fungsi helper untuk aplikasi Buku Besar Musholah21
// Setiap fungsi diberi remark/komentar agar mudah dipahami & dirawat
// =======================================================

require_once 'config.php';

/**
 * getConnection()
 * ----------------------------------------------
 * Membuat koneksi ke database MySQL menggunakan mysqli.
 * - Menggunakan constant dari config.php: DB_HOST, DB_USER, DB_PASS, DB_NAME
 * - Meng-set charset ke utf8mb4 (aman untuk teks Bahasa Indonesia & emoji)
 *
 * @return mysqli Objek koneksi yang siap dipakai query
 */
function getConnection()
{
    // Buat koneksi baru ke database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Cek jika ada error koneksi
    if ($conn->connect_error) {
        // Jika gagal, hentikan aplikasi dan tampilkan pesan
        die('Koneksi gagal: ' . $conn->connect_error);
    }

    // Set karakter ke UTF-8 agar aman untuk karakter non-ASCII
    $conn->set_charset('utf8mb4');

    return $conn;
}

/**
 * getOrCreateCoaId($nama_coa)
 * ----------------------------------------------
 * Fungsi helper untuk memastikan COA ada di tabel mastercoa.
 *
 * Alur:
 * 1. Cek dulu apakah $nama_coa kosong → kalau ya, kembalikan null.
 * 2. Cari COA dengan nama_coa di tabel mastercoa.
 * 3. Jika sudah ada → kembalikan ID-nya.
 * 4. Jika belum ada → INSERT COA baru dan kembalikan ID yang baru dibuat.
 *
 * @param string $nama_coa  Nama COA (misal: "Kas", "Infaq", dll)
 * @return int|null         ID COA dari tabel mastercoa, atau null jika nama kosong
 */
function getOrCreateCoaId($nama_coa)
{
    // Jika nama kosong, tidak bisa buat COA
    if (empty($nama_coa)) {
        return null;
    }

    $conn = getConnection();

    // Cek apakah COA sudah ada di database
    $stmt = $conn->prepare("SELECT id FROM mastercoa WHERE nama_coa = ?");
    $stmt->bind_param("s", $nama_coa);
    $stmt->execute();
    $stmt->bind_result($coa_id);

    if ($stmt->fetch()) {
        // Jika COA ditemukan → kembalikan id-nya
        $stmt->close();
        $conn->close();
        return $coa_id;
    }
    // COA tidak ditemukan → tutup statement dan lanjut ke proses insert
    $stmt->close();

    // Tambahkan COA baru ke mastercoa
    $stmt_insert = $conn->prepare("INSERT INTO mastercoa (nama_coa) VALUES (?)");
    $stmt_insert->bind_param("s", $nama_coa);
    $stmt_insert->execute();

    // Ambil ID dari COA yang baru dibuat
    $new_id = $stmt_insert->insert_id;
    $stmt_insert->close();

    $conn->close();
    return $new_id;
}

/**
 * insertBukuBesarRow($data)
 * ----------------------------------------------
 * Menyimpan satu baris transaksi ke tabel bukubesar.
 *
 * Struktur array $data yang diharapkan:
 * [
 *   'no_urut'    => int,
 *   'tanggal'    => 'YYYY-mm-dd',
 *   'tahun'      => 'YYYY',
 *   'keterangan' => string,
 *   'coa_id'     => int,
 *   'debet'      => float,
 *   'kredit'     => float,
 *   'saldo'      => float,
 *   'created_by' => int|null
 * ]
 *
 * @param array $data Data transaksi yang akan di-insert
 * @return void
 */
function insertBukuBesarRow($data)
{
    $conn = getConnection();

    $sql = "INSERT INTO bukubesar
            (no_urut, tanggal, tahun, keterangan, coa_id, debet, kredit, saldo, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Mapping nilai dari array $data (gunakan default jika tidak ada)
    $no_urut    = $data['no_urut']    ?? null;
    $tanggal    = $data['tanggal']    ?? null;
    $tahun      = $data['tahun']      ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $coa_id     = $data['coa_id']     ?? null;
    $debet      = $data['debet']      ?? 0;
    $kredit     = $data['kredit']     ?? 0;
    $saldo      = $data['saldo']      ?? 0;
    $created_by = $data['created_by'] ?? null;

    // Tipe data: i = integer, s = string, d = double/float
    //    i     s     s     s     i     d      d      d      i
    $stmt->bind_param(
        "isssidddi",
        $no_urut,
        $tanggal,
        $tahun,
        $keterangan,
        $coa_id,
        $debet,
        $kredit,
        $saldo,
        $created_by
    );

    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * getCoaList()
 * ----------------------------------------------
 * Mengambil daftar COA dari tabel mastercoa untuk:
 * - dropdown di form input
 * - filter di laporan
 *
 * Mengembalikan array berisi:
 * [
 *   ['id' => 1, 'nama_coa' => 'Kas'],
 *   ['id' => 2, 'nama_coa' => 'Infaq'],
 *   ...
 * ]
 *
 * @return array
 */
function getCoaList()
{
    $conn = getConnection();

    $result = $conn->query("SELECT id, nama_coa FROM mastercoa ORDER BY nama_coa ASC");

    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row;
    }

    $conn->close();
    return $list;
}

/**
 * getReportBukuBesar($start_date, $end_date, $coa_id = null)
 * ----------------------------------------------
 * Mengambil data laporan buku besar berdasarkan:
 * - Rentang tanggal (start_date s/d end_date)
 * - Opsional filter COA (coa_id)
 *
 * Return berupa array:
 * [
 *   'rows'         => [ ...list transaksi... ],
 *   'total_debet'  => float (total debet di periode tsb),
 *   'total_kredit' => float (total kredit di periode tsb)
 * ]
 *
 * @param string   $start_date  Tanggal awal (YYYY-mm-dd)
 * @param string   $end_date    Tanggal akhir (YYYY-mm-dd)
 * @param int|null $coa_id      ID COA untuk filter (boleh null)
 * @return array
 */
function getReportBukuBesar($start_date, $end_date, $coa_id = null)
{
    $conn = getConnection();

    // Query dasar ambil transaksi + join ke nama COA
    $sql = "SELECT b.id, b.tanggal, b.keterangan, c.nama_coa, b.debet, b.kredit, b.saldo
            FROM bukubesar b
            LEFT JOIN mastercoa c ON b.coa_id = c.id
            WHERE b.tanggal BETWEEN ? AND ?";

    // Tambahkan filter COA jika dipilih
    if (!empty($coa_id)) {
        $sql .= " AND b.coa_id = ?";
    }

    $sql .= " ORDER BY b.tanggal, b.id";

    // Siapkan statement sesuai ada/tidaknya filter COA
    if (!empty($coa_id)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $start_date, $end_date, $coa_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows         = [];
    $total_debet  = 0;
    $total_kredit = 0;

    // Loop setiap baris hasil query → simpan ke array + hitung total
    while ($row = $result->fetch_assoc()) {
        $rows[]        = $row;
        $total_debet  += (float)$row['debet'];
        $total_kredit += (float)$row['kredit'];
    }

    $stmt->close();
    $conn->close();

    return [
        'rows'         => $rows,
        'total_debet'  => $total_debet,
        'total_kredit' => $total_kredit
    ];
}

/**
 * getNextNoUrut()
 * ----------------------------------------------
 * Menentukan nomor urut transaksi berikutnya.
 *
 * Logika:
 * - Ambil nilai MAX(no_urut) dari tabel bukubesar
 * - Jika ada → next = max + 1
 * - Jika belum ada data sama sekali → next = 1
 *
 * @return int Nomor urut berikutnya
 */
function getNextNoUrut()
{
    $conn = getConnection();

    $sql = "SELECT COALESCE(MAX(no_urut), 0) AS max_no FROM bukubesar";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $next = (int)$row['max_no'] + 1;

    $conn->close();
    return $next;
}

/**
 * getTransaksiById($id)
 * ----------------------------------------------
 * Mengambil satu baris transaksi buku besar berdasarkan kolom ID.
 * Biasanya dipakai kalau mau edit / cek detail 1 transaksi.
 *
 * @param int $id  ID baris di tabel bukubesar
 * @return array|null  Data transaksi (assoc array) atau null jika tidak ada
 */
function getTransaksiById($id)
{
    $conn = getConnection();

    $sql = "SELECT * FROM bukubesar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();
    $result = $stmt->get_result();
    $data   = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    // Jika tidak ada data, kembalikan null
    return $data ?: null;
}

function getSaldoSebelumnya($no_urut)
{
    $conn = getConnection(); // buka koneksi database

    // Query untuk mengambil SALDO dari transaksi SEBELUM no_urut sekarang.
    // Logika: cari no_urut yang lebih kecil dari transaksi saat ini,
    // urutkan dari yang paling besar (terdekat sebelumnya), ambil 1 saja.

    $sql = "SELECT saldo FROM bukubesar WHERE no_urut < ? ORDER BY no_urut DESC LIMIT 1";                      

    $stmt = $conn->prepare($sql);          // siapkan statement
    $stmt->bind_param("i", $no_urut);      // binding no_urut sebagai integer
    $stmt->execute();                      // jalankan query
    $stmt->bind_result($saldo);            // ikat hasil ke variabel $saldo (temapat naruh hasilnya)
    $stmt->fetch();                        // ambil data barisnya
    $stmt->close();                        // tutup statement
    $conn->close();                        // tutup koneksi

    return $saldo ?? 0; // default 0 jika tidak ada row sebelumnya
}


/**
 * updateBukuBesarRow($id, $data)
 * ----------------------------------------------
 * Mengupdate satu baris transaksi di tabel bukubesar berdasarkan ID.
 *
 * Struktur $data mirip dengan insertBukuBesarRow (kecuali created_by).
 *
 * @param int   $id   ID baris yang akan di-update
 * @param array $data Data baru (no_urut, tanggal, keterangan, coa_id, debet, kredit, saldo)
 * @return void
 */
function updateBukuBesarRow($id, $data)
{
    $conn = getConnection();

    $sql = "UPDATE bukubesar
            SET no_urut = ?, tanggal = ?, keterangan = ?, coa_id = ?, 
                debet = ?, kredit = ?, saldo = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    // Ambil nilai dari array $data (gunakan default jika kosong)
    $no_urut    = $data['no_urut']    ?? null;
    $tanggal    = $data['tanggal']    ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $coa_id     = $data['coa_id']     ?? null;
    $debet      = $data['debet']      ?? 0;
    $kredit     = $data['kredit']     ?? 0;
    $saldo      = $data['saldo']      ?? 0;

    //    i     s     s     i     d      d      d     i
    $stmt->bind_param(
        "issidddi",
        $no_urut,
        $tanggal,
        $keterangan,
        $coa_id,
        $debet,
        $kredit,
        $saldo,
        $id
    );

    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * deleteBukuBesar($id)
 * ----------------------------------------------
 * Menghapus satu baris transaksi dari tabel bukubesar berdasarkan ID.
 *
 * @param int $id ID baris yang akan dihapus
 * @return void
 */
function deleteBukuBesar($id)
{
    $conn = getConnection();

    $sql = "DELETE FROM bukubesar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// =======================================================
// Fungsi-fungsi terkait autentikasi / login
// =======================================================

/**
 * findUserByUsername($username)
 * ----------------------------------------------
 * Mencari satu user di tabel masteruser berdasarkan username.
 *
 * @param string $username
 * @return array|null  Data user (assoc array) atau null jika tidak ditemukan
 */
function findUserByUsername($username)
{
    $conn = getConnection();

    $sql = "SELECT * FROM masteruser WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $user ?: null;
}

/**
 * loginUser($username, $password)
 * ----------------------------------------------
 * Mencoba login dengan username & password.
 *
 * Alur:
 * 1. Pastikan session sudah dimulai.
 * 2. Cari user berdasarkan username.
 * 3. Jika user tidak ada → return false.
 * 4. Jika user ada → verifikasi password dengan password_verify.
 * 5. Jika password cocok → set data user di $_SESSION dan return true.
 *
 * @param string $username
 * @param string $password  Password plain-text yang diinput user
 * @return bool             true jika login sukses, false jika gagal
 */
function loginUser($username, $password)
{
    // Pastikan session aktif
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Cari user berdasarkan username
    $user = findUserByUsername($username);
    if (!$user) {
        // Username tidak ditemukan
        return false;
    }

    // Verifikasi password hash dari database
    if (!password_verify($password, $user['password'])) {
        // Password tidak cocok
        return false;
    }

    // Simpan info penting user di session
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['role']         = $user['role'];

    return true;
}

/**
 * isLoggedIn()
 * ----------------------------------------------
 * Mengecek apakah user sudah login atau belum.
 * Pengecekan berdasarkan ada/tidaknya $_SESSION['user_id'].
 *
 * @return bool true jika sudah login, false jika belum
 */
function isLoggedIn()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return !empty($_SESSION['user_id']);
}

/**
 * requireLogin()
 * ----------------------------------------------
 * Dipanggil di setiap halaman yang membutuhkan autentikasi.
 * Jika user belum login:
 * - Redirect ke login.php
 * - Hentikan eksekusi script setelah header() dengan exit;
 *
 * @return void
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * logoutUser()
 * ----------------------------------------------
 * Logout user dengan cara:
 * - Memastikan session aktif
 * - Menghapus semua variabel session (session_unset)
 * - Menghancurkan session (session_destroy)
 *
 * @return void
 */
function logoutUser()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_unset();
    session_destroy();
}

/**
 * getLastSaldo()
 * ----------------------------------------------
 * Mengambil saldo terakhir dari tabel bukubesar.
 *
 * Logika:
 * - Ambil 1 baris saldo terakhir, diurutkan dari ID terbesar (transaksi terakhir).
 * - Jika ada data → kembalikan nilai saldo (float).
 * - Jika belum ada data sama sekali → kembalikan 0 (saldo awal).
 *
 * Fungsi ini dipakai saat input transaksi baru
 * untuk menghitung saldo berjalan (running balance).
 *
 * @return float Saldo terakhir (atau 0 jika belum ada data)
 */
function getLastSaldo()
{
    $conn = getConnection();

    $sql = "SELECT saldo FROM bukubesar ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $saldo = (float)$row['saldo'];
    } else {
        // Jika belum ada data transaksi, saldo awal dianggap 0
        $saldo = 0;
    }

    $conn->close();
    return $saldo;
}

?>
