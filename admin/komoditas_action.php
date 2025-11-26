<?php
require '../config/database.php';
require '../config/auth.php';
// inisialisasi koneksi PDO
$pdo = db();

if(!is_admin()){
    header('Location: /sisperlenteng/login/login.php');
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: /sisperlenteng/admin/komoditas_pending.php');
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$alasan = trim($_POST['alasan'] ?? '');
// CSRF validation
$token = $_POST['_csrf'] ?? '';
if (!validate_csrf($token)) {
    $_SESSION['flash_error'] = 'Token CSRF tidak valid.';
    header('Location: /sisperlenteng/admin/komoditas_pending.php');
    exit;
}

if ($id <= 0 || !$action) {
    header('Location: /sisperlenteng/admin/komoditas_pending.php');
    exit;
}

try {
    // ambil data komoditas dulu
    $stmt = $pdo->prepare("SELECT id_komoditas, id_kelompok, nama_komoditas FROM komoditas WHERE id_komoditas = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $_SESSION['flash_error'] = 'Komoditas tidak ditemukan.';
        header('Location: /sisperlenteng/admin/komoditas_pending.php');
        exit;
    }

    $pdo->beginTransaction();

    if ($action === 'approve') {
        $u = $pdo->prepare('UPDATE komoditas SET status = "approved" WHERE id_komoditas = ?');
        $u->execute([$id]);

        // notification
        $not = $pdo->prepare('INSERT INTO notifications (id_user, title, message) VALUES (?, ?, ?)');
        $not->execute([$row['id_kelompok'], 'Komoditas Disetujui', 'Komoditas Anda "' . $row['nama_komoditas'] . '" telah disetujui oleh admin.']);

        // history
        $h = $pdo->prepare('INSERT INTO history_aktivitas (id_kelompok, id_komoditas, aktivitas, detail) VALUES (?, ?, ?, ?)');
        $h->execute([$row['id_kelompok'], $id, 'approve_komoditas', 'Disetujui oleh admin']);
    } elseif ($action === 'reject') {
        // pastikan alasan tersedia
        $alasan_db = $alasan ?: 'Tidak ada alasan diberikan';
        $q = $pdo->prepare('UPDATE komoditas SET status = "rejected", alasan_tolak = ? WHERE id_komoditas = ?');
        $q->execute([$alasan_db, $id]);

        // notification (pastikan $alasan sudah tersedia sebelum dipakai)
        $not = $pdo->prepare('INSERT INTO notifications (id_user, title, message) VALUES (?, ?, ?)');
        $not->execute([$row['id_kelompok'], 'Komoditas Ditolak', 'Komoditas Anda "' . $row['nama_komoditas'] . '" ditolak. Alasan: ' . $alasan_db]);

        // history
        $h = $pdo->prepare('INSERT INTO history_aktivitas (id_kelompok, id_komoditas, aktivitas, detail, status) VALUES (?, ?, ?, ?, ?)');
        $h->execute([$row['id_kelompok'], $id, 'reject_komoditas', $alasan_db, 'rejected']);
    }

    $pdo->commit();
    $_SESSION['flash_success'] = 'Aksi berhasil diproses.';
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['flash_error'] = 'Terjadi kesalahan: ' . $e->getMessage();
}

header('Location: /sisperlenteng/admin/komoditas_pending.php');
exit;


