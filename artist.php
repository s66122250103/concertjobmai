<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$id = intval($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/pages/events.php');
    exit;
}

function get_fixed_img($img_value, $default_file) {
    if (empty($img_value)) return BASE_URL . '/uploads/' . $default_file;
    if (filter_var($img_value, FILTER_VALIDATE_URL)) return $img_value;
    return BASE_URL . '/uploads/' . $img_value;
}

$artist_display_img = get_fixed_img($event['artist_image'], 'DICE 2.jpg');
$pageTitle = 'ประวัติ ' . $event['artist'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.bio-wrap {
    max-width: 500px;
    margin: auto;
    padding: 1rem 1rem 5rem;
    background-color: #0f0f17; /* คุมโทนดำ */
}

/* 1. ส่วนหัวข้อ */
.bio-banner {
    background: linear-gradient(135deg, #6C63FF, #3b3599);
    border-radius: 20px;
    padding: 1.2rem;
    text-align: center;
    margin-bottom: 1.5rem;
}
.bio-banner h1 { color: #fff; font-size: 1.3rem; font-weight: 800; margin: 0; }

/* 2. จุดตาย: ส่วนรูปภาพ (แก้ใหม่หมด) */
.artist-img-container {
    width: 100%;
    margin-bottom: 1.5rem;
    border-radius: 20px;
    overflow: hidden;
    background: #000;
    line-height: 0; /* กันช่องว่างขอบล่างรูป */
    border: 1px solid rgba(255,255,255,0.1);
}

.artist-img-container img {
    width: 100%;
    height: auto; /* ปล่อยความสูงอิสระ เพื่อให้เห็นครบทั้งคนซ้ายและคนขวา */
    display: block;
    /* ห้ามใส่ object-fit: cover เด็ดขาด */
}

/* 3. ส่วนเนื้อหา */
.bio-card {
    background: #16161e;
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 20px;
    padding: 1.5rem;
    color: #ccc;
    line-height: 1.8;
    font-size: 0.95rem;
    white-space: pre-line;
    margin-bottom: 2rem;
}

.btn-back-event {
    display: block;
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    color: #fff !important;
    text-align: center;
    padding: 1rem;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 800;
    box-shadow: 0 5px 15px rgba(255, 65, 108, 0.3);
}
</style>

<div class="bio-wrap">
    <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $event['id'] ?>" 
       style="color:#666; text-decoration:none; display:inline-block; margin-bottom:1rem; font-size: 0.9rem;">
       ← ย้อนกลับ
    </a>

    <div class="bio-banner">
        <h1>ศิลปิน: <?= htmlspecialchars($event['artist']) ?></h1>
    </div>

    <div class="artist-img-container">
        <img src="<?= htmlspecialchars($artist_display_img) ?>" 
             alt="<?= htmlspecialchars($event['artist']) ?>"
             onerror="this.src='<?= BASE_URL ?>/uploads/DICE 2.jpg'">
    </div>

    <?php if (!empty($event['artist_bio'])): ?>
    <div class="bio-card">
        <strong style="color:#7c8cff; display:block; margin-bottom:0.5rem; font-size:1.1rem;">📖 ประวัติ</strong>
        <?= htmlspecialchars($event['artist_bio']) ?>
    </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $event['id'] ?>" class="btn-back-event">
        🎫 จองบัตรคอนเสิร์ต
    </a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>