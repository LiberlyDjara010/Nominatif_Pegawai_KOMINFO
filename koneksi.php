<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'beydjara1290RememberiT*');
define('DB_NAME', 'nominatif_pegawai');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(json_encode(['error' => 'Koneksi database gagal: ' . mysqli_connect_error()]));
}

mysqli_set_charset($conn, 'utf8mb4');

// HITUNG USIA DARI TANGGAL LAHIR

function hitungUsia(string $tanggalLahir): int {
    $lahir = new DateTime($tanggalLahir);
    $sekarang = new DateTime();
    return (int) $lahir->diff($sekarang)->y;
}

// MENENTUKAN USIA PENSIUN BERDASARKAN JENIS JABATAN

function usiaPensiun(string $jenisJabatan): int
{

    $jenis = strtolower(trim($jenisJabatan));

    switch ($jenis) {

        case 'jpt utama':
        case 'jpt madya':
        case 'jpt pratama':
        case 'jf ahli madya':
            return 60;

        case 'administrator':
        case 'pengawas':
        case 'pelaksana':
        case 'jf ahli pertama':
        case 'jf ahli muda':
        case 'jf terampil':
        case 'jf mahir':
        case 'jf penyelia':
        case 'jf pemula':
            return 58;

        case 'jf ahli utama':
            return 65;

        default:
            return 58;
    }
}
function tentukanJenisJabatan(?string $jabatan): string
{
    $j = strtolower(trim((string) $jabatan));

    if ($j === '') return 'pelaksana';

    // JABATAN FUNGSIONAL AHLI (cek paling spesifik dahulu)
    if (strpos($j, 'ahli utama')   !== false) return 'jf ahli utama';
    if (strpos($j, 'ahli madya')   !== false) return 'jf ahli madya';
    if (strpos($j, 'ahli muda')    !== false) return 'jf ahli muda';
    if (strpos($j, 'ahli pertama') !== false) return 'jf ahli pertama';

    // JABATAN FUNGSIONAL KETERAMPILAN
    if (strpos($j, 'penyelia') !== false) return 'jf penyelia';
    if (strpos($j, 'mahir')    !== false) return 'jf mahir';
    if (strpos($j, 'pemula')   !== false) return 'jf pemula';
    if (strpos($j, 'terampil') !== false) return 'jf terampil';

    // JABATAN PIMPINAN TINGGI (JPT) - setara eselon I/II
    if (strpos($j, 'pimpinan tinggi utama')   !== false) return 'jpt utama';
    if (strpos($j, 'pimpinan tinggi madya')   !== false) return 'jpt madya';
    if (strpos($j, 'pimpinan tinggi pratama') !== false) return 'jpt pratama';
    if (strpos($j, 'sekretaris daerah')       !== false) return 'jpt madya';
    if (strpos($j, 'kepala dinas')            !== false) return 'jpt pratama';
    if (strpos($j, 'kepala badan')            !== false) return 'jpt pratama';

    // JABATAN ADMINISTRATOR - setara eselon III
    if (strpos($j, 'sekretaris dinas') !== false) return 'administrator';
    if (strpos($j, 'kepala bidang')    !== false) return 'administrator';
    if (strpos($j, 'kepala bagian')    !== false) return 'administrator';
    if (strpos($j, 'sekretaris')       !== false) return 'administrator';

    // JABATAN PENGAWAS - setara eselon IV
    if (strpos($j, 'kepala seksi')      !== false) return 'pengawas';
    if (strpos($j, 'kepala sub bagian') !== false) return 'pengawas';
    if (strpos($j, 'kepala subbagian')  !== false) return 'pengawas';
    if (strpos($j, 'kasubbag')          !== false) return 'pengawas';

    return 'pelaksana';
}

// JATUH TEMPO BULAN PENSIUN PEGAWAI
function tanggalPensiunDate(string $tanggalLahir, string $jenisJabatan = 'Pelaksana'): DateTime
{
    $lahir = new DateTime($tanggalLahir);

    $lahir->modify("+" . usiaPensiun($jenisJabatan) . " years");
    $lahir->modify("+1 month");

    return $lahir;
}

