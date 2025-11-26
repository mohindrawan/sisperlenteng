<?php
require '../config/database.php';
require '../config/auth.php';
// inisialisasi koneksi PDO
$pdo = db();
if(!is_admin()){ header('Location: /sisperlenteng/login/login.php'); exit; }
include '../partials/header.php';
$pending = $pdo->query("SELECT COUNT(*) as c FROM komoditas WHERE status='pending'")->fetchColumn();
$total_kel = $pdo->query("SELECT COUNT(*) FROM users WHERE role='kelompok'")->fetchColumn();
?>
<h1>Admin Dashboard</h1>
<div class="row">
  <div class="col-md-4"><div class="card p-3">Komoditas Pending: <?php echo $pending; ?></div></div>
  <div class="col-md-4"><div class="card p-3">Total Kelompok: <?php echo $total_kel; ?></div></div>
</div>
<p><a href="/sisperlenteng/admin/komoditas_pending.php" class="btn btn-primary mt-3">Lihat Komoditas Pending</a></p>
<?php include '../partials/footer.php'; ?>


