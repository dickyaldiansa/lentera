# Lentera RS — Panduan Implementasi

Sistem terpadu Diklat Eksternal, KAMKORDIK, dan Komite Etik berbasis Laravel 12. Aplikasi menerapkan satu akun, dashboard berbasis peran, portal pemohon mobile-first, workflow berjejak audit, dan penyimpanan dokumen privat.

## Menjalankan aplikasi

```bash
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Instalasi demo menggunakan SQLite. Untuk MySQL/MariaDB, ubah `DB_CONNECTION` dan kredensial `DB_*` di `.env`, buat database, lalu jalankan migration dan seeder kembali.

## Akun demo

Semua akun menggunakan kata sandi `password`.

| Peran | Email |
|---|---|
| Super Administrator | admin@lentera.test |
| Admin Diklat | diklat@lentera.test |
| Admin KAMKORDIK | kamkordik@lentera.test |
| Reviewer Etik | reviewer@lentera.test |
| Ketua Komite Etik | ketuaetik@lentera.test |
| Pembimbing Klinis | pembimbing@lentera.test |
| Pemohon Eksternal | pemohon@lentera.test |

## Cakupan

- Registrasi/login, 11 role, permission `module.action`, dan pembatasan menu/route.
- Pengajuan eksternal, status history, dokumen privat berversi, invoice, bukti bayar, verifikasi, kegiatan, serta surat digital bertoken verifikasi.
- Institusi/PKS, program pendidikan, peserta, rotasi dengan deteksi bentrok, pembimbing, logbook anonim, dan struktur penilaian.
- Pengajuan etik, penugasan reviewer, form penilaian, rekomendasi/revisi, keputusan, Ethical Clearance, dan verifikasi publik.
- Dashboard/KPI, kalender, pencarian global, laporan cetak/PDF, notifikasi database, pengumuman, audit trail, configurable workflow/requirements/tariffs/number formats, dan endpoint API.

Integrasi eksternal seperti SIMRS, TTE tersertifikasi, email/WhatsApp, payment gateway, dan AI ditempatkan sebagai extension points karena memerlukan kredensial serta kontrak layanan rumah sakit.

## Verifikasi

```bash
php artisan test
npm run build
```
