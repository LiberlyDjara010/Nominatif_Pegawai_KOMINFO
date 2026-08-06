<?php
require 'auth.php';
include 'koneksi.php';
requireSuperAdmin();

$pageTitle    = 'Kelola Akun & Hak Akses';
$pageSubtitle = 'Hanya Super Admin (APTIKA) yang dapat menambah, mengubah, atau menghapus akun';

$pesan = '';
$error = '';

$roleOptions = [
    'superadmin'   => 'Super Admin (APTIKA)',
    'kepegawaian'  => 'User (Bagian Kepegawaian)',
];

// TAMBAH / EDIT AKUN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_akun'])) {
    $id       = trim($_POST['id'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $role     = trim($_POST['role'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $nama === '' || !isset($roleOptions[$role])) {
        $error = 'Username, Nama, dan Role wajib diisi dengan benar.';
    } else {
        if ($id === '') {
            // TAMBAH AKUN BARU
            if ($password === '') {
                $error = 'Password wajib diisi untuk akun baru.';
            } else {
                $cek = $conn->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
                $cek->bind_param('s', $username);
                $cek->execute();
                if ($cek->get_result()->num_rows > 0) {
                    $error = 'Username sudah dipakai, pilih username lain.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO user (username, password, nama, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('ssss', $username, $hash, $nama, $role);
                    if ($stmt->execute()) {
                        $pesan = "Akun \"$username\" berhasil ditambahkan sebagai " . $roleOptions[$role] . '.';
                    } else {
                        $error = 'Gagal menambahkan akun: ' . $stmt->error;
                    }
                }
            }
        } else {
            // EDIT AKUN
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE user SET username=?, nama=?, role=?, password=? WHERE id=?");
                $stmt->bind_param('ssssi', $username, $nama, $role, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE user SET username=?, nama=?, role=? WHERE id=?");
                $stmt->bind_param('sssi', $username, $nama, $role, $id);
            }
            if ($stmt->execute()) {
                $pesan = "Akun \"$username\" berhasil diperbarui.";
            } else {
                $error = 'Gagal memperbarui akun: ' . $stmt->error;
            }
        }
    }
}

// HAPUS AKUN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_akun'])) {
    $id = (int) $_POST['hapus_akun'];

    if ((int) $id === (int) ($_SESSION['id'] ?? 0)) {
        $error = 'Tidak bisa menghapus akun yang sedang Anda gunakan sendiri.';
    } else {
        $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $pesan = 'Akun berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus akun: ' . $stmt->error;
        }
    }
}

$daftarUser = [];
$res = mysqli_query($conn, "SELECT id, username, nama, role FROM user ORDER BY nama ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $daftarUser[] = $row;
    }
}

include 'layout.php';
?>

<?php if ($pesan): ?>
<div style="background:#E8F5E9;border:1px solid #81C784;border-radius:8px;padding:10px 14px;margin-bottom:16px;color:#1B5E20;font-size:13px;">✅ <?= e($pesan) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#FFF0EE;border:1px solid #FFB3AE;border-radius:8px;padding:10px 14px;margin-bottom:16px;color:#D93025;font-size:13px;">⚠ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-header"><h3>Tambah Akun Baru</h3></div>
    <div style="padding:16px;">
        <form method="POST" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;align-items:end;">
            <input type="hidden" name="id" value="">
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Username</label>
                <input type="text" name="username" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Nama Lengkap</label>
                <input type="text" name="nama" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Role</label>
                <select name="role" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
                    <?php foreach ($roleOptions as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Password</label>
                <input type="password" name="password" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div style="grid-column:1/-1;">
                <button type="submit" name="simpan_akun" class="btn btn-success btn-sm">+ Tambah Akun</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Daftar Akun</h3><span style="font-size:12px;color:var(--teks-abu);"><?= count($daftarUser) ?> akun</span></div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>Username</th><th>Nama</th><th>Role</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($daftarUser as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['nama']) ?></td>
                    <td>
                        <span class="badge <?= isSuperAdmin($u['role']) ? 'badge-biru' : 'badge-hijau' ?>">
                            <?= e($roleOptions[$u['role']] ?? ucfirst($u['role'])) ?>
                        </span>
                    </td>
                    <td style="display:flex;gap:6px;">
                        <button type="button" class="btn btn-ghost btn-sm"
                            onclick='isiFormEdit(<?= json_encode($u) ?>)'>Edit</button>
                        <form method="POST" onsubmit="return confirm('Anda Yakin Ingin Menghapusnya?\n\nAkun ini akan dihapus secara permanen.');" style="display:inline;">
                            <input type="hidden" name="hapus_akun" value="<?= (int) $u['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:#D93025;">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalEdit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:100;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:24px;max-width:420px;width:90%;">
        <h3 style="margin-bottom:14px;">Edit Akun</h3>
        <form method="POST" style="display:flex;flex-direction:column;gap:10px;">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Username</label>
                <input type="text" name="username" id="edit_username" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Nama Lengkap</label>
                <input type="text" name="nama" id="edit_nama" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Role</label>
                <select name="role" id="edit_role" required style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
                    <?php foreach ($roleOptions as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;display:block;margin-bottom:4px;">Password Baru (kosongkan jika tidak ganti)</label>
                <input type="password" name="password" style="width:100%;padding:9px 10px;border:1px solid #DDE2EC;border-radius:8px;">
            </div>
            <div style="display:flex;gap:8px;margin-top:8px;">
                <button type="submit" name="simpan_akun" class="btn btn-success btn-sm">Simpan</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('modalEdit').style.display='none';">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function isiFormEdit(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_username').value = u.username;
    document.getElementById('edit_nama').value = u.nama;
    document.getElementById('edit_role').value = u.role;
    document.getElementById('modalEdit').style.display = 'flex';
}
</script>

<?php include 'layout_end.php'; ?>