# Presensi PPKD Jakarta Barat

MVP sistem absensi peserta berbasis web dengan foto, geolokasi, waktu server WIB, panel peserta, dan panel admin.

## Menjalankan aplikasi

```powershell
npm install
npm start
```

Buka `http://localhost:3000`.

Login admin awal:

- Email: `admin@ppkd.local`
- Kata sandi: `Admin123!`

Segera ganti kredensial melalui environment variable `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan `SESSION_SECRET` sebelum database pertama kali dibuat untuk penggunaan nyata.

## Alur pengujian

1. Daftar melalui halaman peserta.
2. Masuk sebagai admin dan ubah status akun menjadi **Aktif**.
3. Kalibrasikan latitude/longitude titik gedung melalui panel admin.
4. Masuk sebagai peserta lewat ponsel dan izinkan kamera serta GPS.
5. Ambil foto, lalu kirim absensi pada jadwal yang ditentukan.

> Kamera dan geolokasi browser hanya bekerja pada HTTPS atau `localhost`. Untuk pengujian dari ponsel melalui IP lokal, gunakan HTTPS/tunnel yang aman.

## Catatan produksi

Versi ini merupakan MVP. Sebelum dipasang untuk penggunaan resmi, gunakan penyimpanan sesi persisten, HTTPS, backup database/foto, pembatasan percobaan login, CSRF protection, penggantian kata sandi admin, serta kebijakan privasi dan retensi data.
