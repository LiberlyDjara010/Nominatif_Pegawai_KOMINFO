<?php

require 'auth.php';
include 'koneksi.php';
requireSuperAdmin(); // migrasi database hanya boleh dijalankan Super Admin

header('Content-Type: text/plain; charset=UTF-8');

if (pegawaiFieldExists($conn, 'skp_2_tahun')) {
    echo "Kolom 'skp_2_tahun' sudah ada. Tidak ada yang perlu diubah.\n";
    exit;
}

$sql = "ALTER TABLE pegawai ADD COLUMN skp_2_tahun VARCHAR(20) NULL AFTER tanggal_pangkat_terakhir";

if (mysqli_query($conn, $sql)) {
    echo "Berhasil menambahkan kolom 'skp_2_tahun' pada tabel pegawai.\n";
    echo "Nilai yang valid: 'Baik' (SKP 2 tahun terakhir berpredikat baik/lebih) atau kosong (belum diverifikasi).\n";
    echo "Silakan isi lewat menu Tambah/Edit Pegawai.\n";
} else {
    echo "Gagal menambahkan kolom: " . mysqli_error($conn) . "\n";
}
