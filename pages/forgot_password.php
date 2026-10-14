<?php
// pages/forgot_password.php — กรอกอีเมลเพื่อขอลิงก์รีเซ็ตรหัสผ่าน
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$pageTitle = 'ลืมรหัสผ่าน | ConcertBook';
$sent  = false;
$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'กรุณากรอกอีเมลให้ถูกต้อง';
    } else {
        try {
            $db = getDB();
            $st = $db->prepare('SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $user = $st->fetch();

            if ($user) {
                // กันกดส่งรัวๆ: ถ้าขอไปแล้วภายใน 60 วินาที ไม่ต้องส่งซ้ำ
                $st = $db->prepare('SELECT COUNT(*) FROM password_resets
                                    WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)');
                $st->execute([$user['id']]);
                $recent = (int)$st->fetchColumn();

                if ($recent === 0) {
                    // ยกเลิกลิงก์เก่าที่ยังไม่ได้ใช้ของผู้ใช้คนนี้
                    $db->prepare('UPDATE password_resets SET used_at = NOW()
                                  WHERE user_id = ? AND used_at IS NULL')->execute([$user['id']]);

                    $token = bin2hex(random_bytes(32));
                    $db->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                                  VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))')
                       ->execute([$user['id'], hash('sha256', $token)]);

                    $link = BASE_URL . '/pages/reset_password.php?token=' . $token;
                    $name = htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES);
                    $safeLink = htmlspecialchars($link, ENT_QUOTES);

                    $html = <<<HTML
<div style="font-family:Tahoma,Arial,sans-serif;max-width:480px;margin:auto;padding:24px;background:#f6f6fb;border-radius:12px">
  <h2 style="color:#6C63FF;margin:0 0 12px">ConcertBook</h2>
  <p>สวัสดีคุณ {$name}</p>
  <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ กดปุ่มด้านล่างเพื่อตั้งรหัสผ่านใหม่</p>
  <p style="text-align:center;margin:24px 0">
    <a href="{$safeLink}" style="background:#6C63FF;color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;display:inline-block">ตั้งรหัสผ่านใหม่</a>
  </p>
  <p style="font-size:13px;color:#666">ลิงก์นี้ใช้ได้ 30 นาทีและใช้ได้ครั้งเดียว ถ้าคุณไม่ได้ขอรีเซ็ตรหัสผ่าน ไม่ต้องทำอะไร รหัสผ่านเดิมยังใช้ได้ตามปกติ</p>
  <p style="font-size:12px;color:#999;word-break:break-all">ถ้ากดปุ่มไม่ได้ ให้คัดลอกลิงก์นี้ไปเปิด:<br>{$safeLink}</p>
</div>
HTML;
                    sendMail($user['email'], $user['full_name'] ?? '', 'รีเซ็ตรหัสผ่าน ConcertBook', $html);
                }
            }
            // แสดงข้อความเดียวกันเสมอ ไม่บอกว่ามีอีเมลนี้ในระบบหรือไม่
            $sent = true;
        } catch (Throwable $e) {
            error_log('forgot_password: ' . $e->getMessage());
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
    .auth-card .btn { width: 100%; margin-top: .4rem; }
    .auth-foot { text-align: center; margin-top: 1.2rem; color: #888; font-size: .9rem; }
    .auth-foot a { color: #8b83ff; text-decoration: none; }
</style>

<div class="auth-wrap">
    <div class="auth-box">
        <div class="auth-icon"><i class="fa-solid fa-key"></i></div>
        <div class="auth-title">ลืมรหัสผ่าน</div>
        <p class="auth-sub">กรอกอีเมลที่ใช้สมัคร เราจะส่งลิงก์ตั้งรหัสผ่านใหม่ให้</p>

        <div class="auth-card">
            <?php if ($sent): ?>
                <div class="alert alert-success">
                    ถ้าอีเมลนี้มีบัญชีอยู่ในระบบ เราได้ส่งลิงก์ตั้งรหัสผ่านใหม่ไปแล้ว
                    กรุณาเช็กกล่องจดหมาย (รวมถึงโฟลเดอร์ Spam) ลิงก์ใช้ได้ 30 นาที
                </div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="post">
                    <div class="form-group">
                        <label for="email">อีเมล</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               placeholder="you@example.com" value="<?= htmlspecialchars($email) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">ส่งลิงก์รีเซ็ตรหัสผ่าน</button>
                </form>
            <?php endif; ?>
            <div class="auth-foot"><a href="<?= BASE_URL ?>/pages/login.php"><i class="fa-solid fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ</a></div>
        </div>
    </div>
</div>
</body>
</html>
