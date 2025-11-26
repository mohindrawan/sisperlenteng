<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../config/auth.php';

$pdo = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo '<div class="alert alert-danger">ID tidak valid.</div>';
    exit;
}

$stmt = $pdo->prepare("SELECT k.*, p.nama_petani, p.no_telepon, u.nama_kelompok, d.nama as nama_desa 
    FROM komoditas k 
    LEFT JOIN petani p ON k.id_petani = p.id_petani 
    LEFT JOIN users u ON k.id_kelompok = u.id 
    LEFT JOIN desa d ON k.id_desa = d.id_desa 
    WHERE k.id_komoditas = ? AND k.status='approved' LIMIT 1");
$stmt->execute([$id]);
$k = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$k) {
    echo '<div class="alert alert-warning">Komoditas tidak ditemukan atau belum disetujui.</div>';
    exit;
}

// safe helpers
$nama = htmlspecialchars((string)$k['nama_komoditas'] ?? '-', ENT_QUOTES);
$jenis = htmlspecialchars((string)$k['jenis_tanaman'] ?? '-', ENT_QUOTES);
$kelompok = htmlspecialchars((string)$k['nama_kelompok'] ?? '-', ENT_QUOTES);
$petani = htmlspecialchars((string)$k['nama_petani'] ?? '-', ENT_QUOTES);
$telepon_petani = htmlspecialchars((string)($k['no_telepon'] ?? '-'), ENT_QUOTES);
$desa = htmlspecialchars((string)$k['nama_desa'] ?? '-', ENT_QUOTES);
$deskripsi = nl2br(htmlspecialchars((string)$k['deskripsi'] ?? '-', ENT_QUOTES));
$stok = (int)($k['stok'] ?? 0);
$foto = '';
if (!empty($k['foto']) && file_exists(__DIR__ . '/../uploads/' . $k['foto'])) {
    $foto = '/sisperlenteng/uploads/' . rawurlencode($k['foto']);
} else {
    $foto = '/sisperlenteng/uploads/no-image.png';
}

// harga historis ringkas (opsional)
$history = [];
try {
    // coba query standar (tanggal, harga)
    $prices = $pdo->prepare("SELECT tanggal, harga FROM harga_komoditas WHERE id_komoditas = ? ORDER BY tanggal ASC LIMIT 100");
    $prices->execute([$id]);
    $history = $prices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // jika kolom berbeda pada skema DB, coba fallback ambil kolom 'harga' saja
    try {
        $prices = $pdo->prepare("SELECT harga FROM harga_komoditas WHERE id_komoditas = ? LIMIT 100");
        $prices->execute([$id]);
        $rows = $prices->fetchAll(PDO::FETCH_ASSOC);
        $history = array_map(function($r){
            return [
                'tanggal' => $r['tanggal'] ?? null,
                'harga'   => $r['harga'] ?? null
            ];
        }, $rows);
    } catch (Exception $e2) {
        $history = [];
    }
}
?>
<div class="row">
  <div class="col-md-5">
    <img src="<?= $foto ?>" alt="<?= $nama ?>" class="img-fluid rounded mb-3" style="width:100%; object-fit:cover;">
  </div>
  <div class="col-md-7">
    <h4 class="mb-1"><?= $nama ?></h4>
    <p class="text-muted mb-1">Jenis: <?= $jenis ?></p>
    <p class="mb-1"><strong>Kelompok:</strong> <?= $kelompok ?></p>
    <p class="mb-1"><strong>Petani:</strong> <?= $petani ?> - Telepon: <?= $telepon_petani ?></p>
    <p class="mb-1"><strong>Desa:</strong> <?= $desa ?></p>
    <p class="mb-1"><strong>Stok:</strong> <?= $stok ?> Kg</p>
    <hr>
    <div class="mb-2"><strong>Deskripsi</strong></div>
    <div class="mb-3"><?= $deskripsi ?></div>

    <?php if ($history): ?>
      <div>
        <strong>Riwayat Harga</strong>
        <ul class="list-unstyled small">
          <?php foreach ($history as $h): 
                $date = $h['tanggal'] ?? '-';
                $price = isset($h['harga']) && $h['harga'] !== null ? 'Rp ' . number_format((float)$h['harga'],0,',','.') : '-';
          ?>
            <li><?= htmlspecialchars($date) ?> — <?= $price ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
</div>

