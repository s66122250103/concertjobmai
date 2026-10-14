<?php
/**
 * ไฟล์ตั้งค่ากลางของหน้าแอดมิน
 * ใช้การเชื่อมต่อฐานข้อมูลเดียวกับหน้าเว็บ (config/database.php → Aiven)
 * จึงใช้ได้ทั้งในเครื่อง (XAMPP) และบน Render โดยไม่ต้องแก้อะไร
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

define('LOGIN_URL', BASE_URL . '/pages/login.php');   // หน้าเข้าสู่ระบบของเว็บ

$pdo = getDB();

/* ---------- ตรวจว่าเป็นแอดมิน ---------- */
// รองรับชื่อ session ที่นิยมใช้ ถ้าเว็บคุณใช้ชื่ออื่นให้เพิ่มตรงนี้
$uid = $_SESSION['user_id'] ?? $_SESSION['id'] ?? ($_SESSION['user']['id'] ?? null);
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

/* ---------- เลย์เอาต์ ---------- */
function admin_header(string $title, string $active): void {
    $menu = [
        'index.php'     => '📊 สรุปยอด',
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
  <a class="brand" href="<?= BASE_URL ?>/pages/events.php">🎵 ConcertBook <span>Admin</span></a>
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
