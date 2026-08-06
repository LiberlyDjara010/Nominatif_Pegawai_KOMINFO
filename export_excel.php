<?php
require 'auth.php';
requireCanManageData();
include 'koneksi.php';
include 'xlsx_writer.php';

$query = mysqli_query($conn, "SELECT * FROM pegawai ORDER BY nama ASC");

if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

$rows = [];
while ($row = mysqli_fetch_assoc($query)) {
    $rows[] = $row;
}

$totalLaki   = 0;
$totalWanita = 0;
foreach ($rows as $r) {
    $jkVal = strtolower(trim($r['jenis_kelamin'] ?? ''));
    if (in_array($jkVal, ['pria', 'l', 'laki-laki', 'laki laki'], true)) $totalLaki++;
    if (in_array($jkVal, ['wanita', 'p', 'perempuan'], true)) $totalWanita++;
}

$sheet = new XlsxWriter('Data Pegawai');

// Kolom (24 total) mengikuti format resmi "DAFTAR NOMINATIF PEGAWAI NEGERI SIPIL":
// NO, NAMA, NIP, TEMPAT LAHIR, TANGGAL LAHIR, L, P, AGAMA, PENDIDIKAN, JURUSAN,
// PANGKAT TERAKHIR, GOL, MASA KERJA THN, MASA KERJA BLN,
// SK PANGKAT: Pejabat/No.SK/Tanggal, JABATAN TERAKHIR,
// SK JABATAN: Pejabat/No.SK/Tanggal, STATUS CPNS, STATUS PNS, PAPUA/NON PAPUA
$sheet->setColWidth(1, 5);    // NO
$sheet->setColWidth(2, 32);   // NAMA
$sheet->setColWidth(3, 22);   // NIP
$sheet->setColWidth(4, 16);   // TEMPAT LAHIR
$sheet->setColWidth(5, 13);   // TANGGAL LAHIR
$sheet->setColWidth(6, 5);    // L
$sheet->setColWidth(7, 5);    // P
$sheet->setColWidth(8, 10);   // AGAMA
$sheet->setColWidth(9, 12);   // PENDIDIKAN
$sheet->setColWidth(10, 22);  // JURUSAN
$sheet->setColWidth(11, 24);  // PANGKAT TERAKHIR
$sheet->setColWidth(12, 8);   // GOL
$sheet->setColWidth(13, 6);   // MASA KERJA THN
$sheet->setColWidth(14, 6);   // MASA KERJA BLN
$sheet->setColWidth(15, 16);  // SK PANGKAT Pejabat
$sheet->setColWidth(16, 18);  // SK PANGKAT No.
$sheet->setColWidth(17, 13);  // SK PANGKAT Tanggal
$sheet->setColWidth(18, 32);  // JABATAN TERAKHIR
$sheet->setColWidth(19, 16);  // SK JABATAN Pejabat
$sheet->setColWidth(20, 18);  // SK JABATAN No.
$sheet->setColWidth(21, 13);  // SK JABATAN Tanggal
$sheet->setColWidth(22, 8);   // CPNS
$sheet->setColWidth(23, 8);   // PNS
$sheet->setColWidth(24, 12);  // PAPUA/NON PAPUA

$lastCol = 24;

// BARIS JUDUL (baris 1-3)
$sheet->addRow([['v' => 'DAFTAR NOMINATIF PEGAWAI NEGERI SIPIL', 'style' => 1]] + array_fill(1, $lastCol - 1, ['v' => '', 'style' => 1]));
$sheet->addRow([['v' => 'DILINGKUNGAN INSTANSI DINAS KOMUNIKASI DAN INFORMATIKA PROVINSI PAPUA', 'style' => 1]] + array_fill(1, $lastCol - 1, ['v' => '', 'style' => 1]));
$sheet->addRow([['v' => 'TAHUN ' . date('Y'), 'style' => 1]] + array_fill(1, $lastCol - 1, ['v' => '', 'style' => 1]));
$sheet->merge('A1:X1');
$sheet->merge('A2:X2');
$sheet->merge('A3:X3');

// BARIS KOSONG PEMISAH (baris 4)
$sheet->addRow(array_fill(0, $lastCol, ['v' => '', 'style' => 0]));

// BARIS HEADER KOLOM (baris 5 & 6)
$sheet->addRow([
    ['v' => 'NO', 'style' => 2],
    ['v' => 'NAMA', 'style' => 2],
    ['v' => 'NIP', 'style' => 2],
    ['v' => 'TEMPAT LAHIR', 'style' => 2],
    ['v' => 'TANGGAL LAHIR', 'style' => 2],
    ['v' => 'JENIS KELAMIN', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'AGAMA', 'style' => 2],
    ['v' => 'PENDIDIKAN TERAKHIR', 'style' => 2],
    ['v' => 'JURUSAN', 'style' => 2],
    ['v' => 'PANGKAT TERAKHIR', 'style' => 2],
    ['v' => 'GOL', 'style' => 2],
    ['v' => 'MASA KERJA/GOL', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'SURAT KEPUTUSAN (PANGKAT)', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'JABATAN TERAKHIR', 'style' => 2],
    ['v' => 'SURAT KEPUTUSAN (JABATAN)', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'STATUS KEPEGAWAIAN', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'PAPUA/NON PAPUA', 'style' => 2],
]);
$sheet->addRow([
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'L', 'style' => 2],
    ['v' => 'P', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'THN', 'style' => 2],
    ['v' => 'BLN', 'style' => 2],
    ['v' => 'Pejabat', 'style' => 2],
    ['v' => 'No. SK', 'style' => 2],
    ['v' => 'Tanggal', 'style' => 2],
    ['v' => '', 'style' => 2],
    ['v' => 'Pejabat', 'style' => 2],
    ['v' => 'No. SK', 'style' => 2],
    ['v' => 'Tanggal', 'style' => 2],
    ['v' => 'CPNS', 'style' => 2],
    ['v' => 'PNS', 'style' => 2],
    ['v' => '', 'style' => 2],
]);

