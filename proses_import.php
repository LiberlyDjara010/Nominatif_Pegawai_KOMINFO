<?php

require 'auth.php';
include 'koneksi.php';

$berhasil = 0;
$gagal = 0;
$errorList = [];

function deteksiDelimitCsv(string $filePath): string {
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        return ';';
    }

    $sample = '';
    for ($i = 0; $i < 5; $i++) {
        $line = fgets($handle);
        if ($line === false) {
            break;
        }
        $sample .= $line;
    }

    fclose($handle);

    $sample = str_replace(["\r", "\n"], '', $sample);
    $countSemicolon = substr_count($sample, ';');
    $countComma = substr_count($sample, ',');
    $countTab = substr_count($sample, "\t");

    if ($countTab > $countComma && $countTab > $countSemicolon) {
        return "\t";
    }

    if ($countSemicolon > $countComma) {
        return ';';
    }

    return ',';
}
function cariPetaKolomBKD(array $row): ?array {
    $peta = [];
    $nomorBerikutnya = 1;
    foreach ($row as $idx => $val) {
        $val = trim((string) $val);
        if ($val === (string) $nomorBerikutnya) {
            $peta[$nomorBerikutnya] = $idx;
            $nomorBerikutnya++;
            if ($nomorBerikutnya > 24) break;
        }
    }
    // Hanya dianggap valid kalau berhasil menemukan urutan LENGKAP 1..24.
    return count($peta) === 24 ? $peta : null;
}

function deteksiFormatBKD(string $filePath, string $delimiter): bool {
    $handle = fopen($filePath, 'r');
    if ($handle === false) return false;

    for ($i = 0; $i < 15; $i++) {
        $row = fgetcsv($handle, 5000, $delimiter);
        if ($row === false) break;
        if (cariPetaKolomBKD($row) !== null) {
            fclose($handle);
            return true;
        }
    }
    fclose($handle);
    return false;
}
function bersihkanNipBKD(string $value): string {
    return preg_replace('/\s+/', '', trim($value));
}

// Cek apakah sebuah teks terlihat seperti tanggal (dd-mm-yyyy / d-m-yyyy dst).
function terlihatSepertiTanggalBKD(string $value): bool {
    return (bool) preg_match('/^\d{1,2}\s*[-\/]\s*\d{1,2}\s*[-\/]\s*\d{2,4}$/', trim($value));
}

