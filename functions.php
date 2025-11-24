<?php
// =======================================================
// functions.php
// Berisi kumpulan fungsi helper untuk aplikasi
// Setiap fungsi diberi remark/komentar agar mudah dibaca
// =======================================================

require_once 'config.php';

/**
 * getConnection()
 * ----------------------------------------------
 * Membuat koneksi ke database MySQL menggunakan mysqli.
 * Mengembalikan objek koneksi yang bisa dipakai di file lain.
 */
function getConnection()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Cek jika ada error koneksi
    if ($conn->connect_error) {
        die('Koneksi gagal: ' . $conn->connect_error);
    }

    // Set karakter ke UTF-8 agar aman untuk bahasa Indonesia
    $conn->set_charset('utf8mb4');

    return $conn;
}

/**
 * getOrCreateCoaId($nama_coa)
 * ----------------------------------------------
 * Mencari COA berdasarkan nama_coa di tabel mastercoa.
 * Jika belum ada, otomatis menambahkan COA baru.
 * Mengembalikan id dari mastercoa.
 */
function getOrCreateCoaId($nama_coa)
{
    if (empty($nama_coa)) {
        return null;
    }

    $conn = getConnection();

    // Cek apakah COA sudah ada
    $stmt = $conn->prepare("SELECT id FROM mastercoa WHERE nama_coa = ?");
    $stmt->bind_param("s", $nama_coa);
    $stmt->execute();
    $stmt->bind_result($coa_id);

    if ($stmt->fetch()) {
        // Jika COA ditemukan, kembalikan id
        $stmt->close();
        $conn->close();
        return $coa_id;
    }
    $stmt->close();

    // Jika belum ada, insert COA baru
    $stmt_insert = $conn->prepare("INSERT INTO mastercoa (nama_coa) VALUES (?)");
    $stmt_insert->bind_param("s", $nama_coa);
    $stmt_insert->execute();
    $new_id = $stmt_insert->insert_id;
    $stmt_insert->close();

    $conn->close();
    return $new_id;
}

/**
 * insertBukuBesarRow($data)
 * ----------------------------------------------
 * Menyimpan satu baris transaksi ke tabel bukubesar.
 * Parameter $data berupa array asosiatif:
 * [
 *   'no_urut'   => int,
 *   'tanggal'   => 'YYYY-mm-dd',
 *   'keterangan'=> string,
 *   'coa_id'    => int,
 *   'debet'     => float,
 *   'kredit'    => float,
 *   'saldo'     => float,
 *   'created_by'=> int|null
 * ]
 */
