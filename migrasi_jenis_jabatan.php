<?php

require 'auth.php';
include 'koneksi.php';
requireSuperAdmin();

header('Content-Type: text/plain; charset=UTF-8');

if (!pegawaiFieldExists($conn, 'jenis_jabatan')) {
    $sql = "ALTER TABLE pegawai ADD COLUMN jenis_jabatan VARCHAR(30) NOT NULL DEFAULT 'pelaksana' AFTER jabatan";
    if (mysqli_query($conn, $sql)) {
        echo "Kolom 'jenis_jabatan' berhasil ditambahkan.\n";
    } else {
        echo "Gagal menambahkan kolom: " . mysqli_error($conn) . "\n";
        exit;
    }
} else {
    echo "Kolom 'jenis_jabatan' sudah ada.\n";
}

$result = mysqli_query($conn, "SELECT id, jabatan FROM pegawai WHERE jenis_jabatan = '' OR jenis_jabatan IS NULL");
$jumlahDiupdate = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tebakan = tentukanJenisJabatan($row['jabatan'] ?? '');
        $stmt = $conn->prepare("UPDATE pegawai SET jenis_jabatan = ? WHERE id = ?");
        $stmt->bind_param('si', $tebakan, $row['id']);
        if ($stmt->execute()) {
            $jumlahDiupdate++;
        }
    }
}

echo "Backfill selesai. $jumlahDiupdate baris pegawai diisi otomatis berdasarkan teks jabatannya.\n";
echo "Silakan cek ulang & sesuaikan manual lewat menu Edit Pegawai kalau ada yang kurang tepat.\n";
