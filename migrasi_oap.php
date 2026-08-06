<?php

require 'auth.php';
include 'koneksi.php';
requireSuperAdmin();

header('Content-Type: text/plain; charset=UTF-8');

if (pegawaiFieldExists($conn, 'kategori_suku')) {
    echo "Kolom 'kategori_suku' sudah ada. Tidak ada yang perlu diubah.\n";
    exit;
}

$sql = "ALTER TABLE pegawai ADD COLUMN kategori_suku ENUM('Papua','Non-Papua') NULL AFTER suku";

if (mysqli_query($conn, $sql)) {
    echo "Berhasil menambahkan kolom 'kategori_suku' pada tabel pegawai.\n";
    echo "Nilai yang valid: 'Papua' (OAP) atau 'Non-Papua'.\n";
    echo "Silakan isi lewat menu Tambah/Edit Pegawai.\n";
    echo "\nCatatan: untuk data yang sudah ada dan belum diisi kolom ini,\n";
    echo "sistem akan tetap mencoba menebak dari teks suku (mis. yang sudah\n";
    echo "tertulis 'Papua') sampai kolom ini diisi manual.\n";
} else {
    echo "Gagal menambahkan kolom: " . mysqli_error($conn) . "\n";
}