<?php
/*
 * ===================================================================================
 * FILE KONFIGURASI DAN FUNGSI GLOBAL
 * ===================================================================================
 * Modifikasi: Logika logout dipindahkan ke sini agar bersifat global.
 */

// 1. PENGATURAN DASAR PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- PERBAIKAN: Universal Logout Handler ---
// Logika ini dijalankan pertama kali saat config.php dipanggil
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

// 2. KONFIGURASI APLIKASI
define('DB_HOST', '192.168.1.2');
define('DB_NAME', 'sik_master');
define('DB_USER', 'client');
define('DB_PASS', 'epotoransu');
define('DB_CHARSET', 'latin1');
define('WEBAPPS_URL', 'http://192.168.1.2/webapps');

// 3. FUNGSI-FUNGSI HELPER
function connect_db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            die("Koneksi ke database gagal. Silakan hubungi administrator sistem.");
        }
    }
    return $pdo;
}

function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function is_user_authorized(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function require_login() {
    if (!is_user_authorized()) {
        header("Location: login.php");
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function track_sql(string $action, string $tableName, array $data) {
    try {
        $pdo = connect_db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userId = $_SESSION['user_id'] ?? 'SYSTEM';
        $dataParts = [];
        foreach ($data as $key => $value) {
            $dataParts[] = "$key: '$value'";
        }
        $dataString = implode(', ', $dataParts);
        $sqle = "User at IP [$ip] performed action: [$action] on table [$tableName]. Data: {$dataString}";
        $stmt = $pdo->prepare("INSERT INTO trackersql (tanggal, sqle, usere) VALUES (NOW(), ?, ?)");
        $stmt->execute([$sqle, $userId]);
    } catch (\PDOException $e) {
        error_log("Audit Trail Logging Failed: " . $e->getMessage());
    }
}
?>

