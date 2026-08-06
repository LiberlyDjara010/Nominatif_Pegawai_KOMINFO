<?php
require 'auth.php';
include 'koneksi.php';

$pageTitle    = 'Data Pegawai';
$pageSubtitle = 'Daftar lengkap seluruh ASN Diskominfo Provinsi Papua';

// FILTER & SEARCH

$keyword  = trim($_GET['keyword'] ?? '');
$filterJK = $_GET['jk'] ?? '';
$filterGol = $_GET['gol'] ?? '';
$filterSuku = $_GET['suku'] ?? '';
$filterAgama = $_GET['agama'] ?? '';

$where = [];
if ($keyword !== '') {
    $kw = mysqli_real_escape_string($conn, $keyword);
    $where[] = "(nama LIKE '%$kw%' OR nip LIKE '%$kw%' OR nik LIKE '%$kw%' OR jabatan LIKE '%$kw%')";
}
if ($filterJK)   $where[] = "jenis_kelamin = '" . mysqli_real_escape_string($conn, $filterJK) . "'";
if ($filterGol)  $where[] = "golongan = '" . mysqli_real_escape_string($conn, $filterGol) . "'";
if ($filterSuku) {
    $filterSukuValue = mysqli_real_escape_string($conn, $filterSuku);
    if ($filterSukuValue === 'OAP') {
        $where[] = "kategori_suku = 'Papua'";
    } elseif ($filterSukuValue === 'Non-OAP') {
        $where[] = "kategori_suku = 'Non-Papua'";
    }
}
if ($filterAgama) $where[] = "agama = '" . mysqli_real_escape_string($conn, $filterAgama) . "'";
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$query = mysqli_query($conn, "SELECT * FROM pegawai $whereSQL ORDER BY nama ASC");

$golOptions = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'];

$sukuOptions = [];
$qSuku = mysqli_query($conn, "SELECT DISTINCT kategori_suku FROM pegawai WHERE kategori_suku IS NOT NULL ORDER BY kategori_suku");
while ($r = mysqli_fetch_assoc($qSuku)) {
    $kategori = $r['kategori_suku'];
    $label = $kategori === 'Papua' ? 'OAP' : ($kategori === 'Non-Papua' ? 'Non-OAP' : $kategori);
    $sukuOptions[] = $label;
}

$AgamaOptions = [];
$qAgama = mysqli_query($conn, "SELECT DISTINCT agama FROM pegawai WHERE agama IS NOT NULL ORDER BY agama");
while ($r = mysqli_fetch_assoc($qAgama)) $AgamaOptions[] = $r['agama'];

include 'layout.php';
?>

<?php

// FLASH MESSAGES

$flash = $_GET['flash'] ?? '';
$flashJumlah = (int)($_GET['jumlah'] ?? 0);
if ($flash === 'hapus_massal'): ?>

<div style=
"background: #F0FFF8;
border:1px solid #86EFAC;
border-radius: 10px;
padding: 12px 18px;
margin-bottom: 16px;
color: #14532D;
font-size: 13px;"
>
<strong><?= $flashJumlah ?> data pegawai</strong> berhasil dihapus.
</div>

