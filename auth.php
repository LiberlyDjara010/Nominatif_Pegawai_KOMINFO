<?php

if(session_status()==PHP_SESSION_NONE){
    session_start();
}

function normalizeRole($role): string {
    return strtolower(trim((string) ($role ?? '')));
}

function isSuperAdmin(?string $role = null): bool {
    $roleName = normalizeRole($role ?? ($_SESSION['role'] ?? ''));
    return $roleName === 'super admin'
        || $roleName === 'superadmin'
        || $roleName === 'aptika'
        || $roleName === 'admin'
        || strpos($roleName, 'super') !== false
        || strpos($roleName, 'aptika') !== false;
}

function isUserRole(?string $role = null): bool {
    $roleName = normalizeRole($role ?? ($_SESSION['role'] ?? ''));
    return $roleName === 'kepegawaian'
        || $roleName === 'bagian kepegawaian'
        || $roleName === 'user';
}

function canManageData(?string $role = null): bool {
    return isSuperAdmin($role) || isUserRole($role);
}
function requireCanManageData(): void {
    if (!canManageData()) {
        $roleSekarang = $_SESSION['role'] ?? '(tidak ada)';
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
        <title>Akses Ditolak</title></head><body style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2>Akses Ditolak</h2>
        <p>Akun Anda tidak memiliki hak untuk mengelola data pegawai.</p>
        <p style="color:#888;font-size:13px;">Role akun saat ini: <strong>' . htmlspecialchars((string) $roleSekarang) . '</strong></p>
        <p style="color:#888;font-size:13px;">Role yang diizinkan: <strong>superadmin</strong> (APTIKA) atau <strong>kepegawaian</strong> (User).<br>
        Jika role akun Anda masih role lama (mis. "sekretaris"/"kepala dinas"), minta Super Admin memperbaruinya lewat menu <em>Kelola Akun</em>.</p>
        <p><a href="dashboard.php">← Kembali ke Dashboard</a></p>
        </body></html>';
        exit;
    }
}

function requireSuperAdmin(): void {
    if (!isSuperAdmin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
        <title>Akses Ditolak</title></head><body style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2>Akses Ditolak</h2>
        <p>Halaman ini hanya bisa diakses oleh <strong>Super Admin (APTIKA)</strong>.</p>
        <p><a href="dashboard.php">← Kembali ke Dashboard</a></p>
        </body></html>';
        exit;
    }
}

if(!isset($_SESSION['login'])){

    header("Location: login.php");
    exit;

}

$timeout = 3600;

if(isset($_SESSION['last_activity'])){

    if(time()-$_SESSION['last_activity']>$timeout){

        session_unset();

        session_destroy();

        header("Location: session_expired.php");
        
        exit;

    }

}

$_SESSION['last_activity']=time();