function gabungkanBlokPegawaiBKD(array $blokBaris, array $peta): array {
    $kolom = [];
    for ($c = 1; $c <= 24; $c++) {
        $rawIdx = $peta[$c] ?? null;
        $nilai = [];
        if ($rawIdx !== null) {
            foreach ($blokBaris as $baris) {
                $v = trim((string) ($baris[$rawIdx] ?? ''));
                if ($v !== '' && $v !== '-') $nilai[] = $v;
            }
        }
        $kolom[$c] = $nilai;
    }

    $gabung = fn(int $c) => implode(' ', $kolom[$c]);
    $gabungTeks = function (int $c) use ($kolom) {
        $bersih = array_filter($kolom[$c], fn($v) => !terlihatSepertiTanggalBKD($v));
        return trim(implode(' ', $bersih));
    };

    $pangkatParts = $kolom[10];
    $tmtPangkat = null;
    if (!empty($pangkatParts) && terlihatSepertiTanggalBKD(end($pangkatParts))) {
        $tmtPangkat = normalisasiTanggal(array_pop($pangkatParts));
    }
    $pangkat = implode(' ', $pangkatParts);

    $jabatanParts = $kolom[17];
    $tmtJabatan = null;
    if (!empty($jabatanParts) && terlihatSepertiTanggalBKD(end($jabatanParts))) {
        $tmtJabatan = normalisasiTanggal(array_pop($jabatanParts));
    }
    $jabatan = trim(preg_replace('/\s+/', ' ', implode(' ', $jabatanParts)));

    $golonganMentah = $gabung(11);
    $kategoriSukuMentah = strtoupper($gabung(23));
    $kategoriSuku = null;
    if (strpos($kategoriSukuMentah, 'NON') !== false) {
        $kategoriSuku = 'Non-Papua';
    } elseif (strpos($kategoriSukuMentah, 'PAPUA') !== false) {
        $kategoriSuku = 'Papua';
    }

    $statusKepegawaian = 'PNS';
    if (!empty($kolom[21])) $statusKepegawaian = 'CPNS';

    return [
        'nip'                      => bersihkanNipBKD($gabung(3)),
        'nama'                     => trim(preg_replace('/\s+/', ' ', $gabungTeks(2))),
        'tempat_lahir'             => $gabungTeks(4) ?: null,
        'tanggal_lahir'            => normalisasiTanggal($gabung(5)),
        'jenis_kelamin'            => normalisasiJenisKelaminBKD($gabung(6)),
        'agama'                    => $gabungTeks(7) ?: null,
        'kategori_suku'            => $kategoriSuku,
        'pendidikan_terakhir'      => $gabungTeks(8) ?: null,
        'pendidikan_jurusan'       => $gabungTeks(9) ?: null,
        'pangkat'                  => $pangkat ?: null,
        'pangkat_terakhir'         => $pangkat ?: null,
        'golongan'                 => $golonganMentah ? normalisasiGolongan($golonganMentah) : null,
        'masa_kerja_tahun'         => $gabung(12) !== '' ? (int) $gabung(12) : null,
        'masa_kerja_bulan'         => $gabung(13) !== '' ? (int) $gabung(13) : null,
        'sk_pejabat'               => $gabungTeks(14) ?: null,
        'sk_nomor'                 => $gabungTeks(15) ?: null,
        'sk_tanggal'               => normalisasiTanggal($gabung(16)),
        'tanggal_pangkat_terakhir' => $tmtPangkat,
        'jabatan'                  => $jabatan ?: null,
        'jenis_jabatan'            => tentukanJenisJabatan($jabatan),
        'tmt_jabatan'              => $tmtJabatan,
        'sk_jabatan_pejabat'       => $gabungTeks(18) ?: null,
        'sk_jabatan_nomor'         => $gabungTeks(19) ?: null,
        'sk_jabatan_tanggal'       => normalisasiTanggal($gabung(20)),
        'status_kepegawaian'       => $statusKepegawaian,
    ];
}

function normalisasiJenisKelaminBKD(string $value): ?string {
    $value = strtoupper(trim($value));
    if ($value === 'L') return 'Pria';
    if ($value === 'P') return 'Wanita';
    return null;
}

function pastikanUtf8BKD($value) {
    if (!is_string($value) || $value === '') return $value;
    if (mb_check_encoding($value, 'UTF-8')) return $value;

    $hasil = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
    if ($hasil !== false && $hasil !== '') return $hasil;

    // fallback terakhir: buang byte yang tidak valid daripada gagal total
    return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
}

function konversiBarisUtf8BKD(array $row): array {
    return array_map('pastikanUtf8BKD', $row);
}

