# Rencana Implementasi — Fase 1: Fondasi (Laravel 13 + Filament v4)

- **Tanggal**: 2026-08-16
- **Mengacu**: `docs/specs/2026-08-16-restrukturisasi-design.md`
- **Lingkungan**: Laragon — Apache + PHP 8.3.30 (C:\laragon\bin\php) + MySQL + Composer 2.10.2. Shell = Windows PowerShell.
- **Subsistem**: Hanya Fase 1 (Fondasi). Fase 2–4 mendapat dokumen rencana terpisah setelah fase ini selesai.

## Goal
Membangun aplikasi Laravel 13 yang terhubung ke database `nominatif_pegawai` existing (data aman), dengan skema ter-versi, model Eloquent, panel Filament v4 yang bisa di-boot, seeder super admin, dan smoke-test Pest — tanpa mengubah aplikasi legacy yang masih berjalan di root.

## Architecture
Aplikasi Laravel dibangun di subfolder `laravel-app/` (strangler), terhubung ke DB `nominatif_pegawai` yang sama dengan aplikasi lama. Migrasi dibuat **idempotent** (`Schema::hasTable`) sehingga tidak menyentuh tabel yang sudah ada — pada DB existing hanya tercatat di tabel `migrations`; pada DB baru tabel dibuat dari skema yang disimpulkan dari kode legacy. Panel Filament memakai model `User` dengan tabel `user` (username + password bcrypt + role), kompatibel dengan akun existing.

## Tech Stack
Laravel 13 (PHP 8.3), MySQL 8, Eloquent ORM, Filament v4 (`~4.0`), Pest, Composer.

## Global Constraints
- Semua UI/komentar/commit message berbahasa Indonesia.
- **JANGAN pernah menampilkan/menulis ulang kata sandi DB** (ada di `koneksi.php` line 4) ke dalam file apa pun yang ter-commit; hanya ke `.env` (gitignored).
- **`LOGO_DISKOMINFO.png` di root TIDAK BOLEH dihapus/dipindah** — dipakai sebagai logo website. Salin (jangan pindah) ke `laravel-app/public/LOGO_DISKOMINFO.png` untuk dipakai panel.
- Jangan jalankan test terhadap DB produksi `nominatif_pegawai` — pakai DB test `nominatif_pegawai_test`.
- Verifikasi tiap file PHP yang dibuat: `php -l <file>`.
- Commit kecil-kecil setelah tiap task; file `.env` dan `vendor/` tidak boleh ikut commit.
- Windows PowerShell: perintah composer dengan `^` harus diganti `~` (contoh: `composer require filament/filament:"~4.0"`).
- Task disusun sekuensial; yang belakang bergantung pada yang depan.

---

## Task 1 — Buat proyek Laravel 13 di `laravel-app/`

**Files**: `laravel-app/` (baru, seluruh skeleton)

**Interfaces**: tidak ada (skeleton standar)

**Steps**:
1. Jalankan di root repo:
   ```
   composer create-project laravel/laravel:^13.0 laravel-app
   ```
2. Verifikasi versi:
   ```
   php laravel-app/artisan --version
   ```
   Output harus `Laravel Framework 13.x`.
3. Cek PHP lint file entry:
   ```
   php -l laravel-app/public/index.php
   php -l laravel-app/artisan
   ```
   Output: `No syntax errors detected in ...`.
4. Git init di dalam `laravel-app` TIDAK perlu (repo root sudah git). Pastikan `.gitignore` bawaan Laravel ada (`Test-Path laravel-app\.gitignore`) — file ini mengabaikan `vendor/`, `.env`, `storage/` secara otomatis.
5. Salin logo (jangan dipindah/dihapus yang di root):
   ```
   Copy-Item LOGO_DISKOMINFO.png laravel-app\public\LOGO_DISKOMINFO.png
   ```
