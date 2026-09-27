<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $namaPegawai = trim($_POST['nama_pegawai'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($namaPegawai == '' || $username == '' || $password == '') {
        $error = "Nama, Username, dan Password wajib diisi.";
    } else {

        $stmt = $conn->prepare("
            SELECT *
            FROM user
            WHERE username=?
            LIMIT 1
        ");

        $stmt->bind_param("s",$username);
        $stmt->execute();

        $result = $stmt->get_result();

        if($result->num_rows==1){

            $user = $result->fetch_assoc();

            if(password_verify($password,$user['password'])){

                catatLoginLog($conn, $namaPegawai, $username, $user['role'], 'berhasil');

                session_regenerate_id(true);

                $_SESSION['login']=true;

                $_SESSION['id']=$user['id'];

                $_SESSION['user']=$user['username'];

                $_SESSION['username']=$user['username'];

                $_SESSION['nama']=$user['nama'];

                $_SESSION['nama_login']=$namaPegawai;

                $_SESSION['role']=$user['role'];

                $_SESSION['last_activity']=time();

                session_write_close();

                header("Location: dashboard.php");
                exit;

            }else{

                catatLoginLog($conn, $namaPegawai, $username, $user['role'] ?? null, 'gagal');

                $error="Username atau Password salah.";

            }

        }else{

            catatLoginLog($conn, $namaPegawai, $username, null, 'gagal');

            $error="Username atau Password salah.";

        }

    }

}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Sistem Nominatif Pegawai Diskominfo Papua</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --biru-tua:   #0C3A6B;
    --biru:       #1A5FA8;
    --biru-muda:  #2D8CFF;
    --putih:      #FFFFFF;
    --abu-bg:     #EEF2F7;
    --teks-gelap: #1E2D45;
    --teks-abu:   #5A6A7E;
    --merah:      #D93025;
}

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background:
        radial-gradient(circle at left top, rgba(60,150,255,0.22), transparent 24%),
        linear-gradient(135deg, #0a4a7c 0%, #0a3d67 45%, #08365a 100%);
}

.login-shell {
    width: min(1280px, 100%);
    min-height: 760px;
    display: grid;
    grid-template-columns: 1.05fr 0.95fr;
    border-radius: 24px;
    overflow: hidden;
    background: rgba(255,255,255,0.08);
    box-shadow: 0 30px 90px rgba(0,0,0,0.22);
}

.brand-panel {
    position: relative;
    padding: 64px 56px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background:
        linear-gradient(160deg, rgba(0,0,0,0.14), transparent 45%),
        linear-gradient(135deg, #0c5c93, #0a4370 60%, #093352);
    color: var(--putih);
}

.brand-panel::after {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at bottom left, rgba(255,255,255,0.10), transparent 20%),
        radial-gradient(circle at top right, rgba(255,255,255,0.06), transparent 18%);
    pointer-events: none;
}

.brand-content,
.brand-footer {
    position: relative;
    z-index: 1;
}

.brand-logo-row {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    margin-bottom: 6px;
}

.brand-logo-row img {
    width: 500px;
    height: 170px;
    object-fit: contain;
    filter: drop-shadow(0 10px 20px rgba(0,0,0,0.22));
}

.brand-text {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.brand-text .brand-title {
    font-size: 30px;
    font-weight: 900;
    letter-spacing: 0.4px;
}

.brand-text .brand-subtitle {
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1.6px;
    opacity: 0.92;
}

.brand-title-main {
    font-size: clamp(2.2rem, 3.4vw, 3rem);
    font-weight: 900;
    line-height: 1.06;
    margin: 0 0 10px 0;
    max-width: 560px;
}

.brand-description {
    max-width: 560px;
    font-size: 1.05rem;
    color: rgba(255,255,255,0.84);
    line-height: 1.7;
}

.brand-footer {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.72);
}

.form-panel {
    background: #f4f7fb;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 42px;
}

.login-card {
    width: min(100%, 470px);
    background: rgba(255,255,255,0.98);
    border-radius: 24px;
    padding: 34px 32px;
    box-shadow: 0 18px 60px rgba(13, 46, 84, 0.16);
}

.login-card h2 {
    font-size: 34px;
    font-weight: 900;
    color: #14263a;
    margin-bottom: 6px;
}

.login-card .sub {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 22px;
}

.field { margin-bottom: 16px; }

.field label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: #334155;
    margin-bottom: 8px;
    letter-spacing: 0.45px;
    text-transform: uppercase;
}

