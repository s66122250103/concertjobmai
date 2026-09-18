<?php
// includes/auth.php - ฟังก์ชันเกี่ยวกับการล็อกอิน/สมัครสมาชิก

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// ตรวจสอบว่าล็อกอินอยู่หรือไม่
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// บังคับให้ล็อกอินก่อน
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// สมัครสมาชิก
function registerUser($username, $email, $password, $full_name, $phone) {
    $db = getDB();
    
    // ตรวจสอบซ้ำ
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'อีเมลหรือชื่อผู้ใช้นี้ถูกใช้งานแล้ว'];
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashed, $full_name, $phone]);
    
    return ['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ'];
}

// ล็อกอิน
function loginUser($email, $password) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
}

// ล็อกเอาต์
function logoutUser() {
    session_destroy();
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit;
}