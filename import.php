<?php
require 'auth.php';
include 'koneksi.php';

requireCanManageData();

$pageTitle = 'Import Data Pegawai';
$pageSubtitle = 'Upload data pegawai dari file CSV';

include 'layout.php';
?>

<div style=
        "background: #d2e6ff;
        border: 1px solid #6a97cb;
        border-radius: 10px;
        padding: 12px 18px;
        margin-bottom: 16px;
        color: #2d50b3;
        font-size: 14px;">

<strong> ℹ️ Petunjuk Import </strong>

<ul style="margin: 8px 0 0 22px; padding-left: 0;">
    <li style="margin-bottom: 6px;">Gunakan template yang telah disediakan</li>
    <li style="margin-bottom: 6px;">Format tanggal: YYYY-MM-DD</li>
    <li style="margin-bottom: 6px;">NIK dan NIP tidak boleh duplikat</li>
    <li>Pastikan seluruh kolom terisi dengan benar</li>
</ul>

</div>

<div class="card">

</div>
<a href="TEMPLATE.xlsx" target="_blank"\
download
style=
"background: #16A34A;
color: white;
padding: 12px 18px;
border-radius: 8px;
text-decoration: none;
font-weight:800;"
>

Download Template

</a>
</div>
<form
action= 
"proses_import.php"
method="POST"
enctype="multipart/form-data"
>

<div
style="
border-radius: 12px;
padding: 50px;
text-align: center;
background: #F8FAFC;
margin-bottom: 20px;"
>

<h3>Pilih File CSV</h3>
<p style="color: #64748B;">
    Upload data pegawai dari Excel yang sudah disimpan sebagai CSV
</p>
<p style="color: #92600A;background:#FFFBEA;border:1px solid #FFE58F;border-radius:8px;padding:10px 14px;font-size:13px;display:inline-block;text-align:left;">
    ⚠ File harus <strong>.csv</strong>, bukan file Excel asli (.xls/.xlsx).
    Kalau data Anda masih dalam bentuk Excel: buka di Microsoft Excel →
    <strong>File &gt; Save As</strong> → pilih tipe <strong>"CSV (Comma delimited)"</strong> → simpan → upload hasilnya di sini.
</p>

<input
type="file"
name="file_csv"
accept=".csv"
required
style=
"margin-top:20px;
font-size: 18px;"
>

</div>
<button
type="submit"
style=
"background: #061d50;
color:white;
border:none;
padding:17px 30px;
border-radius:8px;
cursor:pointer;
font-weight:900;
margin: 7px;"
>
Import Data
</button>

<br><br>
</form>
</div>
<?php include 'layout_end.php'; ?>  
<?php if(isset($_SESSION['import_berhasil'])): ?>
<div style=
"background: #ECFDF5;
border: 1px solid #A7F3D0;
padding: 15px;
border-radius: 10px;
margin-bottom: 20px;"
>

<h3 style=
"margin-top:0;
color: #065F46;"
>
✓ Import Selesai
</h3>

<?php if (($_SESSION['import_format'] ?? '') === 'BKD'): ?>
<p style="margin-top:-8px;font-size:13px;color:#065F46;">
    Format terdeteksi: <strong>DAFTAR NOMINATIF PEGAWAI NEGERI SIPIL (BKD)</strong> —
    baris-baris yang terpecah per pegawai sudah digabungkan otomatis.
</p>
<?php endif; ?>

<p>
    Berhasil:
    <strong><?= $_SESSION['import_berhasil'] ?></strong>
    data 
</p>

<p>
    Gagal:
    <strong><?= $_SESSION['import_gagal'] ?></strong>
    data
</p>

    <?php
    if(!empty($_SESSION['import_error'])){
        echo "<hr>";
        echo "<strong>Detail Error:</strong><br>";

        foreach($_SESSION['import_error'] as $err){
            echo $err . "<br>";
        }
    }
    ?>

</div>

<?php
unset($_SESSION['import_berhasil']);
unset($_SESSION['import_gagal']);
unset($_SESSION['import_error']);
unset($_SESSION['import_format']);
endif;
?>

