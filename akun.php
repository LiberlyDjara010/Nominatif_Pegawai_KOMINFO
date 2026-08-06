<?php
require 'auth.php';
include 'koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$pageTitle    = 'Akun';

$username = $_SESSION['username'] ?? $_SESSION['user'] ?? null;

$currentUser = null;
if ($username) {
    $stmt = $conn->prepare("SELECT username, nama, role FROM user WHERE username = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentUser = $result->fetch_assoc();
        $stmt->close();
    }
}

$roleName = $currentUser['role'] ?? '-';
$roleText = isSuperAdmin($roleName) ? 'Super Admin (APTIKA)' : 'User (Bagian Kepegawaian)';
$pageSubtitle = $roleText;

$pegawai = null;

if ($currentUser && !empty($currentUser['nama'])) {
    $nama = $currentUser['nama'];
    
    $stmtPegawai = $conn->prepare("SELECT * FROM pegawai WHERE nama = ? LIMIT 1");
    if ($stmtPegawai) {
        $stmtPegawai->bind_param('s', $nama);
        $stmtPegawai->execute();
        $resultPegawai = $stmtPegawai->get_result();
        $pegawai = $resultPegawai->fetch_assoc();
        $stmtPegawai->close();
    }
    
    if (!$pegawai) {
        $namaLike = '%' . $nama . '%';
        $stmtPegawai = $conn->prepare("SELECT * FROM pegawai WHERE nama LIKE ? LIMIT 1");
        if ($stmtPegawai) {
            $stmtPegawai->bind_param('s', $namaLike);
            $stmtPegawai->execute();
            $resultPegawai = $stmtPegawai->get_result();
            $pegawai = $resultPegawai->fetch_assoc();
            $stmtPegawai->close();
        }
    }
}

include 'layout.php';
?>

<?php if (!$currentUser): ?> 

