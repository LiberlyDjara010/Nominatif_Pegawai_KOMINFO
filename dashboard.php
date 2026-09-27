<?php
require 'auth.php';
include 'koneksi.php';

// Kalau database ini belum pernah dimigrasi (mis. baru dipasang / masih
// pakai skema tabel pegawai yang lama), kolom seperti jenis_jabatan belum
// ada -- daripada dashboard fatal error mendadak, kasih pesan yang jelas
// dan arahkan ke halaman migrasi.
if (!pegawaiFieldExists($conn, 'jenis_jabatan')) {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
    <title>Perlu Migrasi Database</title></head><body style="font-family:sans-serif;padding:40px;max-width:600px;margin:0 auto;">
    <h2>⚠ Database Belum Dimigrasi</h2>
    <p>Tabel <code>pegawai</code> di database ini belum punya kolom-kolom yang dibutuhkan sistem
    (mis. <code>jenis_jabatan</code>). Ini biasanya terjadi kalau database baru/masih fresh.</p>
    <p>Login sebagai <strong>Super Admin</strong>, lalu buka halaman migrasi ini satu kali untuk melengkapi struktur tabelnya:</p>
    <p><a href="migrasi_lengkap.php" style="display:inline-block;background:#1A5FA8;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;">Jalankan migrasi_lengkap.php →</a></p>
    </body></html>';
    exit;
}

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Ringkasan data kepegawaian per hari ini, ' . date('d F Y');

// STATISTIK

$totalPegawai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM pegawai"))['n'];
$totalPria    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM pegawai WHERE jenis_kelamin='Pria'"))['n'];
$totalWanita  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS n FROM pegawai WHERE jenis_kelamin='Wanita'"))['n'];

$golonganData = [];
$golonganNamaList = [];
$golonganHitung = [];
$qGol = mysqli_query($conn, "SELECT golongan FROM pegawai");
while ($r = mysqli_fetch_assoc($qGol)) {
    $gol = normalisasiGolongan($r['golongan']) ?? '-';
    $golonganHitung[$gol] = ($golonganHitung[$gol] ?? 0) + 1;
}
uksort($golonganHitung, 'strnatcasecmp');
foreach ($golonganHitung as $gol => $n) {
    $golonganData[] = ['golongan' => $gol, 'n' => $n];
}

$qGolNama = mysqli_query($conn, "SELECT nama, golongan, jabatan FROM pegawai ORDER BY nama ASC");
if ($qGolNama) {
    while ($r = mysqli_fetch_assoc($qGolNama)) {
        $gol = normalisasiGolongan($r['golongan']) ?? '(kosong)';
        if (!isset($golonganNamaList[$gol])) $golonganNamaList[$gol] = [];
        $golonganNamaList[$gol][] = ['nama' => $r['nama'], 'jabatan' => $r['jabatan'] ?: '-'];
    }
}
$golonganDetailJson = json_encode($golonganNamaList, JSON_UNESCAPED_UNICODE);

$oapDistribusiData = ['OAP (Papua)' => 0, 'Non-OAP' => 0, '-' => 0];
$oapDistribusiNamaList = ['OAP (Papua)' => [], 'Non-OAP' => [], '-' => []];
$qOapDist = mysqli_query($conn, "SELECT nama, kategori_suku, jabatan FROM pegawai ORDER BY nama ASC");
if ($qOapDist) {
    while ($r = mysqli_fetch_assoc($qOapDist)) {
        $orang = ['nama' => $r['nama'], 'jabatan' => $r['jabatan'] ?: '-'];
        if ($r['kategori_suku'] === 'Papua') {
            $oapDistribusiData['OAP (Papua)']++;
            $oapDistribusiNamaList['OAP (Papua)'][] = $orang;
        } elseif ($r['kategori_suku'] === 'Non-Papua') {
            $oapDistribusiData['Non-OAP']++;
            $oapDistribusiNamaList['Non-OAP'][] = $orang;
        } else {
            $oapDistribusiData['-']++;
            $oapDistribusiNamaList['-'][] = $orang;
        }
    }
}
$oapDistribusiDetailJson = json_encode($oapDistribusiNamaList, JSON_UNESCAPED_UNICODE);

// AGAMMA BREAKDOWN

