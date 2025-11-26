<?php
require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

if (!is_kelompok()) {
    header("Location: /sisperlenteng/");
    exit;
}

$pdo = db();
$id_kelompok = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
if ($id_kelompok <= 0) {
    header("Location: /sisperlenteng/login/login.php");
    exit;
}

// Statistik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM petani WHERE id_kelompok = ?");
$stmt->execute([$id_kelompok]);
$total_anggota = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT komoditas_id) FROM kelompok_komoditas WHERE kelompok_id = ?");
$stmt->execute([$id_kelompok]);
$total_komoditas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jenis_tanaman WHERE id_kelompok = ? AND status = 'pending'");
$stmt->execute([$id_kelompok]);
$total_pending = (int) $stmt->fetchColumn();

// Total produksi (Rp) — jumlahkan harga di harga_komoditas untuk komoditas yang terhubung ke kelompok ini.
// Ambil daftar komoditas_master yang dimiliki kelompok ini
$stmt = $pdo->prepare("SELECT komoditas_id FROM kelompok_komoditas WHERE kelompok_id = ?");
$stmt->execute([$id_kelompok]);
$komRows = $stmt->fetchAll(PDO::FETCH_COLUMN);
$total_produksi = 0;
if (!empty($komRows)) {
    // buat placeholders untuk IN(...)
    $placeholders = implode(',', array_fill(0, count($komRows), '?'));
    // gabungkan dua kondisi: h.komoditas_master_id IN (...) OR komoditas_map.new_id IN (...)
    $sql = "
        SELECT IFNULL(SUM(h.harga),0) AS total
        FROM harga_komoditas h
        LEFT JOIN komoditas_map km ON km.old_id = h.id_komoditas
        WHERE (h.komoditas_master_id IN ($placeholders))
           OR (km.new_id IN ($placeholders))
    ";
    // parameter list = komRows twice (for both INs)
    $params = array_merge($komRows, $komRows);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $total_produksi = (float) $stmt->fetchColumn();
}

// Komoditas terbaru (dari relasi kelompok_komoditas -> komoditas_master)
$stmt = $pdo->prepare("
    SELECT m.nama AS nama_komoditas, kk.created_at 
    FROM kelompok_komoditas kk
    JOIN komoditas_master m ON m.id = kk.komoditas_id
    WHERE kk.kelompok_id = ?
    ORDER BY kk.created_at DESC
    LIMIT 5
");
$stmt->execute([$id_kelompok]);
$komoditas_baru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Riwayat aktivitas
$stmt = $pdo->prepare("
    SELECT aktivitas, detail, waktu 
    FROM history_aktivitas 
    WHERE id_kelompok = ?
    ORDER BY waktu DESC
    LIMIT 5
");
$stmt->execute([$id_kelompok]);
$riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/../partials/header.php";
?>

<div class="container">
    <h3 class="fw-bold mb-3">Dashboard Kelompok Tani</h3>

    <!-- STATISTIK -->
    <div class="row g-3">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6>Jumlah Anggota</h6>
                <h3 class="fw-bold text-primary"><?= $total_anggota ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6>Komoditas Terdaftar</h6>
                <h3 class="fw-bold text-success"><?= $total_komoditas ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6>Pending Validasi</h6>
                <h3 class="fw-bold text-warning"><?= $total_pending ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <h6>Total Produksi (Rp)</h6>
                <h3 class="fw-bold text-danger"><?= number_format($total_produksi, 0, ',', '.') ?></h3>
            </div>
        </div>
    </div>

    <!-- GRAFIK (placeholder static, later bisa diisi dengan query per-komoditas) -->
    <div class="card mt-4 shadow-sm p-3">
        <h6 class="fw-bold mb-3">Grafik Harga / Produksi</h6>
        <canvas id="grafikProduksi" height="120"></canvas>
    </div>

    <!-- LIST KOMODITAS TERBARU -->
    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Komoditas Terbaru</h6>
            <ul class="list-group">
                <?php if (empty($komoditas_baru)): ?>
                    <li class="list-group-item">Belum ada komoditas terdaftar.</li>
                <?php else: ?>
                    <?php foreach ($komoditas_baru as $k): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <?= htmlspecialchars($k['nama_komoditas']) ?>
                        <small class="text-muted"><?= htmlspecialchars($k['created_at']) ?></small>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- RIWAYAT -->
    <div class="card mt-4 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Riwayat Aktivitas</h6>
            <ul class="list-group">
                <?php if (empty($riwayat)): ?>
                    <li class="list-group-item">Belum ada aktivitas.</li>
                <?php else: ?>
                    <?php foreach ($riwayat as $r): ?>
                    <li class="list-group-item">
                        <strong><?= htmlspecialchars($r['aktivitas']) ?></strong> — <?= htmlspecialchars($r['detail']) ?>
                        <br><small class="text-muted"><?= htmlspecialchars($r['waktu']) ?></small>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('grafikProduksi');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan','Feb','Mar','Apr','Mei','Jun'],
        datasets: [{
            label: 'Harga / Produksi',
            data: [120, 190, 150, 220, 280, 300],
            borderWidth: 2
        }]
    }
});
</script>

<?php include __DIR__ . "/../partials/footer.php"; ?>