<div style="
background : #FFF4E5;
border : 1px solid #F5C16C;
padding : 16px;
border-radius : 12px;
margin-bottom : 18px;
color : #8A4B00;
">
Data akun tidak ditemukan. Pastikan Anda login dengan benar.
</div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h3>Profil Pengguna</h3>
        </div>
        
        <div class="card-body" style="text-align: center;">
            <div style="margin-bottom: 24px;">
                
                <div style="
                width : 120px; 
                height : 120px;
                border-radius : 50%;
                background : linear-gradient(135deg, var(--emas), var(--emas-muda));
                display : flex; 
                align-items : center; 
                justify-content : center;
                font-size : 48px; 
                font-weight : 700; 
                color : var(--biru-tua);
                margin : 0 auto 16px;
                ">
                <?= strtoupper(substr($pegawai ? $pegawai['nama'] : $currentUser['nama'], 0, 1)) ?>
            </div>
            
            <div style="font-size: 18px; font-weight: 700; color: var(--teks-gelap);">
                <?= e($pegawai ? $pegawai['nama'] : $currentUser['nama']) ?>
            </div>
            
            <div style="font-size: 13px; color: var(--teks-abu); margin-top: 8px;">
                <?= $roleText ?>
            </div>

            <?php if ($pegawai && $pegawai['nip']): ?>
            
            <div style="
            font-size : 13px; 
            color : var(--teks-abu); 
            margin-top : 4px;
            ">
            NIP: <?= e($pegawai['nip']) ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div style="
    display : grid; 
    grid-template-columns : 1fr 1fr; 
    gap : 18px; 
    max-width : 620px; 
    margin : 0 auto; 
    text-align : left;
    ">
    
    <?php if ($pegawai): ?>
        <?php if ($pegawai['golongan']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); text-transform: uppercase;
                ">
                Golongan
            </strong>

            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            ">
            <?= e($pegawai['golongan']) ?>
        </div>
        </div>
        
        <?php endif; ?>
        
        <?php if ($pegawai['pangkat_terakhir']): ?>
            
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu);
                text-transform : uppercase;
                ">
                Pangkat
            </strong>

            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);">
            <?= e($pegawai['pangkat_terakhir']) ?>
        </div>
    </div>
    
    <?php endif; ?>
    
    <?php if ($pegawai['jabatan']): ?>
        
        <div>
            <strong style="
            font-size : 12px; 
            color : var(--teks-abu); 
            text-transform : uppercase;
            ">
            Jabatan
            </strong>
            
            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            "><?= e($pegawai['jabatan']) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($pegawai['tanggal_lahir']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); 
                text-transform : uppercase;
                ">
                Umur
            </strong>
            
            <div style="margin-top : 6px; 
            color : var(--teks-gelap);
            ">
            <?= hitungUsia($pegawai['tanggal_lahir']) ?> 
            tahun
        </div>
        </div>
        
        <?php endif; ?>
        
        <?php if ($pegawai['jenis_kelamin']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); 
                text-transform : uppercase;
                ">
                Jenis Kelamin
            </strong>
            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            ">
                <?= e($pegawai['jenis_kelamin']) ?>
            </div>
        </div>
        
        <?php endif; ?>
        <?php if ($pegawai['agama']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); 
                text-transform : uppercase;
                ">
                Agama
            </strong>
            
            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            ">
            <?= e($pegawai['agama']) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($pegawai['suku']): ?>
                    <div>
                        <strong style="
                        font-size : 12px; 
                        color : var(--teks-abu); 
                        text-transform : uppercase;
                        ">
                        Suku
                    </strong>
                    <div style="margin-top : 6px; 
                    color : var(--teks-gelap);
                    ">
                    <?= e($pegawai['suku']) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($pegawai['tempat_lahir'] || $pegawai['tanggal_lahir']): ?>
                <div>
                    <strong style="
                    font-size : 12px; 
                    color : var(--teks-abu); 
                    text-transform : uppercase;
                    ">
                    Tempat, Tgl Lahir
                </strong>
                <div style="
                margin-top: 6px; 
                color : var(--teks-gelap);
                ">
                <?= e($pegawai['tempat_lahir'] ?: '-') ?>, 
                <?= e($pegawai['tanggal_lahir'] ? date('d M Y', strtotime($pegawai['tanggal_lahir'])) : '-') ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($pegawai['pendidikan_terakhir']): ?>
                <div>
                    <strong style="
                    font-size : 12px; 
                    color : var(--teks-abu); 
                    text-transform : uppercase;
                    ">Pendidikan
                    </strong>
                    
                    <div style="
                    margin-top : 6px; 
                    color : var(--teks-gelap);
                    ">
                    <?= e($pegawai['pendidikan_terakhir']) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($pegawai['status_kepegawaian']): ?>
                
                <div>
                    <strong style="
                    font-size : 12px; 
                    color : var(--teks-abu); 
                    text-transform : uppercase;
                    ">
                    Status Kepegawaian
                </strong>
                <div style="
                margin-top : 6px; 
                color : var(--teks-gelap);
                ">
                <?= e($pegawai['status_kepegawaian']) ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($pegawai['tanggal_masuk']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); 
                text-transform : uppercase;
                ">
                Tanggal Masuk
            </strong>
            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            ">
            <?= e(formatTanggalMasukDisplay($pegawai['tanggal_masuk'])) ?>
        </div>
        </div>
        <?php endif; ?>
        <?php if ($pegawai['tmt_jabatan']): ?>
            <div>
                <strong style="
                font-size : 12px; 
                color : var(--teks-abu); 
                text-transform : uppercase;
                ">
                TMT Jabatan
            </strong>
            <div style="
            margin-top : 6px; 
            color : var(--teks-gelap);
            ">
            <?= e(date('d M Y', strtotime($pegawai['tmt_jabatan']))) ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($pegawai['alamat']): ?>
        <div style="grid-column: 1 / -1;">
            <strong style="
            font-size : 12px; 
            color : var(--teks-abu); 
            text-transform : uppercase;
            ">
            Alamat
        </strong>
        <div style="
        margin-top : 6px; 
        color : var(--teks-gelap);
        ">
        <?= e($pegawai['alamat']) ?>
    </div>
</div>

<?php endif; ?>
<?php else: ?>
    <div style="
    grid-column : 1 / -1; 
    color : var(--teks-abu);
    ">
    Data pegawai tidak ditemukan di sistem.
</div>
<?php endif; ?>
</div>
</div>
</div>

<?php endif; ?>

<?php include 'layout_end.php'; ?>