6. Commit:
   ```
   git add laravel-app
   git commit -m "Skeleton Laravel 13 untuk restrukturisasi (strangler)"
   ```

## Task 2 — Hubungkan database `nominatif_pegawai` existing

**Files**: `laravel-app/.env` (modify), `laravel-app/.env.example` (modify)

**Interfaces**: env `DB_*` dikonsumsi framework

**Steps**:
1. Edit `laravel-app/.env`: atur blok koneksi MySQL:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nominatif_pegawai
   DB_USERNAME=root
   DB_PASSWORD=<SALIN NILAI DB_PASS dari koneksi.php line 4 — jangan ditulis ke file ter-commit mana pun>
   ```
   (`DB_HOST` pakai `127.0.0.1`, bukan `localhost`, karena PHP 8.3 MySQLi bisa salah tangkap IPv6.)
2. Tambahkan placeholder ke `laravel-app/.env.example` (nilai kosong, aman untuk di-commit):
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nominatif_pegawai
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Verifikasi koneksi:
   ```
   php laravel-app/artisan db:show
   ```
   Harus menampilkan `database: nominatif_pegawai` dengan `Host 127.0.0.1`. Jika error koneksi, cek kredensial di `.env` (jangan pernah menampilkan password ke console/log repo).
4. Commit:
   ```
   git add laravel-app/.env.example
   git commit -m "Konfigurasi koneksi DB nominatif_pegawai di .env"
   ```

## Task 3 — Migrasi idempotent: `pegawai`, `user`, `checklist_pangkat`, index

**Files**:
- `laravel-app/database/migrations/2026_08_16_000001_create_pegawai_table.php` (create)
- `laravel-app/database/migrations/2026_08_16_000002_create_user_table.php` (create)
- `laravel-app/database/migrations/2026_08_16_000003_create_checklist_pangkat_table.php` (create)
- `laravel-app/database/migrations/2026_08_16_000004_add_indexes_to_pegawai.php` (create)

**Interfaces**: tabel dipakai model Eloquent Task 4; skema = hasil simpulan dari kode legacy (tambah.php `$semuaKolomForm`, migrasi_lengkap.php, `pastikanTabelChecklist()` di koneksi.php, query dashboard/pegawai).

**Steps**:
1. Buat `2026_08_16_000001_create_pegawai_table.php`:
   ```php
   <?php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           if (Schema::hasTable('pegawai')) {
               return; // DB existing: jangan sentuh data
           }
           Schema::create('pegawai', function (Blueprint $table) {
               $table->id();
               $table->string('nip', 30)->nullable();
               $table->string('nik', 30)->nullable();
               $table->string('nama')->nullable();
               $table->string('tempat_lahir')->nullable();
               $table->date('tanggal_lahir')->nullable();
               $table->string('jenis_kelamin', 10)->nullable();
               $table->string('agama', 30)->nullable();
               $table->string('suku')->nullable();
               $table->enum('kategori_suku', ['Papua', 'Non-Papua'])->nullable();
               $table->enum('status_kepegawaian', ['CPNS', 'PNS', 'PPPK'])->default('PNS');
               $table->string('pendidikan_terakhir', 50)->nullable();
               $table->string('pendidikan_jurusan', 150)->nullable();
               $table->string('pangkat_terakhir')->nullable();
               $table->string('golongan', 10)->nullable();
               $table->string('jabatan')->nullable();
               $table->string('jenis_jabatan', 30)->default('pelaksana');
               $table->string('status_jabatan', 30)->nullable();
               $table->string('unit_organisasi', 150)->nullable();
               $table->date('tmt_jabatan')->nullable();
               $table->date('tanggal_masuk')->nullable();
               $table->date('tanggal_pangkat_terakhir')->nullable();
               $table->unsignedTinyInteger('masa_kerja_tahun')->nullable();
               $table->unsignedTinyInteger('masa_kerja_bulan')->nullable();
               $table->string('sk_pejabat', 150)->nullable();
               $table->string('sk_nomor', 100)->nullable();
               $table->date('sk_tanggal')->nullable();
               $table->string('sk_jabatan_pejabat', 150)->nullable();
               $table->string('sk_jabatan_nomor', 100)->nullable();
               $table->date('sk_jabatan_tanggal')->nullable();
               $table->string('skp_2_tahun', 20)->nullable();
               $table->text('keterangan')->nullable();
           });
       }

       public function down(): void
       {
           Schema::dropIfExists('pegawai');
       }
   };
   ```
2. Buat `2026_08_16_000002_create_user_table.php`:
   ```php
   <?php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           if (Schema::hasTable('user')) {
               return; // DB existing: jangan sentuh akun
           }
           Schema::create('user', function (Blueprint $table) {
               $table->id();
               $table->string('username')->unique();
               $table->string('nama')->nullable();
               $table->string('role', 30)->default('kepegawaian');
               $table->string('password');
           });
       }

       public function down(): void
       {
           Schema::dropIfExists('user');
       }
   };
   ```
3. Buat `2026_08_16_000003_create_checklist_pangkat_table.php` (meniru `pastikanTabelChecklist()`):
   ```php
   <?php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           if (Schema::hasTable('checklist_pangkat')) {
               return;
           }
           Schema::create('checklist_pangkat', function (Blueprint $table) {
               $table->id();
               $table->unsignedBigInteger('pegawai_id');
               $table->string('item_key', 50);
               $table->boolean('checked')->default(false);
               $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
               $table->unique(['pegawai_id', 'item_key']);
           });
       }

       public function down(): void
       {
           Schema::dropIfExists('checklist_pangkat');
       }
   };
   ```
4. Buat `2026_08_16_000004_add_indexes_to_pegawai.php` (jalankan kondisi kolom ada — aman untuk DB existing dan baru):
   ```php
   <?php
   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Support\Facades\DB;

   return new class extends Migration
   {
       public function up(): void
       {
           $cols = array_column(DB::select('SHOW COLUMNS FROM pegawai'), 'Field');
           $pairs = [
               ['idx_pgw_golongan', 'golongan'],
               ['idx_pgw_jk', 'jenis_kelamin'],
               ['idx_pgw_status_kepegawaian', 'status_kepegawaian'],
               ['idx_pgw_kategori_suku', 'kategori_suku'],
               ['idx_pgw_jabatan', 'jabatan'],
               ['idx_pgw_tanggal_lahir', 'tanggal_lahir'],
               ['idx_pgw_tanggal_pangkat', 'tanggal_pangkat_terakhir'],
           ];
           foreach ($pairs as [$indexName, $column]) {
               if (!in_array($column, $cols, true)) {
                   continue;
               }
               DB::statement("ALTER TABLE pegawai ADD INDEX {$indexName} ({$column})");
           }
       }

       public function down(): void
       {
           DB::statement('DROP INDEX idx_pgw_golongan ON pegawai');
           DB::statement('DROP INDEX idx_pgw_jk ON pegawai');
           DB::statement('DROP INDEX idx_pgw_status_kepegawaian ON pegawai');
           DB::statement('DROP INDEX idx_pgw_kategori_suku ON pegawai');
           DB::statement('DROP INDEX idx_pgw_jabatan ON pegawai');
           DB::statement('DROP INDEX idx_pgw_tanggal_lahir ON pegawai');
           DB::statement('DROP INDEX idx_pgw_tanggal_pangkat ON pegawai');
       }
   };
   ```
   **Catatan**: pada DB existing tabel `pegawai` mungkin belum punya semua kolom di atas; karena migrasi `000001` no-op, kolom yang benar-benar ada diambil dari live DB saat runtime. Jangan menjalankan `migrate:fresh` ke DB ini.
5. Jalankan migrasi:
   ```
   php laravel-app/artisan migrate
   ```
   Output: tabel `migrations` dibuat + 4 record "Ran". Pada DB existing tabel data tidak berubah (guard `hasTable`).
6. Verifikasi:
   ```
   php laravel-app/artisan migrate:status
   php -l laravel-app/database/migrations/2026_08_16_000001_create_pegawai_table.php
   php -l laravel-app/database/migrations/2026_08_16_000002_create_user_table.php
   php -l laravel-app/database/migrations/2026_08_16_000003_create_checklist_pangkat_table.php
   php -l laravel-app/database/migrations/2026_08_16_000004_add_indexes_to_pegawai.php
   ```
   `migrate:status` menampilkan keempat migrasi berstatus `Ran`.
7. Commit:
   ```
   git add laravel-app/database/migrations
   git commit -m "Migrasi idempotent untuk tabel pegawai, user, checklist_pangkat, dan index"
   ```

## Task 4 — Model Eloquent: `Pegawai`, `User`, `ChecklistItem`

**Files**:
- `laravel-app/app/Models/Pegawai.php` (create)
- `laravel-app/app/Models/User.php` (overwrite default)
- `laravel-app/app/Models/ChecklistItem.php` (create)

**Interfaces**:
- `Pegawai::query()` — query builder untuk resource/widget Fase 3
- `$pegawai->checklistItems` — relasi hasMany (dipakai eager loading `with('checklistItems')`)
- `ChecklistItem::whereIn('pegawai_id', $ids)` — batching anti N+1 (Fase 3)

**Steps**:
1. Tulis `laravel-app/app/Models/Pegawai.php`:
   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\Relations\HasMany;

   class Pegawai extends Model
   {
       use HasFactory;

       protected $table = 'pegawai';

       public $timestamps = false;

       protected $fillable = [
           'nip', 'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
           'agama', 'suku', 'kategori_suku', 'status_kepegawaian',
           'pendidikan_terakhir', 'pendidikan_jurusan', 'pangkat_terakhir',
           'golongan', 'jabatan', 'jenis_jabatan', 'status_jabatan', 'unit_organisasi',
           'tmt_jabatan', 'tanggal_masuk', 'tanggal_pangkat_terakhir',
           'masa_kerja_tahun', 'masa_kerja_bulan', 'sk_pejabat', 'sk_nomor',
           'sk_tanggal', 'sk_jabatan_pejabat', 'sk_jabatan_nomor', 'sk_jabatan_tanggal',
           'skp_2_tahun', 'keterangan',
       ];

       protected $casts = [
           'tanggal_lahir'          => 'date',
           'tmt_jabatan'            => 'date',
           'tanggal_masuk'          => 'date',
           'tanggal_pangkat_terakhir' => 'date',
           'sk_tanggal'             => 'date',
           'sk_jabatan_tanggal'     => 'date',
           'masa_kerja_tahun'       => 'integer',
           'masa_kerja_bulan'       => 'integer',
       ];

       public function checklistItems(): HasMany
       {
           return $this->hasMany(ChecklistItem::class, 'pegawai_id');
       }
   }
   ```
