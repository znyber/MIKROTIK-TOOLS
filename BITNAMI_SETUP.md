# H4N5VS Mikrotik System Security - Bitnami Setup Guide

Panduan ini akan membantu Anda menginstal dan mengkonfigurasi H4N5VS Mikrotik System Security pada server Bitnami.

## Persyaratan Sistem

- Bitnami LAMP/LEMP Stack
- PHP 7.4 atau lebih baru
- MySQL/MariaDB
- Akses SSH ke server (untuk konfigurasi)

## Langkah-langkah Instalasi

### 1. Ekstrak Aplikasi

Upload dan ekstrak file aplikasi ke direktori webroot Bitnami:

```bash
# Untuk Apache
sudo mkdir -p /opt/bitnami/apache2/htdocs/h4n5vs
sudo unzip h4n5vs.zip -d /opt/bitnami/apache2/htdocs/h4n5vs

# Untuk Nginx
sudo mkdir -p /opt/bitnami/nginx/html/h4n5vs
sudo unzip h4n5vs.zip -d /opt/bitnami/nginx/html/h4n5vs
```

### 2. Atur Izin File

```bash
# Untuk Apache
cd /opt/bitnami/apache2/htdocs/h4n5vs
sudo chown -R daemon:daemon .
sudo chmod -R 755 .
sudo chmod -R 775 logs/

# Untuk Nginx
cd /opt/bitnami/nginx/html/h4n5vs
sudo chown -R daemon:daemon .
sudo chmod -R 755 .
sudo chmod -R 775 logs/
```

### 3. Buat Database

Gunakan Bitnami MySQL/MariaDB untuk membuat database:

```bash
cd /opt/bitnami
./mysql/bin/mysql -u root -p
```

Di dalam MySQL, jalankan:

```sql
CREATE DATABASE h4n5vs;
CREATE USER 'h4n5vs'@'localhost' IDENTIFIED BY 'password_anda';
GRANT ALL PRIVILEGES ON h4n5vs.* TO 'h4n5vs'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Konfigurasi Database

Edit file `includes/database.php` dan sesuaikan pengaturan database:

```php
$db_host = 'localhost';
$db_name = 'h4n5vs';
$db_user = 'h4n5vs';
$db_pass = 'password_anda';
```

### 5. Konfigurasi OpenAI API (Opsional)

Untuk mengaktifkan fitur AI, tambahkan API key OpenAI ke variabel lingkungan Bitnami:

```bash
# Tambahkan ke /opt/bitnami/scripts/setenv.sh
echo 'export OPENAI_API_KEY="sk-your-key-here"' | sudo tee -a /opt/bitnami/scripts/setenv.sh

# Atau membuat file konfigurasi khusus
sudo mkdir -p /opt/bitnami/apache2/htdocs/h4n5vs/config
echo '<?php $openai_api_key = "sk-your-key-here"; ?>' | sudo tee /opt/bitnami/apache2/htdocs/h4n5vs/config/api_keys.php
sudo chmod 600 /opt/bitnami/apache2/htdocs/h4n5vs/config/api_keys.php
```

### 6. Konfigurasi PHP

Pastikan PHP memiliki pengaturan yang diperlukan:

```bash
# Edit php.ini
sudo nano /opt/bitnami/php/etc/php.ini
```

Atur nilai berikut:
```
allow_url_fopen = On
memory_limit = 128M
max_execution_time = 300
```

### 7. Restart Layanan

```bash
sudo /opt/bitnami/ctlscript.sh restart apache
# Atau
sudo /opt/bitnami/ctlscript.sh restart nginx
sudo /opt/bitnami/ctlscript.sh restart php-fpm
```

## Konfigurasi Router Mikrotik

1. Aktifkan API Service di Router Mikrotik:
   - Buka Winbox dan hubungkan ke router
   - Buka IP → Services
   - Pastikan API service aktif (port default 8728)
   - Pastikan API-SSL service aktif (port default 8729)

2. Buat pengguna dengan izin API:
   - Buka System → Users
   - Tambahkan user baru dengan grup "full" atau grup khusus dengan izin API

3. Konfigurasi Firewall:
   - Pastikan port API (8728/8729) dapat diakses dari server H4N5VS
   - Tambahkan aturan firewall jika diperlukan

## Troubleshooting

### Masalah Koneksi API
Jika terjadi masalah koneksi ke router:
1. Periksa apakah layanan API aktif di router
2. Verifikasi kredensial login
3. Periksa aturan firewall
4. Pastikan server dapat menjangkau router

### Masalah Izin File
Jika terjadi kesalahan "Permission denied":
```bash
sudo chown -R daemon:daemon /path/to/h4n5vs
sudo chmod -R 755 /path/to/h4n5vs
```

### Error PHP
Untuk kesalahan PHP, periksa log PHP Bitnami:
```bash
cat /opt/bitnami/php/logs/php-fpm.log
```

## Bantuan

Jika Anda mengalami masalah selama instalasi atau menggunakan aplikasi, silakan hubungi tim dukungan kami melalui:
- Email: support@h4n5vs-security.com
- Situs web: https://www.h4n5vs-security.com/support