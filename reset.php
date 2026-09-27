<?php

session_start();
include 'koneksi.php';

// ============ GANTI INI ============
define('SECRET_PASSPHRASE', 'lao');
// ====================================

$sudahMasuk = !empty($_SESSION['darurat_terverifikasi'])
    && !empty($_SESSION['darurat_waktu'])
    && (time() - $_SESSION['darurat_waktu']) < 30; // Berlaku hanya dalam waktu 30 detik atau setengah menit setelah itu akan terkunci lagi

$errorPassphrase = '';
$errorReset = '';
$suksesReset = '';
$errorBuat = '';
$suksesBuat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_passphrase'])) {
    $input = $_POST['passphrase'] ?? '';
    if (SECRET_PASSPHRASE === 'olo') {
        $errorPassphrase = 'Passphrase belum diganti dari nilai contoh -- edit dulu file ini (lihat komentar di bagian atas) sebelum dipakai.';
    } elseif (hash_equals(SECRET_PASSPHRASE, $input)) {
        $_SESSION['darurat_terverifikasi'] = true;
        $_SESSION['darurat_waktu'] = time();
        $sudahMasuk = true;
    } else {
        $errorPassphrase = 'Passphrase salah.';
        sleep(2);
    }
}

if ($sudahMasuk && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $passwordBaru = (string) ($_POST['password_baru'] ?? '');

    if (strlen($passwordBaru) < 8) {
        $errorReset = 'Password baru minimal 8 karakter.';
    } else {
        $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $hash, $userId);
        if ($stmt->execute()) {
            $suksesReset = 'Password berhasil direset. Silakan login dengan password baru lewat halaman login biasa.';
        } else {
            $errorReset = 'Gagal reset password: ' . $stmt->error;
        }
    }
}

// Buat akun baru -- dipakai kalau BELUM ADA sama sekali akun dengan role
// "superadmin"/"kepegawaian" di database ini (mis. database baru yang belum
// dimigrasi, cuma ada akun lama peninggalan sistem sebelumnya), sehingga
// tidak ada cara login untuk membuka menu Kelola Akun secara normal.
if ($sudahMasuk && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_akun'])) {
    $usernameBaru = trim((string) ($_POST['username_baru'] ?? ''));
    $namaBaru = trim((string) ($_POST['nama_baru'] ?? ''));
    $roleBaru = (string) ($_POST['role_baru'] ?? '');
    $passwordBaruAkun = (string) ($_POST['password_baru_akun'] ?? '');

    if ($usernameBaru === '' || $namaBaru === '') {
        $errorBuat = 'Username dan Nama wajib diisi.';
    } elseif (!in_array($roleBaru, ['superadmin', 'kepegawaian'], true)) {
        $errorBuat = 'Role tidak valid.';
    } elseif (strlen($passwordBaruAkun) < 8) {
        $errorBuat = 'Password minimal 8 karakter.';
    } else {
        $cek = $conn->prepare("SELECT id FROM user WHERE username = ? LIMIT 1");
        $cek->bind_param('s', $usernameBaru);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $errorBuat = 'Username itu sudah dipakai akun lain.';
        } else {
            $hashBaru = password_hash($passwordBaruAkun, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (username, password, nama, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $usernameBaru, $hashBaru, $namaBaru, $roleBaru);
            if ($stmt->execute()) {
                $suksesBuat = "Akun \"$usernameBaru\" ($roleBaru) berhasil dibuat. Silakan login lewat halaman login biasa.";
            } else {
                $errorBuat = 'Gagal membuat akun: ' . $stmt->error;
            }
        }
    }
}