2. Tulis `laravel-app/app/Models/User.php` (tabel `user`, bukan `users`):
   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Factories\HasFactory;
   use Illuminate\Foundation\Auth\User as Authenticatable;

   class User extends Authenticatable
   {
       use HasFactory;

       protected $table = 'user';

       public $timestamps = false;

       protected $fillable = ['username', 'nama', 'role', 'password'];

       protected $hidden = ['password'];

       protected $casts = [
           'password' => 'hashed',
       ];

       public function isSuperAdmin(): bool
       {
           $role = strtolower(trim((string) $this->role));
           return in_array($role, ['super admin', 'superadmin', 'aptika', 'admin'], true)
               || str_contains($role, 'super')
               || str_contains($role, 'aptika');
       }

       public function canManageData(): bool
       {
           return $this->isSuperAdmin() || in_array(strtolower(trim((string) $this->role)), ['kepegawaian', 'bagian kepegawaian', 'user'], true);
       }
   }
   ```
3. Tulis `laravel-app/app/Models/ChecklistItem.php`:
   ```php
   <?php

   namespace App\Models;

   use Illuminate\Database\Eloquent\Model;
   use Illuminate\Database\Eloquent\Relations\BelongsTo;

   class ChecklistItem extends Model
   {
       protected $table = 'checklist_pangkat';

       public $timestamps = false;

       protected $fillable = ['pegawai_id', 'item_key', 'checked'];

       protected $casts = [
           'checked' => 'boolean',
       ];

       public function pegawai(): BelongsTo
       {
           return $this->belongsTo(Pegawai::class, 'pegawai_id');
       }
   }
   ```
4. Konfigurasi auth provider Laravel ke tabel `user`. Edit `laravel-app/config/auth.php` di bagian providers:
   ```php
   'providers' => [
       'users' => [
           'driver' => 'eloquent',
           'model'  => App\Models\User::class,
       ],
   ],
   ```
   (Pastikan model mengarah `App\Models\User`; tabel sudah di-pin di model.)
5. Verifikasi lint:
   ```
   php -l laravel-app/app/Models/Pegawai.php
   php -l laravel-app/app/Models/User.php
   php -l laravel-app/app/Models/ChecklistItem.php
   ```
   Output `No syntax errors detected`.
6. Verifikasi runtime query tanpa menyentuh data:
   ```
   php laravel-app/artisan tinker --execute="echo App\\Models\\Pegawai::count();"
   ```
   Menampilkan jumlah baris pegawai existing (harus sama dengan jumlah di aplikasi lama).
7. Commit:
   ```
   git add laravel-app/app/Models laravel-app/config/auth.php
   git commit -m "Model Eloquent Pegawai, User, ChecklistItem dengan relasi dan role helper"
   ```

## Task 5 — Instal Filament v4 panel

**Files**: seluruh yang dihasilkan `filament:install` (provider, config, direktori `app/Filament/Resources|Pages`)

**Interfaces**: `/admin` route terdaftar; `AdminPanelProvider` memakai guard auth Laravel default

**Steps**:
1. Instal package Filament (ganti `^` dengan `~` untuk PowerShell):
   ```
   php laravel-app/artisan optimize:clear
   composer --working-dir=laravel-app require filament/filament:"~4.0"
   ```
2. Jalankan installer panel (tekan Enter untuk panel ID default `admin`):
   ```
   php laravel-app/artisan filament:install --panels
   ```
3. Verifikasi provider terdaftar — cek `laravel-app/bootstrap/providers.php` memuat `App\Providers\Filament\AdminPanelProvider::class`. Jika belum, tambahkan manual.
4. Verifikasi route:
   ```
   php laravel-app/artisan route:list
   ```
   Harus ada route `/admin/login` (GET) dan `/admin` (GET).
5. Cek lint file yang dihasilkan:
   ```
   php -l laravel-app/app/Providers/Filament/AdminPanelProvider.php
   php -l laravel-app/app/Filament/Pages/Dashboard.php
   ```
6. Jalankan server dev sejenak dan cek login page mengembalikan HTML:
   ```
   php laravel-app/artisan serve
   ```
   Buka `http://127.0.0.1:8000/admin/login` — halaman login Filament tampil (tanpa error 500). Matikan server (`Ctrl+C`).
   **Catatan**: login default Filament memakai kolom email; penyesuaian ke `username` dikerjakan di Fase 3 bersama role/Shield.
