<?php
require '../config/database.php';
require '../config/auth.php';
$pdo = db();
if(!is_kelompok()){ header('Location: /sisperlenteng/login/login.php'); exit; }
include '../partials/header.php';
$uid = $_SESSION['user']['id'];
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['nama_petani'])){
    $stmt = $pdo->prepare('INSERT INTO petani (id_kelompok, nama_petani, no_telepon, alamat) VALUES (?, ?, ?, ?)');
    $stmt->execute([$uid, $_POST['nama_petani'], $_POST['no_telepon'], $_POST['alamat']]);
    $pdo->prepare('INSERT INTO history_aktivitas (id_kelompok, aktivitas, detail) VALUES (?, "tambah_petani", ?)')->execute([$uid, $_POST['nama_petani']]);
}
$petani = $pdo->prepare('SELECT * FROM petani WHERE id_kelompok=?'); $petani->execute([$uid]);
$petani = $petani->fetchAll();
?>
<h2>Daftar Anggota (Petani)</h2>
<table class="table"><thead><tr><th>Nama</th><th>Telepon</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($petani as $p): ?>
<tr><td><?php echo htmlspecialchars($p['nama_petani']); ?></td><td><?php echo htmlspecialchars($p['no_telepon']); ?></td><td></td></tr>
<?php endforeach; ?></tbody></table>
<hr>
<h3>Tambah Anggota</h3>
<form method="post">
  <div class="mb-3"><label>Nama</label><input class="form-control" name="nama_petani" required></div>
  <div class="mb-3"><label>No Telepon</label><input class="form-control" name="no_telepon"></div>
  <div class="mb-3"><label>Alamat</label><textarea class="form-control" name="alamat"></textarea></div>
  <button class="btn btn-success">Tambah</button>
</form>
<?php include '../partials/footer.php'; ?>


