<?php
require 'auth.php';
include 'koneksi.php';

requireCanManageData();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM pegawai WHERE id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$pegawai = $stmt->get_result()->fetch_assoc();

if (!$pegawai) {
    die("Data pegawai tidak ditemukan.");
}

$pageTitle    = 'Checklist Kelengkapan Berkas';
$pageSubtitle = 'Kenaikan Pangkat — ' . $pegawai['nama'];

$berhasilSimpan = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_checklist'])) {
    $checkedKeys = $_POST['item'] ?? [];
    if (!is_array($checkedKeys)) $checkedKeys = [];
    simpanChecklist($conn, $id, $checkedKeys);

    $jenisKpKeys = $_POST['jenis_kp'] ?? [];
    if (!is_array($jenisKpKeys)) $jenisKpKeys = [];
    simpanJenisKp($conn, $id, $jenisKpKeys);

    header('Location: checklist_pangkat.php?id=' . $id . '&tersimpan=1');
    exit;
}

$status  = getChecklistPegawai($conn, $id);
$lengkap = isChecklistLengkap($status);
$jenisKpStatus = getJenisKpPegawai($conn, $id);
$tersimpan = isset($_GET['tersimpan']);

include 'layout.php';
?>

<?php if ($tersimpan): ?>
<div style="background:#F0FFF8;border:1px solid #86EFAC;border-radius:10px;padding:12px 18px;margin-bottom:16px;color:#14532D;font-size:13px;">
    Checklist berhasil disimpan.
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3><?= e($pegawai['nama']) ?></h3>
        <a href="pangkat.php" class="btn btn-ghost btn-sm">← Kembali ke Kenaikan Pangkat</a>
    </div>
    <div class="card-body" style="padding:16px 20px;">
        <div style="font-size:13px;color:var(--teks-abu);margin-bottom:4px;">NIP: <?= e($pegawai['nip']) ?></div>
        <div style="font-size:13px;color:var(--teks-abu);">Jabatan: <?= e($pegawai['jabatan'] ?: '-') ?> &middot; Golongan: <?= e($pegawai['golongan'] ?: '-') ?></div>

        <div style="margin-top:14px;">
            <?php if ($lengkap): ?>
                <span class="badge badge-hijau" style="font-size:13px;">✔ Berkas Lengkap</span>
            <?php else: ?>
                <span class="badge badge-merah" style="font-size:13px;">⚠ Berkas Belum Lengkap</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="POST">

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Jenis KP (Kenaikan Pangkat)</h3>
    </div>
    <div class="card-body" style="padding:16px 20px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:8px 16px;">
            <?php foreach (jenisKpItemsMaster() as $key => $label): ?>
            <label style="display:flex;align-items:flex-start;gap:8px;font-size:13.5px;cursor:pointer;">
                <input type="checkbox" name="jenis_kp[]" value="<?= e($key) ?>"
                    style="margin-top:3px;width:15px;height:15px;cursor:pointer;"
                    <?= !empty($jenisKpStatus[$key]) ? 'checked' : '' ?>>
                <span><?= e($label) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <h3>Checklist Dokumen Kenaikan Pangkat</h3>
    </div>
    <div class="card-body" style="padding:16px 20px;">
        <p style="font-size:13px;color:var(--teks-abu);margin-bottom:8px;">
            Centang dokumen yang sudah dilengkapi. Status "Lengkap" di halaman Kenaikan Pangkat
            akan otomatis muncul begitu <strong>semua</strong> item di bawah ini dicentang.
        </p>
        <p style="font-size:12.5px;color:#92600A;background:#FFFBEA;border:1px solid #FFE58F;border-radius:8px;padding:8px 12px;margin-bottom:16px;">
            <strong>*</strong> tanda bintang menandakan berkas <strong>wajib</strong> dilampirkan.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px;">
            <?php foreach (checklistItemsMaster() as $key => $label): ?>
            <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border:1px solid var(--abu-border);border-radius:8px;cursor:pointer;">
                <input type="checkbox" name="item[]" value="<?= e($key) ?>"
                    style="margin-top:3px;width:16px;height:16px;cursor:pointer;"
                    <?= !empty($status[$key]) ? 'checked' : '' ?>>
                <span style="font-size:13.5px;">
                    <?php if (str_ends_with($label, '*')): ?>
                        <?= e(rtrim($label, '* ')) ?> <strong style="color:var(--merah);" title="Wajib dilampirkan">*</strong>
                    <?php else: ?>
                        <?= e($label) ?>
                    <?php endif; ?>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px;">
            <button type="submit" name="simpan_checklist" class="btn btn-success">Simpan Checklist</button>
            <a href="pangkat.php" class="btn btn-ghost">Batal</a>
        </div>
    </div>
</div>

</form>

<?php include 'layout_end.php'; ?>