7. Commit:
   ```
   git add laravel-app
   git commit -m "Instal Filament v4 panel admin (entry /admin)"
   ```
   (Pastikan `.env` dan `vendor/` tidak ikut ter-add — cek `git status` sebelum commit.)

## Task 6 — Seeder super admin (idempotent, aman untuk akun existing)

**Files**:
- `laravel-app/database/seeders/SuperAdminSeeder.php` (create)
- `laravel-app/.env.example` (modify — tambah var seed)
- `laravel-app/.env` (modify — isi kredensial seed lokal)

**Interfaces**: seed dipanggil via `php artisan db:seed --class=SuperAdminSeeder`

**Steps**:
1. Tulis `laravel-app/database/seeders/SuperAdminSeeder.php`:
   ```php
   <?php

   namespace Database\Seeders;

   use Illuminate\Database\Seeder;
   use Illuminate\Support\Facades\DB;
   use Illuminate\Support\Facades\Hash;

   class SuperAdminSeeder extends Seeder
   {
       public function run(): void
       {
           $adaSuperAdmin = DB::table('user')
               ->whereRaw("LOWER(role) IN ('super admin','superadmin','aptika','admin') OR LOWER(role) LIKE '%super%' OR LOWER(role) LIKE '%aptika%'")
               ->exists();

           if ($adaSuperAdmin) {
               $this->command?->info('Super admin sudah ada — seeder dilewati.');
               return;
           }

           $username = env('SEED_SUPERADMIN_USERNAME', 'aptika');
           $password = env('SEED_SUPERADMIN_PASSWORD');

           if (empty($password) || strlen($password) < 8) {
               throw new \RuntimeException('SEED_SUPERADMIN_PASSWORD belum diatur/minimal 8 karakter di .env');
           }

           DB::table('user')->insertOrIgnore([
               'username' => $username,
               'nama'     => 'Super Admin APTIKA',
               'role'     => 'super admin',
               'password' => Hash::make($password),
           ]);
       }
   }
   ```
