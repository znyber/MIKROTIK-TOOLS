<?php
/**
 * H4N5VS Mikrotik System Security
 * MySQL Database Setup Script
 * 
 * Jalankan skrip ini di browser untuk mengatur database MySQL
 */

// Fungsi untuk memvalidasi input
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk memeriksa koneksi MySQL
function test_mysql_connection($host, $user, $pass, $db = null) {
    try {
        $dsn = $db ? "mysql:host=$host;dbname=$db;charset=utf8mb4" : "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk membuat database jika belum ada
function create_database($host, $user, $pass, $db) {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Fungsi untuk menulis file konfigurasi
function write_config_file($host, $user, $pass, $db) {
    $config_content = <<<CONFIG
<?php
/**
 * H4N5VS Mikrotik System Security
 * Database Configuration
 * 
 * Generated on: {$_SERVER['REQUEST_TIME']}
 */

\$db_host = '$host';
\$db_name = '$db';
\$db_user = '$user';
\$db_pass = '$pass';
CONFIG;

    // Mencoba menulis file konfigurasi database
    $config_file = __DIR__ . '/../includes/db_config.php';
    if (file_put_contents($config_file, $config_content) !== false) {
        chmod($config_file, 0600); // Tetapkan izin secure
        return true;
    }
    return false;
}

// Fungsi untuk memperbarui file database.php jika perlu
function update_database_file() {
    $database_path = __DIR__ . '/../includes/database.php';
    $database_content = file_get_contents($database_path);
    
    // Cek jika perlu memperbarui file
    if (strpos($database_content, 'require_once __DIR__ . \'/db_config.php\';') === false) {
        // Temukan deklarasi variabel database
        $pattern = '/(\$db_host\s*=\s*\'[^\']*\'\s*;[\s\r\n]*\$db_name\s*=\s*\'[^\']*\'\s*;[\s\r\n]*\$db_user\s*=\s*\'[^\']*\'\s*;[\s\r\n]*\$db_pass\s*=\s*\'[^\']*\'\s*;)/';
        
        // Ganti dengan include file konfigurasi
        $replacement = "require_once __DIR__ . '/db_config.php';";
        $updated_content = preg_replace($pattern, $replacement, $database_content);
        
        if ($updated_content && $updated_content !== $database_content) {
            file_put_contents($database_path, $updated_content);
            return true;
        }
    }
    return false;
}

// Fungsi untuk menjalankan query inisialisasi database
function initialize_tables($host, $user, $pass, $db) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Jika request manual=1, hanya menjalankan inisialisasi tabel
        if (isset($_GET['manual']) && $_GET['manual'] == 1) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS dummy_check (id INT)");
            $pdo->exec("DROP TABLE IF EXISTS dummy_check");
            return true;
        }
        
        // Buat tabel routers
        $sql = "CREATE TABLE IF NOT EXISTS routers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            hostname VARCHAR(255) NOT NULL,
            username VARCHAR(100) NOT NULL,
            password VARCHAR(255) NOT NULL,
            api_port INT DEFAULT 8728,
            ssl_port INT DEFAULT 8729,
            use_ssl BOOLEAN DEFAULT FALSE,
            active BOOLEAN DEFAULT FALSE,
            last_connected DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        
        // Buat tabel logs
        $sql = "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            router_id INT,
            event_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            severity VARCHAR(20) DEFAULT 'info',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE SET NULL
        )";
        $pdo->exec($sql);
        
        // Buat tabel threats
        $sql = "CREATE TABLE IF NOT EXISTS threats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            router_id INT,
            threat_id VARCHAR(100) NOT NULL,
            threat_type VARCHAR(50) NOT NULL,
            source_ip VARCHAR(45),
            severity VARCHAR(20) DEFAULT 'medium',
            status VARCHAR(20) DEFAULT 'active',
            details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            mitigated_at TIMESTAMP NULL,
            FOREIGN KEY (router_id) REFERENCES routers(id) ON DELETE SET NULL
        )";
        $pdo->exec($sql);
        
        // Buat tabel mitigations
        $sql = "CREATE TABLE IF NOT EXISTS mitigations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            threat_id INT NOT NULL,
            action_taken VARCHAR(255) NOT NULL,
            command_executed TEXT,
            result TEXT,
            user_id VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (threat_id) REFERENCES threats(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        
        // Buat tabel settings
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Memproses form jika disubmit
$result = array('status' => '', 'message' => '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan validasi input
    $host = validate_input($_POST['db_host'] ?? 'localhost');
    $user = validate_input($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $db = validate_input($_POST['db_name'] ?? 'h4n5vs');
    
    // Tes koneksi database
    if (test_mysql_connection($host, $user, $pass)) {
        // Coba membuat database jika perlu
        if (!create_database($host, $user, $pass, $db)) {
            $result = array('status' => 'error', 'message' => 'Gagal membuat database. Periksa izin pengguna MySQL.');
        } 
        // Coba menginisialisasi tabel
        elseif (!initialize_tables($host, $user, $pass, $db)) {
            $result = array('status' => 'error', 'message' => 'Gagal membuat tabel. Periksa izin pengguna MySQL.');
        }
        // Coba menulis file konfigurasi
        elseif (!write_config_file($host, $user, $pass, $db)) {
            $result = array('status' => 'error', 'message' => 'Gagal menulis file konfigurasi. Periksa izin tulis direktori.');
        }
        // Perbarui file database.php jika perlu
        else {
            update_database_file();
            $result = array('status' => 'success', 'message' => 'Database berhasil dikonfigurasi!');
        }
    } else {
        $result = array('status' => 'error', 'message' => 'Koneksi MySQL gagal. Periksa pengaturan Anda.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurasi Database MySQL - H4N5VS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #0f1520;
            color: #e9ecef;
            padding-top: 2rem;
        }
        .setup-container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #1a2332;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            color: #00ff41;
            font-weight: bold;
        }
        .form-label {
            color: #adb5bd;
        }
        .form-control {
            background-color: #2c3345;
            border-color: #495057;
            color: #e9ecef;
        }
        .form-control:focus {
            background-color: #2c3345;
            border-color: #00ff41;
            color: #e9ecef;
            box-shadow: 0 0 0 0.25rem rgba(0, 255, 65, 0.25);
        }
        .btn-primary {
            background-color: #00ff41;
            border-color: #00ff41;
            color: #0f1520;
            font-weight: bold;
        }
        .btn-primary:hover {
            background-color: #00d938;
            border-color: #00d938;
            color: #0f1520;
        }
        .alert-success {
            background-color: rgba(0, 255, 65, 0.2);
            border-color: #00ff41;
            color: #00ff41;
        }
        .alert-danger {
            background-color: rgba(255, 80, 80, 0.2);
            border-color: #ff5050;
            color: #ff5050;
        }
        .setup-footer {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="setup-header">
            <h1>H4N5VS</h1>
            <p>Mikrotik System Security</p>
            <h2>Konfigurasi Database MySQL</h2>
        </div>
        
        <?php if ($result['status'] === 'success'): ?>
            <div class="alert alert-success mb-4">
                <h4 class="alert-heading">Sukses!</h4>
                <p><?php echo $result['message']; ?></p>
                <hr>
                <p class="mb-0">Database berhasil dikonfigurasi. Anda dapat melanjutkan ke <a href="../index.php" class="text-success fw-bold">halaman utama</a>.</p>
            </div>
        <?php elseif ($result['status'] === 'error'): ?>
            <div class="alert alert-danger mb-4">
                <h4 class="alert-heading">Error!</h4>
                <p><?php echo $result['message']; ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="mb-3">
                <label for="db_host" class="form-label">Host Database</label>
                <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                <div class="form-text text-muted">Biasanya "localhost" atau alamat IP server MySQL</div>
            </div>
            
            <div class="mb-3">
                <label for="db_name" class="form-label">Nama Database</label>
                <input type="text" class="form-control" id="db_name" name="db_name" value="h4n5vs" required>
                <div class="form-text text-muted">Database akan dibuat jika belum ada</div>
            </div>
            
            <div class="mb-3">
                <label for="db_user" class="form-label">Username MySQL</label>
                <input type="text" class="form-control" id="db_user" name="db_user" required>
            </div>
            
            <div class="mb-3">
                <label for="db_pass" class="form-label">Password MySQL</label>
                <input type="password" class="form-control" id="db_pass" name="db_pass">
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Konfigurasi Database</button>
            </div>
        </form>
        
        <div class="setup-footer mt-4">
            <p>Pastikan MySQL server aktif dan pengguna memiliki izin yang cukup.</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>