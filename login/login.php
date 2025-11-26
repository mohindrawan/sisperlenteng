<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();

// tampilkan error sementara untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Jika sudah login → arahkan ke dashboard role masing-masing
if (is_logged_in()) {

    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: /sisperlenteng/admin/dashboard.php');
    } else {
        header('Location: /sisperlenteng/kelompok/dashboard.php');
    }
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // gunakan SELECT * agar tidak gagal bila beberapa kolom (mis. "password") tidak ada
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1');
    $stmt->execute([$username]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    // verifikasi password — hapus debug sebelumnya agar kode berikut dapat dieksekusi
    $verified = false;
    if ($u) {
        $candidates = [];
        if (!empty($u['password_hash'])) $candidates[] = $u['password_hash'];
        if (!empty($u['passwd'])) $candidates[] = $u['passwd']; // nama kolom legacy jika ada

        foreach ($candidates as $c) {
            if (is_string($c) && preg_match('/^\$(2y|2a|argon2|5|6)\$/', $c)) {
                if (password_verify($password, $c)) { $verified = true; break; }
            } else {
                // fallback plain-text (hanya sementara, hapus setelah migrasi)
                if ($c === $password) { $verified = true; break; }
            }
        }
    }

    if ($verified) {
        // hapus data sensitif sebelum simpan session
        unset($u['password'], $u['password_hash'], $u['passwd']);

        $_SESSION['user'] = $u;

        // Redirect sesuai role
        if (isset($u['role']) && $u['role'] === 'admin') {
            header('Location: /sisperlenteng/admin/dashboard.php');
        } else {
            header('Location: /sisperlenteng/kelompok/dashboard.php');
        }
        exit;
    } else {
        $err = 'Username atau password salah';
    }
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Sistem</title>
    <link rel="stylesheet" href="/sisperlenteng/assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<div id="login-page">
    <div class="login-container">
        <div class="login-card">

            <h2 class="title">Login Sistem</h2>

            <?php if ($err): ?>
                <div class="alert alert-danger"><?= $err ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input class="form-control" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <button class="btn-login">Login</button>
            </form>

        </div>
    </div>
</div>

</body>
</html>