2. Tambah ke `laravel-app/.env.example`:
   ```
   SEED_SUPERADMIN_USERNAME=aptika
   SEED_SUPERADMIN_PASSWORD=
   ```
3. Isi `SEED_SUPERADMIN_PASSWORD` di `laravel-app/.env` (nilai lokal, tidak di-commit) dengan password sementara kuat (mis. buat acak ≥12 karakter) — jangan dipakai untuk produksi.
4. Jalankan seeder:
   ```
   php laravel-app/artisan db:seed --class=SuperAdminSeeder
   ```
   Output: "Super admin sudah ada..." (jika DB existing sudah punya super admin) atau sukses membuat akun baru.
5. Verifikasi jumlah akun tidak bertambah duplikat — jalankan ulang seeder, harus idempotent (tanpa baris baru).
6. Commit:
   ```
   git add laravel-app/database/seeders laravel-app/.env.example
   git commit -m "Seeder super admin idempotent berbasis env"
   ```

## Task 7 — Pest + smoke test dengan DB test terpisah

**Files**:
- `laravel-app/tests/Feature/AppBootsTest.php` (create)
- `laravel-app/tests/Feature/DatabaseConnectionTest.php` (create)
- `laravel-app/.env.testing` (create — DB test)
- `laravel-app/phpunit.xml` (modify jika perlu)