$agamaData = [];
$qAgama = mysqli_query($conn, "SELECT agama, COUNT(*) AS n FROM pegawai GROUP BY agama ORDER BY n DESC");
while ($r = mysqli_fetch_assoc($qAgama)) $agamaData[] = $r;

$agamaNamaList = [];
$qAgamaNama = mysqli_query($conn, "SELECT nama, agama, jabatan FROM pegawai ORDER BY nama ASC");
if ($qAgamaNama) {
    while ($r = mysqli_fetch_assoc($qAgamaNama)) {
        $ag = $r['agama'] ?: '-';
        if (!isset($agamaNamaList[$ag])) $agamaNamaList[$ag] = [];
        $agamaNamaList[$ag][] = ['nama' => $r['nama'], 'jabatan' => $r['jabatan'] ?: '-'];
    }
}
$agamaDetailJson = json_encode($agamaNamaList, JSON_UNESCAPED_UNICODE);

// ULANG TAHUN BULAN INI & BULAN DEPAN

$ulangTahunBulanIni = [];
$ulangTahunBulanDepan = [];

$qBirth = mysqli_query($conn, "SELECT id, nama, jabatan, tanggal_lahir FROM pegawai WHERE tanggal_lahir IS NOT NULL");
$today = new DateTime();
$thisYear = (int) $today->format('Y');
$todayMonth = (int) $today->format('n');
$todayDay = (int) $today->format('j');
$nextMonthDate = new DateTime('first day of next month');
$nextMonth = (int) $nextMonthDate->format('n');

while ($r = mysqli_fetch_assoc($qBirth)) {
    $lahir = new DateTime($r['tanggal_lahir']);
    $birthMonth = (int) $lahir->format('n');
    $birthDay = (int) $lahir->format('j');
    $nextBirthday = new DateTime(sprintf('%04d-%02d-%02d', $thisYear, $birthMonth, $birthDay));
    if ($nextBirthday < $today) {
        $nextBirthday->modify('+1 year');
    }

    $targetMonth = $nextBirthday->format('Y-m');
    $currentMonthKey = $today->format('Y-m');
    $r['tgl_ulang_tahun'] = $nextBirthday->format('d M Y');

    if ($targetMonth === $currentMonthKey) {
        $r['keterangan'] = ($nextBirthday->diff($today)->days <= 7) ? '1 minggu lagi' : 'Bulan ini';
        $ulangTahunBulanIni[] = $r;
    } elseif ($targetMonth === $nextMonthDate->format('Y-m')) {
        $r['keterangan'] = '1 bulan lagi';
        $ulangTahunBulanDepan[] = $r;
    }
}

$oapList = [];
$oapPendidikan = [];
$oapJabatanList = [];
$oapPriaList = [];
$oapWanitaList = [];

$qOap = mysqli_query($conn, "SELECT * FROM pegawai");
if ($qOap) {
    while ($r = mysqli_fetch_assoc($qOap)) {
        if (!isOap($conn, $r)) {
            continue;
        }

        $oapList[] = $r;
        $nama = $r['nama'] ?? '(tanpa nama)';

        $pend = ringkasPendidikan($r['pendidikan_terakhir'] ?? '');
        if (!isset($oapPendidikan[$pend])) $oapPendidikan[$pend] = [];
        $oapPendidikan[$pend][] = $nama;

        $jk = strtolower(trim((string) ($r['jenis_kelamin'] ?? '')));
        if ($jk === 'pria' || $jk === 'laki-laki' || $jk === 'l') {
            $oapPriaList[] = $nama;
        } elseif ($jk === 'wanita' || $jk === 'perempuan' || $jk === 'p') {
            $oapWanitaList[] = $nama;
        }

        $jabatanText = strtolower(trim((string) ($r['jabatan'] ?? '') . ' ' . ($r['jenis_jabatan'] ?? '')));
        if (strpos($jabatanText, 'eselon iv') !== false || strpos($jabatanText, 'eselon 4') !== false || strpos($jabatanText, 'pengawas') !== false || strpos($jabatanText, 'kepala seksi') !== false) {
            $oapJabatanList[] = ['nama' => $nama, 'jabatan' => $r['jabatan'] ?? '-', 'eselon' => 'IV'];
        } elseif (strpos($jabatanText, 'eselon iii') !== false || strpos($jabatanText, 'eselon 3') !== false || strpos($jabatanText, 'administrator') !== false || strpos($jabatanText, 'kepala bidang') !== false || strpos($jabatanText, 'sekretaris') !== false) {
            $oapJabatanList[] = ['nama' => $nama, 'jabatan' => $r['jabatan'] ?? '-', 'eselon' => 'III'];
        }
    }
}

