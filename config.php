<?php
define('DB_HOST', 'librarydb');
define('DB_NAME', 'library_db');
define('DB_USER', 'library_user');
define('DB_PASS', 'admin12');

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
}

session_start();

define('SITE_NAME', 'Bibliothèque EPI');
define('UPLOAD_DIR', __DIR__ . '/uploads');

$directories = [
    UPLOAD_DIR,
    UPLOAD_DIR . '/books',
    UPLOAD_DIR . '/authors',
    UPLOAD_DIR . '/users'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Africa/Tunis');

mb_internal_encoding('UTF-8');

try {
    // Use TCP/IP connection instead of Unix socket
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>