<?php
/**
 * H4N5VS Mikrotik System Security
 * Test Mikrotik Connection Script
 * 
 * File ini untuk mendiagnosis masalah koneksi ke router Mikrotik
 */

// RouterOS API class dari repository
require_once 'includes/routeros_api.php';

// Set error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fungsi untuk menampilkan hasil test dalam format yang rapi
function showResult($test, $result, $details = '') {
    $status = $result ? '<span style="color: green; font-weight: bold;">BERHASIL</span>' : '<span style="color: red; font-weight: bold;">GAGAL</span>';
    $details_html = !empty($details) ? "<div class=\"details\">$details</div>" : '';
    echo "<div class=\"test-result\">
        <div class=\"test-name\">$test</div>
        <div class=\"status\">$status</div>
        $details_html
    </div>";
}

// Form submit handling
$host = '192.168.0.1';
$user = 'hanskuy';
$pass = '1994hans';
$port = '8728';
$secure = false;
$message = '';
$test_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = $_POST['host'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $port = $_POST['port'] ?? '8728';
    $secure = isset($_POST['secure']) && $_POST['secure'] === 'on';
    
    // 1. Test koneksi jaringan dasar dengan ping
    exec("ping -c 1 -W 1 " . escapeshellarg($host), $ping_output, $ping_result);
    $ping_success = ($ping_result === 0);
    $test_results[] = [
        'name' => '1. Koneksi Jaringan (Ping)',
        'result' => $ping_success,
        'details' => $ping_success ? 'Host dapat dijangkau dari server ini.' : 'Host tidak dapat dijangkau. Periksa alamat IP dan koneksi jaringan.'
    ];
    
    // 2. Test port API terbuka
    $socket_test = @fsockopen($host, $port, $errno, $errstr, 2);
    $port_open = $socket_test !== false;
    if ($port_open) {
        fclose($socket_test);
    }
    $test_results[] = [
        'name' => '2. Port API (' . $port . ') Terbuka',
        'result' => $port_open,
        'details' => $port_open ? 'Port API terbuka dan dapat diakses.' : 'Port API tidak dapat diakses. Periksa firewall dan konfigurasi API di router.'
    ];
    
    // 3. Test RouterOS API connection
    $api = new RouterosAPI();
    $api->debug = true;
    
    ob_start(); // Tangkap debug output
    if ($secure) {
        $api_connect = $api->connect($host, $user, $pass, $port, true);
    } else {
        $api_connect = $api->connect($host, $user, $pass, $port);
    }
    $api_debug = ob_get_clean();
    
    $test_results[] = [
        'name' => '3. Koneksi API RouterOS',
        'result' => $api_connect,
        'details' => $api_connect ? 'Berhasil terhubung ke RouterOS API!' : 'Gagal terhubung ke RouterOS API. Detail: ' . nl2br(htmlspecialchars($api_debug))
    ];
    
    // 4. Test perintah API sederhana
    if ($api_connect) {
        ob_start();
        $identity = $api->command('/system/identity/print');
        $api_debug = ob_get_clean();
        
        $command_success = is_array($identity) && !empty($identity);
        $identity_name = $command_success ? $identity[0]['name'] : 'Unknown';
        
        $test_results[] = [
            'name' => '4. Perintah API Basic',
            'result' => $command_success,
            'details' => $command_success ? 'Berhasil menjalankan perintah. Nama Router: ' . $identity_name : 'Gagal menjalankan perintah. Detail: ' . nl2br(htmlspecialchars($api_debug))
        ];
        
        // Tutup koneksi
        $api->disconnect();
    } else {
        $test_results[] = [
            'name' => '4. Perintah API Basic',
            'result' => false,
            'details' => 'Tidak dapat menjalankan perintah karena koneksi API gagal.'
        ];
    }
    
    // 5. Test izin user
    if ($api_connect) {
        $api = new RouterosAPI();
        if ($secure) {
            $api->connect($host, $user, $pass, $port, true);
        } else {
            $api->connect($host, $user, $pass, $port);
        }
        
        ob_start();
        $user_info = $api->commandWithParams('/user/print', [
            '?name' => $user
        ]);
        $api_debug = ob_get_clean();
        
        $user_exists = is_array($user_info) && !empty($user_info);
        $user_group = $user_exists ? $user_info[0]['group'] : 'Unknown';
        
        $test_results[] = [
            'name' => '5. Izin User',
            'result' => $user_exists,
            'details' => $user_exists ? 'User ada dengan group: ' . $user_group . '. Pastikan group memiliki izin API.' : 'Gagal mendapatkan info user. Detail: ' . nl2br(htmlspecialchars($api_debug))
        ];
        
        $api->disconnect();
    } else {
        $test_results[] = [
            'name' => '5. Izin User',
            'result' => false,
            'details' => 'Tidak dapat memeriksa izin user karena koneksi API gagal.'
        ];
    }
    
    // 6. Tes Firewall/IP Services
    if ($ping_success && !$port_open) {
        $message = 'Router dapat dijangkau tetapi port API tertutup. Pastikan API service aktif dan tidak diblokir firewall.';
    } elseif ($port_open && !$api_connect) {
        $message = 'Port API terbuka tetapi koneksi API gagal. Periksa username dan password.';
    } elseif ($api_connect) {
        $message = 'Koneksi ke router Mikrotik berhasil! Anda dapat menggunakan informasi ini di aplikasi H4N5VS.';
    } else {
        $message = 'Koneksi ke router Mikrotik gagal. Perbaiki masalah yang terdeteksi di atas.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Koneksi Mikrotik - H4N5VS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #0f1520;
            color: #e9ecef;
            padding-top: 2rem;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #1a2332;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .test-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .test-header h1 {
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
        .test-results {
            margin-top: 30px;
            border-top: 1px solid #343a40;
            padding-top: 20px;
        }
        .test-result {
            padding: 15px;
            margin-bottom: 10px;
            background-color: #2c3345;
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
        }
        .test-name {
            flex: 1;
            font-weight: bold;
        }
        .status {
            width: 100px;
            text-align: center;
        }
        .details {
            width: 100%;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #495057;
            font-size: 0.9rem;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
        }
        .message.success {
            background-color: rgba(0, 255, 65, 0.1);
            border: 1px solid #00ff41;
        }
        .message.error {
            background-color: rgba(255, 80, 80, 0.1);
            border: 1px solid #ff5050;
        }
        .connection-config {
            margin-top: 30px;
            padding: 15px;
            background-color: #2c3345;
            border-radius: 6px;
            border-left: 4px solid #00ff41;
        }
        .config-title {
            font-weight: bold;
            color: #00ff41;
            margin-bottom: 10px;
        }
        code {
            background-color: #1a2332;
            padding: 2px 5px;
            border-radius: 3px;
            color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container test-container">
        <div class="test-header">
            <h1>H4N5VS</h1>
            <p>Mikrotik System Security</p>
            <h2>Test Koneksi Router Mikrotik</h2>
            <p>Gunakan tool ini untuk mendiagnosis masalah koneksi ke router Mikrotik Anda</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $api_connect ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
            
            <?php if ($api_connect): ?>
                <div class="connection-config">
                    <div class="config-title">Konfigurasi untuk H4N5VS:</div>
                    <p>Gunakan informasi berikut untuk konfigurasi router di aplikasi H4N5VS:</p>
                    <ul>
                        <li><strong>Host/IP:</strong> <code><?php echo htmlspecialchars($host); ?></code></li>
                        <li><strong>Username:</strong> <code><?php echo htmlspecialchars($user); ?></code></li>
                        <li><strong>Password:</strong> <code>(password yang Anda masukkan)</code></li>
                        <li><strong>Port API:</strong> <code><?php echo htmlspecialchars($port); ?></code></li>
                        <li><strong>Gunakan SSL:</strong> <code><?php echo $secure ? 'Ya' : 'Tidak'; ?></code></li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="host" class="form-label">Host/IP Router</label>
                        <input type="text" class="form-control" id="host" name="host" value="<?php echo htmlspecialchars($host); ?>" required>
                        <div class="form-text text-muted">Contoh: 192.168.1.1</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="port" class="form-label">Port API</label>
                        <input type="text" class="form-control" id="port" name="port" value="<?php echo htmlspecialchars($port); ?>">
                        <div class="form-text text-muted">Default: 8728 (API) atau 8729 (API-SSL)</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="user" class="form-label">Username</label>
                        <input type="text" class="form-control" id="user" name="user" value="<?php echo htmlspecialchars($user); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="pass" class="form-label">Password</label>
                        <input type="password" class="form-control" id="pass" name="pass" value="<?php echo htmlspecialchars($pass); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="secure" name="secure" <?php echo $secure ? 'checked' : ''; ?>>
                <label class="form-check-label" for="secure">Gunakan SSL (API-SSL)</label>
                <div class="form-text text-muted">Centang jika Anda menggunakan API-SSL di port 8729</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Test Koneksi Router</button>
                <a href="demo-login.php" class="btn btn-success">Gunakan Mode Demo</a>
            </div>
        </form>
        
        <?php if (!empty($test_results)): ?>
            <div class="test-results">
                <h3>Hasil Test</h3>
                <?php foreach ($test_results as $test): ?>
                    <?php showResult($test['name'], $test['result'], $test['details']); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="index.php" class="btn btn-link text-light">Kembali ke Aplikasi H4N5VS</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>