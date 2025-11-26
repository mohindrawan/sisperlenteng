<?php
require '../config/database.php';
require '../config/auth.php';
if(!is_admin()){ header('Location: /sisperlenteng/login/login.php'); exit; }
include '../partials/header.php';
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$desa_id = isset($_GET['desa_id']) ? (int)$_GET['desa_id'] : 0;
// build query for laporan harga per bulan/tahun
$where = [];
$params = [];
if($bulan) { $where[] = 'h.bulan = ?'; $params[] = $bulan; }
if($tahun) { $where[] = 'h.tahun = ?'; $params[] = $tahun; }
if($desa_id){ $where[] = 'k.id_desa = ?'; $params[] = $desa_id; }
$q = 'SELECT h.*, k.nama_komoditas, d.nama as desa FROM harga_komoditas h LEFT JOIN komoditas k ON h.id_komoditas=k.id_komoditas LEFT JOIN desa d ON k.id_desa=d.id_desa';
if(count($where)) $q .= ' WHERE ' . implode(' AND ', $where);
$q .= ' ORDER BY h.tahun DESC, h.bulan DESC';
$stmt = $pdo->prepare($q); $stmt->execute($params); $rows = $stmt->fetchAll();
$desa = $pdo->query('SELECT * FROM desa')->fetchAll();
?>
<h2>Laporan Harga Komoditas</h2>
<form class="row g-3 mb-3" method="get">
  <div class="col-md-2"><input type="number" name="bulan" class="form-control" placeholder="Bulan (1-12)" value="<?php echo htmlentities($bulan); ?>"></div>
  <div class="col-md-2"><input type="number" name="tahun" class="form-control" placeholder="Tahun" value="<?php echo htmlentities($tahun); ?>"></div>
  <div class="col-md-3"><select name="desa_id" class="form-control"><option value="">Semua Desa</option><?php foreach($desa as $d): ?><option value="<?php echo $d['id_desa']; ?>" <?php if($desa_id==$d['id_desa']) echo 'selected'; ?>><?php echo htmlspecialchars($d['nama']); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
  <div class="col-md-3 text-end"><a class="btn btn-outline-secondary" href="laporan_export.php?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&desa_id=<?php echo $desa_id; ?>">Export CSV</a></div>
</form>
<table class="table"><thead><tr><th>Komoditas</th><th>Desa</th><th>Bulan</th><th>Tahun</th><th>Harga</th><th>Tanggal Update</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?php echo htmlspecialchars($r['nama_komoditas']); ?></td><td><?php echo htmlspecialchars($r['desa']); ?></td><td><?php echo $r['bulan']; ?></td><td><?php echo $r['tahun']; ?></td><td><?php echo $r['harga']; ?></td><td><?php echo $r['tanggal_update']; ?></td></tr><?php endforeach; ?></tbody></table>
<?php include '../partials/footer.php'; ?>


