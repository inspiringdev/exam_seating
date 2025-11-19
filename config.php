<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exam_seating_db');

$conn = null;

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    if (basename($_SERVER['PHP_SELF']) === 'student_api.php') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    } else {
        die("Database connection error. Please check your configuration.");
    }
}

function executeQuery($query, $types = null, $params = null) {
    global $conn;
    
    try {
        if ($types && $params) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
        } else {
            return $conn->query($query);
        }
    } catch (Exception $e) {
        error_log("Query execution error: " . $e->getMessage());
        error_log("Query: " . $query);
        throw $e;
    }
}

function getConnection() {
    global $conn;
    return $conn;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function beginTransaction() {
    global $conn;
    try {
        return $conn->begin_transaction();
    } catch (Exception $e) {
        error_log("Transaction begin error: " . $e->getMessage());
        return false;
    }
}

function commitTransaction() {
    global $conn;
    try {
        return $conn->commit();
    } catch (Exception $e) {
        error_log("Transaction commit error: " . $e->getMessage());
        return false;
    }
}

function rollbackTransaction() {
    global $conn;
    try {
        return $conn->rollback();
    } catch (Exception $e) {
        error_log("Transaction rollback error: " . $e->getMessage());
        return false;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function sanitize($data) {
    global $conn;
    if ($conn) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function translateMySQLError($error) {
    if (strpos($error, 'Duplicate entry') !== false) {
        return 'This record already exists in the system.';
    } elseif (strpos($error, 'foreign key constraint fails') !== false) {
        return 'Cannot delete this record as it is being used elsewhere.';
    } elseif (strpos($error, 'Lock wait timeout') !== false) {
        return 'The operation took too long. Please try again.';
    } elseif (strpos($error, 'Data too long') !== false) {
        return 'Input data exceeds maximum allowed length.';
    } else {
        return 'A database error occurred. Please contact support.';
    }
}

/**
 * Log error with context
 * @param string $message Error message
 * @param array $context Additional context
 */
function logError($message, $context = []) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

// Environment-specific settings
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development'); // Change to 'production' for live deployment
}

if (APP_ENV === 'production') {
    // Production settings
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
} else {
    // Development settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Register shutdown function for cleanup
register_shutdown_function(function() {
    global $conn;
    if ($conn && $conn->ping()) {
        $conn->close();
    }
});
?>