# PANDUAN INSTALASI H4N5VS MIKROTIK SYSTEM SECURITY

## "PERTAHANAN DIGITAL UNTUK ROUTER ANDA: TIDAK SULIT, CUMA SEDIKIT RIBET" 🎭

_Selamat datang di instalasi H4N5VS, di mana keamanan jaringan bertemu dengan komedian teknologi!_ 

---

## PERSIAPAN: "SEPERTI ORANG MAU KENCAN PERTAMA" 🕺

Bayangkan Anda mau kencan pertama. Anda mandi, pakai parfum, dan siapkan mental. Begitu juga server Anda sebelum instalasi H4N5VS:

### Apa yang Dibutuhkan:
- **Server/Komputer**: Minimal seperti komputer kantor yang biasa dipakai untuk Excel dan nonton YouTube sembunyi-sembunyi.
- **PHP**: Versi 7.4 ke atas. Ibarat chef di restoran, dia yang masak semua fiturnya.
- **MySQL**: Database-nya. Anggap saja ini seperti lemari arsip, tapi digital dan tidak berdebu.
- **Akses Internet**: Untuk update dan fitur AI-nya. Kalau internetnya putus-putus kayak hubungan LDR, ya... sabar aja.

**TIP PENTING:** _Jangan install aplikasi ini pakai HP. Serius. Itu seperti mencoba membawa lemari 2 pintu naik motor._

---

## PROSES INSTALASI: "SEPERTI MERAKIT LEMARI IKEA" 🛠️

### Langkah 1: Download dan Ekstrak
```
Bayangkan ini seperti buka kado ulang tahun. Bedanya, ini kado yang melindungi jaringan Anda.
```

1. Download H4N5VS dari situs resmi (atau dari repo ini)
2. Ekstrak ke direktori web server Anda:
   - Di Windows: Biasanya di `C:\xampp\htdocs\h4n5vs`
   - Di Linux: `/var/www/html/h4n5vs` atau di Bitnami `/opt/bitnami/apache2/htdocs/h4n5vs`
   - Di Mac: Wah, pakai Mac? Mahal amat. Biasanya di `/Applications/XAMPP/xamppfiles/htdocs/h4n5vs`

### Langkah 2: Setup Database

Ini seperti menyiapkan buku catatan baru. Bedanya, buku ini otomatis mencatat siapa saja yang mencoba masuk rumah Anda tanpa izin.

**Cara Mudah:**
1. Buka browser, arahkan ke `http://localhost/h4n5vs/install/mysql_setup.php`
2. Isi form dengan detail MySQL Anda:
   - Host: Biasanya `localhost` (kecuali Anda punya alasan aneh untuk mengubahnya)
   - Database: `h4n5vs` (atau nama lain jika Anda ingin lebih kreatif)
   - Username: Biasanya `root` untuk komputer lokal
   - Password: Jika Anda pakai XAMPP dan belum ubah apa-apa, kemungkinan kosong

**Cara Manual (Untuk yang Suka Tantangan):**
1. Buat database MySQL baru bernama `h4n5vs`
2. Import skema database dari file SQL yang disediakan (jika ada)
3. Salin `includes/db_config.php.example` menjadi `includes/db_config.php`
4. Edit file tersebut dengan detail koneksi MySQL Anda

**TIP:** _Jika Anda lupa password MySQL, itu seperti lupa nama mantan. Mungkin lebih baik diatur ulang saja._

### Langkah 3: Konfigurasi API untuk Fitur AI (Opsional)

Ini seperti menambahkan otak jenius ke sistem yang sudah pintar. Kalau tidak diaktifkan juga tidak apa-apa, tapi kalau diaktifkan jadi lebih keren.

1. Daftar akun di OpenAI untuk mendapatkan API key
2. Tambahkan key ke variabel lingkungan `OPENAI_API_KEY`
   - Windows: Buka PowerShell as Admin, ketik: `setx OPENAI_API_KEY "sk-your-key-here"`
   - Linux/Mac: Tambahkan `export OPENAI_API_KEY="sk-your-key-here"` ke file `.bashrc` atau `.zshrc`
   - Bitnami: Ikuti panduan di BITNAMI_SETUP.md
3. Atau tambahkan ke file konfigurasi:
   ```
   mkdir -p config
   echo '<?php $openai_api_key = "sk-your-key-here"; ?>' > config/api_keys.php
   chmod 600 config/api_keys.php
   ```

**TIP:** _Jangan bagikan API key Anda. Seperti sikat gigi, simpan untuk diri sendiri saja._

---

## KONFIGURASI ROUTER MIKROTIK: "MEMPERSIAPKAN OBJEK PERLINDUNGAN" 🔒

Nah, ini ibarat menyiapkan rumah untuk dipasang sistem alarm canggih.

