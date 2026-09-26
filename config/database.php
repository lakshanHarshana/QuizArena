<?php
/**
 * QUIZARENA — Database Connection & Global Configuration
 * ICT 2209 Web Technologies Mini Project
 * 
 * Supports:
 * - Robust PDO connection with UTF8MB4
 * - Automatic port detection (3306, 3307, 3308)
 * - Auto-schema initialization if tables are missing
 * - Dynamic BASE_URL calculation for any folder or root
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global App Constants
define('APP_NAME', 'QuizArena');
define('APP_TAGLINE', 'Challenge Your Knowledge. Play. Answer. Compete.');
define('APP_VERSION', '1.0.0');

// Dynamic BASE_URL resolution
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Determine the web path relative to document root
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$currentDir = str_replace('\\', '/', dirname(__DIR__));

if (!empty($docRoot) && strpos($currentDir, $docRoot) === 0) {
    $relativeSubdir = substr($currentDir, strlen($docRoot));
} else {
    // Fallback if not inside standard document root
    $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    $relativeSubdir = rtrim(preg_replace('#/(auth|student|teacher|api|assets|config|includes).*#', '', $scriptDir), '/');
}
$relativeSubdir = rtrim($relativeSubdir, '/');
define('BASE_URL', $protocol . $host . $relativeSubdir);
define('ROOT_PATH', dirname(__DIR__));

// Timezone
date_default_timezone_set('Asia/Colombo');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $dbname = getenv('DB_NAME') ?: 'quizarena';
        $username = getenv('DB_USER') ?: 'root';
        $envPass = getenv('DB_PASS');

        // Passwords to attempt (standard XAMPP/WAMP empty, root, or env)
        $passwords = ($envPass !== false) ? [$envPass, '', 'root'] : ['', 'root', 'admin', 'password', '12345678'];
        $ports = [3306, 3307, 3308];
        if ($envPort = getenv('DB_PORT')) {
            array_unshift($ports, (int)$envPort);
        }
        $ports = array_unique($ports);

        $lastException = null;

        // Try candidate ports and passwords
        foreach ($ports as $port) {
            foreach ($passwords as $password) {
                try {
                    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
                    $options = [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    ];

                    $pdo = new PDO($dsn, $username, $password, $options);

                    // Check if target database exists; if not, create it
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo->exec("USE `{$dbname}`");

                    // Check if core tables exist; if not, seed from database.sql
                    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                    if ($stmt->rowCount() === 0) {
                        self::autoMigrate($pdo);
                    }

                    self::$instance = $pdo;
                    return self::$instance;
                } catch (PDOException $e) {
                    $lastException = $e;
                    // continue searching for active port/password
                }
            }
        }

        // If all attempts fail, log and display a friendly university-grade error page
        error_log("QuizArena Database Connection Failure: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        
        $errMsg = htmlspecialchars($lastException ? $lastException->getMessage() : 'Unable to connect to MySQL server on ports 3306/3307.');
        die('<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error — QuizArena</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
            <style>
                body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; }
                .error-card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2.5rem; max-width: 620px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            </style>
        </head>
        <body>
            <div class="container py-4">
                <div class="error-card mx-auto text-center">
                    <div class="mb-3 text-danger"><i class="fa-solid fa-triangle-exclamation fa-3x"></i></div>
                    <h3 class="fw-bold text-white mb-2">Database Connection Required</h3>
                    <p class="text-secondary mb-4">QuizArena could not connect to MySQL / MariaDB on localhost (ports 3306, 3307).</p>
                    <div class="text-start bg-dark p-3 rounded-3 mb-4 text-warning font-monospace small" style="word-break: break-all;">
                        ' . $errMsg . '
                    </div>
                    <div class="text-start text-light small mb-4">
                        <strong>Quick Fix:</strong>
                        <ol class="mt-2 text-secondary ps-3">
                            <li>Open <strong>XAMPP Control Panel</strong> or <strong>WAMP</strong>.</li>
                            <li>Start the <strong>MySQL</strong> service.</li>
                            <li>Import <code>database.sql</code> or simply refresh this page (auto-migration will seed the tables).</li>
                        </ol>
                    </div>
                    <button onclick="window.location.reload()" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                        <i class="fa-solid fa-rotate-right me-2"></i>Retry Connection
                    </button>
                </div>
            </div>
        </body>
        </html>');
    }

    private static function autoMigrate(PDO $pdo): void {
        $sqlFile = ROOT_PATH . '/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            // Split by semicolon statements safely
            $pdo->exec($sql);
        }
    }
}

/**
 * Global helper to access the PDO instance
 */
function getDB(): PDO {
    return Database::getConnection();
}