**Interfaces**: `php artisan test` — semua test hijau; tidak menyentuh DB produksi

**Steps**:
1. Buat DB test MySQL:
   ```
   mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS nominatif_pegawai_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```
   (Jika prompt password, gunakan password yang sama dengan `.env`. Laragon MySQL root umumnya tanpa password.)
2. Buat `laravel-app/.env.testing`:
   ```
   APP_ENV=testing
   APP_KEY=<salin dari .env>
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nominatif_pegawai_test
   DB_USERNAME=root
   DB_PASSWORD=<salin dari .env>
   CACHE_STORE=array
   SESSION_DRIVER=array
   ```
3. Instal Pest:
   ```
   composer --working-dir=laravel-app require pestphp/pest --dev
   php laravel-app/artisan pest:install
   ```
4. Tulis `laravel-app/tests/Feature/AppBootsTest.php`:
   ```php
   <?php

   use Illuminate\Foundation\Testing\RefreshDatabase;

   uses(RefreshDatabase::class);

   test('halaman login panel admin merespons 200', function () {
       $this->get('/admin/login')->assertStatus(200);
   });

   test('halaman root panel admin mengalihkan ke login', function () {
       $this->get('/admin')->assertRedirect();
   });
   ```
5. Tulis `laravel-app/tests/Feature/DatabaseConnectionTest.php`:
   ```php
   <?php

   use Illuminate\Foundation\Testing\RefreshDatabase;

   uses(RefreshDatabase::class);

   test('koneksi DB dan migrasi berjalan', function () {
       $this->assertTrue(DB::connection()->getPdo() instanceof PDO);
       $this->assertTrue(Schema::hasTable('pegawai'));
       $this->assertTrue(Schema::hasTable('user'));
       $this->assertTrue(Schema::hasTable('checklist_pangkat'));
   });
   ```
   (Tanpa `RefreshDatabase` pada test login; dengan `RefreshDatabase` pada test DB — pastikan dipisah agar login tidak butuh migrasi ulang. Jika run bersamaan dengan migrasi DB test kosong, login tetap 200 karena Filament hanya merender form.)