// TANGGAL PENSIUN (format tampilan)

function tanggalPensiun(string $tanggalLahir, string $jenisJabatan = 'Pelaksana'): string
{
    return tanggalPensiunDate($tanggalLahir, $jenisJabatan)->format('d M Y');
}

// HITUNG SISA BULAN MENUJU JATUH TEMPO PENSIUN

function sisaBulanPensiun(string $tanggalLahir, string $jenisJabatan = 'Pelaksana'): int
{
    $pensiun = tanggalPensiunDate($tanggalLahir, $jenisJabatan);

    $hariIni = new DateTime();

    if ($pensiun <= $hariIni) {
        return 0;
    }

    $selisih = $hariIni->diff($pensiun);

    return ($selisih->y * 12) + $selisih->m;
}

// SISA BULAN NAIK PANGKAT

function sisaBulanNaikPangkat(
    string $tanggalPangkatTerakhir,
    ?string $tanggalLahir = null,
    string $jenisJabatan = 'Pelaksana'
): int {

    $pangkat = new DateTime($tanggalPangkatTerakhir);

    $berikut = clone $pangkat;

    $berikut->modify('+4 years');

    $tahun = (int)$berikut->format('Y');

    $bulan = (int)$berikut->format('n');

    if ($bulan <= 4) {

        $target = new DateTime("$tahun-04-01");

    } elseif ($bulan <= 10) {

        $target = new DateTime("$tahun-10-01");

    } else {

        $target = new DateTime(($tahun + 1) . "-04-01");

    }

    if ($tanggalLahir) {

        $pensiun = tanggalPensiunDate($tanggalLahir, $jenisJabatan);

        if ($target >= $pensiun) {
            return 999;
        }

    }

    $hariIni = new DateTime();

    if ($target <= $hariIni) {
        return 0;
    }

    $diff = $hariIni->diff($target);

    return ($diff->y * 12) + $diff->m;
}

// TANGGAL NAIK PANGKAT

function tanggalNaikPangkat(
    string $tanggalPangkatTerakhir,
    ?string $tanggalLahir = null,
    string $jenisJabatan = 'Pelaksana'
): string {

    $pangkat = new DateTime($tanggalPangkatTerakhir);

    $berikut = clone $pangkat;

    $berikut->modify('+4 years');

    $tahun = (int)$berikut->format('Y');

    $bulan = (int)$berikut->format('n');

    if ($bulan <= 4) {

        $target = new DateTime("$tahun-04-01");

    } elseif ($bulan <= 10) {

        $target = new DateTime("$tahun-10-01");

    } else {

        $target = new DateTime(($tahun + 1) . "-04-01");

    }

    if ($tanggalLahir) {

        $pensiun = tanggalPensiunDate($tanggalLahir, $jenisJabatan);

        if ($target >= $pensiun) {
            return '-';
        }

    }

    return $target->format('d M Y');
}

// GAJI POKOK BERDASARKAN GOLONGAN

function gajiPokok(string $golongan): int {
    $tabel = [
        // Golongan I
        'I/a' => 1685700,  'I/b' => 1840800,  'I/c' => 1901200,  'I/d' => 1960300,
        // Golongan II
        'II/a' => 2184000, 'II/b' => 2385000, 'II/c' => 2467000, 'II/d' => 2552500,
        // Golongan III
        'III/a' => 2785700,'III/b' => 2903600,'III/c' => 3026400,'III/d' => 3154200,
        // Golongan IV
        'IV/a' => 3287100,'IV/b' => 3425100,'IV/c' => 3567100,'IV/d' => 3714000,'IV/e' => 3865700,
    ];

    $key = strtolower(trim($golongan));
    return $tabel[$key] ?? 0;
}

// LABEL WARNA STATUS PENSIUN

