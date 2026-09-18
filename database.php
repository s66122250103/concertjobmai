<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'concert_booking');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ตรวจจับชื่อโฟลเดอร์อัตโนมัติ
$_folder = basename(__DIR__ . '/..');
// ✅ ถูก
define('BASE_URL', 'http://localhost/Concert');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
        }
    }
    return $pdo;
}