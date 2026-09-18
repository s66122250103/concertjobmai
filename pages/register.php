<?php
// pages/register.php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/events.php');
    exit;
}

$error = ''; $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } else {
        $result = registerUser(
            $_POST['username'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['full_name'] ?? '',
            $_POST['phone'] ?? ''
        );
        if ($result['success']) {
            $success = 'สมัครสมาชิกสำเร็จ! <a href="' . BASE_URL . '/pages/login.php" style="color:#6C63FF">คลิกเพื่อเข้าสู่ระบบ</a>';
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'สมัครสมาชิก';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 64px);padding:2rem 0;">
<div style="width:100%;max-width:460px;padding:1rem;">

    <div style="text-align:center;margin-bottom:1.5rem;">
        <h1 style="font-size:1.6rem;font-weight:700;">สมัครสมาชิก</h1>
        <p style="color:#888;">สร้างบัญชีเพื่อจองบัตรคอนเสิร์ต</p>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control" placeholder="ชื่อของคุณ" required
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" placeholder="username" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" class="form-control" placeholder="0812345678"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="อย่างน้อย 6 ตัวอักษร" required minlength="6">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem;">สมัครสมาชิก</button>
            </form>
            <?php endif; ?>

            <div style="text-align:center;margin-top:1.5rem;color:#888;font-size:.9rem;">
                มีบัญชีอยู่แล้ว?
                <a href="<?= BASE_URL ?>/pages/login.php" style="color:#6C63FF;text-decoration:none;">เข้าสู่ระบบ</a>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>