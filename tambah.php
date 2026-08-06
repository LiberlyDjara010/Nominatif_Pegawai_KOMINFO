<?php
require 'auth.php';
include 'koneksi.php';

requireCanManageData();

$pageTitle    = 'Tambah Pegawai';
$pageSubtitle = 'Daftarkan ASN baru ke dalam sistem';

$errors = [];
$success = false;
$dilewati = [];

// Kolom yang dipakai form ini -- kalau ada yang belum ada di tabel `pegawai`
// (migrasi belum dijalankan), tampilkan peringatan tapi JANGAN fatal error.
$semuaKolomForm = [
    'nip', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
    'agama', 'kategori_suku',
    'pendidikan_terakhir', 'pendidikan_jurusan', 'golongan', 'jabatan',
    'jenis_jabatan', 'tanggal_masuk',
    'tanggal_pangkat_terakhir', 'masa_kerja_tahun', 'masa_kerja_bulan',
    'sk_pejabat', 'sk_nomor', 'sk_tanggal',
    'sk_jabatan_pejabat', 'sk_jabatan_nomor', 'sk_jabatan_tanggal',
    'status_kepegawaian',
    'pangkat_terakhir',
    'tmt_jabatan', 'keterangan',
];
$kolomHilang = kolomHilang($conn, $semuaKolomForm);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {

    $data = [
        'nip' => trim($_POST['nip'] ?? ''),
        'nama' => trim($_POST['nama'] ?? ''),
        'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
        'jenis_kelamin' => trim($_POST['jenis_kelamin'] ?? ''),
        'agama' => trim($_POST['agama'] ?? ''),
        'kategori_suku' => trim($_POST['kategori_suku'] ?? ''),
        'pendidikan_terakhir' => trim($_POST['pendidikan_terakhir'] ?? ''),
        'pendidikan_jurusan' => trim($_POST['pendidikan_jurusan'] ?? ''),
        'golongan' => normalisasiGolongan(trim($_POST['golongan'] ?? '')) ?? '',
        'jabatan' => trim($_POST['jabatan'] ?? ''),
        'jenis_jabatan' => trim($_POST['jenis_jabatan'] ?? ''),
        'tanggal_masuk' => trim($_POST['tanggal_masuk'] ?? ''),
        'tanggal_pangkat_terakhir' => trim($_POST['tanggal_pangkat_terakhir'] ?? ''),
        'masa_kerja_tahun' => trim($_POST['masa_kerja_tahun'] ?? ''),
        'masa_kerja_bulan' => trim($_POST['masa_kerja_bulan'] ?? ''),
        'sk_pejabat' => trim($_POST['sk_pejabat'] ?? ''),
        'sk_nomor' => trim($_POST['sk_nomor'] ?? ''),
        'sk_tanggal' => trim($_POST['sk_tanggal'] ?? ''),
        'sk_jabatan_pejabat' => trim($_POST['sk_jabatan_pejabat'] ?? ''),
        'sk_jabatan_nomor' => trim($_POST['sk_jabatan_nomor'] ?? ''),
        'sk_jabatan_tanggal' => trim($_POST['sk_jabatan_tanggal'] ?? ''),
        'status_kepegawaian' => trim($_POST['status_kepegawaian'] ?? 'PNS'),
        'pangkat_terakhir' => trim($_POST['pangkat_terakhir'] ?? ''),
        'tmt_jabatan' => trim($_POST['tmt_jabatan'] ?? ''),
        'keterangan' => trim($_POST['keterangan'] ?? ''),
    ];

    if (empty($data['jenis_jabatan'])) {
        $data['jenis_jabatan'] = tentukanJenisJabatan($data['jabatan']);
    }
    // "kategori_suku" adalah kolom ENUM('Papua','Non-Papua') -- string
    // kosong '' harus dikonversi ke NULL, supaya tidak error "Data truncated".
    if ($data['kategori_suku'] === '') {
        $data['kategori_suku'] = null;
    }
    if ($data['pangkat_terakhir'] === '') {
        $data['pangkat_terakhir'] = null;
    }
    if ($data['masa_kerja_tahun'] === '') $data['masa_kerja_tahun'] = null;
    if ($data['masa_kerja_bulan'] === '') $data['masa_kerja_bulan'] = null;

    $rawTanggalMasuk = $data['tanggal_masuk'];
    $data['tanggal_masuk'] = normalisasiTanggal($data['tanggal_masuk']);
    if ($rawTanggalMasuk !== '' && $data['tanggal_masuk'] === null) {
        $errors[] = 'Format tanggal masuk kerja tidak valid';
    }
    foreach (['tanggal_lahir', 'tmt_jabatan', 'tanggal_pangkat_terakhir', 'sk_tanggal', 'sk_jabatan_tanggal'] as $kolomTanggal) {
        if ($data[$kolomTanggal] === '') {
            $data[$kolomTanggal] = null;
        }
    }

    if (empty($data['nip']))  $errors[] = 'NIP wajib diisi';
    if (empty($data['nama'])) $errors[] = 'Nama wajib diisi';

    $stmt = $conn->prepare("SELECT id FROM pegawai WHERE nip=? LIMIT 1");
    $stmt->bind_param('s', $data['nip']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) $errors[] = 'NIP sudah terdaftar';

    if (empty($errors)) {
        // simpanPegawai() otomatis hanya memakai kolom yang benar-benar ada
        // di tabel `pegawai` -- kalau ada migrasi yang belum dijalankan,
        // field itu dilewati (bukan fatal error) dan pesannya ditampilkan.
        $hasil = simpanPegawai($conn, $data, null);

        if ($hasil['success']) {
            header('Location: pegawai.php?flash=tambah');
            exit;
        } else {
            $errors[] = $hasil['error'] ?? 'Gagal menyimpan data.';
        }
        $dilewati = $hasil['dilewati'] ?? [];
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
    <div style="background:#FFF0EE;border:1px solid #FFB3AE;border-radius:10px;padding:14px 18px;margin-bottom:20px;color:var(--merah);">
        <strong>⚠ Terdapat kesalahan:</strong>
        <ul style="margin:8px 0 0 16px;">
            <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-header">
            <h3>Form Data Pegawai Baru</h3>
            <a href="pegawai.php" class="btn btn-ghost btn-sm">← Kembali</a>
        </div>
        <div class="card-body">
            <form method="POST">

                <!-- IDENTITAS -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Identitas Pegawai
                </div>

                <div class="form-grid" style="margin-bottom:20px;">
                    <div class="field">
                        <label>NIP <span style="color:var(--merah);">*</span></label>
                        <input type="text" name="nip" maxlength="30" value="<?= e($_POST['nip'] ?? '') ?>" required>
                    </div>

                    <div class="field" style="grid-column:1/-1;">
                        <label>Nama Lengkap <span style="color:var(--merah);">*</span></label>
                        <input type="text" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label>Pendidikan Terakhir <span style="color:var(--merah);">*</span></label>
                        <input type="text" name="pendidikan_terakhir" placeholder="cth: S1, S2, SMA" value="<?= e($_POST['pendidikan_terakhir'] ?? '') ?>" required>
                    </div>

                    <div class="field">
                        <label>Jurusan / Program Studi</label>
                        <input type="text" name="pendidikan_jurusan" placeholder="cth: Manajemen, Teknik Informatika" value="<?= e($_POST['pendidikan_jurusan'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Jenis Kelamin</label>
                        <select name="jenis_kelamin">
                            <option value="Pria"   <?= ($_POST['jenis_kelamin'] ?? '') === 'Pria'   ? 'selected' : '' ?>>Pria</option>
                            <option value="Wanita" <?= ($_POST['jenis_kelamin'] ?? '') === 'Wanita' ? 'selected' : '' ?>>Wanita</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Agama</label>
                        <select name="agama">
                            <?php foreach (['Islam','Kristen','Katolik','Hindu','Budha','Konghucu'] as $ag): ?>
                            <option value="<?= $ag ?>" <?= ($_POST['agama'] ?? '') === $ag ? 'selected' : '' ?>><?= $ag ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Kategori Suku (OAP / Non-OAP)</label>
                        <select name="kategori_suku">
                            <option value="" <?= ($_POST['kategori_suku'] ?? '') === '' ? 'selected' : '' ?>>Belum ditentukan</option>
                            <option value="Papua" <?= ($_POST['kategori_suku'] ?? '') === 'Papua' ? 'selected' : '' ?>>Papua (OAP)</option>
                            <option value="Non-Papua" <?= ($_POST['kategori_suku'] ?? '') === 'Non-Papua' ? 'selected' : '' ?>>Non-Papua</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" value="<?= e($_POST['tanggal_lahir'] ?? '') ?>">
                    </div>

                    <div class="field" style="grid-column:1/-1;">
                        <label>Tempat Lahir <span style="color:var(--merah);">*</span></label>
                        <input type="text" name="tempat_lahir" value="<?= e($_POST['tempat_lahir'] ?? '') ?>" required>
                    </div>

                </div>

                <!-- KEPEGAWAIAN & JABATAN -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Kepegawaian &amp; Jabatan
                </div>

                <div class="form-grid" style="margin-bottom:24px;">
                    <div class="field">
                        <label>Status Kepegawaian</label>
                        <select name="status_kepegawaian">
                            <?php foreach (['PNS','CPNS','PPPK'] as $sk): ?>
                            <option value="<?= $sk ?>" <?= ($_POST['status_kepegawaian'] ?? 'PNS') === $sk ? 'selected' : '' ?>><?= $sk ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field" style="grid-column:1/-1;">
                        <label>Jabatan (Jabatan Terakhir)</label>
                        <input type="text" id="inputJabatan" name="jabatan" value="<?= e($_POST['jabatan'] ?? '') ?>" placeholder="cth: Kepala Dinas Komunikasi dan Informatika Provinsi Papua">
                        <small id="hintJenisJabatan" style="display:block;margin-top:4px;font-size:11.5px;color:var(--teks-abu);"></small>
                        <small style="display:block;margin-top:2px;font-size:11px;color:var(--teks-abu);">Sesuai format nominatif resmi BKD: sertakan nama unit/OPD langsung di dalam teks jabatan (tidak ada kolom unit terpisah).</small>
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
                            $jjSelected = $_POST['jenis_jabatan'] ?? 'pelaksana';
                            foreach ($jjOptions as $val => $label):
                            ?>
                            <option value="<?= $val ?>" <?= $jjSelected === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>TMT Jabatan</label>
                        <input type="date" name="tmt_jabatan" value="<?= e($_POST['tmt_jabatan'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Tanggal Masuk Kerja</label>
                        <input type="text" name="tanggal_masuk" placeholder="MM/YYYY      or      DD/MM/YYYY" value="<?= e($_POST['tanggal_masuk'] ?? '') ?>">
                    </div>
                </div>

                <!-- PANGKAT / GOLONGAN TERAKHIR -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Pangkat / Golongan Terakhir
                </div>

                <div class="form-grid" style="margin-bottom:24px;">
                    <div class="field">
                        <label>Pangkat Terakhir</label>
                        <input type="text" name="pangkat_terakhir" placeholder="cth: Penata Tingkat I" value="<?= e($_POST['pangkat_terakhir'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Golongan</label>
                        <select name="golongan">
                            <option value="">Pilih Golongan</option>
                            <?php
                            $gols = ['I/a','I/b','I/c','I/d','II/a','II/b','II/c','II/d','III/a','III/b','III/c','III/d','IV/a','IV/b','IV/c','IV/d','IV/e'];
                            foreach ($gols as $gol): ?>
                            <option value="<?= $gol ?>" <?= ($_POST['golongan'] ?? '') === $gol ? 'selected' : '' ?>>
                                <?= $gol ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label>Masa Kerja dalam Golongan (Tahun)</label>
                        <input type="number" min="0" max="50" name="masa_kerja_tahun" value="<?= e($_POST['masa_kerja_tahun'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Masa Kerja dalam Golongan (Bulan)</label>
                        <input type="number" min="0" max="11" name="masa_kerja_bulan" value="<?= e($_POST['masa_kerja_bulan'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Tanggal Pangkat Terakhir (TMT)</label>
                        <input type="date" name="tanggal_pangkat_terakhir" value="<?= e($_POST['tanggal_pangkat_terakhir'] ?? '') ?>">
                    </div>
                </div>

                <!-- SURAT KEPUTUSAN PANGKAT/GOLONGAN TERAKHIR -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Surat Keputusan (SK) Pangkat/Golongan Terakhir
                </div>

                <div class="form-grid" style="margin-bottom:24px;">
                    <div class="field">
                        <label>Pejabat Penetap</label>
                        <input type="text" name="sk_pejabat" placeholder="cth: Gubernur Papua" value="<?= e($_POST['sk_pejabat'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Nomor SK</label>
                        <input type="text" name="sk_nomor" value="<?= e($_POST['sk_nomor'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Tanggal SK</label>
                        <input type="date" name="sk_tanggal" value="<?= e($_POST['sk_tanggal'] ?? '') ?>">
                    </div>
                </div>

                <!-- SURAT KEPUTUSAN JABATAN TERAKHIR -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Surat Keputusan (SK) Jabatan Terakhir
                </div>

                <div class="form-grid" style="margin-bottom:24px;">
                    <div class="field">
                        <label>Pejabat Penetap</label>
                        <input type="text" name="sk_jabatan_pejabat" placeholder="cth: Gubernur Papua" value="<?= e($_POST['sk_jabatan_pejabat'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Nomor SK</label>
                        <input type="text" name="sk_jabatan_nomor" value="<?= e($_POST['sk_jabatan_nomor'] ?? '') ?>">
                    </div>

                    <div class="field">
                        <label>Tanggal SK</label>
                        <input type="date" name="sk_jabatan_tanggal" value="<?= e($_POST['sk_jabatan_tanggal'] ?? '') ?>">
                    </div>
                </div>

                <!-- KETERANGAN -->

                <div style="margin-bottom:8px;font-size:12px;font-weight:700;color:var(--biru);text-transform:uppercase;letter-spacing:0.5px;border-bottom:1px solid var(--abu-border);padding-bottom:6px;">
                    Keterangan
                </div>

                <div class="form-grid" style="margin-bottom:24px;">
                    <div class="field" style="grid-column:1/-1;">
                        <label>Keterangan</label>
                        <textarea name="keterangan" rows="2" placeholder="Catatan tambahan (opsional)"><?= e($_POST['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                    <a href="pegawai.php" class="btn btn-ghost">Batal</a>
                </div>

            </form>
        </div>
    </div>

<script>
// Menebak "Jenis Jabatan" dari teks "Jabatan" -- port dari fungsi
// tentukanJenisJabatan() di koneksi.php, supaya dua field ini selalu
// nyambung/konsisten. Auto-update HANYA berjalan selama pengguna belum
// mengubah dropdown Jenis Jabatan secara manual (begitu diubah manual,
// auto-suggest berhenti supaya tidak menimpa pilihan yang disengaja).
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