function labelPensiun(int $sisaBulan): array {
    if ($sisaBulan === 0)    return ['label' => 'Sudah Pensiun', 'class' => 'badge-merah'];
    if ($sisaBulan <= 6)     return ['label' => '≤ 6 bulan', 'class' => 'badge-merah'];
    if ($sisaBulan <= 12)    return ['label' => '≤ 1 tahun', 'class' => 'badge-oranye'];
    if ($sisaBulan <= 24)    return ['label' => '≤ 2 tahun', 'class' => 'badge-kuning'];
    return ['label' => 'Normal', 'class' => 'badge-hijau'];
}

// LABEL WARNA STATUS KENAIKAN PANGKAT

function labelPangkat(int $sisaBulan): array {
    if ($sisaBulan === 0)    return ['label' => 'Sudah Naik', 'class' => 'badge-biru'];
    if ($sisaBulan <= 3)     return ['label' => '≤ 3 bulan', 'class' => 'badge-merah'];
    if ($sisaBulan <= 6)     return ['label' => '≤ 6 bulan', 'class' => 'badge-oranye'];
    if ($sisaBulan <= 12)    return ['label' => '≤ 1 tahun', 'class' => 'badge-kuning'];
    return ['label' => 'Normal', 'class' => 'badge-hijau'];
}

// FORMAT RUPIAH