### Langkah 1: Aktifkan API Service
1. Buka Winbox (atau saya sebut "jendela ajaib pengontrol router")
2. Masuk ke IP → Services
3. Pastikan API aktif (port default 8728) ✓
4. Pastikan API-SSL aktif (port default 8729) ✓

### Langkah 2: Buat User dengan Izin API
1. Buka System → Users
2. Tambahkan user baru:
   - Nama: `h4n5vs_admin` (atau nama keren lainnya)
   - Password: Jangan "password123" ya. Serius.
   - Grup: "full" atau grup khusus dengan izin API

### Langkah 3: Konfigurasi Firewall
1. Buka IP → Firewall
2. Pastikan akses ke port API (8728/8729) diizinkan dari server H4N5VS
3. Jangan buka port API ke internet. Itu seperti meninggalkan pintu rumah terbuka saat liburan.

**TIP:** _Jika router dan server H4N5VS berada di jaringan yang sama, konfigurasi menjadi lebih sederhana. Seperti mencari jodoh di lingkungan sendiri, lebih mudah "prosesnya"._

---

## MENJALANKAN APLIKASI: "AKHIRNYA BERTEMU SETELAH PERJUANGAN PANJANG" 💻

Setelah melewati berbagai "rintangan" di atas, saatnya menikmati hasilnya:

1. Buka browser Anda
2. Masuk ke `http://localhost/h4n5vs/` (atau sesuai alamat server Anda)
3. Login dengan kredensial default:
   - Username: `admin`
   - Password: `h4n5vs_admin`
   - **SEGERA UBAH PASSWORD INI!!!**
4. Tambahkan router Mikrotik Anda:
   - Masuk ke "Manage Routers"
   - Klik "Add Router"
   - Isi detail router (nama, IP/hostname, username, password)

**TIP:** _Jika aplikasi tidak terbuka, coba cek log server. Biasanya masalahnya seperti restoran tutup - karena ada yang salah di dapur._

---

## MODE DEMO: "SIMULASI SEBELUM PERNIKAHAN" 🎮

Tidak punya router Mikrotik? Atau mau coba-coba dulu? Tenang, ada mode demo:

1. Buka `http://localhost/h4n5vs/demo-login.php`
2. Login dengan kredensial apa saja (tidak perlu asli)
3. Jelajahi aplikasi dengan data simulasi

**TIP:** _Mode demo seperti simulator terbang. Anda bisa merasakan pengalaman menerbangkan pesawat tanpa risiko jatuh sungguhan._

---

## TROUBLESHOOTING: "KETIKA HUBUNGAN MENJADI RUMIT" 🔍

### Masalah Koneksi ke Router
```
"Seperti hubungan yang gagal - tidak bisa connect"
```
1. Cek apakah IP router benar
2. Pastikan username dan password benar
3. Cek apakah port API aktif di router
4. Periksa firewall (mungkin dia memblokir koneksi seperti mantan memblokir nomor Anda)

### Masalah Database
```
"Seperti kehilangan memori - semua data hilang"
```
1. Pastikan layanan MySQL berjalan
2. Cek file konfigurasi `includes/db_config.php`
3. Pastikan pengguna MySQL memiliki izin yang cukup

### Aplikasi Lambat
```
"Seperti mantan yang suka lama bales chat"
```
1. Pastikan server memenuhi kebutuhan minimum
2. Kurangi jumlah data yang disimpan jika terlalu banyak
3. Cek apakah ada proses lain yang makan sumber daya server

---

## TIPS KEAMANAN: "SEPERTI NASIHAT DARI TETANGGA YANG PERNAH KEMALINGAN" 🔐

- Ganti password default SEGERA!
- Atur akses ke server H4N5VS hanya dari jaringan lokal
- Backup database secara berkala
- Perbarui aplikasi ke versi terbaru

**TIP ULTRA PENTING:** _Menyimpan password di sticky note dan ditempel di monitor itu seperti menyimpan kunci rumah di bawah keset. Semua orang tahu tempatnya!_

---

## KESIMPULAN: "PERJALANAN SERIBU KILOMETER DIMULAI DENGAN SATU KLIK" 🏁

Selamat! Anda telah berhasil menginstal H4N5VS Mikrotik System Security. Sistem Anda sekarang sudah diawasi 24/7, seperti ibu-ibu yang mengawasi anak muda pacaran.

**Jika Anda masih kebingungan**, ingat kata pepatah: "Ketika bingung, reboot dulu." Kalau masih bingung juga, coba baca lagi dari awal, mungkin ada langkah yang terlewat.

**Untuk bantuan lebih lanjut**, hubungi tim dukungan kami yang ramah dan jarang tidur di support@h4n5vs-security.com atau kunjungi forum kami yang penuh dengan orang-orang yang hidupnya didedikasikan untuk keamanan router.

---

_"Kami membuat keamanan jaringan menjadi menyenangkan, atau setidaknya tidak terlalu menyakitkan."_ - Tim H4N5VS