$daftarAkun = [];
if ($sudahMasuk) {
    // Hanya tampilkan akun dari sistem yang sekarang dipakai (superadmin /
    // kepegawaian) -- akun lama peninggalan sistem sebelumnya (mis. "Kepala
    // Dinas" / "Sekretaris") sengaja tidak ditampilkan di sini lagi.
    $res = mysqli_query($conn, "
        SELECT id, username, nama, role
        FROM user
        WHERE LOWER(role) IN ('superadmin', 'kepegawaian')
        ORDER BY username ASC
    ");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $daftarAkun[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Reset Password Darurat</title>
<meta name="robots" content="noindex, nofollow">
<style>
    body { font-family: -apple-system, Segoe UI, Arial, sans-serif; background:#F4F6F9; margin:0; padding:40px 16px; color:#1e2d45; }
    .box { max-width:480px; margin:0 auto; background:#fff; border-radius:12px; padding:28px 30px; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
    h2 { margin-top:0; font-size:19px; }
    .warn { background:#FFF8E1; border:1px solid #FFD54F; border-radius:8px; padding:12px 14px; font-size:12.5px; color:#6D4C00; margin-bottom:18px; }
    .err { background:#FFF0EE; border:1px solid #FFB3AE; border-radius:8px; padding:10px 14px; font-size:13px; color:#B3261E; margin-bottom:14px; }
    .ok { background:#F0FFF8; border:1px solid #86EFAC; border-radius:8px; padding:10px 14px; font-size:13px; color:#14532D; margin-bottom:14px; }
    label { display:block; font-size:12.5px; font-weight:600; margin-bottom:5px; color:#4a5568; }
    input[type=password], input[type=text], select {
        width:100%; box-sizing:border-box; padding:9px 11px; border:1px solid #D8DEE8; border-radius:8px; font-size:14px; margin-bottom:14px;
    }
    button { background:#1A5FA8; color:#fff; border:none; border-radius:8px; padding:10px 18px; font-size:14px; cursor:pointer; }
    button:hover { background:#154d89; }
    .akun-item { border:1px solid #E4E8EF; border-radius:8px; padding:12px 14px; margin-bottom:10px; }
    .akun-item b { font-size:13.5px; }
    .akun-item span { display:block; font-size:12px; color:#8a94a6; margin-top:2px; }
</style>
</head>
<body>
<div class="box">
    <h2>Reset Password Darurat</h2>

    <?php if (!$sudahMasuk): ?>
        <div class="warn">
            Halaman ini untuk kondisi darurat saat semua akun tidak bisa login.
            Masukkan passphrase rahasia yang sudah diatur di dalam file <code>darurat_reset_password.php</code>.
        </div>

        <?php if ($errorPassphrase): ?>
            <div class="err"><?= htmlspecialchars($errorPassphrase) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Passphrase Rahasia</label>
            <input type="password" name="passphrase" required autofocus>
            <button type="submit" name="cek_passphrase">Masuk</button>
        </form>

    <?php else: ?>

        <?php if ($suksesReset): ?>
            <div class="ok"><?= htmlspecialchars($suksesReset) ?></div>
            <p><a href="login.php">← Ke halaman login</a></p>
        <?php else: ?>

            <?php if ($errorReset): ?>
                <div class="err"><?= htmlspecialchars($errorReset) ?></div>
            <?php endif; ?>

            <?php if (!empty($daftarAkun)): ?>
            <p style="font-size:13px;color:#4a5568;">Pilih akun yang mau direset passwordnya:</p>

            <form method="POST">
                <label>Akun</label>
                <select name="user_id" required>
                    <option value="">-- pilih akun --</option>
                    <?php foreach ($daftarAkun as $u): ?>
                    <option value="<?= (int) $u['id'] ?>">
                        <?= htmlspecialchars($u['username']) ?> — <?= htmlspecialchars($u['nama'] ?: '-') ?> (<?= htmlspecialchars($u['role']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <label>Password Baru (minimal 8 karakter)</label>
                <input type="text" name="password_baru" required minlength="8" placeholder="Ketik password baru di sini">

                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            <?php else: ?>
            <div class="warn">
                Belum ada akun dengan role <strong>superadmin</strong> atau <strong>kepegawaian</strong> di database ini
                (mungkin database masih baru/belum dimigrasi). Buat akun baru dulu lewat form di bawah.
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <hr style="border:none;border-top:1px solid #E4E8EF;margin:24px 0;">

        <h2 style="font-size:16px;">Buat Akun Baru</h2>

        <?php if ($suksesBuat): ?>
            <div class="ok"><?= htmlspecialchars($suksesBuat) ?></div>
            <p><a href="login.php">← Ke halaman login</a></p>
        <?php else: ?>

            <?php if ($errorBuat): ?>
                <div class="err"><?= htmlspecialchars($errorBuat) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Username</label>
                <input type="text" name="username_baru" required placeholder="mis. APTIKA atau Kepegawaian">

                <label>Nama Lengkap</label>
                <input type="text" name="nama_baru" required placeholder="Nama pemilik akun">

                <label>Role</label>
                <select name="role_baru" required>
                    <option value="">-- pilih role --</option>
                    <option value="superadmin">superadmin (Super Admin / APTIKA)</option>
                    <option value="kepegawaian">kepegawaian (Bagian Kepegawaian)</option>
                </select>

                <label>Password (minimal 8 karakter)</label>
                <input type="text" name="password_baru_akun" required minlength="8" placeholder="Ketik password untuk akun baru">

                <button type="submit" name="buat_akun">Buat Akun</button>
            </form>
        <?php endif; ?>

        <p style="font-size:11.5px;color:#8a94a6;margin-top:18px;">
            Sesi darurat ini otomatis berakhir 10 menit setelah passphrase dimasukkan.
        </p>

    <?php endif; ?>
</div>
</body>
</html>
