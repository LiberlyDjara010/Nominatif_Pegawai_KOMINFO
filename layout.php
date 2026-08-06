<?php
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$namaUser    = $_SESSION['nama'] ?? $_SESSION['user'];
$roleUser    = $_SESSION['role'] ?? 'user';
$inisial     = strtoupper(substr($namaUser, 0, 1));

$roleLabel = isSuperAdmin($roleUser) ? 'Super Admin (APTIKA)' : 'User (Kepegawaian)';

// NAV ITEMS

$navItems = [
    ['href' => 'dashboard.php',         'icon' => '',           'label' => 'Dashboard',                 'page' => 'dashboard'],
    ['href' => 'pegawai.php',           'icon' => '',           'label' => 'Data Pegawai',              'page' => 'pegawai'],
    ['href' => 'pensiun.php',           'icon' => '',           'label' => 'Akan Pensiun',              'page' => 'pensiun'],
    ['href' => 'pangkat.php',           'icon' => '',           'label' => 'Kenaikan Pangkat',          'page' => 'pangkat'],
    ['href' => 'tambah.php',            'icon' => '',           'label' => 'Tambah Pegawai',            'page' => 'tambah'],
];

if (isSuperAdmin($roleUser)) {
    $navItems[] = ['href' => 'kelola_akun.php', 'icon' => '', 'label' => 'Kelola Akun (Hak Akses)', 'page' => 'kelola_akun'];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?? 'Sistem Nominatif Pegawai' ?> — Diskominfo Papua</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=20260804">
<?= $extraHead ?? '' ?>
</head>
<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="sidebar-brand">
        <button type="button" class="sidebar-toggle" aria-label="Buka/tutup sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="flex justify-center">
            <img class="sidebar-logo" src="LOGO_DISKOMINFO.png"
            alt="Logo Diskominfo Papua" />
        </div>

        <br>
        <h2>Nominatif Pegawai<br>Diskominfo Papua</h2>
        <p>Sistem Manajemen Kepegawaian</p>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Menu Utama</div>
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="nav-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
            <span class="nav-text"><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <a href="logout.php" class="btn-logout">
            <span class="nav-text">Keluar</span>
        </a>
    </div>

</aside>

<!-- MAIN -->

<div class="main-content">

    <!-- TOPBAR -->

    <div class="topbar">
        <div class="topbar-title">
            <h1><?= $pageTitle ?? 'Dashboard' ?></h1>
            <p><?= $pageSubtitle ?? date('l, d F Y') ?></p>
        </div>
        <div class="topbar-right">
            <div class="topbar-user-chip">
                Login sebagai: <strong><?= e($namaUser) ?></strong>
            </div>
        </div>
    </div>

    <div class="page-body">