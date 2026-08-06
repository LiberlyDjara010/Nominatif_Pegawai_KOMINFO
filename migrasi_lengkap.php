<?php

require 'auth.php';
include 'koneksi.php';
requireSuperAdmin(); // migrasi database hanya boleh dijalankan Super Admin

header('Content-Type: text/plain; charset=UTF-8');

echo "=== MIGRASI LENGKAP: Sistem Nominatif Pegawai ===\n\n";

$kolomBaru = [
    'skp_2_tahun'         => "VARCHAR(20) NULL AFTER tanggal_pangkat_terakhir",
    'kategori_suku'       => "ENUM('Papua','Non-Papua') NULL AFTER suku",
    'jenis_jabatan'       => "VARCHAR(30) NOT NULL DEFAULT 'pelaksana' AFTER jabatan",

    'pendidikan_jurusan'  => "VARCHAR(150) NULL AFTER pendidikan_terakhir",
    'unit_organisasi'     => "VARCHAR(150) NULL AFTER jabatan",
    'status_jabatan'      => "VARCHAR(30) NULL COMMENT 'Struktural / Fungsional / Pelaksana' AFTER unit_organisasi",
    'masa_kerja_tahun'    => "TINYINT UNSIGNED NULL AFTER tanggal_pangkat_terakhir",
    'masa_kerja_bulan'    => "TINYINT UNSIGNED NULL AFTER masa_kerja_tahun",
    'sk_pejabat'          => "VARCHAR(150) NULL COMMENT 'Pejabat penetap SK pangkat/golongan terakhir' AFTER masa_kerja_bulan",
    'sk_nomor'            => "VARCHAR(100) NULL AFTER sk_pejabat",
    'sk_tanggal'          => "DATE NULL COMMENT 'Tanggal SK pangkat/golongan ditetapkan' AFTER sk_nomor",
    'sk_jabatan_pejabat'  => "VARCHAR(150) NULL COMMENT 'Pejabat penetap SK jabatan terakhir' AFTER sk_tanggal",
    'sk_jabatan_nomor'    => "VARCHAR(100) NULL AFTER sk_jabatan_pejabat",
    'sk_jabatan_tanggal'  => "DATE NULL COMMENT 'Tanggal SK jabatan ditetapkan' AFTER sk_jabatan_nomor",
    'keterangan'          => "TEXT NULL",
];

$ditambah = 0;
$dilewati = 0;

foreach ($kolomBaru as $kolom => $definisi) {
    if (pegawaiFieldExists($conn, $kolom)) {
        echo "- Kolom '$kolom' sudah ada, dilewati.\n";
        $dilewati++;
        continue;
    }

    $sql = "ALTER TABLE pegawai ADD COLUMN $kolom $definisi";
    if (mysqli_query($conn, $sql)) {
        echo "+ Kolom '$kolom' berhasil ditambahkan.\n";
        $ditambah++;
    } else {
        echo "! Gagal menambahkan kolom '$kolom': " . mysqli_error($conn) . "\n";
    }
}

$cekEnum = mysqli_query($conn, "SHOW COLUMNS FROM pegawai LIKE 'status_kepegawaian'");
$kolomEnum = $cekEnum ? mysqli_fetch_assoc($cekEnum) : null;
if ($kolomEnum && stripos($kolomEnum['Type'], 'PPPK') === false) {
    $sql = "ALTER TABLE pegawai MODIFY status_kepegawaian ENUM('CPNS','PNS','PPPK') DEFAULT 'PNS'";
    if (mysqli_query($conn, $sql)) {
        echo "+ Kolom 'status_kepegawaian' diperluas: sekarang mendukung PPPK juga.\n";
    } else {
        echo "! Gagal memperluas enum status_kepegawaian: " . mysqli_error($conn) . "\n";
    }
} else if ($kolomEnum) {
    echo "- Kolom 'status_kepegawaian' sudah mendukung PPPK, dilewati.\n";
}

$result = mysqli_query($conn, "SELECT id, jabatan FROM pegawai WHERE jenis_jabatan = '' OR jenis_jabatan IS NULL");
$jumlahDiupdate = 0;
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tebakan = tentukanJenisJabatan($row['jabatan'] ?? '');
        $stmt = $conn->prepare("UPDATE pegawai SET jenis_jabatan = ? WHERE id = ?");
        $stmt->bind_param('si', $tebakan, $row['id']);
        if ($stmt->execute()) $jumlahDiupdate++;
    }
}
if ($jumlahDiupdate > 0) {
    echo "\nBackfill jenis_jabatan: $jumlahDiupdate baris diisi otomatis dari teks jabatan.\n";
}

// Tabel checklist kelengkapan berkas kenaikan pangkat
pastikanTabelChecklist($conn);
echo "\nTabel 'checklist_pangkat' dipastikan ada (untuk fitur checklist kelengkapan berkas kenaikan pangkat).\n";

$golonganDirapikan = 0;
$resultGol = mysqli_query($conn, "SELECT id, golongan FROM pegawai WHERE golongan IS NOT NULL AND golongan != ''");
if ($resultGol) {
    while ($row = mysqli_fetch_assoc($resultGol)) {
        $bersih = normalisasiGolongan($row['golongan']) ?? '';
        if ($bersih !== $row['golongan']) {
            $stmt = $conn->prepare("UPDATE pegawai SET golongan = ? WHERE id = ?");
            $stmt->bind_param('si', $bersih, $row['id']);
            if ($stmt->execute()) $golonganDirapikan++;
        }
    }
}
if ($golonganDirapikan > 0) {
    echo "\nRapikan golongan: $golonganDirapikan baris dirapikan formatnya (mis. \"(IV/d)\" -> \"IV/d\").\n";
} else {
    echo "\nData golongan sudah rapi, tidak ada yang perlu diubah.\n";
}

echo "\n=== SELESAI ===\n";
echo "$ditambah kolom baru ditambahkan, $dilewati kolom sudah ada sebelumnya.\n";
echo "Silakan cek ulang halaman Tambah/Edit Pegawai -- error fatal seharusnya sudah tidak muncul lagi.\n";
