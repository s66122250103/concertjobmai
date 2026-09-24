<?php
/**
 * ไฟล์ตั้งค่ากลางของหน้าแอดมิน
 * ใช้การเชื่อมต่อฐานข้อมูลตัวเดียวกับเว็บหลัก (config/database.php -> getDB())
 * เว็บหลักต่อฐานข้อมูลไหน (XAMPP หรือ Aiven) หน้าแอดมินก็ใช้ตัวนั้น ไม่ต้องตั้งค่าซ้ำ
 *
 * โครงสร้างที่ต้องเป็น:
 *   config/database.php
 *   includes/auth.php
 *   pages/login.php
 *   admin/   <- โฟลเดอร์นี้ วางคู่กับ pages/
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

define('LOGIN_URL', (defined('BASE_URL') ? BASE_URL : '..') . '/pages/login.php');

/* ---------- ตรวจว่าเป็นแอดมิน ---------- */
$uid = $_SESSION['user_id'] ?? null;   // ชื่อเดียวกับที่ pages/payment.php ใช้
if (!$uid) { header('Location: ' . LOGIN_URL); exit; }

$st = $pdo->prepare('SELECT id, full_name, is_admin FROM users WHERE id = ?');
$st->execute([$uid]);
$me = $st->fetch();
if (!$me || !$me['is_admin']) {
    http_response_code(403);
    exit('<h2 style="font-family:sans-serif">หน้านี้สำหรับแอดมินเท่านั้น</h2>');
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'; }
function csrf_check(): void {
    if (($_POST['csrf'] ?? '') !== $_SESSION['csrf']) { http_response_code(400); exit('Invalid token'); }
}

/* ---------- ตัวช่วย ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function baht($n, int $dec = 0): string {
    if ($n === null) return '–';
    return ($n < 0 ? '-' : '') . '฿' . number_format(abs((float)$n), $dec);
}
function flash(?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function type_label(array $t): string {
    return ($t['type_name'] !== '' ? $t['type_name'] : 'ไม่มีชื่อโซน #' . $t['id']);
}

/**
 * เก็บ "ราคากลาง ณ ตอนขาย" ไว้ในแต่ละรายการบัตรที่ขาย (order_items.face_price)
 * จะได้รู้ว่าบัตรแต่ละใบบวกไปเท่าไหร่ แม้ภายหลังจะเปลี่ยนราคาก็ตาม
 *   บวกเพิ่มต่อใบ = ราคาที่ลูกค้าจ่าย (unit_price) - ราคากลาง ณ ตอนนั้น (face_price)
 */
function ensure_item_face_price(PDO $pdo, ?int $eventId = null): void {
    static $checked = false;
    if (!$checked) {
        $has = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'face_price'")->fetchColumn();
        if (!$has) $pdo->exec("ALTER TABLE `order_items` ADD COLUMN `face_price` DECIMAL(10,2) NULL AFTER `unit_price`");
        $checked = true;
    }
    // รายการที่ยังไม่มีราคากลาง ให้ใช้ราคากลางปัจจุบันของโซน (ยังไม่เคยเปลี่ยนราคาตั้งแต่ขาย)
    $sql = "UPDATE order_items oi JOIN ticket_types tt ON tt.id = oi.ticket_type_id
            SET oi.face_price = COALESCE(tt.face_price, tt.price)
            WHERE oi.face_price IS NULL" . ($eventId ? " AND tt.event_id = " . (int)$eventId : "");
    $pdo->exec($sql);
}

/* ---------- เลย์เอาต์ ---------- */
function admin_header(string $title, string $active): void {
    $menu = [
        'index.php'     => '📊 สรุปยอด',
        'sales.php'     => '🎫 บัตรที่ขาย',
        'purchases.php' => '🧾 ซื้อบัตรเข้า',
        'pricing.php'   => '🏷️ ราคากลาง / บวกเพิ่ม',
        'suppliers.php' => '👤 ผู้ขาย',
    ];
    ?>
<!doctype html>
<html lang="th"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · ConcertBook Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head><body>
<nav class="topbar">
  <a class="brand" href="<?= defined('BASE_URL') ? BASE_URL : '..' ?>/pages/events.php">🎵 ConcertBook <span>Admin</span></a>
  <div class="tabs">
    <?php foreach ($menu as $href => $label): ?>
      <a href="<?= $href ?>" class="<?= $href === $active ? 'on' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</nav>
<main>
<h1><?= e($title) ?></h1>
<?php if ($m = flash()): ?><div class="flash"><?= e($m) ?></div><?php endif;
}

function admin_footer(): void { echo "</main></body></html>"; }
