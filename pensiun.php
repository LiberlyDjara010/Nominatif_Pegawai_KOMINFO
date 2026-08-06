<?php
require 'auth.php';
include 'koneksi.php';

$pageTitle    = 'Monitoring Pensiun';
$pageSubtitle = 'Daftar pegawai berdasarkan sisa masa kerja menuju pensiun';

$filter = $_GET['filter'] ?? 'semua';

$qAll = mysqli_query($conn, "SELECT * FROM pegawai WHERE tanggal_lahir IS NOT NULL ORDER BY tanggal_lahir ASC");

$groups = [
    'sudah'   => [],
    'lte6'    => [],   // ≤ 6 bulan
    'lte12'   => [],   // ≤ 12 bulan
    'lte24'   => [],   // ≤ 2 tahun
    'aman'    => [],
];

while ($row = mysqli_fetch_assoc($qAll)) {
    $jenisJabatan = !empty($row['jenis_jabatan']) ? $row['jenis_jabatan'] : tentukanJenisJabatan($row['jabatan'] ?? '');

    $sisa = sisaBulanPensiun($row['tanggal_lahir'], $jenisJabatan);
    $row['sisa_bulan'] = $sisa;
    $row['usia']       = hitungUsia($row['tanggal_lahir']);
    $row['tgl_pensiun']= tanggalPensiun($row['tanggal_lahir'], $jenisJabatan);
    $row['bup']        = usiaPensiun($jenisJabatan);

    if ($sisa === 0)        $groups['sudah'][]  = $row;
    elseif ($sisa <= 6)     $groups['lte6'][]   = $row;
    elseif ($sisa <= 12)    $groups['lte12'][]  = $row;
    elseif ($sisa <= 24)    $groups['lte24'][]  = $row;
    else                    $groups['aman'][]   = $row;
}

include 'layout.php';
?>

<div style=
        "background: #d2e6ff;
        border: 1px solid #6a97cb;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 16px;
        color: #2d50b3;
        font-size: 13px;">
    <p>
        Batas usia pensiun PNS berdasarkan PP No. 11 Tahun 2017 Pasal 239:
    </p>

    <p>
        <strong>58 tahun</strong> bagi pejabat administrasi, pejabat fungsional ahli muda, 
        pejabat fungsional ahli pertama, dan pejabat fungsional keterampilan; 
    </p>

    <p>
        <strong>60 tahun</strong> bagi pejabat pimpinan tinggi dan pejabat fungsional madya; 
    </p>

    <p>
       <strong>65 tahun</strong> bagi PNS yang memangku pejabat fungsional ahli utama.
    </p>
</div>

<!-- RINGKASAN -->
 
<div class="metrics-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="metric-card merah">
        <div class="metric-label">Sudah Pensiun</div>
        <div class="metric-value"><?= count($groups['sudah']) ?></div>
    </div>
    <div class="metric-card merah">
        <div class="metric-label">≤ 6 bulan</div>
        <div class="metric-value"><?= count($groups['lte6']) ?></div>
    </div>
    <div class="metric-card oranye">
        <div class="metric-label">≤ 1 tahun</div>
        <div class="metric-value"><?= count($groups['lte12']) ?></div>
    </div>
    <div class="metric-card kuning">
        <div class="metric-label">≤ 2 tahun</div>
        <div class="metric-value"><?= count($groups['lte24']) ?></div>
    </div>
    <div class="metric-card hijau">
        <div class="metric-label">Lebih Dari 2 Tahun</div>
        <div class="metric-value"><?= count($groups['aman']) ?></div>
    </div>
</div>

<?php

// TAMPILKAN SEMUA GRUP

$sectionConfig = [
    'sudah' => ['title' => 'Sudah Memasuki Usia Pensiun', 'class' => 'badge-merah'],
    'lte6'  => ['title' => 'Pensiun dalam 6 Bulan ke Depan', 'class' => 'badge-merah'],
    'lte12' => ['title' => 'Pensiun dalam 1 Tahun ke Depan', 'class' => 'badge-oranye'],
    'lte24' => ['title' => 'Pensiun dalam 2 Tahun ke Depan', 'class' => 'badge-kuning'],
    'aman'  => ['title' => 'Lebih dari 2 Tahun', 'class' => 'badge-hijau'],
];

foreach ($sectionConfig as $key => $cfg):
    if (empty($groups[$key])) continue;
?>
<div class="card">
    <div class="card-header">
        <h3><?= $cfg['title'] ?></h3>
        <span style="font-size:12px;color:var(--teks-abu);"><?= count($groups[$key]) ?> pegawai</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama / Jabatan</th>
                    <th>Golongan</th>
                    <th>Tgl Lahir</th>
                    <th>Usia Sekarang</th>
                    <th>BUP</th>
                    <th>Tgl Pensiun</th>
                    <th>Sisa Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach ($groups[$key] as $r): ?>
                <tr>
                    <td style="color:var(--teks-abu);font-size:12px;"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight:600;"><?= e($r['nama']) ?></div>
                        <div style="font-size:11px;color:var(--teks-abu);"><?= e($r['jabatan'] ?: '-') ?></div>
                    </td>
                    <td><strong><?= e($r['golongan'] ?: '-') ?></strong></td>
                    <td><?= date('d M Y', strtotime($r['tanggal_lahir'])) ?></td>
                    <td><?= $r['usia'] ?> tahun</td>
                    <td><span style="color:var(--teks-abu);">≤ <?= $r['bup'] ?> thn</span></td>
                    <td><strong><?= $r['tgl_pensiun'] ?></strong></td>
                    <td>
                        <?php if ($r['sisa_bulan'] === 0): ?>
                            <span style="color:var(--merah);font-weight:600;">Sudah waktunya</span>
                        <?php else: ?>
                            <?= $r['sisa_bulan'] >= 12 ? floor($r['sisa_bulan']/12) . ' thn ' . ($r['sisa_bulan']%12) . ' bln' : $r['sisa_bulan'] . ' bulan' ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $cfg['class'] ?>"><?= labelPensiun($r['sisa_bulan'])['label'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php include 'layout_end.php'; ?>