<?php elseif ($flash === 'tambah'): ?>
    <div style=
    "background: #F0FFF8;
    border: 1px solid #86EFAC;
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 16px;
    color: #14532D;
    font-size: 13px;">
        Data pegawai berhasil ditambahkan.
    </div>
    
    <?php elseif ($flash === 'edit'): ?>
        <div style=
        "background: #EFF6FF;
        border: 1px solid #93C5FD;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 16px;
        color: #1D4ED8;
        font-size: 13px;"
        >
        
        Data pegawai berhasil diperbarui.
    </div>
    <?php endif; ?>
    
    <!-- SEARCH & FILTER -->
     
    <div class="card">
        <div class="card-body" style="padding:16px 20px;">
            <form method="GET" style="display:flex;gap:10px; flex-wrap:wrap; align-items:flex-end;">
                <div class="search-input-wrap" style="flex:2; min-width:220px;">
                    <input 
                    type="text" 
                    name="keyword" 
                    
                    placeholder="Cari nama, NIP, NIK, jabatan"
                    value="<?= e($keyword) ?>">
                </div>

                <select name="jk" class="filter-select">
                    <option value="">Semua J/K</option>
                    <option value="Pria"   <?= $filterJK === 'Pria'   ? 'selected' : '' ?>>Pria</option>
                    <option value="Wanita" <?= $filterJK === 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                </select>

                <select name="gol" class="filter-select">
                    <option value="">Semua Golongan</option>
                    <?php foreach ($golOptions as $g): ?>
                        <option value="<?= e($g) ?>" <?= $filterGol === $g ? 'selected' : '' ?>><?= e($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="suku" class="filter-select">
                        <option value="">Semua</option>
                        <?php foreach ($sukuOptions as $s): ?>
                            <option value="<?= e($s) ?>" <?= $filterSuku === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="agama" class="filter-select">
                            <option value="">Semua Agama</option>
                            <option value="Islam"   <?= $filterAgama === 'Islam'   ? 'selected' : '' ?>>Islam</option>
                            <option value="Kristen" <?= $filterAgama === 'Kristen' ? 'selected' : '' ?>>Kristen</option>
                            <option value="Katolik" <?= $filterAgama === 'Katolik' ? 'selected' : '' ?>>Katolik</option>
                            <option value="Hindu" <?= $filterAgama === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                            <option value="Budha" <?= $filterAgama === 'Budha' ? 'selected' : '' ?>>Budha</option>
                            <option value="Konghucu" <?= $filterAgama === 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="pegawai.php" class="btn btn-ghost">Reset</a>
                        <a href="import.php" class="btn btn-primary"> ⬇ Import</a>
                        <a href="preview_export.php" class="btn btn-success"> Preview & Download Excel </a>
                    </form>
                </div>
            </div>

<!-- TABEL -->
 
<div class="card">
    <div class="card-header">
        <h3>Daftar Pegawai</h3>
        <div style="display:flex;align-items:center;gap:10px;">
            <span style="font-size:12px;color:var(--teks-abu);"><?= mysqli_num_rows($query) ?> data ditemukan</span>
            <span id="info-pilih" style="font-size:12px;color:var(--biru);display:none;"></span>
            <button id="btn-hapus-terpilih" onclick="hapusTerpilih()"
            
            style=
            "display: none;
            padding: 6px 14px;
            background: #D93025;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;"
            >
            Hapus Terpilih
        </button>
    </div>
</div>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:36px;">
                    <input type="checkbox" id="cb-all" onclick="pilihSemua(this)" title="Pilih semua" 
                    
                    style=
                    "cursor: pointer;
                    width: 15px;
                    height: 15px;
                    ">
                    
                </th>
                <th>No</th>
                <th>Nama / NIP</th>
                <th>J/K, Suku & Agama</th>
                <th>Tgl Lahir / Usia</th>
                <th>Golongan & Jabatan Terakhir</th>
                <th>Pangkat  & Status Kepegawaian</th>
                <th>Pensiun</th>
                <th>Naik Pangkat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($query) === 0): ?>
                <tr>
                    <td colspan="9" class="empty-state">
                        <?php

                        if ($filterAgama != '') {
                            echo "Tidak ada pegawai beragama" . e ($filterAgama);
                        } elseif ($filterSuku != '') {
                            echo "Tidak ada pegawai dengan kategori" . e ($filterSuku);
                        } elseif ($filterGol != '') {
                            echo "Tidak ada pegawai golongan" . e ($filterGol);
                        } elseif ($filterJK != '') {
                            echo "Tidak ada pegawai dengan jenis kelamin" . e ($filterJK);
                        } else {
                            echo "Tidak ada data yang sesuai dengan filter";
                        }

                        ?>
                        <td>
            </tr>
            
            <?php else:
            $no = 1;
            while ($row = mysqli_fetch_assoc($query)):
                $usia       = $row['tanggal_lahir'] ? hitungUsia($row['tanggal_lahir']) : '-';
                $sisaPensiun = $row['tanggal_lahir'] ? sisaBulanPensiun($row['tanggal_lahir']) : '999';
                $tglPensiun       = $row['tanggal_lahir'] ? tanggalPensiun($row['tanggal_lahir']) : '-';
                $lbPensiun       = $row['tanggal_lahir'] ? labelPensiun($sisaPensiun) : ['label' => '-', 'class' => ''];
                $sisaPangkat       = $row['tanggal_pangkat_terakhir'] ? sisaBulanNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null) : '999';
                $pangkat_terakhir       = $row['pangkat_terakhir'] ?: '-';
                $lbPangkat       = $row['tanggal_pangkat_terakhir'] ? tanggalNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null) : '-';
                $status       = $row['tanggal_pangkat_terakhir'] ? labelPangkat($sisaPangkat) : ['label' => '-', 'class' => ''];
                ?>
               <tr>
                <td>
                    <input type="checkbox" class="cb-row" value="<?= $row['id'] ?>"
                    onclick="updatePilihan()"
                    style=
                    "cursor : pointer;
                    width : 15px;
                    height : 15px;"
                    >
                </td>
                
                <td style="color:var(--teks-abu);font-size:12px;"><?= $no++ ?></td>
                <td>
                    <div style="font-weight:600;"><?= e($row['nama']) ?></div>
                    <div style="font-size:11px;color:var(--teks-abu);">NIP: <?= e($row['nip']) ?></div>
                    <div style="font-size:11px;color:var(--teks-abu);">NIK: <?= e($row['nik']) ?></div>
                </td>

                <td>
                    <div><?= $row['jenis_kelamin'] === 'Pria' ? 'Pria' : 'Wanita' ?></div>
                    <div style="font-size:11px;color:var(--teks-abu);">
                        <?php
                        if ($row['kategori_suku'] === 'Papua') echo 'OAP';
                        elseif ($row['kategori_suku'] === 'Non-Papua') echo 'Non-OAP';
                        else echo '-';
                        ?>
                    </div>
                    <div style="font-size:11px;color:var(--teks-abu);"><?= e($row['agama'] ?: '-') ?></div>
                </td>

                <td>
                    <div><?= $row['tanggal_lahir'] ? date('d M Y', strtotime($row['tanggal_lahir'])) : '-' ?></div>
                    <div style="font-size:11px;color:var(--teks-abu);"><?= is_int($usia) ? $usia . ' tahun' : '-' ?></div>
                </td>
                
                <td>
                    <div><strong><?= e($row['golongan'] ?: '-') ?></strong></div>
                    <div style="font-size:12px;color:var(--teks-abu);"><?= e($row['jabatan'] ?: '-') ?></div>
                </td>
                
                <td>
                    <div><strong><?= e($row['pangkat_terakhir']) ?></strong></div>
                    <div style="font-size:12px;color:var(--teks-abu);"><?= e($row['status_kepegawaian']) ?></div>
                </td>
                
                <td>
                    <div style="font-size:12px;"><?= $tglPensiun ?></div>
                    <?php if ($row['tanggal_lahir']): ?>
                        <span class="badge <?= $lbPensiun['class'] ?>"><?= $lbPensiun['label'] ?></span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div style="font-size:12px;"><?= $lbPangkat ?></div>
                        <?php if ($row['tanggal_pangkat_terakhir']): ?>
                            <span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div style="display:flex;gap:4px;">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
    function pilihSemua(cb){
        document.querySelectorAll('.cb-row').forEach(function(item){
            item.checked = cb.checked;
        });
        updatePilihan();
    }
    function updatePilihan(){
        var dipilih = document.querySelectorAll('.cb-row:checked');
        var total   = document.querySelectorAll('.cb-row').length;
        var btn  = document.getElementById('btn-hapus-terpilih');
        var info = document.getElementById('info-pilih');
        var cbAll = document.getElementById('cb-all');
        
        if(dipilih.length > 0){
            btn.style.display = 'inline-block';
            info.style.display = 'inline';
            info.innerHTML =
            dipilih.length + " dari " + total + " dipilih";
            
            cbAll.indeterminate =
            dipilih.length > 0 &&
            dipilih.length < total;
            
            cbAll.checked =
            dipilih.length === total;
        
        }else{
            btn.style.display = 'none';
            info.style.display = 'none';
            cbAll.indeterminate = false;
            cbAll.checked = false;
        }
    }
    function hapusTerpilih(){
        var ids = [];
        document.querySelectorAll('.cb-row:checked').forEach(function(cb){
            ids.push(cb.value);
        });
        
        if(ids.length === 0){
            alert('Pilih data terlebih dahulu');
            return;
        }
        
        if(!confirm(
            'Anda Yakin Ingin Menghapusnya?\n\n' +
            ids.length +
            ' data pegawai akan dihapus secara permanen.'
        )){
            return;
        }
        
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'hapus.php';
        
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids';
        input.value = ids.join(',');
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>
    <?php include 'layout_end.php'; ?>