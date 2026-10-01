<?php
/**
 * QUIZARENA — Database Connection & Global Configuration
 * Real-Time Online MCQ Quiz Platform
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

// Global Graceful Exception Handler (Controls unhandled PDO/runtime errors)
set_exception_handler(function (Throwable $e) {
    error_log("QuizArena Handled Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    // If an API request, return JSON
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
        exit();
    }

    // Friendly user notification with navigation options (no infinite auto-refresh loop)
    if (!headers_sent()) {
        http_response_code(500);
    }
    $homeUrl = defined('BASE_URL') ? BASE_URL . '/index.php' : '/';
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Notice — QuizArena</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
        <style>body { background: #0f172a; color: #f8fafc; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }</style>
    </head>
    <body>
        <div class="container py-4 text-center" style="max-width: 580px;">
            <div class="card p-4 p-md-5 bg-dark text-white border-secondary rounded-4 shadow">
                <div class="mb-3 text-warning fs-1">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h4 class="fw-bold mb-2">Something Went Wrong</h4>
                <p class="text-secondary small mb-3">An unexpected situation occurred while processing your request.</p>
                <div class="alert alert-secondary py-2 small text-start font-monospace mb-4 text-secondary">' . htmlspecialchars($e->getMessage()) . '</div>
                <div class="d-flex justify-content-center gap-2">
                    <button onclick="window.location.reload()" class="btn btn-primary rounded-pill px-4">
                        <i class="fa-solid fa-rotate-right me-1"></i> Try Again
                    </button>
                    <a href="' . htmlspecialchars($homeUrl) . '" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fa-solid fa-house me-1"></i> Go to Home
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit();
});

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
                        PDO::ATTR_TIMEOUT            => 1,
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
                    } else {
                        // Self-healing schema migration: ensure username column and messages table exist
                        self::ensureSchemaUpdates($pdo);
                    }

                    self::$instance = $pdo;
                    return self::$instance;
                } catch (PDOException $e) {
                    $lastException = $e;
                    // continue searching for active port/password
                }
            }
        }

        // If all attempts fail, log and display auto-refreshing recovery page
        error_log("QuizArena Database Connection Failure: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
        
        $errMsg = htmlspecialchars($lastException ? $lastException->getMessage() : 'Unable to connect to MySQL server on ports 3306/3307.');
        die('<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="refresh" content="3">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connecting — QuizArena</title>
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
                    <div class="mb-3 text-warning">
                        <div class="spinner-border text-warning mb-2" role="status"></div>
                        <h4 class="fw-bold text-white mb-2">Connecting to Database...</h4>
                    </div>
                    <p class="text-secondary mb-3">Attempting to establish MySQL connection. Page will <strong>automatically refresh in <span id="dbTimer" class="text-warning fw-bold">3</span>s</strong>.</p>
                    <div class="text-start bg-dark p-3 rounded-3 mb-4 text-warning font-monospace small" style="word-break: break-all;">
                        ' . $errMsg . '
                    </div>
                    <div class="text-start text-light small mb-4">
                        <strong>Quick Fix:</strong>
                        <ol class="mt-2 text-secondary ps-3">
                            <li>Open <strong>XAMPP Control Panel</strong> or <strong>WAMP</strong>.</li>
                            <li>Start the <strong>MySQL</strong> service.</li>
                            <li>Once started, this page will automatically load your dashboard!</li>
                        </ol>
                    </div>
                    <button onclick="window.location.reload()" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold">
                        <i class="fa-solid fa-rotate-right me-2"></i>Retry Now
                    </button>
                </div>
            </div>
            <script>
            let t = 3;
            const tEl = document.getElementById("dbTimer");
            setInterval(() => {
                t--;
                if (tEl) tEl.textContent = Math.max(0, t);
                if (t <= 0) {
                    window.location.reload();
                }
            }, 1000);
            </script>
        </body>
        </html>');
    }

    private static function autoMigrate(PDO $pdo): void {
        $sqlFile = ROOT_PATH . '/database.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            // Strip single-line SQL comments safely
            $sql = preg_replace('/--.*$/m', '', $sql);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    try {
                        $pdo->exec($stmt);
                    } catch (PDOException $e) {
                        // ignore safe duplicate schema notices
                    }
                }
            }
        }
    }

    private static function ensureSchemaUpdates(PDO $pdo): void {
        try {
            // Check if username column exists in users
            $colCheck = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'username'");
            if ($colCheck->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `users` ADD COLUMN `username` VARCHAR(50) NULL AFTER `id`");

                // Populate known default user usernames
                $pdo->exec("UPDATE `users` SET `username` = 'silva_teacher' WHERE `id` = 1 AND (`username` IS NULL OR `username` = '')");
                $pdo->exec("UPDATE `users` SET `username` = 'kasun_p' WHERE `id` = 2 AND (`username` IS NULL OR `username` = '')");
                $pdo->exec("UPDATE `users` SET `username` = 'nimal_s' WHERE `id` = 3 AND (`username` IS NULL OR `username` = '')");
                $pdo->exec("UPDATE `users` SET `username` = 'amali_w' WHERE `id` = 4 AND (`username` IS NULL OR `username` = '')");

                // Populate any other user with clean email prefix
                $remaining = $pdo->query("SELECT id, email FROM `users` WHERE `username` IS NULL OR `username` = ''")->fetchAll();
                foreach ($remaining as $u) {
                    $prefix = explode('@', $u['email'])[0];
                    $cleanPrefix = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
                    if (empty($cleanPrefix)) {
                        $cleanPrefix = 'user_' . $u['id'];
                    }
                    $stmtUp = $pdo->prepare("UPDATE `users` SET `username` = ? WHERE `id` = ?");
                    $stmtUp->execute([$cleanPrefix, $u['id']]);
                }

                $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `username` VARCHAR(50) NOT NULL");
                try {
                    $pdo->exec("ALTER TABLE `users` ADD UNIQUE INDEX `idx_users_username` (`username`)");
                } catch (Exception $idxEx) {
                    // index already exists
                }
            }

            // Ensure messages table exists
            $msgCheck = $pdo->query("SHOW TABLES LIKE 'messages'");
            if ($msgCheck->rowCount() === 0) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `email` VARCHAR(120) NOT NULL,
                    `message` TEXT NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (Exception $e) {
            error_log("Schema self-healing error: " . $e->getMessage());
        }
    }
}

/**
 * Global helper to access the PDO instance
 */
function getDB(): PDO {
    return Database::getConnection();
}
