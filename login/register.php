<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();

if (is_logged_in()) { 
    header('Location: /sisperlenteng/index.php'); 
    exit;
}

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_kelompok'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $id_desa = $_POST['id_desa'] ?: null;

    try {
        $stmt = $pdo->prepare('INSERT INTO users (nama_kelompok, username, password_hash, id_desa, role, kontak) 
                               VALUES (?, ?, ?, ?, "kelompok", ?)');
        $stmt->execute([$nama, $username, $password, $id_desa, $_POST['kontak']]);

        header('Location: login.php');
        exit;

    } catch (Exception $e) {
        $err = 'Gagal mendaftar: ' . $e->getMessage();
    }
}

$desa = $pdo->query('SELECT * FROM desa ORDER BY nama')->fetchAll();

include '../partials/header.php';
?>

<link rel="stylesheet" href="/sisperlenteng/assets/css/register.css">

<div id="register-page">

    <div class="register-container">
        <div class="register-card">

            <h2 class="title">Daftar Kelompok Tani</h2>
            <p class="subtitle">Buat akun untuk mengelola hasil pertanian</p>

            <?php if ($err): ?>
                <div class="alert alert-danger"><?= $err ?></div>
            <?php endif; ?>

            <form method="post">

                <div class="form-group">
                    <label>Nama Kelompok</label>
                    <input class="form-control" name="nama_kelompok" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input class="form-control" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="form-group">
                    <label>Desa</label>
                    <select name="id_desa" class="form-control">
                        <option value="">- Pilih Desa -</option>
                        <?php foreach ($desa as $d): ?>
                            <option value="<?= $d['id_desa']; ?>"><?= htmlspecialchars($d['nama']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kontak</label>
                    <input class="form-control" name="kontak">
                </div>

                <button class="btn-register">Daftar</button>

                <p class="login-link">
                    Sudah punya akun? <a href="login.php">Login di sini</a>
                </p>

            </form>

        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>


