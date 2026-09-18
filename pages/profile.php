<?php
// pages/profile.php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();

// ดึงข้อมูล user
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// สถิติ
$stat = $db->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as paid_orders
    FROM orders WHERE user_id = ?
");
$stat->execute([$_SESSION['user_id']]);
$stat = $stat->fetch();

$success = '';
$error   = '';

// POST — แก้ข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $cur_pass  = $_POST['current_password'] ?? '';

    if (empty($full_name)) {
        $error = 'กรุณากรอกชื่อ-นามสกุล';
    } else {
        // เปลี่ยนรหัสผ่าน
        if (!empty($new_pass)) {
            if (!password_verify($cur_pass, $user['password'])) {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            } elseif (strlen($new_pass) < 6) {
                $error = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
            } else {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET full_name=?, phone=?, password=? WHERE id=?")
                   ->execute([$full_name, $phone, $hash, $_SESSION['user_id']]);
                $_SESSION['full_name'] = $full_name;
                $success = 'อัปเดตข้อมูลและรหัสผ่านเรียบร้อยแล้ว';
            }
        } else {
            $db->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?")
               ->execute([$full_name, $phone, $_SESSION['user_id']]);
            $_SESSION['full_name'] = $full_name;
            $success = 'อัปเดตข้อมูลเรียบร้อยแล้ว';
        }

        if (empty($error)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        }
    }
}

$pageTitle = 'โปรไฟล์ของฉัน';
include __DIR__ . '/../includes/header.php';
?>

<style>
.profile-wrap {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 1rem 5rem;
}

/* Avatar */
.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6C63FF, #ff6584);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}

/* Stat cards */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 16px;
    padding: 1rem;
    text-align: center;
}
.stat-card .stat-val {
    font-size: 1.4rem;
    font-weight: 800;
    color: #6C63FF;
}
.stat-card .stat-label {
    font-size: .75rem;
    color: #888;
    margin-top: .2rem;
}

/* Section title */
.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #aaa;
    margin-bottom: 1rem;
    padding-bottom: .5rem;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
</style>

<div class="profile-wrap">
    <h1 style="font-size:1.8rem;font-weight:700;margin-bottom:1.5rem;">👤 โปรไฟล์ของฉัน</h1>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Avatar + ชื่อ -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:1.2rem;">
                <div class="profile-avatar">
                    <?= mb_substr($user['full_name'], 0, 1) ?>
                </div>
                <div>
                    <div style="font-size:1.2rem;font-weight:700;"><?= htmlspecialchars($user['full_name']) ?></div>
                    <div style="color:#888;font-size:.9rem;"><?= htmlspecialchars($user['email']) ?></div>
                    <div style="color:#6C63FF;font-size:.8rem;margin-top:.2rem;">
                        สมาชิกตั้งแต่ <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สถิติ -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-val"><?= $stat['total_orders'] ?></div>
            <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= $stat['paid_orders'] ?></div>
            <div class="stat-label">ชำระแล้ว</div>
        </div>
        <div class="stat-card">
            <div class="stat-val">฿<?= number_format($stat['total_spent'] ?? 0) ?></div>
            <div class="stat-label">ยอดรวมทั้งหมด</div>
        </div>
    </div>

    <!-- ฟอร์มแก้ข้อมูล -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <div class="section-title">✏️ แก้ไขข้อมูลส่วนตัว</div>
            <form method="POST">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" class="form-control"
                           value="<?= htmlspecialchars($user['email']) ?>" disabled
                           style="opacity:.5;cursor:not-allowed;">
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                           placeholder="0812345678">
                </div>

                <div class="section-title" style="margin-top:1.5rem;">🔒 เปลี่ยนรหัสผ่าน (ไม่บังคับ)</div>
                <div class="form-group">
                    <label>รหัสผ่านปัจจุบัน</label>
                    <input type="password" name="current_password" class="form-control"
                           placeholder="กรอกเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่าน">
                </div>
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="อย่างน้อย 6 ตัวอักษร">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    💾 บันทึกข้อมูล
                </button>
            </form>
        </div>
    </div>

    <!-- ลิงก์ด่วน -->
    <div class="card">
        <div class="card-body">
            <div class="section-title">🔗 เมนูด่วน</div>
            <div style="display:flex;flex-direction:column;gap:.8rem;">
                <a href="<?= BASE_URL ?>/pages/Myticket.php"
                   style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1rem;
                          background:rgba(108,99,255,.08);border:1px solid rgba(108,99,255,.2);
                          border-radius:12px;color:#fff;text-decoration:none;">
                    🎫 <span>บัตรของฉัน</span>
                </a>
                <a href="<?= BASE_URL ?>/pages/events.php"
                   style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1rem;
                          background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);
                          border-radius:12px;color:#fff;text-decoration:none;">
                    🎵 <span>ดูอีเวนต์ทั้งหมด</span>
                </a>
                <a href="<?= BASE_URL ?>/pages/logout.php"
                   style="display:flex;align-items:center;gap:.8rem;padding:.8rem 1rem;
                          background:rgba(255,77,109,.08);border:1px solid rgba(255,77,109,.2);
                          border-radius:12px;color:#ff6584;text-decoration:none;">
                    🚪 <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>