function rupiah(int $angka): string {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function isOapSuku(?string $suku): bool {
    $value = strtolower(trim((string) ($suku ?? '')));
    if ($value === '') return false;

    return $value === 'oap'
        || $value === 'papua'
        || strpos($value, 'oap') !== false
        || strpos($value, 'orang asli papua') !== false
        || strpos($value, 'papua') !== false;
}
function isOap($conn, array $row): bool {
    if (pegawaiFieldExists($conn, 'kategori_suku') && !empty($row['kategori_suku'])) {
        return strtolower(trim((string) $row['kategori_suku'])) === 'papua';
    }
    return isOapSuku($row['suku'] ?? '');
}

// RINGKASAN PENDIDIKAN

function ringkasPendidikan(?string $pendidikan): string {
    $value = strtolower(trim((string) ($pendidikan ?? '')));
    if ($value === '') return 'Belum diisi';

    $value = preg_replace('/\b(19|20)\d{2}\b/', '', $value);
    $value = trim($value);
    if ($value === '') return 'Belum diisi';

    $ringkas = preg_replace('/[\s\.\-\/]+/', '', $value);

    if (strpos($ringkas, 's3') !== false || strpos($value, 'doktor') !== false) return 'S3';
    if (strpos($ringkas, 's2') !== false || strpos($value, 'magister') !== false) return 'S2';
    if (strpos($ringkas, 's1') !== false || strpos($value, 'sarjana') !== false) return 'S1';
    if (strpos($ringkas, 'd4') !== false || strpos($ringkas, 'div') !== false) return 'D4';
    if (strpos($ringkas, 'd3') !== false || strpos($ringkas, 'diii') !== false) return 'D3';
    if (strpos($ringkas, 'd2') !== false || strpos($ringkas, 'dii') !== false) return 'D2';
    if (strpos($ringkas, 'd1') !== false || strpos($ringkas, 'di') !== false) return 'D1';
    if (strpos($value, 'aliyah') !== false) return 'Madrasah Aliyah';
    if (strpos($value, 'sma') !== false) return 'SMA';
    if (strpos($value, 'smu') !== false) return 'SMU';
    if (strpos($value, 'smk') !== false) return 'SMK';
    if (strpos($value, 'stm') !== false) return 'STM';
    if (strpos($value, 'smp') !== false || strpos($value, 'mts') !== false || strpos($value, 'tsanawiyah') !== false) return 'SMP';
    if (strpos($value, 'sd') !== false || strpos($value, ' mi') !== false || $value === 'mi' || strpos($value, 'ibtidaiyah') !== false) return 'SD';

    return ucfirst($value);
}

// CEK APAKAH KOLOM TERSEDIA PADA TABEL PEGAWAI
function pegawaiFieldExists($conn, string $field): bool {
    static $cache = [];
    if (isset($cache[$field])) return $cache[$field];

    $res = mysqli_query($conn, 'DESCRIBE pegawai');
    $exists = false;
    while ($row = mysqli_fetch_assoc($res)) {
        if (($row['Field'] ?? '') === $field) {
            $exists = true;
            break;
        }
    }
    $cache[$field] = $exists;
    return $exists;
}
function kolomHilang($conn, array $wantedCols): array {
    $hilang = [];
    foreach ($wantedCols as $col) {
        if (!pegawaiFieldExists($conn, $col)) $hilang[] = $col;
    }
    return $hilang;
}
function simpanPegawai($conn, array $data, ?int $id = null): array {
    $kolomDipakai = [];
    $nilaiDipakai = [];
    $dilewati = [];

    foreach ($data as $kolom => $nilai) {
        if (pegawaiFieldExists($conn, $kolom)) {
            $kolomDipakai[] = $kolom;
            $nilaiDipakai[] = $nilai;
        } else {
            $dilewati[] = $kolom;
        }
    }

    if (empty($kolomDipakai)) {
        return ['success' => false, 'error' => 'Tidak ada kolom valid untuk disimpan.', 'id' => null, 'dilewati' => $dilewati];
    }

    if ($id === null) {
        // INSERT
        $placeholders = implode(',', array_fill(0, count($kolomDipakai), '?'));
        $sql = 'INSERT INTO pegawai (' . implode(',', $kolomDipakai) . ') VALUES (' . $placeholders . ')';
    } else {
        // UPDATE
        $setParts = array_map(fn($k) => "$k=?", $kolomDipakai);
        $sql = 'UPDATE pegawai SET ' . implode(',', $setParts) . ' WHERE id=?';
        $nilaiDipakai[] = $id;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return ['success' => false, 'error' => 'Query gagal disiapkan: ' . $conn->error, 'id' => null, 'dilewati' => $dilewati];
    }

    $types = str_repeat('s', count($kolomDipakai)) . ($id === null ? '' : 'i');

    $bindRefs = [];
    foreach ($nilaiDipakai as $k => $v) {
        $bindRefs[$k] = &$nilaiDipakai[$k];
    }
    $stmt->bind_param($types, ...$bindRefs);

    if (!$stmt->execute()) {
        return ['success' => false, 'error' => 'Gagal menyimpan: ' . $stmt->error, 'id' => null, 'dilewati' => $dilewati];
    }

    return [
        'success'  => true,
        'error'    => null,
        'id'       => $id ?? $stmt->insert_id,
        'dilewati' => $dilewati,
    ];
}
function pastikanTabelChecklist($conn): void {
    static $sudah = false;
    if ($sudah) return;
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS checklist_pangkat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            pegawai_id INT NOT NULL,
            item_key VARCHAR(50) NOT NULL,
            checked TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_pegawai_item (pegawai_id, item_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $sudah = true;
}
function checklistItemsMaster(): array {
    return [
        'item_01' => 'Nota Usul',
        'item_02' => 'Surat Pengantar',
        'item_03' => 'Surat Penunjukan PLT',
        'item_04' => 'Salinan sah SK pangkat terakhir',
        'item_05' => 'Salinan sah SK jabatan terakhir',
        'item_06' => 'Asli PAK',
        'item_07' => 'Asli Klarifikasi PAK',
        'item_08' => 'Berita Acara/Sumpah/Janji/Pelantikan Jabatan',
        'item_09' => 'Surat Perintah Melaksanakan Tugas (SPMT)',
        'item_10' => 'Surat Pernyataan Pelantikan',
        'item_11' => 'Rekomendasi dari KASN tentang hasil seleksi terbuka JPT',
        'item_12' => 'Salinan sah SKP dan PPK 1 atau 2 tahun terakhir *',
        'item_13' => 'Salinan sah tanda lulus ujian dinas tingkat I atau II *',
        'item_14' => 'Surat Keputusan penetapan prestasi kerja luar biasa baiknya',
        'item_15' => 'Salinan sah Kepres tentang penemuan baru yang bermanfaat bagi negara Badan/Lembaga',
        'item_16' => 'Salinan sah SK pemberhentikan dari jabatan organik',
        'item_17' => 'Salinan sah dari STTB/Ijazah/Diploma',
        'item_18' => 'Salinan sah STL ujian KP penyesuaian ijazah',
        'item_19' => 'Surat keterangan PPK tentang uraian tugas yang dibebankan',
        'item_20' => 'Salinan sah perintah untuk tugas / ijin belajar *',
        'item_21' => 'Salinan sah keputusan penugasan di luar instansi induk',
        'item_22' => 'Salinan sah SK CPNS dan SK PNS untuk Kenaikan Pangkat pertama',
    ];
}
function jenisKpItemsMaster(): array {
    return [
        'jkp_reguler'                      => 'Reguler',
        'jkp_penemuan_baru'                => 'Penemuan Baru',
        'jkp_dpk_js_jft'                   => 'DPK diangkat JS dan JFT',
        'jkp_jabatan_struktural'           => 'Jabatan Struktural (JS)',
        'jkp_pengangkatan_kepres'          => 'Jabatan pengangkatan KEPRES',
        'jkp_tugas_belajar'                => 'Melaksanakan Tugas Belajar',
        'jkp_jft'                          => 'Jabatan Fungsional Tertentu (JFT)',
        'jkp_ijazah_js'                    => 'Memperoleh Ijazah JS',
        'jkp_selesai_tugas_belajar'        => 'Selesai/lulus Tugas Belajar',
        'jkp_kplb'                         => 'KPLB',
        'jkp_ijazah_jft'                   => 'Memperoleh Ijazah JFT',
        'jkp_pejabat_negara_diberhentikan' => 'Pejabat Negara diberhentikan dari',
    ];
}

function getChecklistPegawai($conn, int $pegawaiId): array {
    pastikanTabelChecklist($conn);
    $status = [];
    foreach (array_keys(checklistItemsMaster()) as $key) $status[$key] = false;

    $stmt = $conn->prepare("SELECT item_key, checked FROM checklist_pangkat WHERE pegawai_id=?");
    $stmt->bind_param('i', $pegawaiId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($status[$row['item_key']])) {
            $status[$row['item_key']] = ((int)$row['checked']) === 1;
        }
    }
    return $status;
}

function isChecklistLengkap(array $status): bool {
    foreach (checklistItemsMaster() as $key => $label) {
        if (empty($status[$key])) return false;
    }
    return true;
}

function simpanChecklist($conn, int $pegawaiId, array $checkedKeys): void {
    pastikanTabelChecklist($conn);
    $stmt = $conn->prepare("
        INSERT INTO checklist_pangkat (pegawai_id, item_key, checked)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE checked = VALUES(checked)
    ");
    foreach (array_keys(checklistItemsMaster()) as $key) {
        $checked = in_array($key, $checkedKeys, true) ? 1 : 0;
        $stmt->bind_param('isi', $pegawaiId, $key, $checked);
        $stmt->execute();
    }
}

function getJenisKpPegawai($conn, int $pegawaiId): array {
    pastikanTabelChecklist($conn);
    $status = [];
    foreach (array_keys(jenisKpItemsMaster()) as $key) $status[$key] = false;

    $stmt = $conn->prepare("SELECT item_key, checked FROM checklist_pangkat WHERE pegawai_id=?");
    $stmt->bind_param('i', $pegawaiId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($status[$row['item_key']])) {
            $status[$row['item_key']] = ((int) $row['checked']) === 1;
        }
    }
    return $status;
}

function simpanJenisKp($conn, int $pegawaiId, array $checkedKeys): void {
    pastikanTabelChecklist($conn);
    $stmt = $conn->prepare("
        INSERT INTO checklist_pangkat (pegawai_id, item_key, checked)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE checked = VALUES(checked)
    ");
    foreach (array_keys(jenisKpItemsMaster()) as $key) {
        $checked = in_array($key, $checkedKeys, true) ? 1 : 0;
        $stmt->bind_param('isi', $pegawaiId, $key, $checked);
        $stmt->execute();
    }
}

// CEK KELENGKAPAN BERKAS UNTUK KENAIKAN PANGKAT

function syaratNaikPangkat($conn, array $row): array {
    $catatan = [];
    $lengkap = true;

    if (trim((string) ($row['tanggal_pangkat_terakhir'] ?? '')) === '') {
        $lengkap = false;
        $catatan[] = 'Tanggal pangkat terakhir belum diisi';
    }

    if (pegawaiFieldExists($conn, 'skp_2_tahun')) {
        if (trim((string) ($row['skp_2_tahun'] ?? '')) === '') {
            $lengkap = false;
            $catatan[] = 'SKP 2 tahun terakhir belum lengkap';
        }
    } else {
        $lengkap = false;
        $catatan[] = 'Verifikasi SKP 2 tahun terakhir masih perlu dilakukan';
    }

    $golongan = strtoupper(trim((string) ($row['golongan'] ?? '')));
    if ($golongan === 'III/D') {
        $pendidikan = strtolower(trim((string) ($row['pendidikan_terakhir'] ?? '')));
        $adaS2 = strpos($pendidikan, 's2') !== false || strpos($pendidikan, 'magister') !== false || strpos($pendidikan, 's3') !== false;
        if (!$adaS2) {
            $catatan[] = 'Untuk naik ke IV/a: perlu STLUD II / Diklatpim III / Diklat Administrator, atau ijazah S2 (belum terverifikasi dari data pendidikan)';
            $lengkap = false;
        }
    }

    return ['lengkap' => $lengkap, 'catatan' => $catatan];
}
function dokumenKenaikanPangkat(): array {
    return [
        'SK Kenaikan Pangkat terakhir',
        'SK Jabatan Struktural (bagi yang menduduki jabatan struktural)',
        'Surat Pernyataan Pelantikan (TMT pelantikan)',
        'SKP 2 tahun terakhir berpredikat minimal Baik',
        'STLUD Tingkat II / Sertifikat Diklatpim III / Diklat Administrator / Ijazah S2 (khusus kenaikan III/d → IV/a)',
        'KARPEG',
        'Ijazah pendidikan terakhir',
        'Dokumen lain sesuai SE internal instansi (mis. surat keterangan kekosongan pejabat penilai)',
    ];
}

function getNotifikasiPensiun($conn, int $batasBulan = 12): array {
    $hasil = [];
    $q = mysqli_query($conn, "SELECT * FROM pegawai WHERE tanggal_lahir IS NOT NULL");
    if (!$q) return $hasil;

    while ($row = mysqli_fetch_assoc($q)) {
        if (empty($row['tanggal_lahir']) || $row['tanggal_lahir'] === '0000-00-00') {
            continue;
        }
        $jenisJabatan = !empty($row['jenis_jabatan']) ? $row['jenis_jabatan'] : tentukanJenisJabatan($row['jabatan'] ?? '');
        try {
            $sisa = sisaBulanPensiun($row['tanggal_lahir'], $jenisJabatan);
        } catch (\Throwable $e) {
            continue;
        }
        if ($sisa <= $batasBulan) {
            $row['sisa_bulan']  = $sisa;
            $row['tgl_pensiun'] = tanggalPensiun($row['tanggal_lahir'], $jenisJabatan);
            $hasil[] = $row;
        }
    }

    usort($hasil, fn($a, $b) => $a['sisa_bulan'] <=> $b['sisa_bulan']);
    return $hasil;
}
function getNotifikasiKenaikanPangkat($conn, int $batasBulan = 6): array {
    $hasil = [];
    $q = mysqli_query($conn, "SELECT * FROM pegawai WHERE tanggal_pangkat_terakhir IS NOT NULL");
    if (!$q) return $hasil;

    while ($row = mysqli_fetch_assoc($q)) {
        if (empty($row['tanggal_pangkat_terakhir']) || $row['tanggal_pangkat_terakhir'] === '0000-00-00') {
            continue;
        }
        $jenisJabatan = !empty($row['jenis_jabatan']) ? $row['jenis_jabatan'] : tentukanJenisJabatan($row['jabatan'] ?? '');
        try {
            $sisa = sisaBulanNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null, $jenisJabatan);
        } catch (\Throwable $e) {
            continue;
        }
        if ($sisa !== 999 && $sisa <= $batasBulan) {
            $syarat = syaratNaikPangkat($conn, $row);
            $row['sisa_bulan'] = $sisa;
            $row['tgl_naik']   = tanggalNaikPangkat($row['tanggal_pangkat_terakhir'], $row['tanggal_lahir'] ?? null, $jenisJabatan);
            $row['syarat']     = $syarat;
            $hasil[] = $row;
        }
    }

    usort($hasil, fn($a, $b) => $a['sisa_bulan'] <=> $b['sisa_bulan']);
    return $hasil;
}

function getRingkasanNotifikasi($conn): array {
    $pensiun = getNotifikasiPensiun($conn, 12);
    $pangkat = getNotifikasiKenaikanPangkat($conn, 6);

    $pangkatBelumLengkap = array_values(array_filter($pangkat, fn($r) => !$r['syarat']['lengkap']));

    return [
        'pensiun'              => $pensiun,
        'pangkat'              => $pangkat,
        'pangkat_belum_lengkap'=> $pangkatBelumLengkap,
        'total'                => count($pensiun) + count($pangkat),
    ];
}
function normalisasiGolongan(?string $value): ?string {
    if ($value === null) return null;
    $value = trim($value, " \t\n\r\0\x0B()");
    if ($value === '') return null;

    $value = preg_replace('/\s*\/\s*/', '/', $value);  // rapikan spasi di sekitar "/"
    $value = preg_replace('/\s+/', '', $value);          // buang sisa spasi lain

    // Format tanpa garis miring, misal "IIIa", "IVb" -> sisipkan "/"
    if (preg_match('/^(I|II|III|IV)([A-Ea-e])$/i', $value, $m)) {
        $value = $m[1] . '/' . $m[2];
    }

    if (strpos($value, '/') !== false) {
        [$romawi, $ruang] = explode('/', $value, 2);
        $value = strtoupper($romawi) . '/' . strtolower($ruang);
    } else {
        $value = strtoupper($value);
    }

    return $value;
}

function e($str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function normalisasiTanggal($value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $value = ltrim($value, "'\"");
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $value = str_replace(['/', '\\', '.'], '-', $value);
    $value = preg_replace('/\s*-\s*/', '-', $value);  // rapikan spasi di sekitar "-"
    $value = preg_replace('/\s+/', '-', $value);       // sisa spasi (tanpa dash) jadi "-"
    $value = preg_replace('/-+/', '-', $value);        // gabungkan dash berulang jadi satu
    $value = trim($value, '-');

    if (preg_match('/^(\d{4})-(\d{1,2})$/', $value, $matches)) {
        return sprintf('%04d-%02d-01', (int)$matches[1], (int)$matches[2]);
    }

    if (preg_match('/^(\d{1,2})-(\d{4})$/', $value, $matches)) {
        return sprintf('%04d-%02d-01', (int)$matches[2], (int)$matches[1]);
    }

    if (preg_match('/^00\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
        return sprintf('%04d-%02d-01', (int)$matches[2], (int)$matches[1]);
    }

    if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})$/', $value, $matches)) {
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        if ($year < 100) {
            $year += $year >= 70 ? 1900 : 2000;
        }
        if ($day === 0 && $month >= 1 && $month <= 12 && $year >= 1) {
            return sprintf('%04d-%02d-01', $year, $month);
        }
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $matches)) {
        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];
        if ($day === 0 && $month >= 1 && $month <= 12 && $year >= 1) {
            return sprintf('%04d-%02d-01', $year, $month);
        }
        if (checkdate($month, $day, $year)) {
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

function formatTanggalMasukDisplay(?string $value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '-';
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3] === '00' ? '01' : $matches[3];
        return sprintf('%02d/%02d/%04d', (int)$day, (int)$month, (int)$year);
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    return date('d/m/Y', $timestamp);
}

function formatTanggalMasukInput(?string $value): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }

    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        $year = $matches[1];
        $month = $matches[2];
        $day = $matches[3] === '00' ? '01' : $matches[3];
        return sprintf('%02d/%02d/%04d', (int)$day, (int)$month, (int)$year);
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    return date('d/m/Y', $timestamp);
}
?>