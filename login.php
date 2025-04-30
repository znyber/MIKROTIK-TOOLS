<?php
/**
 * H4N5VS Mikrotik System Security
 * Login page
 */

// Start session
session_start();

// Include functions
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Check if user is already logged in
if (is_authenticated()) {
    header('Location: index.php');
    exit;
}

$error_message = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (authenticate($username, $password)) {
        // Log successful login - using a simple error_log since log_activity may not be defined
        error_log("User {$username} logged in successfully");
        
        header('Location: index.php');
        exit;
    } else {
        $error_message = 'Invalid username or password';
        // Log failed login attempt
        error_log("Failed login attempt for user {$username}");
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - H4N5VS Mikrotik System Security</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-box">
                    <h1 class="text-center mb-4">H4N5VS</h1>
                    <h2 class="text-center mb-4">Mikrotik System Security</h2>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">ACCESS SYSTEM</button>
                            <a href="demo-login.php" class="btn btn-secondary mt-2">DEMO MODE</a>
                        </div>
                    </form>
                    
                    <div class="system-info mt-4 text-center">
                        <p>ADVANCED NETWORK PROTECTION SYSTEM</p>
                        <p class="version">V1.0.0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            feather.replace();
        });
    </script>
</body>
</html>
