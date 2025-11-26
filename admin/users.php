<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

// ambil daftar pengguna
$users = $pdo->query('SELECT u.id, u.username, u.nama_kelompok, u.role, u.kontak, d.nama AS desa FROM users u LEFT JOIN desa d ON u.id_desa = d.id_desa ORDER BY u.id DESC')->fetchAll(PDO::FETCH_ASSOC);

include '../partials/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Kelola Pengguna</h4>
        <a href="/sisperlenteng/login/register.php" class="btn btn-primary">Buat Pengguna Baru</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($users)): ?>
                <div class="p-3">Belum ada pengguna.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama / Username</th>
                                <th>Role</th>
                                <th>Desa</th>
                                <th class="text-end">Kontak</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $i => $u): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <div><strong><?= htmlspecialchars($u['nama_kelompok'] ?: $u['username']) ?></strong></div>
                                        <div class="text-muted small"><?= htmlspecialchars($u['username']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($u['role']) ?></td>
                                    <td><?= htmlspecialchars($u['desa']) ?></td>
                                    <td class="text-end"><?= htmlspecialchars($u['kontak']) ?></td>
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
