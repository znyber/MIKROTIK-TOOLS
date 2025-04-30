<?php
/**
 * H4N5VS Mikrotik System Security
 * Initialize Database Tables
 * 
 * Halaman ini untuk mengeksekusi pembuatan tabel database dari konfigurasi manual
 */

// Membaca file konfigurasi database
$db_config_file = __DIR__ . '/../includes/db_config.php';
if (!file_exists($db_config_file)) {
    die("File konfigurasi database tidak ditemukan. Harap buat file includes/db_config.php terlebih dahulu.");
}

require_once $db_config_file;

// Coba membuat koneksi database
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    
    $success = true;
    $message = "Semua tabel database berhasil dibuat!";
} catch (PDOException $e) {
    $success = false;
    $message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inisialisasi Tabel Database - H4N5VS</title>
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
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="setup-header">
            <h1>H4N5VS</h1>
            <p>Mikrotik System Security</p>
            <h2>Inisialisasi Tabel Database</h2>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success mb-4">
                <h4 class="alert-heading">Sukses!</h4>
                <p><?php echo $message; ?></p>
                <hr>
                <p class="mb-0">Tabel database berhasil dibuat. Anda dapat melanjutkan ke <a href="../index.php" class="text-success fw-bold">halaman utama</a>.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-danger mb-4">
                <h4 class="alert-heading">Error!</h4>
                <p><?php echo $message; ?></p>
                <hr>
                <p class="mb-0">Periksa konfigurasi database Anda di file <code>includes/db_config.php</code>.</p>
            </div>
        <?php endif; ?>
        
        <div class="d-grid gap-2">
            <a href="../index.php" class="btn btn-primary">Ke Halaman Utama</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>