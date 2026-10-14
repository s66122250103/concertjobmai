<?php
// pages/reset_password.php — ตั้งรหัสผ่านใหม่จากลิงก์ในอีเมล
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// ชื่อคอลัมน์รหัสผ่านในตาราง users (แก้ตรงนี้ถ้าใช้ชื่ออื่น เช่น password_hash)
const PASSWORD_COLUMN = 'password';

$pageTitle = 'ตั้งรหัสผ่านใหม่ | ConcertBook';
$token  = $_GET['token'] ?? $_POST['token'] ?? '';
$error  = '';
$done   = false;
$reset  = null;

// ตรวจโทเคน
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $st = getDB()->prepare('SELECT id, user_id FROM password_resets
                                WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
                                LIMIT 1');
        $st->execute([hash('sha256', $token)]);
        $reset = $st->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('reset_password: ' . $e->getMessage());
    }
}

if ($reset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (mb_strlen($pass) < 8) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif ($pass !== $confirm) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } else {
        try {
            $db = getDB();
            $db->beginTransaction();
            $db->prepare('UPDATE users SET `' . PASSWORD_COLUMN . '` = ? WHERE id = ?')
               ->execute([password_hash($pass, PASSWORD_DEFAULT), $reset['user_id']]);
            // ปิดโทเคนทั้งหมดของผู้ใช้คนนี้
            $db->prepare('UPDATE password_resets SET used_at = NOW()
                          WHERE user_id = ? AND used_at IS NULL')->execute([$reset['user_id']]);
            $db->commit();
            $done = true;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            error_log('reset_password: ' . $e->getMessage());
            $error = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
    .auth-wrap { min-height: calc(100vh - 64px); display: flex; align-items: center; justify-content: center; padding: 2rem 16px; }
    .auth-box { width: 100%; max-width: 390px; text-align: center; }
    .auth-icon { font-size: 3rem; color: #7d72e8; margin-bottom: .8rem; }
    .auth-title { font-size: 1.6rem; font-weight: 700; background: linear-gradient(135deg,#6C63FF,#ff6584);
                  -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .auth-sub { color: #999; margin: .4rem 0 1.8rem; font-size: .95rem; }
    .auth-card { background: #1a1a26; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; padding: 1.6rem 1.5rem; text-align: left; }
    .auth-card .btn { width: 100%; margin-top: .4rem; text-align: center; }
    .auth-foot { text-align: center; margin-top: 1.2rem; color: #888; font-size: .9rem; }
    .auth-foot a { color: #8b83ff; text-decoration: none; }
</style>

<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-icon"><i class="fa-solid fa-lock"></i></div>
        <div class="auth-title">ตั้งรหัสผ่านใหม่</div>
        <p class="auth-sub">ConcertBook</p>

        <div class="auth-card">
            <?php if ($done): ?>
                <div class="alert alert-success">เปลี่ยนรหัสผ่านเรียบร้อยแล้ว เข้าสู่ระบบด้วยรหัสผ่านใหม่ได้เลย</div>
                <a href="<?= BASE_URL ?>/pages/login.php" class="btn btn-primary">ไปหน้าเข้าสู่ระบบ</a>

            <?php elseif (!$reset): ?>
                <div class="alert alert-danger">ลิงก์นี้ไม่ถูกต้อง หมดอายุ หรือถูกใช้ไปแล้ว</div>
                <a href="<?= BASE_URL ?>/pages/forgot_password.php" class="btn btn-primary">ขอลิงก์ใหม่</a>

            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-group">
                        <label for="password">รหัสผ่านใหม่</label>
                        <input type="password" id="password" name="password" class="form-control"
                               required minlength="8" placeholder="อย่างน้อย 8 ตัวอักษร">
                    </div>
                    <div class="form-group">
                        <label for="confirm">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" id="confirm" name="confirm" class="form-control"
                               required minlength="8" placeholder="พิมพ์รหัสผ่านอีกครั้ง">
                    </div>
                    <button type="submit" class="btn btn-primary">บันทึกรหัสผ่านใหม่</button>
                </form>
            <?php endif; ?>
            <div class="auth-foot"><a href="<?= BASE_URL ?>/pages/login.php"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a></div>
        </div>
    </div>
</div>
</body>
</html>