function imporFormatBKD(mysqli $conn, string $filePath, string $delimiter): array {
    $handle = fopen($filePath, 'r');
    $berhasil = 0;
    $gagal = 0;
    $errorList = [];
    $lewatiHeader = true;
    $peta = null;
    $blokBaris = [];
    $blokDitutup = false; // true setelah baris kosong pemisah muncul
    $nomorPegawaiKe = 0;

    while (($row = fgetcsv($handle, 5000, $delimiter)) !== false) {
        $row = konversiBarisUtf8BKD($row);

        if ($lewatiHeader) {
            $petaDicoba = cariPetaKolomBKD($row);
            if ($petaDicoba !== null) {
                $peta = $petaDicoba;
                $lewatiHeader = false;
            }
            continue;
        }

        $kolom0 = trim((string) ($row[$peta[1]] ?? ''));
        $barisKosong = count(array_filter($row, fn($v) => trim((string) $v) !== '')) === 0;

        if ($kolom0 !== '' && ctype_digit($kolom0)) {
            // Baris ini mulai blok pegawai baru -- proses blok sebelumnya dulu (kalau ada).
            if (!empty($blokBaris)) {
                $nomorPegawaiKe++;
                $data = gabungkanBlokPegawaiBKD($blokBaris, $peta);
                if ($data['nama'] === '' && $data['nip'] === '') {
                    // blok kosong/aneh, lewati tanpa dihitung
                } else {
                    $hasil = simpanPegawai($conn, $data, null);
                    if ($hasil['success']) {
                        $berhasil++;
                    } else {
                        $gagal++;
                        $errorList[] = 'Pegawai ke-' . $nomorPegawaiKe . ' (' . ($data['nama'] ?: '?') . '): ' . $hasil['error'];
                    }
                }
            }
            $blokBaris = [$row];
            $blokDitutup = false;
        } elseif ($barisKosong) {
            $blokDitutup = true;
            continue;
        } elseif ($kolom0 === '' && !$blokDitutup) {
            $blokBaris[] = $row;
        } else {
            break;
        }
    }

    if (!empty($blokBaris)) {
        $nomorPegawaiKe++;
        $data = gabungkanBlokPegawaiBKD($blokBaris, $peta);
        if (!($data['nama'] === '' && $data['nip'] === '')) {
            $hasil = simpanPegawai($conn, $data, null);
            if ($hasil['success']) {
                $berhasil++;
            } else {
                $gagal++;
                $errorList[] = 'Pegawai ke-' . $nomorPegawaiKe . ' (' . ($data['nama'] ?: '?') . '): ' . $hasil['error'];
            }
        }
    }

    fclose($handle);
    return [$berhasil, $gagal, $errorList];
}


function nilaiKosongKeNull($value) {
    $value = trim((string) $value);
    if ($value === '' || $value === 'NULL') {
        return null;
    }
    return $value;
}

function pisahTempatTanggalLahir($value): array {
    $value = trim((string) $value);
    if ($value === '') {
        return [null, null];
    }

    if (strpos($value, ',') !== false) {
        [$tempat, $tgl] = array_map('trim', explode(',', $value, 2));
        if (preg_match('/^(\d{1,2}[-\/\.\s]\d{1,2}[-\/\.\s]\d{2,4})$/', $tgl)) {
            return [
                $tempat !== '' ? $tempat : null,
                normalisasiTanggal($tgl)
            ];
        }
    }

    if (preg_match('/^(.*?)(?:\s+|\s*[-\/\s]\s*)(\d{1,2}[-\/\.\s]\d{1,2}[-\/\.\s]\d{2,4})$/', $value, $matches)) {
        return [
            trim($matches[1]) ?: null,
            normalisasiTanggal($matches[2])
        ];
    }

    return [null, normalisasiTanggal($value)];
}

function cariIndexKolom(array $headers, array $candidates, array $excludeIndex = []): ?int {
    foreach ($headers as $index => $header) {
        if (in_array($index, $excludeIndex, true)) {
            continue;
        }
        $normalized = strtolower(trim((string) $header));
        if ($normalized === '') {
            continue;
        }

        foreach ($candidates as $candidate) {
            if ($normalized === strtolower(trim((string) $candidate))) {
                return $index;
            }
        }
    }

    foreach ($headers as $index => $header) {
        if (in_array($index, $excludeIndex, true)) {
            continue;
        }
        $normalized = strtolower(trim((string) $header));
        if ($normalized === '') {
            continue;
        }

        foreach ($candidates as $candidate) {
            if (strpos($normalized, strtolower(trim((string) $candidate))) !== false) {
                return $index;
            }
        }
    }

    return null;
}

