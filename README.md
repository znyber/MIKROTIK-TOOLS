# H4N5VS Mikrotik System Security

Aplikasi monitoring dan keamanan untuk router Mikrotik berbasis PHP.

## Fitur Utama

* Monitoring sistem router secara real-time
* Deteksi otomatis serangan DDoS, botnet, dan aktivitas mencurigakan
* Mitigasi otomatis terhadap ancaman keamanan
* Pemeriksaan alamat IP mencurigakan
* Dashboard interaktif untuk analisis lalu lintas jaringan
* Log sistem komprehensif

## Cara Penggunaan di Server Lokal (Bitnami)

### Persyaratan
* PHP 7.4+ atau PHP 8.0+
* Web server Apache atau Nginx
* Koneksi internet (opsional, untuk pemeriksaan IP)

### Instalasi pada XAMPP/Bitnami

1. **Unduh atau Clone Repository**
   ```
   git clone https://github.com/username/h4n5vs-mikrotik-security.git
   ```
   atau ekstrak arsip ZIP ke direktori web server Anda.

2. **Konfigurasikan Web Server**
   - Salin semua file aplikasi ke direktori htdocs (XAMPP) atau htdocs di Bitnami
   - Pastikan direktori web root mengarah ke folder aplikasi

3. **Buat Folder Logs**
   ```
   mkdir logs
   ```
   Dan pastikan folder tersebut memiliki izin yang tepat (dapat ditulis oleh web server):
   ```
   chmod 755 logs
   ```

4. **Akses Aplikasi**
   Buka browser web dan arahkan ke:
   ```
   http://localhost/h4n5vs/
   ```
   atau URL yang sesuai dengan konfigurasi server Anda.

5. **Login ke Aplikasi**
   - Username: admin
   - Password: admin123

6. **Konfigurasi Router Mikrotik**
   Masukkan detail router Mikrotik Anda:
   - IP Address
   - Username
   - Password

### Tampilan Alternatif

Kami menyediakan dua tampilan dashboard yang dapat digunakan:
1. Dashboard standar: `/dashboard.php`
2. Dashboard dengan tema hitam: `/dashboard-new.php`

Untuk menggunakan dashboard dengan tema hitam, ubah file `index.php` untuk mengarahkan ke `dashboard-new.php` alih-alih `dashboard.php`.

## Struktur Direktori

```
/
├── api/                  # API endpoints untuk data real-time
├── assets/               # Asset statis (CSS, JavaScript, gambar)
├── includes/             # File-file PHP yang digunakan bersama
├── logs/                 # Direktori untuk menyimpan log aplikasi
├── config.php            # Konfigurasi router
├── dashboard.php         # Dashboard utama
├── dashboard-new.php     # Dashboard dengan tema alternatif
├── index.php             # Halaman indeks
├── login.php             # Halaman login
└── logout.php            # Proses logout
```

## Mode Demo

Jika Anda tidak memiliki router Mikrotik, aplikasi akan berjalan dalam "mode demo" yang mensimulasikan koneksi ke router menggunakan data contoh. Ini memungkinkan Anda untuk mengeksplorasi antarmuka dan fungsionalitas tanpa perangkat keras router yang sebenarnya.

## Kustomisasi

Anda dapat menyesuaikan aplikasi dengan mengedit file berikut:
- `assets/css/style.css` - untuk tampilan standar
- `assets/css/dark-theme.css` - untuk tampilan tema gelap

## Keamanan

Untuk lingkungan produksi, pastikan untuk:
1. Mengubah kredensial default (username/password)
2. Mengaktifkan HTTPS untuk koneksi yang aman
3. Membatasi akses ke aplikasi hanya untuk pengguna yang berwenang
4. Memperbarui RouterOS API secara teratur

## Dukungan dan Kontribusi

Untuk pertanyaan atau saran, silakan buka issue di repositori ini. Kontribusi sangat diterima melalui pull requests.

## Lisensi

[MIT License](LICENSE)