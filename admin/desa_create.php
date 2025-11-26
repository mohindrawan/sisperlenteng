<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

$error = '';
$nama = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');

    if ($nama === '') {
        $error = 'Nama desa wajib diisi.';
    } else {
        try {
            // cek duplikat (case-insensitive)
            $chk = $pdo->prepare('SELECT COUNT(*) FROM desa WHERE LOWER(nama)=LOWER(?)');
            $chk->execute([$nama]);
            if ($chk->fetchColumn() > 0) {
                $error = 'Nama desa sudah ada.';
            } else {
                $ins = $pdo->prepare('INSERT INTO desa (nama) VALUES (?)');
                $ins->execute([$nama]);
                $_SESSION['flash_success'] = 'Desa berhasil ditambahkan.';
                header('Location: /sisperlenteng/admin/desa_list.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage();
        }
    }
}

include '../partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Tambah Desa</h4>
        <a href="/sisperlenteng/admin/desa_list.php" class="btn btn-secondary">Kembali ke Daftar Desa</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post" action="">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Desa</label>
                    <input id="nama" name="nama" class="form-control" value="<?= htmlspecialchars($nama) ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a href="/sisperlenteng/admin/desa_list.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>

