<?php
// pages/login.php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/events.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        header('Location: ' . BASE_URL . '/pages/events.php');
        exit;
    }
    $error = $result['message'];
}

$pageTitle = 'เข้าสู่ระบบ';
include __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 64px);">
<div style="width:100%;max-width:420px;padding:1rem;">

    <!-- Logo -->
    <div style="text-align:center;margin-bottom:2rem;">
        <div style="font-size:3rem;margin-bottom:.5rem;">🎵</div>
        <h1 style="font-size:1.6rem;font-weight:700;background:linear-gradient(135deg,#6C63FF,#ff6584);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
            ConcertBook
        </h1>
        <p style="color:#888;margin-top:.3rem;">Welcome back, Sign in to book your concert</p>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem;">
                    เข้าสู่ระบบ
                </button>
            </form>

            <div style="text-align:center;margin-top:1.5rem;color:#888;font-size:.9rem;">
                ยังไม่มีบัญชี?
                <a href="<?= BASE_URL ?>/pages/register.php" style="color:#6C63FF;text-decoration:none;">สมัครสมาชิก</a>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>