$oapPria = count($oapPriaList);
$oapWanita = count($oapWanitaList);
$oapEselon = [
    'III' => count(array_filter($oapJabatanList, fn($j) => $j['eselon'] === 'III')),
    'IV'  => count(array_filter($oapJabatanList, fn($j) => $j['eselon'] === 'IV')),
];

$oapDetailJson = json_encode([
    'pria'       => $oapPriaList,
    'wanita'     => $oapWanitaList,
    'pendidikan' => $oapPendidikan,
    'jabatan'    => $oapJabatanList,
], JSON_UNESCAPED_UNICODE);

// USIA DISTRIBUSI

$usiaData = ['< 30 th' => 0, '30–39 th' => 0, '40–49 th' => 0, '50–57 th' => 0, '≥ 58 th' => 0, '-' => 0];
$usiaNamaList = ['< 30 th' => [], '30–39 th' => [], '40–49 th' => [], '50–57 th' => [], '≥ 58 th' => [], '-' => []];
$qUsia = mysqli_query($conn, "SELECT nama, jabatan, tanggal_lahir FROM pegawai");
while ($r = mysqli_fetch_assoc($qUsia)) {
    $orang = ['nama' => $r['nama'], 'jabatan' => $r['jabatan'] ?: '-'];
    if (empty($r['tanggal_lahir'])) {
        $usiaData['-']++;
        $usiaNamaList['-'][] = $orang;
        continue;
    }
    $usia = hitungUsia($r['tanggal_lahir']);
    if ($usia < 30)       { $usiaData['< 30 th']++;   $usiaNamaList['< 30 th'][] = $orang; }
    elseif ($usia < 40)   { $usiaData['30–39 th']++;  $usiaNamaList['30–39 th'][] = $orang; }
    elseif ($usia < 50)   { $usiaData['40–49 th']++;  $usiaNamaList['40–49 th'][] = $orang; }
    elseif ($usia < 58)   { $usiaData['50–57 th']++;  $usiaNamaList['50–57 th'][] = $orang; }
    else                  { $usiaData['≥ 58 th']++;   $usiaNamaList['≥ 58 th'][] = $orang; }
}
$usiaDetailJson = json_encode($usiaNamaList, JSON_UNESCAPED_UNICODE);

$pensiunDekat = [];

