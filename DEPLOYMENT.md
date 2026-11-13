# Deployment LENTERA

Domain produksi: `https://lentera.appcatalog.id`

Status saat ini: DNS Cloudflare dan tunnel `lentera-appcatalog` sudah dibuat dan aktif.

Cloudflare Tunnel meneruskan trafik ke virtual host Apache/Laragon `lentera.test` pada port 80. Aplikasi tidak memakai `php artisan serve`. Skrip startup memverifikasi Apache/Laragon dan koneksi aktif ke edge Cloudflare sebelum menyatakan layanan siap. Untuk penggunaan jangka panjang, jalankan skrip ini melalui mekanisme startup Windows yang telah disetujui administrator komputer.

## Menjalankan demo dari komputer ini

Jalankan:

```powershell
.\start-lentera-demo.ps1
```

Komputer harus tetap menyala dan terhubung ke internet selama demo. Setelah demo, tunnel lokal dapat dihentikan dengan:

```powershell
.\stop-lentera-demo.ps1
```

Apache/Laragon harus sudah berjalan sebelum `start-lentera-demo.ps1` dijalankan. Menghentikan tunnel tidak menghentikan Apache/Laragon.
Saat dijalankan otomatis bersama Windows, skrip menunggu hingga 60 detik agar Apache/Laragon siap sebelum menghubungkan tunnel.

Untuk mendukung akses lokal `http://localhost/lentera` dan domain publik pada instalasi yang sama, gunakan `SESSION_DOMAIN=null` dan `SESSION_SECURE_COOKIE=null`. Cookie sesi berlaku untuk host yang sedang dibuka; atribut Secure otomatis aktif pada permintaan HTTPS, termasuk melalui proxy lokal yang dipercaya. Jangan mengunci domain cookie ke domain publik karena login dari localhost akan gagal dengan 419. Setelah mengubah konfigurasi, jalankan `php artisan config:clear` dan muat ulang halaman login sebelum mengirim form.

## DNS

Buat salah satu record berikut pada zona `appcatalog.id`:

- Jika memakai server/VPS: record `A`, nama `lentera`, target alamat IPv4 publik server.
- Jika memakai Cloudflare Tunnel: record `CNAME`, nama `lentera`, target hostname tunnel (`<UUID>.cfargotunnel.com`).
- Jika memakai hosting dengan hostname: record `CNAME`, nama `lentera`, target hostname yang diberikan penyedia hosting.

Jangan mengarahkan record ke alamat lokal seperti `127.0.0.1`, `localhost`, `192.168.x.x`, atau `10.x.x.x`.

## Web server

Pilihan yang paling aman adalah mengatur document root domain ke folder `public` proyek:

`D:/laragon/www/lentera/public`

Jika hosting hanya dapat diarahkan ke root proyek, `.htaccess` di root sudah membatasi akses ke source code dan meneruskan request ke Laravel.

## HTTPS

- Aktifkan sertifikat SSL untuk `lentera.appcatalog.id`.
- Jika memakai Cloudflare, gunakan mode SSL/TLS `Full (strict)` setelah origin memiliki sertifikat yang valid.
- Jangan gunakan mode `Flexible`, karena dapat menyebabkan redirect atau cookie login bermasalah.

## Perintah setelah deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
npm ci
npm run build
```

Pastikan folder `storage` dan `bootstrap/cache` dapat ditulis oleh proses web server. Database SQLite pada `database/database.sqlite` juga harus dapat ditulis jika konfigurasi demo tetap menggunakan SQLite.

## Pemeriksaan sebelum demo

- Halaman `/`, `/login`, dan `/register` dapat dibuka melalui HTTPS.
- Login setiap akun demonstrasi berhasil.
- File CSS/JavaScript dari `/build/assets` tidak menghasilkan 404.
- Session login tidak hilang setelah berpindah halaman.
- Upload dokumen dapat ditulis ke `storage/app/private`.
- `APP_DEBUG=false` dan halaman error tidak menampilkan detail teknis.