function ambilNilaiKolom(array $row, ?int $index, $default = null) {
    if ($index === null) {
        return $default;
    }

    $value = $row[$index] ?? '';
    $trimmed = trim((string) $value);

    if ($trimmed === '' || $trimmed === 'NULL') {
        return $default;
    }

    return $trimmed;
}

function normalisasiJenisKelamin($value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $normalized = strtoupper($value);
    if (in_array($normalized, ['L', 'LAKI', 'LAKI-LAKI', 'PRIA', '1'], true)) {
        return 'Pria';
    }

    if (in_array($normalized, ['P', 'PEREMPUAN', 'WANITA', '2'], true)) {
        return 'Wanita';
    }

    return null;
}
function cariKolomJenisKelaminSplit(array $headers): array {
    $jkIndex = cariIndexKolom($headers, ['jenis kelamin', 'jenis', 'jk']);

    if ($jkIndex !== null) {
        $nextHeader = strtolower(trim((string) ($headers[$jkIndex + 1] ?? '')));
        if ($nextHeader === '') {
            return [$jkIndex, $jkIndex + 1];
        }
        // Ini untuk jaga-jaga kalau nanti template yang di kasih itu label eksplisit.
        if (strpos($nextHeader, 'perempuan') !== false || strpos($nextHeader, 'wanita') !== false) {
            return [$jkIndex, $jkIndex + 1];
        }
    }

    $lakiIndex = cariIndexKolom($headers, ['laki-laki', 'laki laki']);
    $perempuanIndex = cariIndexKolom($headers, ['perempuan', 'wanita']);
    if ($lakiIndex !== null || $perempuanIndex !== null) {
        return [$lakiIndex, $perempuanIndex];
    }

    return [null, null];
}

function buatSqlInsertPegawai(mysqli $conn, array $data): bool {
    $columns = [
        'nik',
        'nip',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'suku',
        'alamat',
        'no_seri',
        'karpeg',
        'status_kepegawaian',
        'pendidikan_terakhir',
        'pangkat_terakhir',
        'pangkat',
        'golongan',
        'ruang',
        'jabatan',
        'jenis_jabatan',
        'tmt_jabatan',
        'tanggal_masuk',
        'tanggal_pangkat_terakhir'
    ];

    $values = [];
    foreach ($columns as $column) {
        $values[] = $data[$column] ?? null;
    }

    $escaped = [];
    foreach ($values as $value) {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $escaped[] = 'NULL';
        } else {
            $escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
        }
    }

    $sql = 'INSERT INTO pegawai (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $escaped) . ')';
    return mysqli_query($conn, $sql);
}

