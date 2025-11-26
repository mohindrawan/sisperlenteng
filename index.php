<?php
require 'config/database.php';
require 'config/auth.php';

$pdo = db();

// lists for filters (use komoditas_master as source)
$desa_list = $pdo->query("SELECT * FROM desa ORDER BY nama")->fetchAll();
$jenis_list = $pdo->query("SELECT nama FROM komoditas_master WHERE aktif = 1 ORDER BY nama")->fetchAll(PDO::FETCH_COLUMN);

// read filters
$desa   = isset($_GET['desa']) && $_GET['desa'] !== '' ? (int)$_GET['desa'] : 0;
$jenis  = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? trim($_GET['jenis']) : '';
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort    = isset($_GET['sort']) ? $_GET['sort'] : 'new';

// summary numbers
$total_petani = (int)$pdo->query("SELECT COUNT(*) FROM petani")->fetchColumn();
// total_stok tidak tersedia di skema baru — gunakan 0 sebagai placeholder
$total_stok = 0;
$total_desa = (int)$pdo->query("SELECT COUNT(*) FROM desa")->fetchColumn();

// Build where for komoditas_master + kelompok_komoditas view
$where = ["m.aktif = 1"];
$params = [];

if ($desa) {
    // desa filter applies to users (kelompok) linked to kelompok_komoditas
    $where[] = 'u.id_desa = ?';
    $params[] = $desa;
}
if ($jenis) {
    // jenis selected corresponds to komoditas_master.nama (Komoditas)
    $where[] = 'm.nama = ?';
    $params[] = $jenis;
}
if ($keyword) {
    $where[] = "(m.nama LIKE ? OR u.nama_kelompok LIKE ? OR p.nama_petani LIKE ? OR d.nama LIKE ?)";
    $kw = "%$keyword%";
    $params[] = $kw; $params[] = $kw; $params[] = $kw; $params[] = $kw;
}

$order = "m.created_at DESC";
if ($sort === 'az') $order = "m.nama ASC";
if ($sort === 'za') $order = "m.nama DESC";

// Query: komoditas_master entries joined to kelompok_komoditas (if any) and kelompok info
// pick one petani per kelompok (earliest) via subquery pmap
$sql = "
SELECT
  m.id AS id_komoditas,
  m.nama AS nama_komoditas,
  kk.jenis_tanaman,
  u.nama_kelompok,
  d.nama AS nama_desa,
  p.nama_petani,
  COALESCE(kk.created_at, m.created_at) AS tanggal_input,
  'master' AS source
FROM komoditas_master m
LEFT JOIN kelompok_komoditas kk ON kk.komoditas_id = m.id
LEFT JOIN users u ON kk.kelompok_id = u.id
LEFT JOIN desa d ON u.id_desa = d.id_desa
LEFT JOIN (
    SELECT id_kelompok, MIN(id_petani) AS pid FROM petani GROUP BY id_kelompok
) pmap ON pmap.id_kelompok = u.id
LEFT JOIN petani p ON p.id_petani = pmap.pid
";

if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY $order LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SisPertanian Lenteng</title>

  <!-- Bootstrap + Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Custom CSS -->
  <link href="/sisperlenteng/assets/css/style.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm py-3">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
      <img src="assets/img/logo.jfif" width="45" class="me-2">
      <span class="fw-bold text-success">SisPertanian Lenteng</span>
    </a>
    <div>
      <a href="login/login.php" class="btn btn-outline-success me-2">Masuk</a>
      <a href="login/register.php" class="btn btn-success">Daftar Kelompok</a>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="hero-content">
    <h1 class="fw-bold">Sistem Informasi Pertanian</h1>
    <h4>Kecamatan Lenteng, Kabupaten Sumenep</h4>
    <p>Temukan hasil tani, petani, dan potensi pertanian di Desa Lenteng</p>
  </div>
</section>

<section class="container" style="margin-top:-60px; position:relative; z-index:3;">
  <div class="row g-3 justify-content-center">
    <div class="col-md-4"><div class="stats-box"><h3><?= number_format($total_petani) ?></h3><p>Jumlah Petani</p></div></div>
    <div class="col-md-4"><div class="stats-box"><h3><?= number_format($total_stok) ?></h3><p>Total Stok (Kg)</p></div></div>
    <div class="col-md-4"><div class="stats-box"><h3><?= number_format($total_desa) ?></h3><p>Jumlah Desa</p></div></div>
  </div>
