<?php
// matikan tampilan error di halaman (sementara, hapus kalau mau debug)
@ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/auth.php';

// Status login
$is_public     = !isset($_SESSION['user']);
$is_admin      = !$is_public && is_admin();
$is_kelompok   = !$is_public && is_kelompok();
$is_masyarakat = $is_public; // masyarakat tidak login

// Base URL project
$BASE_URL = "/sisperlenteng";
$BASE_URL = rtrim($BASE_URL, '/');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SISPER - Sistem Informasi Pertanian</title>

    <!-- set base agar path relatif bekerja -->
    <base href="<?= $BASE_URL ?>/">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS (pakai base tag sehingga cukup relative path) -->
    <link href="assets/css/style.css?v=<?= time() ?>" rel="stylesheet">

    <style>
        .app-row { display: flex; gap: 20px; }
        .main-area { flex: 1; padding: 18px; }
    </style>
</head>

<body>

<?php if ($is_admin): ?>
<div class="app-row">
<?php endif; ?>

    <!-- SIDEBAR – HANYA UNTUK ADMIN -->
    <?php if ($is_admin): ?>
        <aside style="width:260px;">
            <?php
                $sidebar_file = __DIR__ . "/sidebar.html";
                echo file_exists($sidebar_file)
                    ? file_get_contents($sidebar_file)
                    : "<div class='p-3'>Sidebar tidak ditemukan</div>";
            ?>
        </aside>
    <?php endif; ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="main-area" style="<?= $is_admin ? '' : 'width:100%; padding:0;' ?>">

        <!-- TOP NAVBAR -->
        <div class="topbar d-flex justify-content-between align-items-center mb-2 px-3 py-2"
             style="background:white; border-bottom:1px solid #e0e0e0;">

            <div class="d-flex align-items-center gap-2">
                <?php if ($is_admin): ?>
                    <button class="btn btn-sm btn-outline-secondary d-md-none" id="toggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                <?php endif; ?>

                <strong>SISPER</strong>
                <small class="text-muted">/ Sistem Informasi Pertanian</small>
            </div>

            <div>
                <?php if (!$is_public): ?>
                    <span class="me-2">
                        Halo, <strong>
                            <?= htmlspecialchars(
                                $_SESSION['user']['nama_kelompok']
                                ?? $_SESSION['user']['nama']
                                ?? $_SESSION['user']['username']
                            ) ?>
                        </strong>
                    </span>

                    <a class="btn btn-sm btn-outline-danger" href="<?= $BASE_URL ?>/login/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>

                <?php else: ?>

                    <a class="btn btn-sm btn-outline-success me-2" href="<?= $BASE_URL ?>/login/login.php">Masuk</a>
                    <a class="btn btn-sm btn-success" href="<?= $BASE_URL ?>/login/register.php">Daftar</a>

                <?php endif; ?>
            </div>
        </div>

        <?php if ($is_kelompok): ?>
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-3 px-3">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuKelompok"
                        aria-controls="menuKelompok" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="menuKelompok">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $BASE_URL ?>/kelompok/anggota.php"><i class="bi bi-people"></i> Kelola Anggota</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $BASE_URL ?>/kelompok/komoditas.php"><i class="bi bi-plus-circle"></i> Input Komoditas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $BASE_URL ?>/kelompok/riwayat.php"><i class="bi bi-clock-history"></i> Riwayat Aktivitas</a>
                        </li>
                    </ul>
                </div>
            </nav>
        <?php endif; ?>

        <!-- Konten akan dimulai di file halaman (index.php, dll) -->
        <div class="container-fluid p-0">

