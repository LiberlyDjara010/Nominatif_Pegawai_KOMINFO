# AGENTS.md

Sistem Nominatif Pegawai Diskominfo Papua. Flat procedural PHP 8 + MySQL app (no framework, no Composer, no tests). Served by XAMPP/Apache. All UI text, comments, and commit messages are in Indonesian — keep it that way.

## Commands
- Syntax check edited files: `php -l <file>` (PHP 8.3.30 is on PATH). That's the only verification available; there is no lint/build/test setup.

## Architecture
- Every page follows the same skeleton: `require 'auth.php';` → `include 'koneksi.php';` → do work → `include 'layout.php';` → HTML → `include 'layout_end.php';`.
  - `auth.php` defines role helpers, starts the session, enforces login + 1h timeout. `layout.php` calls `isSuperAdmin()`, so **`auth.php` must be required before `layout.php`** — missing this caused a past fatal error (see Notes.txt).
- `koneksi.php` is the shared logic module, not just a connection: pension-age rules, pay-grade table, date/golongan normalization, `simpanPegawai()`, checklist helpers, notification queries. Add shared helpers here.
- DB `nominatif_pegawai`: credentials are **hardcoded in `koneksi.php` and committed** — don't echo/log them further.
- Schema for `user` and `pegawai` is **not in the repo** (created manually via phpMyAdmin). `migrasi_*.php` are browser-run, one-off ALTER scripts guarded by `requireSuperAdmin()` — open them via URL as super admin, never via CLI. The `checklist_pangkat` table is auto-created by `pastikanTabelChecklist()`.
- Saves go through `simpanPegawai()` / `kolomHilang()`, which silently skip DB columns that don't exist yet (tolerant of unrun migrations). Use them instead of hand-written INSERT/UPDATE with fixed columns.
- Export uses a hand-rolled minimal XLSX writer (`xlsx_writer.php`); requires PHP `zip` extension. Import is CSV-only (`proses_import.php` auto-detects delimiter and recognizes the official BKD 24-column format); `TEMPLATE.xlsx` is a download-only reference.

## Conventions & gotchas
- Dates are stored `Y-m-d` (or NULL), never display format. Normalize input with `normalisasiTanggal()` (accepts dd/mm/yyyy, mm/yyyy, and 2-digit years: >=70 → 19xx, else 20xx).
- Golongan is normalized with `normalisasiGolongan()` (`IIIa` → `III/a`); `gajiPokok()` keys are lowercase golongan.
- Empty-string values on NULLable/ENUM columns (`kategori_suku`, `status_kepegawaian`, dates) trigger MySQL "Data truncated" errors — convert `''` to NULL before saving (see tambah.php:66).
- `jenis_jabatan` is auto-derived from `jabatan` text via `tentukanJenisJabatan()` when left empty.
- Roles: `super admin` (APTIKA) and `kepegawaian` (user). `isSuperAdmin()` matches loosely ('admin', 'aptika', any role containing 'super'). Gate pages with `requireCanManageData()` / `requireSuperAdmin()`.
- Escape output with the `e()` helper from `koneksi.php`.
- Emergency tooling is committed with hardcoded secrets: `pass.php` (secret `secret$!`) and `reset.txt` (secret `aptikakerenbanget`). `reset.txt` is PHP saved as `.txt`, so it will **not execute** under Apache as-is — rename to `.php` before relying on it.
