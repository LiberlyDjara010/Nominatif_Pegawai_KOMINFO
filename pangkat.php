<?php
require 'auth.php';
include 'koneksi.php';

$pageTitle    = 'Kenaikan Pangkat';
$pageSubtitle = 'Monitoring jadwal kenaikan pangkat reguler pegawai';

$qAll = mysqli_query($conn, "SELECT * FROM pegawai WHERE tanggal_pangkat_terakhir IS NOT NULL ORDER BY tanggal_pangkat_terakhir ASC");

$groups = ['lte3' => [], 'lte6' => [], 'lte12' => [], 'lte24' => [], 'aman' => []];

while ($row = mysqli_fetch_assoc($qAll)) {
    $jenisJabatan = !empty($row['jenis_jabatan']) ? $row['jenis_jabatan'] : tentukanJenisJabatan($row['jabatan'] ?? '');

    $sisa = sisaBulanNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null, $jenisJabatan);
    $row['sisa_bulan'] = $sisa;
    $row['tgl_naik']   = tanggalNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null, $jenisJabatan);
    $row['syarat']     = syaratNaikPangkat($conn, $row);

    if (!empty($row['tanggal_lahir'])) {
        $pensiunDate = tanggalPensiunDate($row['tanggal_lahir'], $jenisJabatan);
        $naikDate = new DateTime($row['tgl_naik'] === '-' ? 'now' : $row['tgl_naik']);

        if ($pensiunDate <= new DateTime()) {
            continue;
        }

        if ($pensiunDate <= $naikDate) {
            continue; 
        }
    }

    if ($sisa === 0)        $groups['lte3'][]  = $row;
    elseif ($sisa <= 3)     $groups['lte3'][]  = $row;
    elseif ($sisa <= 6)     $groups['lte6'][]  = $row;
    elseif ($sisa <= 12)    $groups['lte12'][] = $row;
    elseif ($sisa <= 24)    $groups['lte24'][] = $row;
    else                    $groups['aman'][]  = $row;
}

// HAPUS DUPLIKAT KARENA LOGIKA DI ATAS

$groups['lte3'] = array_unique($groups['lte3'], SORT_REGULAR);

include 'layout.php';
?>

<div style=
        "background: #d2e6ff;
        border: 1px solid #6a97cb;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 16px;
        color: #2d50b3;
        font-size: 14px;">
    Kenaikan pangkat reguler PNS diberikan <strong>setingkat lebih tinggi</strong>
    sekurang-kurangnya <strong>telah 4 (empat) tahun dalam pangkat terakhir</strong> sesuai <strong>PP No. 99 Tahun 2000</strong>
    untuk kenaikan pangkat pilihan karena jabatan struktural, syaratnya minimal 1 tahun dalam pangkat
    terakhir dan 1 tahun menduduki jabatan struktural (PP No. 11 Tahun 2017 &amp; KepKa BKN No. 12/2002).
    <br><br>
    <strong>Berkas yang wajib dilengkapi:</strong>
    <?= e(implode(', ', dokumenKenaikanPangkat())) ?>.
    <br><br>
    Klik badge <strong>Kelengkapan Berkas</strong> pada setiap baris untuk mencentang dokumen yang sudah dilengkapi.
    Status akan otomatis berubah menjadi <strong>✔ Lengkap</strong> begitu semua dokumen dicentang.
</div>

<!-- METRIC CARD -->

<div class="metrics-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="metric-card merah">
        <div class="metric-label">≤ 3 bulan</div>
        <div class="metric-value"><?= count($groups['lte3']) ?></div>
    </div>
    <div class="metric-card oranye">
        <div class="metric-label">≤ 6 bulan</div>
        <div class="metric-value"><?= count($groups['lte6']) ?></div>
    </div>
    <div class="metric-card kuning">
        <div class="metric-label">≤ 1 tahun</div>
        <div class="metric-value"><?= count($groups['lte12']) ?></div>
    </div>
    <div class="metric-card biru">
        <div class="metric-label">≤ 2 tahun</div>
        <div class="metric-value"><?= count($groups['lte24']) ?></div>
    </div>
    <div class="metric-card hijau">
        <div class="metric-label">Masih aman</div>
        <div class="metric-value"><?= count($groups['aman']) ?></div>
    </div>