$qAll = mysqli_query($conn, "
SELECT
id,
nama,
jabatan,
jenis_jabatan,
golongan,
tanggal_lahir
FROM pegawai
WHERE tanggal_lahir IS NOT NULL
ORDER BY tanggal_lahir ASC
");

while ($r = mysqli_fetch_assoc($qAll)) {

    $sisa = sisaBulanPensiun(
    $r['tanggal_lahir'],
    $r['jenis_jabatan'] ?? 'Pelaksana'
);
// INI ATAS
    if ($sisa <= 12) {
        $r['sisa_bulan'] = $sisa;

        $r['tgl_pensiun'] = tanggalPensiun(
    $r['tanggal_lahir'],
    $r['jenis_jabatan'] ?? 'Pelaksana'
);
        $pensiunDekat[] = $r;
    }
}

$warnaUsia  = ['#2D8CFF','#1A8754','#F4A100','#D93025','#8B5CF6','#94A3B8'];
$warnaSuku  = ['#0C3A6B','#1A5FA8','#2D8CFF','#60A5FA','#1A8754','#2EA87A','#F4A100','#8B5CF6'];
$warnaAgama = ['#2D8CFF','#1A8754','#F4A100','#D93025','#8B5CF6','#0891B2','#DB2777'];

// WARNA-WARNA PER GOLONGAN BERDASARKAN KELAS (I=BIRU, II=HIJAU, III=KUNING, IV=MERAH)

function warnaGolongan(string $gol): string {
    $peta = [
        'I/a'   => '#93C5FD', 'I/b'   => '#60A5FA', 'I/c'   => '#3B82F6', 'I/d'   => '#1D4ED8',
        'II/a'  => '#86EFAC', 'II/b'  => '#4ADE80', 'II/c'  => '#16A34A', 'II/d'  => '#14532D',
        'III/a' => '#FDE68A', 'III/b' => '#FCD34D', 'III/c' => '#D97706', 'III/d' => '#92400E',
        'IV/a'  => '#FCA5A5', 'IV/b'  => '#F87171', 'IV/c'  => '#DC2626', 'IV/d'  => '#991B1B', 'IV/e' => '#7F1D1D',
    ];
    return $peta[trim($gol)] ?? '#94A3B8';
}

include 'layout.php';
?>

<style>

/* CSS PIE CHART */

.dist-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.dist-card {
    background: var(--putih, #fff);
    border: 1px solid var(--border, #E8EDF4);
    border-radius: 12px;
    padding: 20px;
}

.dist-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--teks-gelap, #1A2333);
    margin: 0 0 16px;
}

/* PIE CHART PAKAI CONIC-GRADIENT */

.pie-chart {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    margin: 0 auto 16px;
    position: relative;
}

.pie-hole {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    background: var(--putih, #fff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    color: var(--teks-gelap, #1A2333);
    text-align: center;
    line-height: 1.3;
}

/* LEGEND LIST */

.dist-legend {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.dist-legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    gap: 8px;
}

.dist-legend-kiri {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
}

.dist-legend-kotak {
    width: 10px;
    height: 10px;
    border-radius: 2px;
    flex-shrink: 0;
}

.dist-legend-nama {
    color: var(--teks-gelap, #1A2333);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}
.dist-legend-val {
    color: var(--teks-abu, #6B7A99);
    white-space: nowrap;
    font-size: 11px;
}

/* BAR CHART */

.bar-section{
    background:#fff;
    border:1px solid #E2E8F0;
    border-radius:16px;
    padding:24px;
    margin-bottom:24px;
}

.bar-section h3{
    margin:0 0 20px;
    font-size:18px;
    font-weight:700;
    color:#1E293B;
}

.bar-chart{
    height:380px;
    display:flex;
    align-items:flex-end;
    gap:14px;
    padding-top:20px;
    border-left:2px solid #CBD5E1;
    border-bottom:2px solid #CBD5E1;
    position:relative;
}

.bar-chart::before,
.bar-chart::after{
    content:"";
    position:absolute;
    left:0;
    right:0;
    border-top:1px dashed #E2E8F0;
}

.bar-chart::before{
    top:33%;
}

.bar-chart::after{
    top:66%;
}

.bar-item{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:flex-end;
    align-items:center;
}

.bar-value{
    font-size:13px;
    font-weight:700;
    color:#0F172A;
    margin-bottom:6px;
}

.bar{
    width:100%;
    max-width:70px;
    border-radius:12px 12px 0 0;
    transition:0.3s;
}

.bar:hover{
    opacity:.85;
    transform:translateY(-3px);
}

.bar-label{
    margin-top:10px;
    font-size:12px;
    font-weight:600;
    color:#475569;
}

@media (max-width: 900px) {
    .dist-grid { grid-template-columns: 1fr; }
}

</style>

<!-- METRIC CARDS -->

<div class="metrics-grid">
    <div class="metric-card biru">
        <div class="metric-label">Total Pegawai</div>
        <div class="metric-value"><?= $totalPegawai ?></div>
        <div class="metric-sub">ASN aktif terdaftar</div>
    </div>
    <div class="metric-card hijau">
        <div class="metric-label">Pria</div>
        <div class="metric-value"><?= $totalPria ?></div>
        <div class="metric-sub"><?= $totalPegawai > 0 ? round($totalPria/$totalPegawai*100) : 0 ?>% dari total</div>
    </div>
    <div class="metric-card merah">
        <div class="metric-label">Wanita</div>
        <div class="metric-value"><?= $totalWanita ?></div>
        <div class="metric-sub"><?= $totalPegawai > 0 ? round($totalWanita/$totalPegawai*100) : 0 ?>% dari total</div>
    </div>
    <div class="metric-card oranye">
        <div class="metric-label">Segera Pensiun</div>
        <div class="metric-value"><?= count($pensiunDekat) ?></div>
        <div class="metric-sub">dalam 12 bulan ke depan</div>
    </div>
    <div class="metric-card emas">
        <div class="metric-label">Ragam Suku</div>
        <div class="metric-value"><?= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT suku) AS n FROM pegawai WHERE suku IS NOT NULL AND suku != ''"))['n'] ?></div>
        <div class="metric-sub">suku yang terdaftar</div>
    </div>
</div>

<!-- ALERT PENSIUN -->
 
<div class="alert-grid">
    <div class="alert-box" style="grid-column: 1 / -1;">
        <div class="alert-box-header biru">
            <div>
                <h3>Segera Pensiun</h3>
                <p>Pegawai dengan sisa waktu ≤ 12 bulan</p>
            </div>
        </div>
        <div class="alert-list">
            <?php if (empty($pensiunDekat)): ?>
                <div class="alert-empty">Tidak ada pegawai yang akan pensiun dalam 12 bulan</div>
            <?php else: foreach ($pensiunDekat as $p):
                $lb = labelPensiun($p['sisa_bulan']); ?>
                <div class="alert-item">
                    <div style="flex:1">
                        <div class="nama"><?= e($p['nama']) ?></div>
                        <div class="info"><?= e($p['jabatan']) ?> · Pensiun: <?= $p['tgl_pensiun'] ?></div>
                    </div>
                    <span class="badge <?= $lb['class'] ?>"><?= $lb['label'] ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<div class="alert-grid" style="margin-top:18px;">
    <div class="alert-box">
        <div class="alert-box-header kuning">
            <div>
                <h3>Ulang Tahun Bulan Ini</h3>
                <p>Yang diperingati dalam bulan ini</p>
            </div>
        </div>
        <div class="alert-list">
            <?php if (empty($ulangTahunBulanIni)): ?>
                <div class="alert-empty">Tidak ada yang ulang tahun bulan ini</div>
            <?php else: foreach ($ulangTahunBulanIni as $p): ?>
                <div class="alert-item">
                    <div style="flex:1">
                        <div class="nama"><?= e($p['nama']) ?></div>
                        <div class="info"><?= e($p['jabatan']) ?> · <?= e($p['keterangan']) ?> · Tgl: <?= e($p['tgl_ulang_tahun']) ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="alert-box">
        <div class="alert-box-header kuning">
            <div>
                <h3>Ulang Tahun Bulan Depan</h3>
                <p>Yang akan berulang tahun bulan depan</p>
            </div>
        </div>
        <div class="alert-list">
            <?php if (empty($ulangTahunBulanDepan)): ?>
                <div class="alert-empty">Tidak ada yang ulang tahun bulan depan</div>
            <?php else: foreach ($ulangTahunBulanDepan as $p): ?>
                <div class="alert-item">
                    <div style="flex:1">
                        <div class="nama"><?= e($p['nama']) ?></div>
                        <div class="info"><?= e($p['jabatan']) ?> · <?= e($p['keterangan']) ?> · Tgl: <?= e($p['tgl_ulang_tahun']) ?></div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <div class="alert-box">
        <div class="alert-box-header merah">
            <div>
                <h3>Statistik OAP</h3>
                <p>Orang Asli Papua yang terdata</p>
            </div>
        </div>
        <div class="alert-list" style="padding:16px;">
            <?php if (empty($oapList)): ?>
                <div class="alert-empty">Belum ada data OAP terdaftar</div>
            <?php else: ?>

                <div style="font-size:13px;color:var(--teks-abu);margin-bottom:12px;">
                    <strong style="color:var(--teks-gelap);"> Total OAP: <?= count($oapList) ?> orang</strong>
                </div>

                <div style="font-size:12px;font-weight:700;color:var(--teks-abu);text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">
                    Jenis Kelamin
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                    <button type="button" class="oap-chip" onclick="bukaOapModal('pria')">
                        Pria: <strong><?= $oapPria ?></strong>
                    </button>
                    <button type="button" class="oap-chip" onclick="bukaOapModal('wanita')">
                        Wanita: <strong><?= $oapWanita ?></strong>
                    </button>
                </div>

                <div style="font-size:12px;font-weight:700;color:var(--teks-abu);text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">
                    Pendidikan Terakhir
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
                    <?php if (empty($oapPendidikan)): ?>
                        <span style="font-size:12.5px;color:var(--teks-abu);">Belum ada data.</span>
                    <?php else: foreach ($oapPendidikan as $level => $namaList): ?>
                    <button type="button" class="oap-chip" onclick="bukaOapModal('pendidikan', '<?= e($level) ?>')">
                        <?= e($level) ?>: <strong><?= count($namaList) ?></strong>
                    </button>
                    <?php endforeach; endif; ?>
                </div>

                <div style="font-size:12px;font-weight:700;color:var(--teks-abu);text-transform:uppercase;letter-spacing:0.4px;margin-bottom:6px;">
                    Menduduki Jabatan Struktural
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <button type="button" class="oap-chip" onclick="bukaOapModal('jabatan')">
                        Total: <strong><?= count($oapJabatanList) ?></strong> orang
                        (Eselon III: <strong><?= $oapEselon['III'] ?></strong>, Eselon IV: <strong><?= $oapEselon['IV'] ?></strong>)
                    </button>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.oap-chip {
    background: #F5F7FA;
    border: 1px solid #DDE2EC;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 12.5px;
    color: var(--teks-gelap);
    cursor: pointer;
    transition: background 0.15s;
}
.oap-chip:hover {
    background: #E8EDF5;
    border-color: #C5CEDB;
}
.oap-chip strong {
    color: var(--biru);
}
</style>


<?php
function conicGradient(array $data, array $warna): string {
    $total = array_sum($data);
    if ($total == 0) return 'conic-gradient(#ccc 0% 100%)';
    $hasil = [];
    $akumulasi = 0;
    $i = 0;
    foreach ($data as $val) {
        $pct = $val / $total * 100;
        $warna_i = $warna[$i % count($warna)];
        $hasil[] = "{$warna_i} {$akumulasi}% " . ($akumulasi + $pct) . "%";
        $akumulasi += $pct;
        $i++;
    }
    return 'conic-gradient(' . implode(', ', $hasil) . ')';
}
?>

<div class="dist-grid">

    <!-- PIE CHART USIA -->

    <div class="dist-card">
        <h3>Distribusi Usia Pegawai</h3>
        <p style="font-size:11.5px;color:var(--teks-abu);margin:-6px 0 10px;">Klik salah satu baris untuk lihat daftar nama.</p>
        <?php
        $nilaiUsia = array_values($usiaData);
        $totalUsia = array_sum($nilaiUsia);
        $gradUsia  = conicGradient($nilaiUsia, $warnaUsia);
        ?>
        <div class="pie-chart" style="background: <?= $gradUsia ?>;">
            <div class="pie-hole"><?= $totalUsia ?><br>orang</div>
        </div>
        <div class="dist-legend">
            <?php $i = 0; foreach ($usiaData as $label => $jumlah):
                $pct = $totalUsia > 0 ? round($jumlah / $totalUsia * 100) : 0;
                $w   = $warnaUsia[$i % count($warnaUsia)]; $i++;
            ?>
            <div class="dist-legend-item" style="cursor:pointer;" onclick="bukaUsiaModal('<?= e($label) ?>')" title="Klik untuk lihat daftar nama">
                <div class="dist-legend-kiri">
                    <span class="dist-legend-kotak" style="background:<?= $w ?>"></span>
                    <span class="dist-legend-nama"><?= htmlspecialchars($label) ?></span>
                </div>
                <span class="dist-legend-val"><?= $jumlah ?> org &nbsp;·&nbsp; <?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PIE CHART OAP / NON-OAP -->

    <div class="dist-card">
        <h3>Distribusi OAP / Non-OAP</h3>
        <p style="font-size:11.5px;color:var(--teks-abu);margin:-6px 0 10px;">Klik salah satu baris untuk lihat daftar nama.</p>
        <?php
        $nilaiOapDist = array_values($oapDistribusiData);
        $totalOapDist = array_sum($nilaiOapDist);
        $warnaOapDist = ['#3B82F6', '#F59E0B', '#94A3B8'];
        $gradOapDist  = conicGradient($nilaiOapDist, $warnaOapDist);
        ?>
        <div class="pie-chart" style="background: <?= $gradOapDist ?>;">
            <div class="pie-hole"><?= $totalOapDist ?><br>orang</div>
        </div>
        <div class="dist-legend">
            <?php $i = 0; foreach ($oapDistribusiData as $label => $jumlah):
                $pct = $totalOapDist > 0 ? round($jumlah / $totalOapDist * 100) : 0;
                $w   = $warnaOapDist[$i % count($warnaOapDist)]; $i++;
            ?>
            <div class="dist-legend-item" style="cursor:pointer;" onclick="bukaDistribusiOapModal('<?= e($label) ?>')" title="Klik untuk lihat daftar nama">
                <div class="dist-legend-kiri">
                    <span class="dist-legend-kotak" style="background:<?= $w ?>"></span>
                    <span class="dist-legend-nama"><?= htmlspecialchars($label) ?></span>
                </div>
                <span class="dist-legend-val"><?= $jumlah ?> org &nbsp;·&nbsp; <?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PIE CHART AGAMA -->

    <div class="dist-card">
        <h3>Distribusi Agama Pegawai</h3>
        <p style="font-size:11.5px;color:var(--teks-abu);margin:-6px 0 10px;">Klik salah satu baris untuk lihat daftar nama.</p>
        <?php
        $nilaiAgama = array_column($agamaData, 'n');
        $totalAgama = array_sum($nilaiAgama);
        $gradAgama  = conicGradient($nilaiAgama, $warnaAgama);
        ?>
        <div class="pie-chart" style="background: <?= $gradAgama ?>;">
            <div class="pie-hole"><?= $totalAgama ?><br>orang</div>
        </div>
        <div class="dist-legend">
            <?php $i = 0; foreach ($agamaData as $row):
                $pct = $totalAgama > 0 ? round($row['n'] / $totalAgama * 100) : 0;
                $w   = $warnaAgama[$i % count($warnaAgama)]; $i++;
                $agamaLabel = $row['agama'] ?: '-';
            ?>
            <div class="dist-legend-item" style="cursor:pointer;" onclick="bukaAgamaModal('<?= e($agamaLabel) ?>')" title="Klik untuk lihat daftar nama">
                <div class="dist-legend-kiri">
                    <span class="dist-legend-kotak" style="background:<?= $w ?>"></span>
                    <span class="dist-legend-nama"><?= e($row['agama'] ?: '-') ?></span>
                </div>
                <span class="dist-legend-val"><?= $row['n'] ?> org &nbsp;·&nbsp; <?= $pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php
$maxGol = 1;

foreach($golonganData as $g){
    if($g['n'] > $maxGol){
        $maxGol = $g['n'];
    }
}
?>

<div class="bar-section">

    <h3>Rekap Pegawai per Golongan</h3>
    <p style="font-size:12px;color:var(--teks-abu);margin:-8px 0 12px;">Klik salah satu batang untuk lihat daftar nama pegawainya.</p>

    <div class="bar-chart">

        <?php foreach($golonganData as $g):

            $tinggi = ($g['n'] / $maxGol) * 300;
            $warna  = warnaGolongan($g['golongan']);

        ?>

        <div class="bar-item" style="cursor:pointer;" onclick="bukaGolonganModal('<?= e($g['golongan']) ?: '(kosong)' ?>')" title="Klik untuk lihat daftar nama">

            <div class="bar-value">
                <?= $g['n'] ?>
            </div>

            <div
                class="bar"
                style="
                    height: <?= $tinggi ?>px;
                    background: <?= $warna ?>;
                ">
            </div>

            <div class="bar-label">
                <?= e($g['golongan']) ?>
            </div>

        </div>

        <?php endforeach; ?>

    </div>

</div>

<div id="detailModal" onclick="tutupDetailModal(event)" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);z-index:200;align-items:center;justify-content:center;padding:20px;">
    <div onclick="event.stopPropagation()" style="background:#fff;border-radius:14px;max-width:480px;width:100%;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 20px 50px rgba(0,0,0,0.25);">
        <div style="padding:16px 20px;border-bottom:1px solid #EEF2F7;display:flex;align-items:center;justify-content:space-between;">
            <h4 id="detailModalTitle" style="margin:0;font-size:15px;color:var(--teks-gelap);"></h4>
            <button type="button" onclick="tutupDetailModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--teks-abu);line-height:1;">&times;</button>
        </div>
        <div id="detailModalBody" style="padding:8px 20px;overflow-y:auto;"></div>
    </div>
</div>

<script>
var oapDetailData = <?= $oapDetailJson ?: '{}' ?>;
var golonganDetailData = <?= $golonganDetailJson ?: '{}' ?>;
var usiaDetailData = <?= $usiaDetailJson ?: '{}' ?>;
var oapDistribusiDetailData = <?= $oapDistribusiDetailJson ?: '{}' ?>;
var agamaDetailData = <?= $agamaDetailJson ?: '{}' ?>;

function escapeHtmlOap(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

function tampilkanDetailModal(title, list, tampilkanSubInfo) {
    document.getElementById('detailModalTitle').textContent = title + ' (' + list.length + ' orang)';

    var body = document.getElementById('detailModalBody');
    if (list.length === 0) {
        body.innerHTML = '<div style="padding:24px 4px;text-align:center;color:#8a94a6;font-size:13px;">Tidak ada data.</div>';
    } else {
        var html = '<ul style="margin:0;padding:0 0 12px;list-style:none;">';
        list.forEach(function (item, idx) {
            var no = idx + 1;
            if (tampilkanSubInfo && typeof item === 'object') {
                html += '<li style="padding:10px 4px;border-bottom:1px solid #F1F4F8;">'
                    + '<div style="font-size:13.5px;font-weight:600;color:#1e2d45;">' + no + '. ' + escapeHtmlOap(item.nama) + '</div>'
                    + '<div style="font-size:12px;color:#8a94a6;margin-top:2px;">' + escapeHtmlOap(item.jabatan || '') + (item.eselon ? ' &middot; Eselon ' + escapeHtmlOap(item.eselon) : '') + '</div>'
                    + '</li>';
            } else {
                var teks = typeof item === 'object' ? item.nama : item;
                html += '<li style="padding:9px 4px;border-bottom:1px solid #F1F4F8;font-size:13.5px;color:#1e2d45;">' + no + '. ' + escapeHtmlOap(teks) + '</li>';
            }
        });
        html += '</ul>';
        body.innerHTML = html;
    }

    document.getElementById('detailModal').style.display = 'flex';
}

function tutupDetailModal(e) {
    if (e && e.target !== document.getElementById('detailModal')) return;
    document.getElementById('detailModal').style.display = 'none';
}

// STATISTIK OAP
function bukaOapModal(kategori, sub) {
    var title = '';
    var list = [];
    var tampilkanSubInfo = false;

    if (kategori === 'pria') {
        title = 'OAP — Pria';
        list = oapDetailData.pria || [];
    } else if (kategori === 'wanita') {
        title = 'OAP — Wanita';
        list = oapDetailData.wanita || [];
    } else if (kategori === 'pendidikan') {
        title = 'OAP — Pendidikan ' + sub;
        list = (oapDetailData.pendidikan && oapDetailData.pendidikan[sub]) || [];
    } else if (kategori === 'jabatan') {
        title = 'OAP — Menduduki Jabatan Struktural';
        list = oapDetailData.jabatan || [];
        tampilkanSubInfo = true;
    }

    tampilkanDetailModal(title, list, tampilkanSubInfo);
}

// REKAP PER GOLONGAN
function bukaGolonganModal(golongan) {
    var list = golonganDetailData[golongan] || [];
    tampilkanDetailModal('Golongan ' + golongan, list, true);
}

// DISTRIBUSI USIA
function bukaUsiaModal(label) {
    var list = usiaDetailData[label] || [];
    tampilkanDetailModal('Usia ' + label, list, true);
}

// DISTRIBUSI OAP / NON-OAP
function bukaDistribusiOapModal(label) {
    var list = oapDistribusiDetailData[label] || [];
    tampilkanDetailModal(label, list, true);
}

// DISTRIBUSI AGAMA
function bukaAgamaModal(agama) {
    var list = agamaDetailData[agama] || [];
    tampilkanDetailModal('Agama ' + agama, list, true);
}
</script>

<?php include 'layout_end.php'; ?>