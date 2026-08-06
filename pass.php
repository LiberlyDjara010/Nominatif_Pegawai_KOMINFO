<?php

define('KATA_KUNCI_RAHASIA', 'secret$!'); // <-- GANTI INI

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lock'])) {
    $_SESSION = [];
    session_destroy();
    http_response_code(204);
    exit;
}

$sudahMasukKunci = !empty($_SESSION['kunci_darurat_ok']);
$errorKunci = '';
$hash = '';
$password = '';
$errorPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['kata_kunci'])) {
        // LANGKAH 1: verifikasi kata kunci rahasia
        if ($_POST['kata_kunci'] === KATA_KUNCI_RAHASIA) {
            $_SESSION['kunci_darurat_ok'] = true;
            $sudahMasukKunci = true;
        } else {
            $errorKunci = 'Kata kunci salah.';
        }
    } elseif (isset($_POST['password']) && $sudahMasukKunci) {
        // LANGKAH 2: generate hash (hanya kalau sudah lolos langkah 1)
        $password = $_POST['password'];
        if ($password === '') {
            $errorPassword = 'Password baru wajib diisi.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Hash Password (Darurat)</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #eef4ff; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 560px; background: #fff; border-radius: 14px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        h3 { margin: 0 0 8px; color: #0c3a6b; }
        p { color: #5a6a7e; font-size: 13.5px; line-height: 1.6; }
        .field { margin: 16px 0; }
        .field label { display: block; margin-bottom: 6px; font-weight: 600; color: #1e2d45; font-size: 14px; }
        .field input { width: 100%; padding: 11px 12px; border: 1px solid #d7deea; border-radius: 10px; font-size: 14px; }
        .btn { width: 100%; background: linear-gradient(135deg, #1a5fa8, #2d8cff); color: #fff; border: none; border-radius: 10px; padding: 12px; font-weight: 700; cursor: pointer; font-size: 14px; }
        .hasil { margin-top: 20px; padding: 14px; background: #f0fff8; border: 1px solid #86efac; border-radius: 10px; }
        .hasil label { font-weight: 700; color: #166534; font-size: 13px; display: block; margin-bottom: 6px; }
        .hasil textarea { width: 100%; padding: 10px; border: 1px solid #86efac; border-radius: 8px; font-family: monospace; font-size: 12.5px; resize: none; }
        .warn { background: #fff8e1; border: 1px solid #ffd54f; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; color: #6d4c00; font-size: 13px; }
        .err { background: #fff0ee; border: 1px solid #ffb3ae; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; color: #d93025; font-size: 13px; }
        ol { padding-left: 18px; font-size: 13.5px; color: #5a6a7e; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">

        <?php if (!$sudahMasukKunci): ?>

            <h3>Akses Terbatas</h3>
            <p>Masukkan kata kunci rahasia untuk melanjutkan.</p>

            <?php if ($errorKunci): ?>
                <div class="err"><?= htmlspecialchars($errorKunci) ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="field">
                    <label>Kata Kunci Rahasia</label>
                    <input type="password" name="kata_kunci" required autofocus>
                </div>
                <button type="submit" class="btn">Masuk</button>
            </form>

        <?php else: ?>

            <h3>Generate Hash Password (Darurat)</h3>
            <p>Dipakai HANYA kalau akun Super Admin terkunci dan tidak bisa login sama sekali.</p>

            <div class="warn">
                Ini bukan cara "reset password" langsung. Halaman ini cuma bikin kode hash-nya.
                Kamu tetap perlu masuk ke <strong>phpMyAdmin</strong> untuk menempelkannya ke database.
                Kalau tab ini ditinggal/disembunyikan, sesi ini otomatis keluar dan harus masukkan kata kunci lagi.
            </div>

            <?php if ($errorPassword): ?>
                <div class="err"><?= htmlspecialchars($errorPassword) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label>Password Baru yang Diinginkan</label>
                    <input type="text" name="password" value="<?= htmlspecialchars($password) ?>" placeholder="Ketik password baru di sini" required>
                </div>
                <button type="submit" class="btn">Generate Hash</button>
            </form>

            <?php if ($hash): ?>
            <div class="hasil">
                <label>Hash untuk password "<?= htmlspecialchars($password) ?>":</label>
                <textarea rows="2" readonly onclick="this.select()"><?= htmlspecialchars($hash) ?></textarea>
            </div>

            <p><strong>Langkah selanjutnya di phpMyAdmin:</strong></p>
            <ol>
                <li>Buka database <code>nominatif_pegawai</code> → tabel <code>user</code></li>
                <li>Klik <strong>Edit</strong> pada baris akun Super Admin yang mau direset</li>
                <li>Copy hash di atas, tempel ke kolom <code>password</code> (timpa isi lama)</li>
                <li>Klik <strong>Go</strong> / simpan</li>
                <li>Login lagi pakai password baru yang tadi diketik</li>
            </ol>
            <?php endif; ?>

        <?php endif; ?>

        </div>
    </div>

    <script>

        var lockTimer = null;

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                lockTimer = setTimeout(function () {
                    var url = window.location.pathname + '?lock=1';
                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(url);
                    } else {
                        fetch(url, { method: 'GET', keepalive: true });
                    }
                }, 2000);
            } else {

            if (lockTimer) {
                    clearTimeout(lockTimer);
                    lockTimer = null;
                }
            }
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
