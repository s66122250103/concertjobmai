<?php
// config/database.php
// - บน Render: อ่านค่าทั้งหมดจาก Environment Variables
// - ในเครื่อง (XAMPP): อ่านรหัสผ่านจาก config/secret.php (ไฟล์นี้ไม่ขึ้น GitHub)

define('DB_HOST', getenv('DB_HOST') ?: 'mysql-37bbae36-ssru-a6f2.a.aivencloud.com');
define('DB_PORT', getenv('DB_PORT') ?: '22581');
define('DB_NAME', getenv('DB_NAME') ?: 'defaultdb');
define('DB_USER', getenv('DB_USER') ?: 'avnadmin');
define('DB_PASS', getenv('DB_PASS') ?: (file_exists(__DIR__ . '/secret.php') ? require __DIR__ . '/secret.php' : ''));
define('DB_CHARSET', 'utf8mb4');

// เปิดผ่าน localhost -> ลิงก์ชี้ไปที่เครื่องตัวเอง / เปิดบน Render -> ลิงก์ชี้ไปที่ Render
$__host = $_SERVER['HTTP_HOST'] ?? '';
$__isLocal = in_array($__host, ['localhost', '127.0.0.1']) || str_starts_with($__host, 'localhost:');
define('BASE_URL', getenv('BASE_URL') ?: ($__isLocal ? 'http://localhost/Concert' : 'https://concertjobmai.onrender.com'));
unset($__host, $__isLocal);

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST .
                   ";port=" . DB_PORT .
                   ";dbname=" . DB_NAME .
                   ";charset=" . DB_CHARSET;

            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]);

        } catch (PDOException $e) {
            die("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: " . $e->getMessage());
        }
    }

    return $pdo;
}
