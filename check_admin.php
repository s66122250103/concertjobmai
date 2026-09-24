<?php
// ไฟล์ตรวจปัญหาปุ่มแอดมิน (ใช้ชั่วคราว ดูผลเสร็จแล้วลบทิ้ง)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: text/html; charset=utf-8');

$ok  = fn($b) => $b ? '✅' : '❌';
$hdr = @file_get_contents(__DIR__ . '/includes/header.php') ?: '';

echo '<div style="font-family:sans-serif;font-size:17px;line-height:1.9;max-width:720px;margin:30px auto">';
echo '<h2>ตรวจปุ่มแอดมิน</h2>';

// 1) ไฟล์ header
echo $ok(str_contains($hdr, 'nav-admin')) . ' includes/header.php เป็นตัวใหม่ (มีปุ่มแอดมิน)'
   . ' <small style="color:#888">แก้ไขล่าสุด ' . date('d/m/Y H:i', @filemtime(__DIR__ . '/includes/header.php')) . '</small><br>';

// 2) ล็อกอิน
$uid = $_SESSION['user_id'] ?? null;
echo $ok((bool)$uid) . ' ล็อกอินอยู่ ' . ($uid ? "(user_id = $uid)" : '— ให้ล็อกอินก่อนแล้วเปิดหน้านี้ใหม่') . '<br>';

// 3) ฐานข้อมูล + สิทธิ์
try {
    $db = getDB();
    echo '✅ ต่อฐานข้อมูลได้: <b>' . htmlspecialchars($db->query('SELECT DATABASE()')->fetchColumn()) . '</b><br>';
    if ($uid) {
        $st = $db->prepare('SELECT email, is_admin FROM users WHERE id = ?');
        $st->execute([$uid]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        echo $ok(!empty($u['is_admin'])) . ' บัญชี ' . htmlspecialchars($u['email'] ?? '?') . ' is_admin = ' . htmlspecialchars((string)($u['is_admin'] ?? 'ไม่มีค่า')) . '<br>';
    }
} catch (Throwable $e) {
    echo '❌ error: ' . htmlspecialchars($e->getMessage()) . '<br>';
}

// 4) โฟลเดอร์ admin
echo $ok(is_file(__DIR__ . '/admin/index.php')) . ' มีโฟลเดอร์ admin<br>';
echo '<p style="color:#888">แคปหน้านี้ส่งให้ Claude แล้วลบไฟล์ check_admin.php ทิ้ง</p></div>';
