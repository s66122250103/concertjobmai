<?php
// pages/logout.php - ออกจากระบบ
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ล้างข้อมูลใน session ทั้งหมด
$_SESSION = [];

// ลบคุกกี้ session ในเบราว์เซอร์
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: ' . BASE_URL . '/pages/login.php');
exit;