</section>

<section class="container mt-5 p-4 bg-white shadow-sm rounded-4">
  <form method="get" class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Desa</label>
      <select name="desa" class="form-select">
        <option value="">Semua Desa</option>
        <?php foreach($desa_list as $d): ?>
          <option value="<?= $d['id_desa'] ?>" <?= $desa==$d['id_desa']?'selected':'' ?>>
            <?= htmlspecialchars($d['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Komoditas</label>
      <select name="jenis" class="form-select">
        <option value="">Semua Komoditas</option>
        <?php foreach($jenis_list as $j): ?>
          <option value="<?= htmlspecialchars($j) ?>" <?= $jenis===$j?'selected':'' ?>>
            <?= htmlspecialchars($j) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Cari Petani / Kelompok</label>
      <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" class="form-control" placeholder="Nama petani / kelompok / desa...">
    </div>

    <div class="col-md-2 d-grid">
      <button class="btn btn-success mt-4"><i class="bi bi-search"></i> Cari</button>
    </div>
  </form>
</section>

<section class="container mt-5">
  <h4 class="fw-bold mb-3">Hasil Tani</h4>
  <div class="row g-4">

    <?php if(empty($items)): ?>
      <div class="alert alert-warning">Tidak ada hasil sesuai filter.</div>
    <?php endif; ?>

    <?php foreach($items as $it):
        $nama_komoditas = $it['nama_komoditas'] ?? "-";
        $jenis          = $it['jenis_tanaman'] ?? "-";
        $nama_petani    = $it['nama_petani'] ?: "Tidak ada petani";
        $nama_desa      = $it['nama_desa'] ?: "Tidak diketahui";

        // foto masih disimpan per-kelompok/jenis di implementasi selanjutnya
        $fotoUrl = '/sisperlenteng/uploads/no-image.png';
    ?>

<div class="col-md-4 fade-in">
    <div class="card hasil-card">
        <img src="<?= $fotoUrl ?>" class="card-img-top" alt="<?= htmlspecialchars($nama_komoditas) ?>">
        <div class="card-body">
            <h5 class="fw-bold mb-2"><?= htmlspecialchars($nama_komoditas) ?></h5>
            <span class="tag-komoditas">
                <i class="bi bi-leaf"></i> <?= htmlspecialchars($jenis) ?>
            </span>

            <div class="info-item mb-1">
                <i class="bi bi-person"></i> <?= htmlspecialchars($it['nama_kelompok'] ?? $nama_petani) ?>
            </div>

            <div class="info-item mb-3">
                <i class="bi bi-geo"></i> <?= htmlspecialchars($nama_desa) ?>
            </div>

            <button type="button" class="btn btn-outline-success btn-detail w-100" data-id="<?= (int)($it['id_komoditas'] ?? 0) ?>">
               <i class="bi bi-eye"></i> Lihat Detail
            </button>
        </div>
    </div>
</div>

    <?php endforeach; ?>
  </div>
</section>

<!-- modal, scripts, chart section tetap — gunakan existing chart code but adapt queries if needed -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function(e){
    const btn = e.target.closest('.btn-detail');
    if (!btn) return;
    const id = btn.dataset.id || 0;
    const modalEl = document.getElementById('komoditasModal');
    const modal = new bootstrap.Modal(modalEl);
    modalEl.querySelector('.modal-body').innerHTML = '<div class="text-center py-4">Memuat...</div>';
    fetch('/sisperlenteng/masyarakat/detail-komoditas.php?id=' + encodeURIComponent(id))
      .then(resp => { if (!resp.ok) throw new Error('Fetch error'); return resp.text(); })
      .then(html => { modalEl.querySelector('.modal-body').innerHTML = html; modal.show(); })
      .catch(() => { modalEl.querySelector('.modal-body').innerHTML = '<div class="alert alert-danger">Gagal memuat detail.</div>'; modal.show(); });
});
</script>

<?php include 'partials/footer.php'; ?>