</div>

<?php
$sectionConfig = [
    'lte3'  => ['title' => 'Segera Naik Pangkat (≤ 3 bulan)', 'class' => 'badge-merah'],
    'lte6'  => ['title' => 'Naik Pangkat dalam 6 Bulan', 'class' => 'badge-oranye'],
    'lte12' => ['title' => 'Naik Pangkat dalam 1 Tahun', 'class' => 'badge-kuning'],
    'lte24' => ['title' => 'Naik Pangkat dalam 2 Tahun', 'class' => 'badge-biru'],
    'aman'  => ['title' => 'Lebih dari 2 Tahun', 'class' => 'badge-hijau'],
];

// URUTAN GOLONGAN UNTUK NAIK KE BERIKUTNYA

$urutan = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'];

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
                    <th>Gol. Sekarang</th>
                    <th>Gol. Berikutnya</th>
                    <th>Pangkat Terakhir</th>
                    <th>Tgl Naik Pangkat</th>
                    <th>Sisa Waktu</th>
                    <th>Status</th>
                    <th>Kelengkapan Berkas</th>
                </tr>
            </thead>
            <tbody>
            <?php $no = 1; foreach ($groups[$key] as $r):
                $golSekarang = $r['golongan'] ?: '-';
                $idxSekarang = array_search($golSekarang, $urutan);
                $golBerikutnya = ($idxSekarang !== false && $idxSekarang < count($urutan)-1)
                    ? $urutan[$idxSekarang + 1] : 'Puncak';
            ?>
                <tr>
                    <td style="color:var(--teks-abu);font-size:12px;"><?= $no++ ?></td>
                    <td>
                        <div style="font-weight:600;"><?= e($r['nama']) ?></div>
                        <div style="font-size:11px;color:var(--teks-abu);"><?= e($r['jabatan'] ?: '-') ?></div>
                    </td>
                    <td>
                        <strong><?= e($golSekarang) ?></strong>
                    </td>
                    <td>
                        <?php if ($golBerikutnya === 'Puncak'): ?>
                            <span class="badge badge-biru">Puncak Golongan</span>
                        <?php else: ?>
                            <strong style="color:var(--hijau);"><?= e($golBerikutnya) ?></strong>
                        <?php endif; ?>
                    </td>

                    <td><?= $r['tanggal_pangkat_terakhir'] ? date('d M Y', strtotime($r['tanggal_pangkat_terakhir'])) : '-' ?></td>
                    <td><strong><?= $r['tgl_naik'] ?></strong></td>
                    <td>
                        <?php if ($r['sisa_bulan'] === 0): ?>
                            <span style="color:var(--biru);font-weight:600;">Waktunya naik</span>
                        <?php else: ?>
                            <?= $r['sisa_bulan'] >= 12 ? floor($r['sisa_bulan']/12) . ' thn ' . ($r['sisa_bulan']%12) . ' bln' : $r['sisa_bulan'] . ' bulan' ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $cfg['class'] ?>"><?= labelPangkat($r['sisa_bulan'])['label'] ?></span></td>
                    <td>
                        <?php
                        $checklistStatus = getChecklistPegawai($conn, (int) $r['id']);
                        $checklistLengkap = isChecklistLengkap($checklistStatus);
                        $jumlahCentang = count(array_filter($checklistStatus));
                        $jumlahTotal = count($checklistStatus);
                        ?>
                        <?php if ($checklistLengkap): ?>
                            <a href="checklist_pangkat.php?id=<?= (int) $r['id'] ?>" class="badge badge-hijau" style="text-decoration:none;">✔ Lengkap</a>
                        <?php else: ?>
                            <a href="checklist_pangkat.php?id=<?= (int) $r['id'] ?>" class="badge badge-merah" style="text-decoration:none;">⚠ Belum Lengkap (<?= $jumlahCentang ?>/<?= $jumlahTotal ?>)</a>
                            <?php if (!empty($r['syarat']['catatan'])): ?>
                            <div style="font-size:10.5px;color:#B83A3A;margin-top:3px;max-width:180px;">
                                <?= e(implode('; ', $r['syarat']['catatan'])) ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php include 'layout_end.php'; ?>