<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

// Handle create action (dari modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $_SESSION['flash_error'] = 'Nama desa wajib diisi.';
    } else {
        try {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM desa WHERE LOWER(nama)=LOWER(?)');
            $chk->execute([$nama]);
            if ($chk->fetchColumn() > 0) {
                $_SESSION['flash_error'] = 'Nama desa sudah ada.';
            } else {
                $ins = $pdo->prepare('INSERT INTO desa (nama) VALUES (?)');
                $ins->execute([$nama]);
                $_SESSION['flash_success'] = 'Desa berhasil ditambahkan.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage();
        }
    }
    header('Location: /sisperlenteng/admin/desa_list.php');
    exit;
}

// Handle update action (dari modal edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    if ($id <= 0) {
        $_SESSION['flash_error'] = 'ID desa tidak valid.';
    } elseif ($nama === '') {
        $_SESSION['flash_error'] = 'Nama desa wajib diisi.';
    } else {
        try {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM desa WHERE LOWER(nama)=LOWER(?) AND id_desa <> ?');
            $chk->execute([$nama, $id]);
            if ($chk->fetchColumn() > 0) {
                $_SESSION['flash_error'] = 'Nama desa sudah ada.';
            } else {
                $upd = $pdo->prepare('UPDATE desa SET nama = ? WHERE id_desa = ?');
                $upd->execute([$nama, $id]);
                $_SESSION['flash_success'] = 'Desa berhasil diperbarui.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage();
        }
    }
    header('Location: /sisperlenteng/admin/desa_list.php');
    exit;
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM desa WHERE id_desa = ?');
            $stmt->execute([$id]);
            $_SESSION['flash_success'] = 'Desa berhasil dihapus.';
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus desa: ' . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = 'ID desa tidak valid.';
    }
    header('Location: /sisperlenteng/admin/desa_list.php');
    exit;
}

// Ambil daftar desa
$desa = $pdo->query('SELECT id_desa, nama FROM desa ORDER BY nama')->fetchAll(PDO::FETCH_ASSOC);

include '../partials/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Daftar Desa</h4>
        <div>
            <!-- tombol buka modal -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddDesa">Tambah Desa</button>
        </div>
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
            <?php if (empty($desa)): ?>
                <div class="p-3">Belum ada data desa.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Desa</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($desa as $i => $d): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($d['nama']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-desa" data-id="<?= (int)$d['id_desa'] ?>" data-nama="<?= htmlspecialchars($d['nama'], ENT_QUOTES) ?>">Edit</button>

                                        <form method="post" action="/sisperlenteng/admin/desa_list.php" class="d-inline-block ms-1 form-delete-desa">
                                            <input type="hidden" name="id" value="<?= (int)$d['id_desa'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
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

<!-- Modal Tambah Desa -->
<div class="modal fade" id="modalAddDesa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/sisperlenteng/admin/desa_list.php">
        <input type="hidden" name="action" value="create">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Desa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="desaNama" class="form-label">Nama Desa</label>
            <input id="desaNama" name="nama" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

    <!-- Modal Edit Desa -->
    <div class="modal fade" id="modalEditDesa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="post" action="/sisperlenteng/admin/desa_list.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editDesaId" value="">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Desa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editDesaNama" class="form-label">Nama Desa</label>
                            <input id="editDesaNama" name="nama" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // konfirmasi sebelum menghapus
    document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form.classList.contains('form-delete-desa')) {
                    if (!confirm('Yakin ingin menghapus desa ini? Aksi tidak dapat dibatalkan.')) {
                            e.preventDefault();
                    }
            }
    });

    // buka modal edit dan isi data
    document.querySelectorAll('.btn-edit-desa').forEach(function(btn){
            btn.addEventListener('click', function(){
                    const id = this.getAttribute('data-id');
                    const nama = this.getAttribute('data-nama');
                    document.getElementById('editDesaId').value = id;
                    document.getElementById('editDesaNama').value = nama;
                    var editModalEl = document.getElementById('modalEditDesa');
                    var modal = new bootstrap.Modal(editModalEl);
                    modal.show();
            });
    });
    </script>

<?php include '../partials/footer.php'; ?>

