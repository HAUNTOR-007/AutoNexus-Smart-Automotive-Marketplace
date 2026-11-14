<?php
/**
 * Auto Nexus - Database Connection & Common Utilities
 * Compatible with XAMPP / phpMyAdmin MySQL
 */

// Set CORS and JSON Headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'autonexus');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Fallback: check if autonexus_db exists
        try {
            $fallbackDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=autonexus_db;charset=utf8mb4";
            $pdo = new PDO($fallbackDsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $ex) {
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit();
        }
    }

    return $pdo;
}

/**
 * Helper to get parsed request payload (handles both application/json and form-data)
 */
function getRequestData() {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
    
    // Also check php://input if $_POST is empty (PUT/DELETE or JSON sent without header)
    if (empty($_POST)) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) return $data;
        }
    }
    
    return $_POST;
}

/**
 * Standard JSON response helper
 */
function sendResponse($success, $data_or_message = null, $extra = []) {
    $response = ['success' => (bool)$success];
    if ($success) {
        if (is_array($data_or_message)) {
            $response['data'] = $data_or_message;
        } elseif (is_string($data_or_message)) {
            $response['message'] = $data_or_message;
        }
    } else {
        $response['error'] = is_string($data_or_message) ? $data_or_message : 'An error occurred';
    }

    foreach ($extra as $key => $val) {
        $response[$key] = $val;
    }

    echo json_encode($response);
    exit();
}
