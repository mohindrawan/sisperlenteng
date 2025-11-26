<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$pdo = db();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

// AJAX endpoint: ?action=history&id=...
if (isset($_GET['action'])) {
    // History (per komoditas)
    if ($_GET['action'] === 'history') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            http_response_code(400);
            echo '<div class="alert alert-warning">ID komoditas tidak valid.</div>';
            exit;
        }
        try {
            $stmt = $pdo->prepare("SELECT h.id_harga, h.bulan, h.tahun, h.harga, h.tanggal_update, u.nama_kelompok AS admin_name
                FROM harga_komoditas h
                LEFT JOIN users u ON h.id_admin = u.id
                WHERE h.id_komoditas = ?
                ORDER BY h.tahun DESC, h.bulan DESC, h.id_harga DESC
                LIMIT 200");
            $stmt->execute([$id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            http_response_code(500);
            echo '<div class="alert alert-danger">Gagal memuat riwayat: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }

        if (empty($rows)) {
            echo '<div class="alert alert-info">Belum ada data harga untuk komoditas ini.</div>';
            exit;
        }

        echo '<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr><th>Periode</th><th>Harga (Rp)</th><th>Diupdate</th><th>Admin</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            printf(
                '<tr><td>%s</td><td>Rp %s</td><td>%s</td><td>%s</td></tr>',
                sprintf('%02d/%04d', (int)($r['bulan'] ?? 0), (int)($r['tahun'] ?? 0)),
                number_format($r['harga'],0,',','.'),
                htmlspecialchars($r['tanggal_update'] ?? '-'),
                htmlspecialchars($r['admin_name'] ?? '-')
            );
        }
        echo '</tbody></table></div>';
        exit;
    }
}

include __DIR__ . '/../partials/header.php';

// ambil daftar komoditas (approved + pending)
$komsStmt = $pdo->prepare("SELECT id_komoditas, nama_komoditas FROM komoditas WHERE status IN ('approved','pending') ORDER BY nama_komoditas");
$komsStmt->execute();
$koms = $komsStmt->fetchAll(PDO::FETCH_ASSOC);

// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_komoditas'])) {
    $errors = [];
    $id = (int)($_POST['id_komoditas'] ?? 0);
    $bulan = (int)($_POST['bulan'] ?? 0);
    $tahun = (int)($_POST['tahun'] ?? 0);
    $harga = str_replace(',', '', trim($_POST['harga'] ?? '0'));
    $harga = is_numeric($harga) ? (float)$harga : 0.0;
    $admin = $_SESSION['user']['id'] ?? null;

    if ($id <= 0) $errors[] = 'Pilih komoditas yang valid.';
    if ($bulan < 1 || $bulan > 12) $errors[] = 'Bulan harus antara 1-12.';
    if ($tahun < 2000 || $tahun > 2100) $errors[] = 'Tahun tidak valid.';
    if ($harga <= 0) $errors[] = 'Harga harus lebih besar dari 0.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // cek apakah sudah ada entry untuk komoditas + bulan + tahun
            $chk = $pdo->prepare('SELECT id_harga FROM harga_komoditas WHERE id_komoditas = ? AND bulan = ? AND tahun = ? LIMIT 1');
            $chk->execute([$id, $bulan, $tahun]);
            $existing = $chk->fetch(PDO::FETCH_COLUMN);

            if ($existing) {
                // update existing
                $upd = $pdo->prepare('UPDATE harga_komoditas SET harga = ?, id_admin = ?, tanggal_update = NOW() WHERE id_harga = ?');
                $upd->execute([$harga, $admin, $existing]);
                $_SESSION['flash_success'] = 'Harga berhasil diperbarui untuk periode yang sama.';
            } else {
                // insert baru
                $ins = $pdo->prepare('INSERT INTO harga_komoditas (id_komoditas, bulan, tahun, harga, id_admin) VALUES (?, ?, ?, ?, ?)');
                $ins->execute([$id, $bulan, $tahun, $harga, $admin]);
                $_SESSION['flash_success'] = 'Harga berhasil ditambahkan.';
            }

            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error'] = 'Gagal menyimpan harga: ' . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = implode(' ', $errors);
    }

    // redirect untuk menghindari double submit dan menampilkan flash
    header('Location: /sisperlenteng/admin/harga.php');
    exit;
}

// ambil histori terakhir (limit 50)
$rows = $pdo->query('SELECT h.id_harga, h.bulan, h.tahun, h.harga, h.tanggal_update, k.nama_komoditas, u.nama_kelompok AS admin_name
    FROM harga_komoditas h
    LEFT JOIN komoditas k ON h.id_komoditas = k.id_komoditas
    LEFT JOIN users u ON h.id_admin = u.id
    ORDER BY h.tahun DESC, h.bulan DESC, h.tanggal_update DESC
    LIMIT 50')->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Kelola Harga Komoditas</h2>

<?php if (!empty($_SESSION['flash_success'])): ?>
  <div class="alert alert-success"><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="d-flex justify-content-start align-items-center mb-4">
  <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#modalAddPrice">
    <i class="bi bi-plus-lg me-1"></i> Tambah / Perbarui Harga
  </button>
  <!-- Ringkasan dihapus -->
</div>

<!-- Modal: Tambah / Perbarui Harga -->
<div class="modal fade" id="modalAddPrice" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="/sisperlenteng/admin/harga.php">
        <div class="modal-header">
          <h5 class="modal-title">Tambah / Perbarui Harga Komoditas</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Komoditas</label>
            <select name="id_komoditas" class="form-select" required>
              <option value="">-- Pilih Komoditas --</option>
              <?php foreach ($koms as $k): ?>
                <option value="<?= (int)$k['id_komoditas'] ?>"><?= htmlspecialchars($k['nama_komoditas']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Bulan</label>
              <input type="number" name="bulan" min="1" max="12" class="form-control" value="<?= date('n') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label">Tahun</label>
              <input type="number" name="tahun" class="form-control" value="<?= date('Y') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Harga (Rp)</label>
            <input name="harga" type="number" step="0.01" min="0" class="form-control" required>
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

<script>
// fokus ke select ketika modal Add terbuka (tetap)
document.getElementById('modalAddPrice')?.addEventListener('shown.bs.modal', function () {
  const sel = this.querySelector('select[name="id_komoditas"]');
  if (sel) setTimeout(() => sel.focus(), 120);
});
// ringkasan dihapus — tidak ada listener modalSummary
</script>

<hr>
<h3>Histori Harga Terbaru</h3>
<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <tr><th>Komoditas</th><th>Periode</th><th>Harga</th><th>Admin</th><th>Tanggal Update</th></tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center">Belum ada data harga.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama_komoditas'] ?? '-') ?></td>
            <td><?= sprintf('%02d/%04d', (int)($r['bulan'] ?? 0), (int)($r['tahun'] ?? 0)) ?></td>
            <td><?= isset($r['harga']) ? 'Rp ' . number_format($r['harga'],0,',','.') : '-' ?></td>
            <td><?= htmlspecialchars($r['admin_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['tanggal_update'] ?? '-') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>


