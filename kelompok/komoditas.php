<?php
require '../config/database.php';
require '../config/auth.php';

$pdo = db();
// pastikan PDO mengeluarkan exception agar kita dapat menangkap error DB
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if(!is_kelompok()){ header('Location: /sisperlenteng/login/login.php'); exit; }
include '../partials/header.php';
$uid = $_SESSION['user']['id'];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['nama_komoditas'])){
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        // validasi sederhana
        $allowed = ['image/jpeg','image/png','image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['foto']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowed) && $_FILES['foto']['size'] <= 2*1024*1024) {
            // buat nama aman dan unik
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $safe = preg_replace('/[^a-z0-9_\-]/i','_', pathinfo($_FILES['foto']['name'], PATHINFO_FILENAME));
            $fname = time() . '_' . $safe . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $fname;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                $foto = $fname;
            }
        }
    }

    // validasi sederhana & normalisasi input
    $nama = trim($_POST['nama_komoditas']);
    $jenis = trim($_POST['jenis_tanaman'] ?? '');
    $stok = (int)($_POST['stok'] ?? 0);
    $id_petani = (int)($_POST['id_petani'] ?? 0);
    $id_desa = (int)($_POST['id_desa'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    // cek keberadaan petani & desa (hindari FK failure)
    $err = null;
    try {
        if ($id_petani > 0) {
            $c = $pdo->prepare('SELECT COUNT(*) FROM petani WHERE id_petani = ?');
            $c->execute([$id_petani]);
            if ($c->fetchColumn() == 0) $err = 'Petani yang dipilih tidak ditemukan.';
        } else {
            $err = 'Pilih petani yang valid.';
        }

        if ($id_desa > 0) {
            $c2 = $pdo->prepare('SELECT COUNT(*) FROM desa WHERE id_desa = ?');
            $c2->execute([$id_desa]);
            if ($c2->fetchColumn() == 0) $err = 'Desa yang dipilih tidak ditemukan.';
        } else {
            $err = 'Pilih desa yang valid.';
        }
    } catch (PDOException $e) {
        $err = 'Error pengecekan referensi: ' . $e->getMessage();
    }

    if ($err) {
        $error_message = $err;
    } else {
        // lakukan insert dengan try/catch — tampilkan error jika gagal
        $stmt = $pdo->prepare('INSERT INTO komoditas (nama_komoditas, jenis_tanaman, stok, id_petani, id_kelompok, id_desa, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        try {
            $ok = $stmt->execute([$nama, $jenis, $stok, $id_petani, $uid, $id_desa, $deskripsi, $foto]);
            if ($ok) {
                $lastId = $pdo->lastInsertId();
                $success_message = "Komoditas berhasil disimpan (ID: {$lastId}).";
                // jangan redirect dulu agar kita bisa lihat pesan; jika mau redirect hapus garis berikut
                // header('Location: /sisperlenteng/kelompok/komoditas.php'); exit;
            } else {
                $info = $stmt->errorInfo();
                $error_message = 'Gagal insert: ' . ($info[2] ?? json_encode($info));
            }
        } catch (PDOException $e) {
            $error_message = 'Exception saat menyimpan: ' . $e->getMessage();
        }
    }
}

$my = $pdo->prepare('SELECT * FROM komoditas WHERE id_kelompok=? ORDER BY tanggal_input DESC'); $my->execute([$uid]); $my = $my->fetchAll();
$petani = $pdo->prepare('SELECT * FROM petani WHERE id_kelompok=?'); $petani->execute([$uid]); $petani = $petani->fetchAll();
$desa = $pdo->query('SELECT * FROM desa ORDER BY nama')->fetchAll();
?>
<h2>Kelola Komoditas</h2>

<?php if (!empty($error_message)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
<?php endif; ?>
<?php if (!empty($success_message)): ?>
  <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<table class="table"><thead><tr><th>Nama</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>
<?php foreach($my as $m): ?>
<tr><td><?php echo htmlspecialchars($m['nama_komoditas']); ?></td><td><?php echo $m['status']; ?></td><td><?php echo $m['tanggal_input']; ?></td><td><?php if($m['status']=='pending') echo '<a class="btn btn-sm btn-warning" href="komoditas_edit.php?id='.$m['id_komoditas'].'">Edit</a>'; ?></td></tr>
<?php endforeach; ?></tbody></table>
<hr>
<h3>Tambah Komoditas</h3>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3"><label>Nama Komoditas</label><input class="form-control" name="nama_komoditas" required></div>
  <div class="mb-3"><label>Jenis Tanaman</label><input class="form-control" name="jenis_tanaman"></div>
  <div class="mb-3"><label>Stok</label><input class="form-control" name="stok"></div>
  <div class="mb-3"><label>Petani (pemilik)</label>
    <select name="id_petani" class="form-control">
      <?php foreach($petani as $p): ?><option value="<?php echo $p['id_petani']; ?>"><?php echo htmlspecialchars($p['nama_petani']); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3"><label>Desa</label>
    <select name="id_desa" class="form-control">
      <?php foreach($desa as $d): ?><option value="<?php echo $d['id_desa']; ?>"><?php echo htmlspecialchars($d['nama']); ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3"><label>Deskripsi</label><textarea class="form-control" name="deskripsi"></textarea></div>
  <div class="mb-3"><label>Foto</label><input type="file" class="form-control" name="foto"></div>
  <button class="btn btn-primary">Simpan (Pending)</button>
</form>
<?php include '../partials/footer.php'; ?>


