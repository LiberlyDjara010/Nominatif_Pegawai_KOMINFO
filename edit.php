<?php
require 'auth.php';
include 'koneksi.php';

requireCanManageData();

$pageTitle    = 'Edit Pegawai';
$pageSubtitle = 'Perbarui data ASN yang sudah terdaftar';

$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    die("Data tidak ditemukan.");
}

$errors = [];

$semuaKolomForm = [
    'nip', 'nama', 'pendidikan_terakhir',
    'pendidikan_jurusan', 'jenis_kelamin', 'agama', 'kategori_suku',
    'tanggal_lahir', 'tempat_lahir', 'status_kepegawaian',
    'pangkat_terakhir', 'golongan',
    'jabatan', 'jenis_jabatan',
    'tmt_jabatan', 'tanggal_masuk', 'tanggal_pangkat_terakhir',
    'masa_kerja_tahun', 'masa_kerja_bulan', 'sk_pejabat', 'sk_nomor',
    'sk_tanggal', 'sk_jabatan_pejabat', 'sk_jabatan_nomor', 'sk_jabatan_tanggal',
    'keterangan',
];
$kolomHilang = kolomHilang($conn, $semuaKolomForm);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $fields = $semuaKolomForm;

    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    $data['golongan'] = normalisasiGolongan($data['golongan']) ?? '';
    if (empty($data['jenis_jabatan'])) $data['jenis_jabatan'] = tentukanJenisJabatan($data['jabatan']);

    $rawTanggalMasuk = $data['tanggal_masuk'];
    $data['tanggal_masuk'] = normalisasiTanggal($data['tanggal_masuk']);
    if ($rawTanggalMasuk !== '' && $data['tanggal_masuk'] === null) {
        $errors[] = 'Format tanggal masuk kerja tidak valid';
    }

    foreach (['tanggal_lahir', 'tanggal_pangkat_terakhir', 'tmt_jabatan', 'sk_tanggal', 'sk_jabatan_tanggal'] as $kolomTanggal) {
        if ($data[$kolomTanggal] === '') {
            $data[$kolomTanggal] = null;
        }
    }

    foreach (['kategori_suku'] as $kolomNullable) {
        if ($data[$kolomNullable] === '') {
            $data[$kolomNullable] = null;
        }
    }
    if ($data['masa_kerja_tahun'] === '') $data['masa_kerja_tahun'] = null;
    if ($data['masa_kerja_bulan'] === '') $data['masa_kerja_bulan'] = null;

    // VALIDASI

    if (empty($data['nip']))  $errors[] = 'NIP wajib diisi';
    if (empty($data['nama'])) $errors[] = 'Nama wajib diisi';

    // cek duplikat NIP selain dirinya sendiri
    $cek = $conn->prepare("SELECT id FROM pegawai WHERE nip=? AND id != ? LIMIT 1");
    $cek->bind_param("si", $data['nip'], $id);
    $cek->execute();

    if ($cek->get_result()->num_rows > 0) {
        $errors[] = "NIP sudah digunakan pegawai lain";
    }

    if (empty($errors)) {
        $hasil = simpanPegawai($conn, $data, (int) $id);

        if ($hasil['success']) {
            header("Location: pegawai.php?flash=edit");
            exit;
        } else {
            $errors[] = $hasil['error'] ?? "Gagal update data.";
        }
    }
}

include 'layout.php';
?>


<?php if (!empty($kolomHilang)): ?>
<div style="background:#FFF8E1;border:1px solid #FFD54F;border-radius:10px;padding:14px 18px;margin-bottom:20px;color:#6D4C00;font-size:13.5px;">
    <strong>⚠ Beberapa kolom formulir belum tersedia di database:</strong>
    <?= e(implode(', ', $kolomHilang)) ?>.
    Field ini akan ditampilkan di form tapi <strong>tidak akan tersimpan</strong> sampai migrasi dijalankan.
    Login sebagai Super Admin lalu buka <code>migrasi_lengkap.php</code> satu kali untuk menambahkan kolom yang kurang.
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div style="
background:#FFF0EE;
border:1px solid #FFB3AE;
border-radius:10px;
padding:14px 18px;
margin-bottom:20px;
color:var(--merah);
">

<strong>⚠ Terdapat kesalahan :</strong>

