<?php
/**
 * H4N5VS Mikrotik System Security
 * Database Functions
 */

/**
 * Get database connection
 * @return PDO Database connection
 */
function get_db_connection() {
    static $db = null;
    
    if ($db === null) {
        $db_host = 'localhost'; // Ganti dengan host MySQL Anda
        $db_name = 'h4n5vs';    // Ganti dengan nama database Anda
        $db_user = 'root';      // Ganti dengan username MySQL Anda
        $db_pass = '';          // Ganti dengan password MySQL Anda
        
        try {
            $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $db;
}

/**
 * Initialize database tables
 * @return bool Success status
 */
function init_database() {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        // Create routers table if it doesn't exist
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
        $db->exec($sql);
        
        // Create logs table if it doesn't exist
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
        $db->exec($sql);
        
        // Create threats table if it doesn't exist
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
        $db->exec($sql);
        
        // Create mitigations table if it doesn't exist
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
        $db->exec($sql);
        
        // Create settings table if it doesn't exist
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'string',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all routers
 * @return array List of routers
 */
function get_all_routers() {
    $db = get_db_connection();
    
    if (!$db) {
        return [];
    }
    
    try {
        $stmt = $db->query("SELECT * FROM routers ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting routers: " . $e->getMessage());
        return [];
    }
}

/**
 * Get router by ID
 * @param int $id Router ID
 * @return array|null Router data or null if not found
 */
function get_router($id) {
    $db = get_db_connection();
    
    if (!$db) {
        return null;
    }
    
    try {
        $stmt = $db->prepare("SELECT * FROM routers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting router: " . $e->getMessage());
        return null;
    }
}

/**
 * Get active router
 * @return array|null Active router data or null if none
 */
function get_active_router() {
    $db = get_db_connection();
    
    if (!$db) {
        return null;
    }
    
    try {
        $stmt = $db->query("SELECT * FROM routers WHERE active = TRUE LIMIT 1");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting active router: " . $e->getMessage());
        return null;
    }
}

/**
 * Set active router
 * @param int $id Router ID
 * @return bool Success status
 */
function set_active_router($id) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Set all routers to inactive
        $db->exec("UPDATE routers SET active = FALSE");
        
        // Set the specified router to active
        $stmt = $db->prepare("UPDATE routers SET active = TRUE WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        // Commit transaction
        $db->commit();
        
        return true;
    } catch (PDOException $e) {
        // Rollback transaction on error
        $db->rollBack();
        error_log("Error setting active router: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a new router
 * @param array $data Router data
 * @return int|false New router ID or false on failure
 */
function add_router($data) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO routers (name, hostname, username, password, api_port, ssl_port, use_ssl) 
                              VALUES (:name, :hostname, :username, :password, :api_port, :ssl_port, :use_ssl)");
        
        $stmt->execute([
            'name' => $data['name'],
            'hostname' => $data['hostname'],
            'username' => $data['username'],
            'password' => $data['password'],
            'api_port' => isset($data['api_port']) ? $data['api_port'] : 8728,
            'ssl_port' => isset($data['ssl_port']) ? $data['ssl_port'] : 8729,
            'use_ssl' => isset($data['use_ssl']) ? $data['use_ssl'] : 0
        ]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding router: " . $e->getMessage());
        return false;
    }
}

/**
 * Update router
 * @param int $id Router ID
 * @param array $data Router data
 * @return bool Success status
 */
function update_router($id, $data) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("UPDATE routers SET 
                              name = :name, 
                              hostname = :hostname, 
                              username = :username, 
                              password = :password, 
                              api_port = :api_port, 
                              ssl_port = :ssl_port, 
                              use_ssl = :use_ssl 
                              WHERE id = :id");
        
        $stmt->execute([
            'name' => $data['name'],
            'hostname' => $data['hostname'],
            'username' => $data['username'],
            'password' => $data['password'],
            'api_port' => isset($data['api_port']) ? $data['api_port'] : 8728,
            'ssl_port' => isset($data['ssl_port']) ? $data['ssl_port'] : 8729,
            'use_ssl' => isset($data['use_ssl']) ? $data['use_ssl'] : 0,
            'id' => $id
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating router: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete router
 * @param int $id Router ID
 * @return bool Success status
 */
function delete_router($id) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM routers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error deleting router: " . $e->getMessage());
        return false;
    }
}

/**
 * Update router last connected timestamp
 * @param int $id Router ID
 * @return bool Success status
 */
function update_router_connection_status($id) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("UPDATE routers SET last_connected = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating router connection status: " . $e->getMessage());
        return false;
    }
}

/**
 * Log router activity
 * @param int|null $router_id Router ID or null if not router-specific
 * @param string $event_type Type of event
 * @param string $message Log message
 * @param string $severity Log severity (info, warning, error, critical)
 * @param string|null $ip_address IP address or null
 * @return bool Success status
 */
function log_router_activity($router_id, $event_type, $message, $severity = 'info', $ip_address = null) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO logs (router_id, event_type, message, severity, ip_address) 
                              VALUES (:router_id, :event_type, :message, :severity, :ip_address)");
        
        $stmt->execute([
            'router_id' => $router_id,
            'event_type' => $event_type,
            'message' => $message,
            'severity' => $severity,
            'ip_address' => $ip_address
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging router activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent logs
 * @param int|null $router_id Router ID or null for all routers
 * @param int $limit Number of logs to retrieve
 * @return array List of logs
 */
function get_recent_logs($router_id = null, $limit = 100) {
    $db = get_db_connection();
    
    if (!$db) {
        return [];
    }
    
    try {
        if ($router_id === null) {
            $stmt = $db->prepare("SELECT l.*, r.name as router_name 
                                  FROM logs l 
                                  LEFT JOIN routers r ON l.router_id = r.id 
                                  ORDER BY l.created_at DESC 
                                  LIMIT :limit");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        } else {
            $stmt = $db->prepare("SELECT l.*, r.name as router_name 
                                  FROM logs l 
                                  LEFT JOIN routers r ON l.router_id = r.id 
                                  WHERE l.router_id = :router_id 
                                  ORDER BY l.created_at DESC 
                                  LIMIT :limit");
            $stmt->bindValue(':router_id', $router_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Save a threat to the database
 * @param int $router_id Router ID
 * @param array $threat Threat data
 * @return int|false New threat ID or false on failure
 */
function save_threat($router_id, $threat) {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO threats (router_id, threat_id, threat_type, source_ip, severity, status, details) 
                              VALUES (:router_id, :threat_id, :threat_type, :source_ip, :severity, :status, :details)");
        
        $stmt->execute([
            'router_id' => $router_id,
            'threat_id' => $threat['id'],
            'threat_type' => $threat['type'],
            'source_ip' => $threat['source_ip'],
            'severity' => $threat['severity'],
            'status' => $threat['status'],
            'details' => json_encode($threat)
        ]);
        
        return $db->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error saving threat: " . $e->getMessage());
        return false;
    }
}

/**
 * Get setting value
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function get_setting($key, $default = null) {
    $db = get_db_connection();
    
    if (!$db) {
        return $default;
    }
    
    try {
        $stmt = $db->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $setting = $stmt->fetch();
        
        if (!$setting) {
            return $default;
        }
        
        // Convert value based on type
        switch ($setting['setting_type']) {
            case 'boolean':
                return (bool) $setting['setting_value'];
            case 'integer':
                return (int) $setting['setting_value'];
            case 'float':
                return (float) $setting['setting_value'];
            case 'json':
                return json_decode($setting['setting_value'], true);
            default:
                return $setting['setting_value'];
        }
    } catch (PDOException $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set setting value
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $type Setting type (string, boolean, integer, float, json)
 * @return bool Success status
 */
function set_setting($key, $value, $type = 'string') {
    $db = get_db_connection();
    
    if (!$db) {
        return false;
    }
    
    // Format value based on type
    switch ($type) {
        case 'boolean':
            $value = $value ? '1' : '0';
            break;
        case 'integer':
            $value = (string) (int) $value;
            break;
        case 'float':
            $value = (string) (float) $value;
            break;
        case 'json':
            $value = json_encode($value);
            break;
        default:
            $value = (string) $value;
            $type = 'string';
    }
    
    try {
        // Check if setting exists
        $stmt = $db->prepare("SELECT 1 FROM settings WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        
        if ($stmt->fetch()) {
            // Update existing setting
            $stmt = $db->prepare("UPDATE settings SET setting_value = :value, setting_type = :type WHERE setting_key = :key");
        } else {
            // Insert new setting
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (:key, :value, :type)");
        }
        
        $stmt->execute([
            'key' => $key,
            'value' => $value,
            'type' => $type
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error setting setting: " . $e->getMessage());
        return false;
    }
}