if (isset($_FILES['file_csv'])) {

    $file = $_FILES['file_csv']['tmp_name'];
    $namaAsli = $_FILES['file_csv']['name'] ?? '';

    if (!$file) {
        die("File tidak ditemukan!");
    }
    $handleCekFormat = fopen($file, 'rb');
    $delapanByte = $handleCekFormat ? fread($handleCekFormat, 8) : '';
    if ($handleCekFormat) fclose($handleCekFormat);

    $adalahOle2 = substr($delapanByte, 0, 4) === "\xD0\xCF\x11\xE0"; // .xls lama (Excel 97-2003)
    $adalahZip  = substr($delapanByte, 0, 2) === "PK";               // .xlsx / .xlsm (format zip)

    if ($adalahOle2 || $adalahZip) {
        $jenisFile = $adalahOle2 ? '.xls (Excel 97-2003)' : '.xlsx/.xlsm';
        header('Content-Type: text/plain; charset=UTF-8');
        die(
            "GAGAL IMPORT: File yang diupload (" . htmlspecialchars($namaAsli) . ") adalah file Excel asli ($jenisFile), bukan file CSV.\n\n" .
            "Fitur import ini hanya bisa membaca file CSV (teks biasa dipisah koma/titik-koma), bukan file Excel biner.\n\n" .
            "Cara memperbaikinya:\n" .
            "1. Buka file Excel-nya di Microsoft Excel\n" .
            "2. Klik File > Save As / Simpan Sebagai\n" .
            "3. Pilih tipe file: \"CSV (Comma delimited) (*.csv)\" atau \"CSV UTF-8 (Comma delimited)\"\n" .
            "4. Simpan, lalu upload file .csv hasilnya di sini (bukan file .xls/.xlsx aslinya)\n\n" .
            "Kembali ke halaman import: " . dirname($_SERVER['PHP_SELF']) . "/import.php"
        );
    }

    $handle = fopen($file, "r");

    if (!$handle) {
        die("File tidak dapat dibuka!");
    }

    $delimiter = deteksiDelimitCsv($file);

    if (deteksiFormatBKD($file, $delimiter)) {
        fclose($handle);

        mysqli_query($conn, "TRUNCATE TABLE pegawai");
        [$berhasil, $gagal, $errorList] = imporFormatBKD($conn, $file, $delimiter);

        $_SESSION['import_berhasil'] = $berhasil;
        $_SESSION['import_gagal'] = $gagal;
        $_SESSION['import_error'] = $errorList;
        $_SESSION['import_format'] = 'BKD';
        header("Location: import.php");
        exit;
    }

    mysqli_query($conn, "TRUNCATE TABLE pegawai");

    $headerRow = fgetcsv($handle, 5000, $delimiter);
    $headers = [];
    if ($headerRow !== false) {
        $headers = array_map(function ($value) {
            return trim((string) $value);
        }, $headerRow);
    }

    $baris = 1;

    while (($data = fgetcsv($handle, 5000, $delimiter)) !== false) {
        $baris++;
        $data = konversiBarisUtf8BKD($data);
        $row = array_pad($data, 30, '');

        $nikIndex = cariIndexKolom($headers, ['nik']);
        $nipIndex = cariIndexKolom($headers, ['nip']);
        $namaIndex = cariIndexKolom($headers, ['nama']);
        $tempatLahirIndex = cariIndexKolom($headers, ['tempat lahir', 'tempat_lahir', 'tempatlahir', 'tempat']);
        $tanggalLahirIndex = cariIndexKolom($headers, ['tanggal lahir', 'tanggal_lahir', 'tgl lahir', 'tgl_lahir', 'ttl', 'ttl lahir']);
        [$jkLakiIndex, $jkPerempuanIndex] = cariKolomJenisKelaminSplit($headers);
        $jenisKelaminIndex = cariIndexKolom($headers, ['jenis', 'kelamin', 'jk']);
        $agamaIndex = cariIndexKolom($headers, ['agama']);
        $sukuIndex = cariIndexKolom($headers, ['suku']);
        $alamatIndex = cariIndexKolom($headers, ['alamat']);
        $noSeriIndex = cariIndexKolom($headers, ['seri']);
        $karpegIndex = cariIndexKolom($headers, ['karpeg']);
        $statusIndex = cariIndexKolom($headers, ['status']);
        $pendidikanIndex = cariIndexKolom($headers, ['pendidikan']);
        $tanggalPangkatTerakhirIndex = cariIndexKolom($headers, ['tanggal pangkat', 'tanggal_pangkat', 'tmt pangkat', 'tmt_pangkat']);
        $pangkatExclude = $tanggalPangkatTerakhirIndex !== null ? [$tanggalPangkatTerakhirIndex] : [];
        $pangkatIndex = cariIndexKolom($headers, ['pangkat terakhir', 'pangkat'], $pangkatExclude);
        $golonganIndex = cariIndexKolom($headers, ['golongan']);
        $jabatanIndex = cariIndexKolom($headers, ['jabatan']);
        $tanggalMasukIndex = cariIndexKolom($headers, ['tanggal masuk', 'tanggal_masuk', 'tmt']);

        $nik = ambilNilaiKolom($row, $nikIndex, '');
        $nip = ambilNilaiKolom($row, $nipIndex, '');
        $nama = ambilNilaiKolom($row, $namaIndex, '');

        if ($nama === '' && $nip === '' && $nik === '') {
            continue;
        }

        if (strtoupper(trim((string) ($row[0] ?? ''))) === 'NO' || strtoupper(trim((string) ($row[0] ?? ''))) === 'JUMLAH') {
            continue;
        }

        if ($jkLakiIndex !== null && trim((string) ($row[$jkLakiIndex] ?? '')) !== '') {
            $jenisKelamin = 'Pria';
        } elseif ($jkPerempuanIndex !== null && trim((string) ($row[$jkPerempuanIndex] ?? '')) !== '') {
            $jenisKelamin = 'Wanita';
        } else {
            $jenisKelamin = normalisasiJenisKelamin(ambilNilaiKolom($row, $jenisKelaminIndex, ''));
        }

        [$tempatLahirValue, $tanggalLahirValue] = pisahTempatTanggalLahir(ambilNilaiKolom($row, $tanggalLahirIndex, ''));
        if ($tempatLahirValue !== null) {
            $tempatLahir = $tempatLahirValue;
        } else {
            $tempatLahir = ambilNilaiKolom($row, $tempatLahirIndex, null);
        }
        $tanggalLahir = $tanggalLahirValue;

        $pangkat = ambilNilaiKolom($row, $pangkatIndex, null);
        $golongan = ambilNilaiKolom($row, $golonganIndex, null);
        $jabatan = ambilNilaiKolom($row, $jabatanIndex, null);
        $jenisJabatan = tentukanJenisJabatan($jabatan ?? '');
        $status = ambilNilaiKolom($row, $statusIndex, 'PNS');
        $agama = ambilNilaiKolom($row, $agamaIndex, null);
        $suku = ambilNilaiKolom($row, $sukuIndex, null);
        $alamat = ambilNilaiKolom($row, $alamatIndex, null);
        $noSeri = ambilNilaiKolom($row, $noSeriIndex, null);
        $karpeg = ambilNilaiKolom($row, $karpegIndex, null);
        $pendidikanTerakhir = ambilNilaiKolom($row, $pendidikanIndex, null);
        $tanggalMasuk = normalisasiTanggal(ambilNilaiKolom($row, $tanggalMasukIndex, '') ?? '');
        $tanggalPangkatTerakhir = normalisasiTanggal(ambilNilaiKolom($row, $tanggalPangkatTerakhirIndex, '') ?? '');

        $data = [
            'nik' => $nik,
            'nip' => $nip,
            'nama' => $nama,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $jenisKelamin,
            'agama' => $agama,
            'suku' => $suku,
            'alamat' => $alamat,
            'no_seri' => $noSeri,
            'karpeg' => $karpeg,
            'status_kepegawaian' => $status,
            'pendidikan_terakhir' => $pendidikanTerakhir,
            'pangkat_terakhir' => $pangkat,
            'pangkat' => $pangkat,
            'golongan' => $golongan,
            'ruang' => null,
            'jabatan' => $jabatan,
            'jenis_jabatan' => $jenisJabatan,
            'tmt_jabatan' => null,
            'tanggal_masuk' => $tanggalMasuk,
            'tanggal_pangkat_terakhir' => $tanggalPangkatTerakhir,
        ];

        if (buatSqlInsertPegawai($conn, $data)) {
            $berhasil++;
        } else {
            $errorList[] = 'Baris ' . $baris . ': ' . mysqli_error($conn);
            $gagal++;
        }
    }

    fclose($handle);

    $_SESSION['import_berhasil'] = $berhasil;
    $_SESSION['import_gagal'] = $gagal;
    $_SESSION['import_error'] = $errorList;
    header("Location: import.php");
    exit;
}