function insertBukuBesarRow($data)
{
    $conn = getConnection();

    $sql = "INSERT INTO bukubesar
            (no_urut, tanggal, keterangan, coa_id, debet, kredit, saldo, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $no_urut    = $data['no_urut']    ?? null;
    $tanggal    = $data['tanggal']    ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $coa_id     = $data['coa_id']     ?? null;
    $debet      = $data['debet']      ?? 0;
    $kredit     = $data['kredit']     ?? 0;
    $saldo      = $data['saldo']      ?? 0;
    $created_by = $data['created_by'] ?? null;

    //    i     s     s     i     d      d      d      i
    $stmt->bind_param(
        "issidddi",
        $no_urut,
        $tanggal,
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
 * Mengambil daftar COA dari tabel mastercoa untuk
 * dropdown filter dan kebutuhan lain.
 * Mengembalikan array: [ ['id'=>1,'nama_coa'=>'Kas'], ... ]
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
 * - rentang tanggal (start_date s/d end_date)
 * - opsional filter COA (coa_id)
 *
 * Mengembalikan array:
 * [
 *   'rows' => [ ...data transaksi... ],
 *   'total_debet' => float,
 *   'total_kredit'=> float
 * ]
 */
function getReportBukuBesar($start_date, $end_date, $coa_id = null)
{
    $conn = getConnection();

    $sql = "SELECT b.id, b.tanggal, b.keterangan, c.nama_coa, b.debet, b.kredit, b.saldo
            FROM bukubesar b
            LEFT JOIN mastercoa c ON b.coa_id = c.id
            WHERE b.tanggal BETWEEN ? AND ?";

    // Jika ada filter COA
    if (!empty($coa_id)) {
        $sql .= " AND b.coa_id = ?";
    }

    $sql .= " ORDER BY b.tanggal, b.id";

    if (!empty($coa_id)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $start_date, $end_date, $coa_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $total_debet = 0;
    $total_kredit = 0;

    // Loop data ke array dan hitung total
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
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
 * Mengambil no_urut berikutnya dari tabel bukubesar.
 * Logika: ambil MAX(no_urut) lalu + 1.
 * Jika belum ada data, mulai dari 1.
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
 * Mengambil satu baris transaksi buku besar berdasarkan ID.
 * Mengembalikan array asosiatif atau null jika tidak ditemukan.
 */
function getTransaksiById($id)
{
    $conn = getConnection();
    $sql = "SELECT * FROM bukubesar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $data ?: null;
}

/**
 * updateBukuBesarRow($id, $data)
 * ----------------------------------------------
 * Mengupdate satu baris transaksi di tabel bukubesar berdasarkan ID.
 * Struktur $data sama dengan insertBukuBesarRow (kecuali created_by boleh diabaikan).
 */
function updateBukuBesarRow($id, $data)
{
    $conn = getConnection();

    $sql = "UPDATE bukubesar
            SET no_urut = ?, tanggal = ?, keterangan = ?, coa_id = ?, 
                debet = ?, kredit = ?, saldo = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $no_urut    = $data['no_urut'] ?? null;
    $tanggal    = $data['tanggal'] ?? null;
    $keterangan = $data['keterangan'] ?? null;
    $coa_id     = $data['coa_id'] ?? null;
    $debet      = $data['debet'] ?? 0;
    $kredit     = $data['kredit'] ?? 0;
    $saldo      = $data['saldo'] ?? 0;

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
 * Mencari 1 user di tabel masteruser berdasarkan username.
 * Jika ada, mengembalikan array data user.
 * Jika tidak ada, mengembalikan null.
 */
function findUserByUsername($username)
{
    $conn = getConnection();

    $sql = "SELECT * FROM masteruser WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $user ?: null;
}

/**
 * loginUser($username, $password)
 * ----------------------------------------------
 * Mencoba login dengan username & password.
 * - Cek apakah user ada.
 * - Cek password dengan password_verify.
 * Jika sukses, set data di $_SESSION dan mengembalikan true.
 * Jika gagal, mengembalikan false.
 */
function loginUser($username, $password)
{
    // Pastikan session sudah dimulai
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user = findUserByUsername($username);
    if (!$user) {
        return false;
    }

    // Verifikasi password hash (pastikan kolom password di DB berisi HASH)
    if (!password_verify($password, $user['password'])) {
        return false;
    }

    // Simpan info user di session
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['username']      = $user['username'];
    $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
    $_SESSION['role']          = $user['role'];

    return true;
}

/**
 * isLoggedIn()
 * ----------------------------------------------
 * Mengecek apakah user sudah login atau belum
 * berdasarkan keberadaan $_SESSION['user_id'].
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
 * Dipakai di halaman-halaman yang butuh login.
 * Jika belum login, user akan diarahkan ke login.php.
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
 * Menghapus semua data session user (logout).
 */
function logoutUser()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_unset();
    session_destroy();
}

function getLastSaldo()
{
    // buka koneksi baru, sama seperti fungsi lain
    $conn = getConnection();

    $sql = "SELECT saldo FROM bukubesar ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $saldo = (float)$row['saldo'];
    } else {
        $saldo = 0; // kalau belum ada data, saldo awal = 0
    }

    $conn->close();
    return $saldo;
}

?>
