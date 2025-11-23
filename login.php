<?php
// =======================================================
// login.php
// Halaman login: tampilan mirip desain contoh
// dan membaca data dari tabel masteruser
// =======================================================

require 'functions.php';

// Mulai session untuk menangani login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung arahkan ke index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = "";

// -------------------------------------------------------
// PROSES SAAT FORM LOGIN DI-SUBMIT
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Cek kosong/tidak
    if ($username === '' || $password === '') {
        $error = "Username dan password wajib diisi.";
    } else {
        // Panggil fungsi loginUser
        if (loginUser($username, $password)) {
            // Jika sukses, pindah ke index.php
            header('Location: index.php');
            exit;
        } else {
            // Jika gagal, tampilkan pesan error
            $error = "Username atau password salah.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Aplikasi Keuangan Musholla</title>
    <style>
        /* ===========================
           BASIC PAGE STYLE
           =========================== */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            height: 100vh;
            background: linear-gradient(135deg, #2ca4a4, #1f7f7f);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Overlay card semi-transparan */
        .login-container {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 40px 50px;
            width: 420px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            backdrop-filter: blur(4px);
        }

        /* Icon user group di atas form */
        .login-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .login-icon .circle {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            margin: 0 auto;
            position: relative;
        }
        .login-icon .circle::before,
        .login-icon .circle::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            border: 2px solid #ffffff;
        }
        /* Kepala user utama */
        .login-icon .circle::before {
            width: 24px;
            height: 24px;
            top: 10px;
            left: calc(50% - 12px);
        }
        /* Badan user utama (setengah lingkaran) */
        .login-icon .circle::after {
            width: 40px;
            height: 40px;
            bottom: -2px;
            left: calc(50% - 20px);
            border-top: none;
        }

        .login-title {
            text-align: center;
            color: #ffffff;
            font-size: 20px;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }

        /* Field wrapper */
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            color: #e8f7f7;
            font-size: 13px;
            margin-bottom: 4px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 10px 38px;
            border-radius: 4px;
            border: none;
            background: rgba(255,255,255,0.9);
            font-size: 14px;
            outline: none;
        }

        /* Icon kecil di dalam input (dummy pakai ::before) */
        .input-icon {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            font-size: 14px;
            color: #777;
        }

        /* Baris remember + forgot password */
        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-top: 5px;
            color: #e6f5f5;
        }
        .options-row a {
            color: #e6f5f5;
            text-decoration: none;
        }
        .options-row a:hover {
            text-decoration: underline;
        }

        /* Tombol login */
        .btn-login {
            width: 100%;
            margin-top: 25px;
            padding: 11px;
            border: none;
            border-radius: 4px;
            background: #0b6767;
            color: #ffffff;
            font-size: 16px;
            letter-spacing: 1px;
            cursor: pointer;
        }
        .btn-login:hover {
            background: #095252;
        }

        /* Pesan error */
        .error-message {
            background: rgba(255, 80, 80, 0.15);
            color: #ffe5e5;
            border: 1px solid rgba(255, 0, 0, 0.4);
            padding: 8px 10px;
            border-radius: 4px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        /* Checkbox remember */
        .remember-me input {
            margin-right: 4px;
        }

        @media (max-width: 480px) {
            .login-container {
                width: 90%;
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-icon">
        <div class="circle"></div>
    </div>
    <div class="login-title">USER LOGIN</div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">

        <!-- USERNAME -->
        <div class="form-group">
            <label class="form-label">Username</label>
            <div class="input-wrapper">
                <span class="input-icon">&#128100;</span> <!-- icon user -->
                <input type="text" name="username" required>
            </div>
        </div>

        <!-- PASSWORD -->
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrapper">
                <span class="input-icon">&#128274;</span> <!-- icon lock -->
                <input type="password" name="password" required>
            </div>
        </div>

        <!-- REMEMBER + FORGOT -->
        <div class="options-row">
            <label class="remember-me">
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
            <a href="#">Forgot Password</a> <!-- belum fungsi, hanya tampilan -->
        </div>

        <!-- TOMBOL LOGIN -->
        <button type="submit" class="btn-login">LOGIN</button>
    </form>
</div>

</body>
</html>
