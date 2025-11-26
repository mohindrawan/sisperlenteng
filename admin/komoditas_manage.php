<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $token = $_POST['_csrf'] ?? '';
    if (!validate_csrf($token)) {
        $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
        header('Location: /sisperlenteng/admin/komoditas_manage.php'); exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM komoditas WHERE id_komoditas = ?');
            $stmt->execute([$id]);
            $_SESSION['flash_success'] = 'Komoditas berhasil dihapus.';
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus: ' . $e->getMessage();
        }
    }
    header('Location: /sisperlenteng/admin/komoditas_manage.php');
    exit;
}

$komoditas = $pdo->query('SELECT id_komoditas, nama_komoditas, status FROM komoditas ORDER BY nama_komoditas')->fetchAll(PDO::FETCH_ASSOC);

include '../partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Kelola Komoditas</h4>
        <a href="/sisperlenteng/admin/komoditas_pending.php" class="btn btn-secondary">Komoditas Pending</a>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($komoditas)): ?>
                <div class="p-3">Belum ada data komoditas.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Komoditas</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($komoditas as $i => $k): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($k['nama_komoditas']) ?></td>
                                <td><?= htmlspecialchars($k['status'] ?? '') ?></td>
                                <td class="text-end">
                                    <form method="post" action="/sisperlenteng/admin/komoditas_manage.php" class="d-inline-block">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$k['id_komoditas'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Hapus komoditas ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
