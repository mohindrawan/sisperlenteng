<?php
require '../config/database.php'; require '../config/auth.php'; if(!is_kelompok()) { header('Location: /sisper_php/login/login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; $uid = $_SESSION['user']['id'];
$kom = $pdo->prepare('SELECT * FROM komoditas WHERE id_komoditas=? AND id_kelompok=?'); $kom->execute([$id,$uid]); $kom = $kom->fetch();
if(!$kom){ header('Location: komoditas.php'); exit; }
if($kom['status'] !== 'pending'){ echo 'Komoditas sudah tidak dapat diedit karena status bukan pending.'; exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
    $foto = $kom['foto'];
    if(isset($_FILES['foto']) && $_FILES['foto']['error']===0){ $fname = time().'_'.basename($_FILES['foto']['name']); move_uploaded_file($_FILES['foto']['tmp_name'], __DIR__.'/../assets/img/'.$fname); $foto=$fname; }
    $pdo->prepare('UPDATE komoditas SET nama_komoditas=?, jenis_tanaman=?, id_petani=?, id_desa=?, deskripsi=?, foto=? WHERE id_komoditas=?')->execute([$_POST['nama_komoditas'], $_POST['jenis_tanaman'], $_POST['id_petani'], $_POST['id_desa'], $_POST['deskripsi'], $foto, $id]);
    header('Location: komoditas.php'); exit;
}
$petani = $pdo->prepare('SELECT * FROM petani WHERE id_kelompok=?'); $petani->execute([$uid]); $petani = $petani->fetchAll(); $desa = $pdo->query('SELECT * FROM desa ORDER BY nama')->fetchAll();
include '../partials/header.php';
?>
<h2>Edit Komoditas (Pending)</h2>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3"><label>Nama Komoditas</label><input class="form-control" name="nama_komoditas" value="<?php echo htmlspecialchars($kom['nama_komoditas']); ?>"></div>
  <div class="mb-3"><label>Jenis Tanaman</label><input class="form-control" name="jenis_tanaman" value="<?php echo htmlspecialchars($kom['jenis_tanaman']); ?>"></div>
  <div class="mb-3"><label>Petani</label><select name="id_petani" class="form-control"><?php foreach($petani as $p): ?><option value="<?php echo $p['id_petani']; ?>" <?php if($p['id_petani']==$kom['id_petani']) echo 'selected'; ?>><?php echo htmlspecialchars($p['nama_petani']); ?></option><?php endforeach; ?></select></div>
  <div class="mb-3"><label>Desa</label><select name="id_desa" class="form-control"><?php foreach($desa as $d): ?><option value="<?php echo $d['id_desa']; ?>" <?php if($d['id_desa']==$kom['id_desa']) echo 'selected'; ?>><?php echo htmlspecialchars($d['nama']); ?></option><?php endforeach; ?></select></div>
  <div class="mb-3"><label>Deskripsi</label><textarea class="form-control" name="deskripsi"><?php echo htmlspecialchars($kom['deskripsi']); ?></textarea></div>
  <div class="mb-3"><label>Foto (ganti jika perlu)</label><input type="file" name="foto" class="form-control"></div>
  <button class="btn btn-primary">Simpan Perubahan</button>
</form>
<?php include '../partials/footer.php'; ?>


