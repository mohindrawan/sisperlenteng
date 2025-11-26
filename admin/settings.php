<?php
require '../config/auth.php';
if (!is_admin()) {
    header('Location: /sisperlenteng/login/login.php');
    exit;
}
include '../partials/header.php';
?>

<div class="container py-4">
    <h4>Pengaturan</h4>
    <div class="card mt-3">
        <div class="card-body">
            <p>Halaman pengaturan sederhana. Tambahkan konfigurasi aplikasi di sini (placeholder).</p>
            <ul>
                <li>Pengaturan umum</li>
                <li>Backup / Restore (belum diimplementasikan)</li>
                <li>Notifikasi</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../partials/footer.php'; ?>