.field input {
    width: 100%;
    padding: 14px 15px;
    border: 1.5px solid #d8e1ec;
    border-radius: 12px;
    font-size: 14px;
    font-family: inherit;
    color: #14263a;
    background: #f8fbff;
    transition: border-color .2s, box-shadow .2s, background .2s;
    outline: none;
}

.field input:focus {
    border-color: #2d8cff;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(45,140,255,0.16);
}

.password-wrap {
    position: relative;
}

.password-wrap input {
    padding-right: 52px;
}

.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    color: #64748b;
    font-size: 20px;
    line-height: 1;
    padding: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.toggle-password:hover {
    background: rgba(26,95,168,0.08);
}

.toggle-password.closed::after {
    content: "";
    position: absolute;
    width: 1.4em;
    height: 2px;
    background: currentColor;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(45deg);
    border-radius: 1px;
}

.forgot-note {
    display: block;
    text-align: right;
    font-size: 12px;
    color: #64748b;
    margin-top: 8px;
}

.error-box {
    background: #fff2f0;
    border: 1px solid #ffb9b2;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #b42318;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-login {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #1e69bf, #2b8cff);
    color: var(--putih);
    border: none;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 900;
    font-family: inherit;
    cursor: pointer;
    letter-spacing: 0.5px;
    transition: transform .15s, box-shadow .15s, filter .15s;
    box-shadow: 0 12px 28px rgba(26,95,168,0.3);
    margin-top: 10px;
}

.btn-login:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
    box-shadow: 0 12px 28px rgba(26,95,168,0.4);
}

@media (max-width: 900px) {
    .login-shell {
        grid-template-columns: 1fr;
    }

    .brand-panel {
        min-height: 340px;
    }
}
</style>
</head>
<body>

<div class="login-shell">
    <section class="brand-panel">
        <div class="brand-content">
            <div class="brand-logo-row">
                <img src="LOGO_DISKOMINFO.png" alt="Logo Diskominfo Papua" />
            </div>

            <h1 class="brand-title-main">Sistem Nominatif Pegawai<br>Diskominfo Provinsi Papua</h1>
            <p class="brand-description">
                Aplikasi manajemen data kepegawaian untuk memudahkan pengelolaan,
                monitoring, dan pelaporan pegawai secara terstruktur.
            </p>
        </div>

        <div class="brand-footer">© <?= date('Y') ?> Dinas Komunikasi &amp; Informatika Provinsi Papua</div>
    </section>

    <section class="form-panel">
        <div class="login-card">
            <h2>Sign In</h2>
            <p class="sub">Welcome back! Please enter your details.</p>

            <?php if ($error): ?>
            <div class="error-box">⚠ <?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label for="nama_pegawai">Nama Anda</label>
                    <input type="text" id="nama_pegawai" name="nama_pegawai"
                           placeholder="Nama lengkap yang login" required
                           value="<?= e($_POST['nama_pegawai'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username"
                           placeholder="Masukkan username" required
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password closed"
                        id="togglePassword" aria-label="Lihat password">👁</button>
                    </div>
                    <span class="forgot-note">Lupa password? Hubungi Super Admin (APTIKA).</span>
                </div>

                <button type="submit" name="login" class="btn-login">MASUK →</button>
            </form>
        </div>
    </section>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    if (passwordInput && togglePassword) {
        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.classList.toggle('closed', !isHidden);
            togglePassword.setAttribute('aria-label', isHidden ? 'Sembunyikan Password' : 'Lihat Password');
        });
    }
</script>
</body>
</html>