<ul style="margin:8px 0 0 16px;">
    <?php foreach($errors as $e): ?>
        <li><?= e($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php endif; ?>

<div class="card">
<div class="card-header">
    <h3>Edit Data Pegawai</h3>
    <a href="pegawai.php" class="btn btn-ghost btn-sm">← Kembali</a>
</div>

<div class="card-body">
    <form method="POST">

<!-- IDENTITAS -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">

Identitas Pegawai
</div>

<div class="form-grid" style="margin-bottom:20px;">
<div class="field">
    <label>NIP</label>
    <input type="text" name="nip" value="<?= e($_POST['nip'] ?? $row['nip']) ?>" required>
</div>

<div class="field" style="grid-column:1/-1;">
    <label>Nama Lengkap</label>
    <input type="text" name="nama" value="<?= e($_POST['nama'] ?? $row['nama']) ?>" required>
</div>

<div class="field">
    <label>Pendidikan Terakhir</label>
    <input type="text" name="pendidikan_terakhir" value="<?= e($_POST['pendidikan_terakhir'] ?? $row['pendidikan_terakhir']) ?>">
</div>

<div class="field">
    <label>Jurusan / Program Studi</label>
    <input type="text" name="pendidikan_jurusan" value="<?= e($_POST['pendidikan_jurusan'] ?? ($row['pendidikan_jurusan'] ?? '')) ?>">
</div>

<div class="field">
    <label>Jenis Kelamin</label>
    <select name="jenis_kelamin">

    <option value="Pria"
        <?= ($_POST['jenis_kelamin'] ?? $row['jenis_kelamin']) == 'Pria' ? 'selected' : '' ?>>
        Pria
    </option>

    <option value="Wanita"
    <?= ($_POST['jenis_kelamin'] ?? $row['jenis_kelamin']) == 'Wanita' ? 'selected' : '' ?>>
    Wanita
    </option>

</select>
</div>

<div class="field">
    <label>Agama</label>
    <select name="agama">

        <?php
        $agamas = ['Islam','Kristen','Katolik','Hindu','Budha','Konghucu'];
        foreach($agamas as $ag):
        ?>

        <option value="<?= $ag ?>"
        <?= ($_POST['agama'] ?? $row['agama']) == $ag ? 'selected' : '' ?>>
        <?= $ag ?>
        </option>

        <?php endforeach; ?>
    </select>
</div>

<div class="field">
    <label>Kategori Suku (OAP)</label>
    <?php $kategoriSukuVal = $_POST['kategori_suku'] ?? ($row['kategori_suku'] ?? ''); ?>
    <select name="kategori_suku">
        <option value="" <?= $kategoriSukuVal === '' ? 'selected' : '' ?>>Belum ditentukan</option>
        <option value="Papua" <?= $kategoriSukuVal === 'Papua' ? 'selected' : '' ?>>Papua (OAP)</option>
        <option value="Non-Papua" <?= $kategoriSukuVal === 'Non-Papua' ? 'selected' : '' ?>>Non-Papua</option>
    </select>
</div>

<div class="field">
    <label>Tanggal Lahir</label>
    <input type="date" name="tanggal_lahir" value="<?= e($_POST['tanggal_lahir'] ?? $row['tanggal_lahir']) ?>">
</div>

<div class="field">
    <label>Tempat Lahir</label>
    <input type="text" name="tempat_lahir" value="<?= e($_POST['tempat_lahir'] ?? $row['tempat_lahir']) ?>">
</div>

</div>

<!-- KEPEGAWAIAN & JABATAN -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">

Kepegawaian &amp; Jabatan

</div>
<div class="form-grid" style="margin-bottom:24px;">

<div class="field">
    <label>Status Kepegawaian</label>
    <?php $statusKepegVal = $_POST['status_kepegawaian'] ?? ($row['status_kepegawaian'] ?? 'PNS'); ?>
    <select name="status_kepegawaian">
        <?php foreach (['PNS','CPNS','PPPK'] as $sk): ?>
        <option value="<?= $sk ?>" <?= $statusKepegVal === $sk ? 'selected' : '' ?>><?= $sk ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="field" style="grid-column:1/-1;">
    <label>Jabatan (Jabatan Terakhir)</label>
    <input type="text" id="inputJabatan" name="jabatan" value="<?= e($_POST['jabatan'] ?? $row['jabatan']) ?>">
    <small id="hintJenisJabatan" style="display:block;margin-top:4px;font-size:11.5px;color:var(--teks-abu);"></small>
    <small style="display:block;margin-top:2px;font-size:11px;color:var(--teks-abu);">Sesuai format nominatif resmi BKD: sertakan nama unit/OPD langsung di dalam teks jabatan.</small>
</div>

<div class="field">
    <label>Jenis Jabatan (untuk Batas Usia Pensiun)</label>
    <select id="selectJenisJabatan" name="jenis_jabatan">
        <?php
        $jjOptions = [
            'pelaksana'       => 'Pelaksana',
            'pengawas'        => 'Pengawas / Eselon IV',
            'administrator'   => 'Administrator / Eselon III',
            'jf ahli pertama' => 'JF Ahli Pertama',
            'jf ahli muda'    => 'JF Ahli Muda',
            'jf terampil'     => 'JF Terampil',
            'jf mahir'        => 'JF Mahir',
            'jf penyelia'     => 'JF Penyelia',
            'jf pemula'       => 'JF Pemula',
            'jpt pratama'     => 'JPT Pratama / Eselon II',
            'jpt madya'       => 'JPT Madya / Eselon I',
            'jpt utama'       => 'JPT Utama',
            'jf ahli madya'   => 'JF Ahli Madya',
            'jf ahli utama'   => 'JF Ahli Utama',
        ];
        $jjSelected = $_POST['jenis_jabatan'] ?? ($row['jenis_jabatan'] ?? 'pelaksana');
        foreach ($jjOptions as $val => $label):
        ?>
        <option value="<?= $val ?>" <?= $jjSelected === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="field">
    <label>TMT Jabatan</label>
    <input type="date" name="tmt_jabatan" value="<?= e($_POST['tmt_jabatan'] ?? ($row['tmt_jabatan'] ?? '')) ?>">
</div>

<div class="field">
    <label>Tanggal Masuk Kerja</label>
    <input type="text" name="tanggal_masuk" placeholder="MM/YYYY      or      DD/MM/YYYY" value="<?= e($_POST['tanggal_masuk'] ?? (formatTanggalMasukInput($row['tanggal_masuk']) ?? '')) ?>">
</div>

</div>

<!-- PANGKAT / GOLONGAN TERAKHIR -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">
Pangkat / Golongan Terakhir
</div>
<div class="form-grid" style="margin-bottom:24px;">

<div class="field">
    <label>Pangkat Terakhir</label>
    <input type="text" name="pangkat_terakhir" value="<?= e($_POST['pangkat_terakhir'] ?? ($row['pangkat_terakhir'] ?? '')) ?>">
</div>

<div class="field">
    <label>Golongan</label>
    <select name="golongan">
        <option value="">Pilih Golongan</option>

        <?php
        $gols = [
            'I/a','I/b','I/c','I/d',
            'II/a','II/b','II/c','II/d',
            'III/a','III/b','III/c','III/d',
            'IV/a','IV/b','IV/c','IV/d','IV/e'
            ];

            foreach($gols as $gol):
            ?>
            <option value="<?= $gol ?>"
            <?= ($_POST['golongan'] ?? $row['golongan']) == $gol ? 'selected' : '' ?>>
            <?= $gol ?>
        </option>

        <?php endforeach; ?>

    </select>
</div>

<div class="field">
    <label>Masa Kerja dalam Golongan (Tahun)</label>
    <input type="number" min="0" max="50" name="masa_kerja_tahun" value="<?= e($_POST['masa_kerja_tahun'] ?? ($row['masa_kerja_tahun'] ?? '')) ?>">
</div>

<div class="field">
    <label>Masa Kerja dalam Golongan (Bulan)</label>
    <input type="number" min="0" max="11" name="masa_kerja_bulan" value="<?= e($_POST['masa_kerja_bulan'] ?? ($row['masa_kerja_bulan'] ?? '')) ?>">
</div>

<div class="field">
    <label>Tanggal Pangkat Terakhir (TMT)</label>
    <input type="date" name="tanggal_pangkat_terakhir" value="<?= e($_POST['tanggal_pangkat_terakhir'] ?? $row['tanggal_pangkat_terakhir']) ?>">
</div>

</div>

<!-- SURAT KEPUTUSAN -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">
Surat Keputusan (SK) Pangkat/Golongan Terakhir
</div>
<div class="form-grid" style="margin-bottom:24px;">

<div class="field">
    <label>Pejabat Penetap</label>
    <input type="text" name="sk_pejabat" value="<?= e($_POST['sk_pejabat'] ?? ($row['sk_pejabat'] ?? '')) ?>">
</div>

<div class="field">
    <label>Nomor SK</label>
    <input type="text" name="sk_nomor" value="<?= e($_POST['sk_nomor'] ?? ($row['sk_nomor'] ?? '')) ?>">
</div>

<div class="field">
    <label>Tanggal SK</label>
    <input type="date" name="sk_tanggal" value="<?= e($_POST['sk_tanggal'] ?? ($row['sk_tanggal'] ?? '')) ?>">
</div>

</div>

<!-- SURAT KEPUTUSAN JABATAN -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">
Surat Keputusan (SK) Jabatan Terakhir
</div>
<div class="form-grid" style="margin-bottom:24px;">

<div class="field">
    <label>Pejabat Penetap</label>
    <input type="text" name="sk_jabatan_pejabat" value="<?= e($_POST['sk_jabatan_pejabat'] ?? ($row['sk_jabatan_pejabat'] ?? '')) ?>">
</div>

<div class="field">
    <label>Nomor SK</label>
    <input type="text" name="sk_jabatan_nomor" value="<?= e($_POST['sk_jabatan_nomor'] ?? ($row['sk_jabatan_nomor'] ?? '')) ?>">
</div>

<div class="field">
    <label>Tanggal SK</label>
    <input type="date" name="sk_jabatan_tanggal" value="<?= e($_POST['sk_jabatan_tanggal'] ?? ($row['sk_jabatan_tanggal'] ?? '')) ?>">
</div>

</div>

<!-- KETERANGAN -->

<div style="
margin-bottom:8px;
font-size:12px;
font-weight:700;
color:var(--biru);
text-transform:uppercase;
letter-spacing:0.5px;
border-bottom:1px solid var(--abu-border);
padding-bottom:6px;
">
Keterangan
</div>
<div class="form-grid" style="margin-bottom:24px;">

<div class="field" style="grid-column:1/-1;">
    <label>Keterangan</label>
    <textarea name="keterangan" rows="2"><?= e($_POST['keterangan'] ?? ($row['keterangan'] ?? '')) ?></textarea>
</div>

</div>

<div style="display:flex;gap:10px;">
    <button type="submit" name="update" class="btn btn-primary">
        Update Data
    </button>

    <a href="pegawai.php" class="btn btn-ghost">
        Batal
    </a>
</div>
</form>
</div>
</div>

<script>
function tentukanJenisJabatanJS(jabatan) {
    var j = (jabatan || '').toLowerCase().trim();
    if (j === '') return 'pelaksana';

    if (j.indexOf('ahli utama') !== -1) return 'jf ahli utama';
    if (j.indexOf('ahli madya') !== -1) return 'jf ahli madya';
    if (j.indexOf('ahli muda') !== -1) return 'jf ahli muda';
    if (j.indexOf('ahli pertama') !== -1) return 'jf ahli pertama';

    if (j.indexOf('penyelia') !== -1) return 'jf penyelia';
    if (j.indexOf('mahir') !== -1) return 'jf mahir';
    if (j.indexOf('pemula') !== -1) return 'jf pemula';
    if (j.indexOf('terampil') !== -1) return 'jf terampil';

    if (j.indexOf('pimpinan tinggi utama') !== -1) return 'jpt utama';
    if (j.indexOf('pimpinan tinggi madya') !== -1) return 'jpt madya';
    if (j.indexOf('pimpinan tinggi pratama') !== -1) return 'jpt pratama';
    if (j.indexOf('sekretaris daerah') !== -1) return 'jpt madya';
    if (j.indexOf('kepala dinas') !== -1) return 'jpt pratama';
    if (j.indexOf('kepala badan') !== -1) return 'jpt pratama';

    if (j.indexOf('sekretaris dinas') !== -1) return 'administrator';
    if (j.indexOf('kepala bidang') !== -1) return 'administrator';
    if (j.indexOf('kepala bagian') !== -1) return 'administrator';
    if (j.indexOf('sekretaris') !== -1) return 'administrator';

    if (j.indexOf('kepala seksi') !== -1) return 'pengawas';
    if (j.indexOf('kepala sub bagian') !== -1) return 'pengawas';
    if (j.indexOf('kepala subbagian') !== -1) return 'pengawas';
    if (j.indexOf('kasubbag') !== -1) return 'pengawas';

    return 'pelaksana';
}

(function () {
    var inputJabatan = document.getElementById('inputJabatan');
    var selectJJ = document.getElementById('selectJenisJabatan');
    var hint = document.getElementById('hintJenisJabatan');
    if (!inputJabatan || !selectJJ) return;

    var manualOverride = false;

    selectJJ.addEventListener('change', function () {
        manualOverride = true;
        if (hint) hint.textContent = '';
    });

    inputJabatan.addEventListener('input', function () {
        if (manualOverride) return;
        var suggestion = tentukanJenisJabatanJS(inputJabatan.value);
        for (var i = 0; i < selectJJ.options.length; i++) {
            if (selectJJ.options[i].value === suggestion) {
                selectJJ.value = suggestion;
                if (hint) hint.textContent = 'Otomatis disesuaikan dari teks jabatan. Boleh diganti manual kalau kurang tepat.';
                break;
            }
        }
    });
})();
</script>

<?php include 'layout_end.php'; ?>
