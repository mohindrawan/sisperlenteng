<?php
require '../config/database.php';
require '../config/auth.php';
// inisialisasi koneksi PDO
$pdo = db();


if(!is_admin()){
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

// lanjutkan halaman
include '../partials/header.php';
$items = $pdo->query("SELECT k.*, u.nama_kelompok FROM komoditas k LEFT JOIN users u ON k.id_kelompok=u.id WHERE k.status='pending' ORDER BY k.tanggal_input DESC")->fetchAll();
?>
<h2>Komoditas Pending</h2>
<table class="table">
  <thead><tr><th>Nama</th><th>Kelompok</th><th>Tanggal</th><th>Aksi</th></tr></thead>
  <tbody>
  <?php foreach($items as $it): ?>
    <tr>
      <td><?php echo htmlspecialchars($it['nama_komoditas']); ?></td>
      <td><?php echo htmlspecialchars($it['nama_kelompok']); ?></td>
      <td><?php echo $it['tanggal_input']; ?></td>
      <td>
        <form method="post" action="/sisperlenteng/admin/komoditas_action.php" style="display:inline-block">
          <input type="hidden" name="id" value="<?php echo (int)$it['id_komoditas']; ?>">
          <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
        </form>
        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo (int)$it['id_komoditas']; ?>">Reject</button>
        <!-- Modal -->
        <div class="modal fade" id="rejectModal<?php echo (int)$it['id_komoditas']; ?>" tabindex="-1">
          <div class="modal-dialog"><div class="modal-content"><form method="post" action="/sisperlenteng/admin/komoditas_action.php">
            <div class="modal-header"><h5 class="modal-title">Alasan Reject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="hidden" name="id" value="<?php echo (int)$it['id_komoditas']; ?>"><textarea name="alasan" class="form-control" required></textarea></div>
            <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button name="action" value="reject" class="btn btn-danger">Kirim</button></div>
          </form></div></div>
        </div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php include '../partials/footer.php'; ?>


