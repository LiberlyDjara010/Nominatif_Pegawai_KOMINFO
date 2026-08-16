# Desain Restrukturisasi — Sistem Nominatif Pegawai Diskominfo Papua (Laravel 13 + Filament v4)

- **Tanggal**: 2026-08-16 (revisi stack: Laravel 13 + Filament)
- **Status**: Menunggu persetujuan final
- **Pendekatan**: Strangler pattern (migrasi bertahap, aplikasi lama tetap jalan)
- **Runtime**: Laragon (Apache + PHP 8.3.30 + MySQL 8 + Composer 2.10.2)

## 1. Latar belakang

Kondisi awal: 26 file PHP datar tanpa framework/Composer/test; `koneksi.php` (768 baris) = god module; kredensial DB hardcoded + backdoor (`pass.php`, `reset.txt`) ter-commit.

Tujuan:
- Pisahkan controller / request / logic secara nyata.
- Eager loading (anti N+1) tanpa menaikkan beban server.
- UI real-time (reaktif + live update lintas pengguna).
- Tanpa Blade untuk halaman (diputuskan user), lalu memilih panel admin Filament.
- Jembatan ke ekosistem Laravel modern + menutup lubang keamanan.

## 2. Tech stack final

| Komponen | Pilihan | Catatan |
|---|---|---|
| Framework | **Laravel 13** | PHP 8.3+; support bugfix Q3 2027, security Q1 2028 |
| Admin panel | **Filament v4** (≥ v4.12) | Kompatibel L13 terkonfirmasi (Filament 3 TIDAK) — panel form/tabel/widget real-time |
| Database | **MySQL 8** (Laragon) | DB `nominatif_pegawai` existing tetap, data aman |
| ORM | **Eloquent** | Eager loading `with()`, anti N+1 |
| Auth & role | Panel auth custom (tabel `user`) + **Filament Shield** (role super admin / kepegawaian) | Password bcrypt existing kompatibel |
| Spreadsheet | **maatwebsite/excel** | Export XLSX 24 kolom BKD + import CSV (deteksi delimiter) |
| Testing | **Pest** | Smoke test + Filament testing helpers |
| Real-time | **Filament polling** (widget/tabel, TTL 10–30 detik) default; **Reverb + Echo** opsional untuk push instan | Tanpa Node/WebSocket wajib |
| Keamanan | CSRF + origin check, session `HttpOnly`/`SameSite`, throttling login, header keamanan | Bawaan Laravel/Filament |
| Logging/Error | Monolog + exception handler | Ganti `die()` acak |
| Cache | File driver | Agregat dashboard (TTL 60 detik) |

Tidak dipakai (YAGNI): Vite/Node build, template Blade manual, frontend framework, Redis/queue, Laravel AI/JSON:API/passkey.

## 3. Struktur proyek

```
nominatif-pegawai/
├── app/
│   ├── Filament/
│   │   ├── Resources/          ← PegawaiResource, UserResource
│   │   ├── Pages/              ← halaman custom (mis. ImportPegawai, AkanPensiun, NaikPangkat)
│   │   └── Widgets/            ← StatistikPensiun, StatistikPangkat, GrafikGolongan, Notifikasi
│   ├── Models/                 ← Pegawai, User, ChecklistItem (relasi Eloquent, eager load)
│   ├── Services/               ← PensiunService, PangkatService, GajiService, OapService,
│   │                              ImportService, ExportService (logika bisnis, tanpa SQL/HTML)
│   ├── Support/                ← normalisasiTanggal(), normalisasiGolongan(), rupiah(), e()
│   ├── Policies/               ← PegawaiPolicy, UserPolicy (role-aware)
│   └── Providers/
├── bootstrap/app.php
├── config/ + .env              ← kredensial (gitignored)
├── database/migrations/        ← skema existing di-porting + index
├── database/seeders/           ← seeder super admin
├── routes/
├── resources/views/            ← minimal (Filament menangani UI)
├── public/                     ← docroot (vhost Laragon)
├── tests/                      ← Pest
└── storage/logs/
```

## 4. Pemetaan fitur existing → Filament

| Fitur lama | Implementasi baru |
|---|---|
| `layout.php` + halaman HTML | Panel Filament (UI otomatis) |
| Login + role (super admin/kepegawaian) | Auth panel custom + Shield |
| Dashboard statistik (golongan, JK, suku, pensiun) | Widget dashboard: StatsOverview + Chart (polling real-time) |
| Halaman Akan Pensiun | Page/widget tabel: filter sisa bulan ≤ 12, peringatan warna |
| Halaman Kenaikan Pangkat | Page/widget tabel: sisa bulan + syarat kelengkapan |
| Checklist berkas pangkat | RelationManager di PegawaiResource (`checklist_pangkat`) |
| Tambah/Edit Pegawai | Form PegawaiResource (25 kolom, tersegmentasi) |
| Import CSV / BKD | Page import + ImportService (maatwebsite/excel) |
| Export XLSX 24 kolom | Action export + ExportService (format BKD) |
| Info gaji pegawai | Kolom/field komputasi `gajiPokok()` |
| Kelola akun | UserResource (khusus super admin) |
| `pass.php` / `reset.txt` | DIHAPUS (backdoor) |

## 5. Eager loading tanpa membebani server

- Eloquent `with()` untuk relasi pegawai ↔ checklist_pangkat (1 query batching, bukan N+1).
- Query builder hanya memilih kolom yang dibutuhkan pada list.
- Pagination + filter di tabel Filament (server-side, LIMIT/OFFSET).
- Agregat dashboard lewat query `GROUP BY`/`COUNT` sekali jalan; cache 60 detik.
- Index DB via migrasi: `golongan`, `jenis_kelamin`, `status_kepegawaian`, `kategori_suku`, `jabatan`, `tanggal_lahir`, `tanggal_pangkat_terakhir`.

## 6. Keamanan & kualitas

- Kredensial → `.env`; `pass.php`/`reset.txt` dihapus dari repo.
- CSRF, header keamanan, session hardening, throttling login (bawaan).
- FormRequest/validasi Filament terpusat.
- Global exception handler + log.
- Pest test tiap halaman (HTTP 200 + data inti), `php -l` per file.
- `.gitignore` (`vendor/`, `.env`, `storage/`, node modules); bersihkan riwayat git dari secret.

## 7. Roadmap strangler

1. **Fondasi**: `composer create-project laravel/laravel:^13.0`; sambungkan DB `nominatif_pegawai`; port skema existing → migrations; seeder super admin; install Filament v4 + Shield + maatwebsite/excel.
2. **Domain**: port `koneksi.php` → `Services/` + `Support/`; model + relasi + eager loading; policy role.
3. **Fitur**: PegawaiResource (CRUD + checklist RelationManager), widget dashboard, halaman pensiun/pangkat, import/export, UserResource.
4. **Cutover**: vhost Laragon → `public/`; hapus file legacy; test Pest; bersihkan riwayat git.

## 8. Kriteria selesai

- Semua fungsionalitas lama berfungsi di panel baru (pegawai, pensiun, pangkat, checklist, import BKD, export 24 kolom, login/role).
- Dashboard & tabel real-time (polling) tanpa reload manual.
- Tidak ada secret baru di git; `.env` gitignored.
- `php -l` + Pest lulus; tidak ada N+1 (verifikasi dengan query log).
- Vhost Laragon menunjuk ke `public/`.