6. Jalankan test:
   ```
   php laravel-app/artisan test
   ```
   Semua test hijau (PASSED). Jika `RefreshDatabase` di test DB berjalan normal terhadap `nominatif_pegawai_test`, tidak ada risiko pada DB produksi.
7. Verifikasi lint file baru:
   ```
   php -l laravel-app/tests/Feature/AppBootsTest.php
   php -l laravel-app/tests/Feature/DatabaseConnectionTest.php
   ```
8. Commit:
   ```
   git add laravel-app/tests laravel-app/.env.testing.example 2>$null; git add laravel-app/phpunit.xml laravel-app/composer.json laravel-app/composer.lock
   git commit -m "Smoke test Pest dengan DB test terpisah"
   ```
   (Catatan: `.env.testing` bersifat lokal — jangan di-commit; simpan contoh sebagai `.env.testing.example` bila perlu.)

## Task 8 — Cek akhir Fase 1 & commit

**Files**: `docs/` (penanda kemajuan, opsional)

**Steps**:
1. Jalankan rangkaian verifikasi lengkap:
   ```
   php -l laravel-app/app/Models/*.php
   php laravel-app/artisan migrate:status
   php laravel-app/artisan route:list | Select-String "/admin"
   php laravel-app/artisan test
   ```
2. Pastikan tidak ada secret baru ter-commit:
   ```
   git status --porcelain
   ```
   `.env`, `.env.testing`, `vendor/`, `storage/` TIDAK muncul.
3. Commit akhir (jika ada sisa):
   ```
   git add .
   git commit -m "Fase 1 fondasi selesai: Laravel 13 + Filament v4 terhubung DB existing"
   ```

---

## Self-review rencana

- **Cakupan spec**: semua item Fase 1 design doc tercakup (proyek L13, koneksi DB, migrasi skema+index, model+relasi, Filament panel, seeder, test, secret-safe).
- **Placeholder**: tidak ada "TBD"/"TODO"; semua langkah berisi perintah/kode konkret. Satu pengecualian disengaja: nilai `DB_PASSWORD`/`SEED_SUPERADMIN_PASSWORD` tidak ditulis eksplisit demi keamanan — diganti instruksi "salin dari koneksi.php/.env".
- **Konsistensi tipe**: tabel/model konsisten (`pegawai` ↔ `Pegawai`, `user` ↔ `User`, `checklist_pangkat` ↔ `ChecklistItem`); relasi `pegawai_id` konsisten; guard `hasTable` konsisten di semua migrasi.

## Handoff eksekusi

Pilih mode eksekusi:
1. **Subagent-Driven** — subagent baru per task, review antar task.
2. **Inline Execution** — dieksekusi di sesi ini dengan checkpoint per task.
