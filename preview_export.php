<?php
require 'auth.php';
include 'koneksi.php';

$pageTitle    = 'Preview Export Excel';
$pageSubtitle = 'Tampilan data sebelum diunduh';

$query = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama ASC");

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

$rows       = [];
$totalLaki  = 0;
$totalWanita = 0;

while ($r = mysqli_fetch_assoc($query)) {
    $rows[] = $r;
    $jk = strtolower(trim($r['jenis_kelamin'] ?? ''));
    if ($jk === 'pria' || $jk === 'l' || $jk === 'laki-laki' || $jk === 'laki laki') $totalLaki++;
    if ($jk === 'wanita' || $jk === 'p' || $jk === 'perempuan' || $jk ===  'perempuan') $totalWanita++;
}

include 'layout.php';
?>

<style>
/* EXCEL-LIKE STYLES */
.xl-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--putih);
    border: 1px solid var(--abu-border);
    border-radius: 10px 10px 0 0;
    flex-wrap: wrap;
}
.xl-toolbar-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--teks-gelap);
    margin-right: 8px;
}
.xl-badge {
    font-size: 11px;
    color: var(--teks-abu);
    background: var(--abu-bg);
    border: 1px solid var(--abu-border);
    border-radius: 4px;
    padding: 3px 10px;
}
.xl-badge.laki  { color: #1A5FA8; }
.xl-badge.wanita { color: #B83A3A; }

.xl-sheet-wrap {
    overflow: auto;
    border: 1px solid #C0C0C0;
    border-top: none;
    border-radius: 0 0 10px 10px;
    max-height: 70vh;
    background: #fff;
}
.xl-table {
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
    font-family: Calibri, 'Segoe UI', Arial, sans-serif;
    min-width: 100%;
    white-space: nowrap;
}

/* Row numbers and column headers (like Excel) */
.xl-rh, .xl-ch {
    background: #F2F2F2;
    border: 1px solid #D0D0D0;
    color: #666;
    font-size: 11px;
    text-align: center;
    padding: 2px 4px;
    user-select: none;
    white-space: nowrap;
}
.xl-ch {
    position: sticky;
    top: 0;
    z-index: 10;
    height: 18px;
}
.xl-rh {
    position: sticky;
    left: 0;
    z-index: 5;
    width: 36px;
    min-width: 36px;
}
.xl-ch.corner {
    left: 0;
    z-index: 11;
}

/* Title row */
.xl-title td {
    background: #1F497D;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    padding: 6px 8px;
    border: 1px solid #1A3D6B;
    letter-spacing: 0.2px;
    white-space: normal;
}

/* Column header rows */
.xl-header td {
    background: #D9E1F2;
    color: #1F3864;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    padding: 4px 6px;
    border: 1px solid #AABACF;
    vertical-align: middle;
    white-space: normal;
}
.xl-subheader td {
    background: #BDD7EE;
    color: #1F3864;
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    padding: 3px 6px;
    border: 1px solid #AABACF;
    white-space: normal;
}

/* Data rows */
.xl-data-even td { background: #ffffff; border: 1px solid #D9D9D9; padding: 3px 7px; vertical-align: middle; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.xl-data-odd  td { background: #F8FAFE; border: 1px solid #D9D9D9; padding: 3px 7px; vertical-align: middle; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.xl-data-even:hover td,
.xl-data-odd:hover td { background: #EBF0FB; }

/* Specific column styles */
.xl-no    { text-align: center; color: #666; font-size: 11px; }
.xl-nama  { font-weight: 500; }
.xl-nip   { font-family: 'Courier New', monospace; font-size: 11px; color: #444; }
.xl-jk-l  { text-align: center; font-weight: 700; color: #1A5FA8; }
.xl-jk-p  { text-align: center; font-weight: 700; color: #BF3A3A; }
.xl-gol   { text-align: center; font-weight: 600; color: #155c37; }
.xl-status { text-align: center; }
.xl-tgl   { text-align: center; font-size: 11px; }
.pns-pill {
    display: inline-block;
    background: #E8F5E9;
    color: #1B5E20;
    border: 0.5px solid #81C784;
    border-radius: 2px;
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 600;
}
.cpns-pill {
    display: inline-block;
    background: #FFF8E1;
    color: #6D4C00;
    border: 0.5px solid #FFD54F;
    border-radius: 2px;
    padding: 1px 6px;
    font-size: 10px;
    font-weight: 600;
}

/* Jumlah row */
.xl-jumlah td {
    background: #E2EFDA;
    color: #1B5E20;
    font-weight: 700;
    border: 1px solid #A9D18E;
    font-size: 12px;
    padding: 4px 7px;
}
</style>

<!-- TOOLBAR -->
<div class="xl-toolbar">
    <span class="xl-toolbar-title">DATA PEGAWAI KOMINFO</span>
    <a href="export_excel.php" class="btn btn-success btn-sm" style="display:flex;align-items:center;gap:5px;">
        ⬇ Download Excel
    </a>
    <a href="pegawai.php" class="btn btn-ghost btn-sm">← Kembali</a>
    <div style="margin-left:auto;display:flex;gap:6px;flex-wrap:wrap;">
        <span class="xl-badge">Total: <?= count($rows) ?> pegawai</span>
        <span class="xl-badge laki">♂ <?= $totalLaki ?> laki-laki</span>
        <span class="xl-badge wanita">♀ <?= $totalWanita ?> perempuan</span>
    </div>
</div>

<!-- SHEET -->
<div class="xl-sheet-wrap">
<table class="xl-table">
<thead>
    <!-- Baris huruf kolom (A, B, C...) -->
    <tr>
        <td class="xl-ch xl-rh corner" style="z-index:11;"></td>
        <td class="xl-ch" style="width:30px;">A</td>
        <td class="xl-ch" style="width:200px;">B</td>
        <td class="xl-ch" style="width:145px;">C</td>
        <td class="xl-ch" style="width:110px;">D</td>
        <td class="xl-ch" style="width:85px;">E</td>
        <td class="xl-ch" style="width:42px;">F</td>
        <td class="xl-ch" style="width:42px;">G</td>
        <td class="xl-ch" style="width:65px;">H</td>
        <td class="xl-ch" style="width:90px;">I</td>
        <td class="xl-ch" style="width:120px;">J</td>
        <td class="xl-ch" style="width:60px;">K</td>
        <td class="xl-ch" style="width:38px;">L</td>
        <td class="xl-ch" style="width:38px;">M</td>
        <td class="xl-ch" style="width:85px;">N</td>
        <td class="xl-ch" style="width:100px;">O</td>
        <td class="xl-ch" style="width:85px;">P</td>
        <td class="xl-ch" style="width:170px;">Q</td>
        <td class="xl-ch" style="width:85px;">R</td>
        <td class="xl-ch" style="width:100px;">S</td>
        <td class="xl-ch" style="width:85px;">T</td>
        <td class="xl-ch" style="width:45px;">U</td>
        <td class="xl-ch" style="width:45px;">V</td>
        <td class="xl-ch" style="width:70px;">W</td>
        <td class="xl-ch" style="width:80px;">X</td>
    </tr>

    <!-- Baris 1: Judul -->
    <tr class="xl-title">
        <td class="xl-rh" style="background:#F2F2F2;border:1px solid #D0D0D0;color:#666;">1</td>
        <td colspan="24">
            DAFTAR NOMINATIF PEGAWAI NEGERI SIPIL &mdash; DILINGKUNGAN INSTANSI DINAS KOMUNIKASI DAN INFORMATIKA PROVINSI PAPUA &mdash; TAHUN <?= date('Y') ?>
        </td>
    </tr>

    <!-- Baris 2: Header kolom utama -->
    <tr class="xl-header">
        <td class="xl-rh">2</td>
        <td rowspan="2" style="vertical-align:middle;">NO</td>
        <td rowspan="2" style="vertical-align:middle;">NAMA</td>
        <td rowspan="2" style="vertical-align:middle;">NIP</td>
        <td rowspan="2" style="vertical-align:middle;">TEMPAT<br>LAHIR</td>
        <td rowspan="2" style="vertical-align:middle;">TANGGAL<br>LAHIR</td>
        <td colspan="2">JENIS KELAMIN</td>
        <td rowspan="2" style="vertical-align:middle;">AGAMA</td>
        <td rowspan="2" style="vertical-align:middle;">PENDIDIKAN<br>TERAKHIR</td>
        <td rowspan="2" style="vertical-align:middle;">JURUSAN</td>
        <td rowspan="2" style="vertical-align:middle;">PANGKAT<br>TERAKHIR</td>
        <td rowspan="2" style="vertical-align:middle;">GOL</td>
        <td colspan="2">MASA KERJA/GOL</td>
        <td colspan="3">SURAT KEPUTUSAN (PANGKAT)</td>
        <td rowspan="2" style="vertical-align:middle;">JABATAN TERAKHIR</td>
        <td colspan="3">SURAT KEPUTUSAN (JABATAN)</td>
        <td colspan="2">STATUS KEPEG.</td>
        <td rowspan="2" style="vertical-align:middle;">PAPUA/<br>NON PAPUA</td>
    </tr>

    <!-- Baris 3: Sub-header -->
    <tr class="xl-subheader">
        <td class="xl-rh">3</td>
        <td>L</td>
        <td>P</td>
        <td>THN</td>
        <td>BLN</td>
        <td>Pejabat</td>
        <td>No. SK</td>
        <td>Tanggal</td>
        <td>Pejabat</td>
        <td>No. SK</td>
        <td>Tanggal</td>
        <td>CPNS</td>
        <td>PNS</td>
    </tr>
</thead>

<tbody>
<?php
$no = 1;
$excelRow = 4;
foreach ($rows as $r):
    $jk       = strtolower(trim($r['jenis_kelamin'] ?? ''));
    $isLaki   = in_array($jk, ['pria', 'l', 'laki-laki', 'laki laki']);
    $isWanita = in_array($jk, ['wanita', 'p', 'perempuan', 'perempuan']);
    $rowClass = $no % 2 === 1 ? 'xl-data-odd' : 'xl-data-even';

    $tglLahir = '-';
    if (!empty($r['tanggal_lahir']) && $r['tanggal_lahir'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['tanggal_lahir']);
        $tglLahir = $dt ? $dt->format('d/m/Y') : $r['tanggal_lahir'];
    }
    $tmtPangkat = '-';
    if (!empty($r['tanggal_pangkat_terakhir']) && $r['tanggal_pangkat_terakhir'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['tanggal_pangkat_terakhir']);
        $tmtPangkat = $dt ? $dt->format('d/m/Y') : $r['tanggal_pangkat_terakhir'];
    }
    $skTanggal = '-';
    if (!empty($r['sk_tanggal']) && $r['sk_tanggal'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['sk_tanggal']);
        $skTanggal = $dt ? $dt->format('d/m/Y') : $r['sk_tanggal'];
    }
    $skJabatanTanggal = '-';
    if (!empty($r['sk_jabatan_tanggal']) && $r['sk_jabatan_tanggal'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['sk_jabatan_tanggal']);
        $skJabatanTanggal = $dt ? $dt->format('d/m/Y') : $r['sk_jabatan_tanggal'];
    }

    $statusUpper2 = strtoupper(trim($r['status_kepegawaian'] ?? ''));
    $isCpns = $statusUpper2 === 'CPNS';
    $isPns  = $statusUpper2 === 'PNS' || (!$isCpns && $statusUpper2 !== 'PPPK');

    $kategoriSuku = $r['kategori_suku'] ?? null;
    $oap = $kategoriSuku === 'Papua' ? 'PAPUA' : ($kategoriSuku === 'Non-Papua' ? 'NON PAPUA' : '-');
?>
    <tr class="<?= $rowClass ?>">
        <td class="xl-rh xl-no"><?= $excelRow++ ?></td>
        <td class="xl-no"><?= $no++ ?></td>
        <td class="xl-nama" title="<?= e($r['nama']) ?>"><?= e(strtoupper($r['nama'])) ?></td>
        <td class="xl-nip"><?= e($r['nip']) ?></td>
        <td><?= e($r['tempat_lahir'] ?? '-') ?></td>
        <td class="xl-tgl"><?= e($tglLahir) ?></td>
        <td class="xl-jk-l"><?= $isLaki   ? 'L' : '' ?></td>
        <td class="xl-jk-p"><?= $isWanita ? 'P' : '' ?></td>
        <td><?= e($r['agama'] ?? '-') ?></td>
        <td><?= e($r['pendidikan_terakhir'] ?? '-') ?></td>
        <td><?= e($r['pendidikan_jurusan'] ?? '-') ?></td>
        <td title="<?= e($r['pangkat_terakhir']) ?>"><?= e(strtoupper($r['pangkat_terakhir'] ?? '-')) ?><br><small style="color:#888;"><?= e($tmtPangkat) ?></small></td>
        <td class="xl-gol"><?= e($r['golongan'] ?? '-') ?></td>
        <td class="xl-status"><?= e($r['masa_kerja_tahun'] ?? '-') ?></td>
        <td class="xl-status"><?= e($r['masa_kerja_bulan'] ?? '-') ?></td>
        <td><?= e($r['sk_pejabat'] ?? '-') ?></td>
        <td><?= e($r['sk_nomor'] ?? '-') ?></td>
        <td class="xl-tgl"><?= e($skTanggal) ?></td>
        <td title="<?= e($r['jabatan']) ?>"><?= e(strtoupper($r['jabatan'] ?? '-')) ?></td>
        <td><?= e($r['sk_jabatan_pejabat'] ?? '-') ?></td>
        <td><?= e($r['sk_jabatan_nomor'] ?? '-') ?></td>
        <td class="xl-tgl"><?= e($skJabatanTanggal) ?></td>
        <td class="xl-status"><?= $isCpns ? '✔' : '' ?></td>
        <td class="xl-status"><?= (!$isCpns && $isPns) ? '✔' : '' ?></td>
        <td class="xl-status"><?= e($oap) ?></td>
    </tr>
<?php endforeach; ?>

    <!-- Baris jumlah -->
    <tr class="xl-jumlah">
        <td class="xl-rh xl-no"><?= $excelRow ?></td>
        <td colspan="5" style="text-align:center;">JUMLAH</td>
        <td style="text-align:center;"><?= $totalLaki ?></td>
        <td style="text-align:center;"><?= $totalWanita ?></td>
        <td colspan="17"></td>
    </tr>
</tbody>
</table>
</div>