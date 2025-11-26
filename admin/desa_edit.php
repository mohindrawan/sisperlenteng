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
$id = (int)($_GET['id'] ?? 0);

// ambil data awal
if ($id > 0) {
    $stmt = $pdo->prepare('SELECT id_desa, nama FROM desa WHERE id_desa = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $_SESSION['flash_error'] = 'Desa tidak ditemukan.';
        header('Location: /sisperlenteng/admin/desa_list.php');
        exit;
    }
    $nama = $row['nama'];
} else {
    $_SESSION['flash_error'] = 'ID desa tidak valid.';
    header('Location: /sisperlenteng/admin/desa_list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $error = 'Nama desa wajib diisi.';
    } else {
        try {
            // cek duplikat kecuali sendiri
            $chk = $pdo->prepare('SELECT COUNT(*) FROM desa WHERE LOWER(nama)=LOWER(?) AND id_desa <> ?');
            $chk->execute([$nama, $id]);
            if ($chk->fetchColumn() > 0) {
                $error = 'Nama desa sudah ada.';
            } else {
                $upd = $pdo->prepare('UPDATE desa SET nama = ? WHERE id_desa = ?');
                $upd->execute([$nama, $id]);
                $_SESSION['flash_success'] = 'Desa berhasil diperbarui.';
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
        <h4>Edit Desa</h4>
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
                    <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
                    <a href="/sisperlenteng/admin/desa_list.php" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
