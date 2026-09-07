# SAPA PPKD

SAPA PPKD adalah sistem presensi peserta pelatihan PPKD Jakarta Barat berbasis Laravel, Inertia, React, dan TypeScript. Fitur utamanya mencakup registrasi dan verifikasi peserta, panel terpisah untuk empat peran, data master, presensi pagi-sore dengan kamera dan GPS, izin/sakit, monitoring, koreksi teraudit, import peserta, laporan Excel/PDF/cetak, notifikasi, dan PWA.

Jarak dihitung ulang di server dengan Haversine. Koordinat resmi tidak disertakan dan wajib diisi admin. Radius awal 20 meter dapat diubah.

## Persyaratan server

- PHP 8.3+ dengan ekstensi `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, dan `zip`.
- Composer 2, Node.js 20+, dan npm.
- MySQL 8/MariaDB 10.6+ atau PostgreSQL untuk production; SQLite untuk pengembangan.
- HTTPS valid di production agar kamera, geolocation, dan PWA berfungsi.
- Queue worker dan cron scheduler.

## Instalasi lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Di Linux/macOS gunakan `cp`. Untuk SQLite, buat `database/database.sqlite`. Aplikasi tersedia di `http://127.0.0.1:8000`; untuk HMR jalankan `npm run dev` pada terminal lain.

## Environment dan database

Atur `APP_URL`, database, email, serta akun awal. Contoh production MySQL:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://absensi.example.go.id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sapa_ppkd
DB_USERNAME=sapa_ppkd
DB_PASSWORD=password-kuat
SUPER_ADMIN_NAME="Super Admin PPKD"
SUPER_ADMIN_USERNAME=admin
SUPER_ADMIN_EMAIL=admin@example.go.id
SUPER_ADMIN_PASSWORD=password-kuat-sementara
```

Buat database dan user dengan hak terbatas, lalu jalankan `php artisan migrate --seed --force`. Seeder aman dijalankan ulang dan hanya membuat super admin bila semua variabelnya terisi. Segera ganti password awal melalui Keamanan Akun. Jangan commit `.env`.

## Konfigurasi awal

1. Masuk sebagai super admin.
2. Isi identitas instansi, zona waktu, aturan foto, retensi, dan jadwal bawaan pada **Pengaturan**.
3. Pada **Lokasi Absensi**, masukkan latitude/longitude resmi hasil pengukuran, radius, batas akurasi, lalu aktifkan.
4. Pilih lokasi default dan buat program, angkatan, kelas, instruktur, jadwal, serta kode registrasi.

Koordinat berupa desimal (`latitude -90..90`, `longitude -180..180`). Dokumentasi ini sengaja tidak memberikan koordinat perkiraan.

## Storage, queue, dan scheduler

Foto serta dokumen disimpan privat dan hanya dilayani controller berotorisasi. `storage:link` hanya diperlukan untuk aset publik tambahan.

```bash
php artisan storage:link
php artisan queue:work database --queue=notifications,default --tries=3 --timeout=60
php artisan schedule:list
```

Jalankan worker dengan Supervisor/systemd. Tambahkan cron berikut, sesuaikan path:

```cron
* * * * * cd /var/www/sapa-ppkd && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menyelesaikan rekap tiap 15 menit, memproses notifikasi, membersihkan upload sementara, dan memangkas foto sesuai retensi.

## Pengujian

```bash
vendor/bin/pint --format agent
php artisan test --compact
npm run typecheck
npm run build
php artisan route:list --except-vendor
php artisan migrate:status
```

## Import dan laporan

Unduh template pada **Import Data**, unggah XLSX, tinjau validasi per baris, lalu konfirmasi. NIK, nomor peserta, dan email duplikat ditolak. Excel/PDF/cetak mengikuti filter aktif dan query ekspor memakai streaming/chunking sesuai format.

## Deployment cPanel

1. Pilih PHP 8.3+ dan aktifkan ekstensi yang dibutuhkan.
2. Simpan source di luar `public_html`; arahkan document root domain ke folder `public` proyek.
3. Jalankan `composer install --no-dev --optimize-autoloader` dan `npm ci && npm run build` melalui terminal/CI.
4. Buat `.env` production dan database, lalu `php artisan migrate --seed --force` dan `php artisan optimize`.
5. Atur cron `schedule:run`, process manager untuk queue worker, sertifikat SSL, dan paksa HTTPS.

## Deployment Ubuntu/Nginx

Document root Nginx harus `/var/www/sapa-ppkd/public` dan request lain diteruskan ke `index.php` melalui PHP-FPM.

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --seed --force
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

Konfigurasikan Supervisor, cron, TLS, batas upload, dan header proxy HTTPS. Lakukan smoke test login, kamera/GPS pada perangkat fisik, absensi, dan export.

## Backup dan pemulihan

- Backup database konsisten (`mysqldump --single-transaction` atau setara), seluruh `storage/app`, dan `.env` melalui penyimpanan rahasia.
- Enkripsi, batasi akses, rotasi, dan uji restore berkala.
- Restore database dan storage pada versi migration yang selaras, lalu `php artisan optimize:clear` dan `php artisan optimize`.

## Troubleshooting kamera/GPS

- Pastikan HTTPS (kecuali localhost) dan permission situs aktif.
- Jika kamera ditolak, izinkan melalui pengaturan browser lalu muat ulang; galeri tidak dipakai pada alur normal.
- Jika GPS timeout/tidak akurat, aktifkan lokasi presisi tinggi, matikan hemat daya, pindah ke area terbuka, lalu ambil ulang.
- Jika di luar radius, verifikasi koordinat resmi dan pembacaan lokasi terbaru.
- Untuk PWA lama, tutup tab lalu hapus data situs/service worker. Jika UI lama, jalankan build dan hard refresh.
- Periksa `storage/logs/laravel.log`, `php artisan queue:failed`, `APP_URL`, dan konfigurasi proxy saat ada error.

## Checklist pengujian manual sebelum go-live

Catat perangkat, versi browser, waktu, hasil, dan bukti:

- [ ] Android Chrome pada lebar minimal 360 px.
- [ ] iPhone Safari jika tersedia.
- [ ] Browser desktop.
- [ ] Kamera diizinkan dan preview/foto berhasil.
- [ ] Kamera ditolak dengan pesan pemulihan jelas.
- [ ] GPS diizinkan.
- [ ] GPS ditolak.
- [ ] GPS tidak akurat ditolak.
- [ ] Posisi di dalam radius diterima.
- [ ] Posisi di luar radius ditolak.
- [ ] Internet lambat/offline tidak membuat data ganda.
- [ ] Klik ganda tidak membuat absensi ganda.
- [ ] Refresh setelah foto meminta pengambilan ulang.
- [ ] Session habis saat kirim meminta login ulang.
- [ ] Foto terlalu besar ditangani jelas.
- [ ] Jadwal belum dibuka ditolak.
- [ ] Jadwal sudah ditutup ditolak.
- [ ] Peserta belum diverifikasi tidak dapat absen.
- [ ] Koreksi admin menyimpan alasan dan audit.
- [ ] Export besar selesai tanpa kehabisan memori.

## Keamanan production

Gunakan `APP_ENV=production`, `APP_DEBUG=false`, password unik, HTTPS, backup, monitoring log, dan pembaruan dependency berkala. Foto, dokumen, koordinat, IP, dan metadata perangkat adalah data terbatas. Sistem memberi indikator kecurangan, tetapi website tidak dapat menjamin deteksi fake GPS 100%.