$sheet->merge('A5:A6');
$sheet->merge('B5:B6');
$sheet->merge('C5:C6');
$sheet->merge('D5:D6');
$sheet->merge('E5:E6');
$sheet->merge('F5:G5');
$sheet->merge('H5:H6');
$sheet->merge('I5:I6');
$sheet->merge('J5:J6');
$sheet->merge('K5:K6');
$sheet->merge('L5:L6');
$sheet->merge('M5:N5');
$sheet->merge('O5:Q5');
$sheet->merge('R5:R6');
$sheet->merge('S5:U5');
$sheet->merge('V5:W5');
$sheet->merge('X5:X6');

// DATA PEGAWAI
$no = 1;
foreach ($rows as $r) {
    $jk       = strtolower(trim($r['jenis_kelamin'] ?? ''));
    $isLaki   = in_array($jk, ['pria', 'l', 'laki-laki', 'laki laki'], true);
    $isWanita = in_array($jk, ['wanita', 'p', 'perempuan'], true);

    $tglLahir = '';
    if (!empty($r['tanggal_lahir']) && $r['tanggal_lahir'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['tanggal_lahir']);
        $tglLahir = $dt ? $dt->format('d/m/Y') : $r['tanggal_lahir'];
    }
    $skTanggal = '';
    if (!empty($r['sk_tanggal']) && $r['sk_tanggal'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['sk_tanggal']);
        $skTanggal = $dt ? $dt->format('d/m/Y') : $r['sk_tanggal'];
    }
    $skJabatanTanggal = '';
    if (!empty($r['sk_jabatan_tanggal']) && $r['sk_jabatan_tanggal'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['sk_jabatan_tanggal']);
        $skJabatanTanggal = $dt ? $dt->format('d/m/Y') : $r['sk_jabatan_tanggal'];
    }

    $statusUpper = strtoupper(trim($r['status_kepegawaian'] ?? ''));
    $isCpns = $statusUpper === 'CPNS';
    $isPns  = $statusUpper === 'PNS' || (!$isCpns && $statusUpper !== 'PPPK');

    $kategoriSuku = $r['kategori_suku'] ?? null;
    $oap = $kategoriSuku === 'Papua' ? 'PAPUA' : ($kategoriSuku === 'Non-Papua' ? 'NON PAPUA' : '');

    $pangkatCell = strtoupper($r['pangkat_terakhir'] ?? '');
    if (!empty($r['tanggal_pangkat_terakhir']) && $r['tanggal_pangkat_terakhir'] !== '0000-00-00') {
        $dt = DateTime::createFromFormat('Y-m-d', $r['tanggal_pangkat_terakhir']);
        $tmt = $dt ? $dt->format('d-m-Y') : $r['tanggal_pangkat_terakhir'];
        $pangkatCell .= ($pangkatCell !== '' ? "\nTMT " : 'TMT ') . $tmt;
    }

    $sheet->addRow([
        ['v' => $no++, 'type' => 'n', 'style' => 4],
        ['v' => strtoupper($r['nama'] ?? ''), 'style' => 3],
        ['v' => (string) ($r['nip'] ?? ''), 'style' => 3],
        ['v' => $r['tempat_lahir'] ?? '', 'style' => 3],
        ['v' => $tglLahir, 'style' => 4],
        ['v' => $isLaki ? 'L' : '', 'style' => 4],
        ['v' => $isWanita ? 'P' : '', 'style' => 4],
        ['v' => $r['agama'] ?? '', 'style' => 3],
        ['v' => $r['pendidikan_terakhir'] ?? '', 'style' => 3],
        ['v' => $r['pendidikan_jurusan'] ?? '', 'style' => 3],
        ['v' => $pangkatCell, 'style' => 3],
        ['v' => $r['golongan'] ?? '', 'style' => 4],
        ['v' => $r['masa_kerja_tahun'] !== null ? (int) $r['masa_kerja_tahun'] : '', 'style' => 4],
        ['v' => $r['masa_kerja_bulan'] !== null ? (int) $r['masa_kerja_bulan'] : '', 'style' => 4],
        ['v' => $r['sk_pejabat'] ?? '', 'style' => 3],
        ['v' => $r['sk_nomor'] ?? '', 'style' => 3],
        ['v' => $skTanggal, 'style' => 4],
        ['v' => strtoupper($r['jabatan'] ?? ''), 'style' => 3],
        ['v' => $r['sk_jabatan_pejabat'] ?? '', 'style' => 3],
        ['v' => $r['sk_jabatan_nomor'] ?? '', 'style' => 3],
        ['v' => $skJabatanTanggal, 'style' => 4],
        ['v' => $isCpns ? 'V' : '', 'style' => 4],
        ['v' => (!$isCpns && $isPns) ? 'V' : '', 'style' => 4],
        ['v' => $oap, 'style' => 4],
    ]);
}

// BARIS JUMLAH
$sheet->addRow([
    ['v' => 'JUMLAH', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => $totalLaki, 'type' => 'n', 'style' => 5],
    ['v' => $totalWanita, 'type' => 'n', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
    ['v' => '', 'style' => 5],
]);
$totalRow = count($rows) + 7;
$sheet->merge('A' . $totalRow . ':E' . $totalRow);
$sheet->merge('H' . $totalRow . ':X' . $totalRow);

$sheet->output('DATA_PEGAWAI_KOMINFO_' . date('Ymd_His') . '.xlsx');
