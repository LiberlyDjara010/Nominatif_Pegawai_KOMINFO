<?php
require 'auth.php';
include 'koneksi.php';

requireSuperAdmin();
pastikanTabelLoginLog($conn);

// Lengkapi lokasi yang masih kosong (khusus IP publik) -- dilakukan di sini
// (saat halaman dibuka), bukan saat login, supaya proses login tidak pernah
// diperlambat oleh pengecekan lokasi ke internet.
lengkapiLokasiKosong($conn);

$pageTitle    = 'Notifikasi Login';
$pageSubtitle = 'Riwayat siapa saja yang login (atau mencoba login) ke sistem';

$filterStatus = $_GET['status'] ?? '';
$where = '';
if ($filterStatus === 'berhasil' || $filterStatus === 'gagal') {
    $filterStatusSql = mysqli_real_escape_string($conn, $filterStatus);
    $where = "WHERE status = '$filterStatusSql'";
}

$logs = [];
$res = mysqli_query($conn, "SELECT * FROM login_log $where ORDER BY waktu DESC LIMIT 200");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) $logs[] = $row;
}

// Setelah dibuka, tandai semua sebagai sudah dibaca supaya badge notifikasi di menu hilang.
mysqli_query($conn, "UPDATE login_log SET dibaca_superadmin = 1 WHERE dibaca_superadmin = 0");

include 'layout.php';
?>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <h3>Riwayat Login (200 terbaru)</h3>
        <div style="display:flex;gap:8px;">
            <a href="notifikasi_login.php" class="btn btn-sm <?= $filterStatus === '' ? 'btn-primary' : 'btn-ghost' ?>">Semua</a>
            <a href="notifikasi_login.php?status=berhasil" class="btn btn-sm <?= $filterStatus === 'berhasil' ? 'btn-primary' : 'btn-ghost' ?>">Berhasil</a>
            <a href="notifikasi_login.php?status=gagal" class="btn btn-sm <?= $filterStatus === 'gagal' ? 'btn-primary' : 'btn-ghost' ?>">Gagal</a>
        </div>
    </div>

    <div class="card-body" style="padding:0;">
        <?php if (empty($logs)): ?>
            <div class="alert-empty" style="margin:20px;">Belum ada riwayat login.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="data-table" style="width:100%;">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Nama yang Login</th>
                    <th>Akun</th>
                    <th>Status</th>
                    <th>Alamat IP</th>
                    <th>Perkiraan Lokasi</th>
                    <th>Perangkat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:12.5px;color:var(--teks-abu);">
                        <?= e(date('d/m/Y H:i', strtotime($log['waktu']))) ?>
                    </td>
                    <td><strong><?= e($log['nama_pegawai']) ?></strong></td>
                    <td>
                        <?= e($log['username']) ?>
                        <?php if (!empty($log['role'])): ?>
                        <div style="font-size:11px;color:var(--teks-abu);"><?= e($log['role']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['status'] === 'berhasil'): ?>
                            <span class="badge badge-hijau">✔ Berhasil</span>
                        <?php else: ?>
                            <span class="badge badge-merah">✕ Gagal</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12.5px;color:var(--teks-abu);"><?= e($log['ip_address'] ?: '-') ?></td>
                    <td style="font-size:12.5px;"><?= e($log['lokasi'] ?: '-') ?></td>
                    <td style="font-size:12.5px;color:var(--teks-abu);"><?= e($log['device_info'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<p style="font-size:11.5px;color:var(--teks-abu);margin-top:14px;">
    Catatan: login selalu tercatat langsung tanpa tergantung jaringan. Untuk login dari jaringan kantor (LAN/WiFi lokal)
    lokasinya otomatis tertulis "Jaringan Lokal / Kantor (LAN)". Untuk login dari IP publik (internet), lokasinya
    diperiksa belakangan saat halaman ini dibuka -- kalau kolom Lokasi masih kosong, coba buka ulang halaman ini
    beberapa saat lagi (perlu koneksi internet dari server untuk memeriksanya).
</p>

<?php include 'layout_end.php'; ?>
