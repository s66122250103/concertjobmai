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

/* --- ฟังก์ชันเช็คที่มาของรูปภาพ (แก้ปัญหารูปดำ/รูปไม่ขึ้น) --- */
function get_img_url($img_path, $default) {
    if (empty($img_path)) return BASE_URL . '/uploads/' . $default;
    if (filter_var($img_path, FILTER_VALIDATE_URL)) return $img_path;
    return BASE_URL . '/uploads/' . $img_path;
}

$event_img = get_img_url($event['event_image'], 'Dice_Poster.jpg');
$artist_img = get_img_url($event['artist_image'], 'DICE 2.jpg');

$stmt2 = $db->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC");
$stmt2->execute([$id]);
$tickets = $stmt2->fetchAll();

$pageTitle = $event['title'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.detail-wrap {
    max-width: 480px;
    margin: auto;
    padding: 1rem 1rem 5rem;
}

/* --- ส่วนที่แก้ไขเพื่อซูมเน้นศิลปินคนเดียว --- */
.hero {
    width: 100%;
    border-radius: 24px;
    overflow: hidden;
    background: #111;
    margin-bottom: 1.5rem;
}

.hero img {
    width: 100%;
    height: auto;
    display: block;
}


.event-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.3rem;
}

.event-sub {
    color: #aaa;
    font-size: .95rem;
    margin-bottom: 1.5rem;
}

.status {
    background: linear-gradient(135deg, #6C63FF, #a78bfa);
    padding: .7rem 1rem;
    border-radius: 12px;
    margin-bottom: 1.2rem;
    font-weight: 600;
    display: inline-block;
}

.ticket-card {
    background: rgba(255, 255, 255, .05);
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 18px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ticket-date {
    background: #6C63FF;
    padding: .5rem;
    border-radius: 12px;
    min-width: 60px;
    text-align: center;
    font-size: .8rem;
    font-weight: 700;
    line-height: 1.2;
}

.ticket-info { flex: 1; }
.ticket-type { color: #fff; font-weight: 600; font-size: 1rem; }
.ticket-qty { color: #999; font-size: .85rem; }

.buy {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    padding: .6rem 1.2rem;
    border-radius: 30px;
    color: #fff !important;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.9rem;
}

/* โปรไฟล์วง */
.artist-bio {
    margin-top: 2rem;
    border-radius: 20px;
    overflow: hidden;
    text-decoration: none;
    display: block;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
}

.artist-bio-header {
    background: #11182f;
    padding: 1.2rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.artist-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid #6C63FF;
}

.artist-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.artist-bio-header h3 { color: #fff; margin: 0; font-size: 1.1rem; }
.artist-bio-header span { color: #7c8cff; font-size: .85rem; }

.artist-bio-body {
    padding: 1.2rem;
    color: #bbb;
    font-size: .9rem;
    line-height: 1.8;
}

.artist-bio-footer {
    padding: 1rem;
    color: #6C63FF;
    text-align: right;
    font-weight: 700;
    background: rgba(108, 99, 255, 0.08);
    font-size: 0.9rem;
}
</style>

<div class="detail-wrap">

<a href="<?= BASE_URL ?>/pages/events.php" style="color:#888; text-decoration:none; display:inline-block; margin-bottom:1.5rem;">← กลับ</a>

<div class="hero">
    <img src="<?= htmlspecialchars($event_img) ?>" alt="Concert Image">
</div>

<div class="event-title">
    <?= htmlspecialchars($event['title']) ?>
</div>

<div class="event-sub">
    📅 <?= date('d/m/Y', strtotime($event['event_date'])) ?> 
    · 
    📍 <?= htmlspecialchars($event['venue']) ?>
</div>

<div class="status">🎫 เปิดขายบัตร</div>

<?php foreach($tickets as $t): ?>
<div class="ticket-card">
    <div class="ticket-date">
        <?= date('d/m', strtotime($event['event_date'])) ?><br>
        <?= date('y', strtotime($event['event_date'])) ?>
    </div>

    <div class="ticket-info">
        <div class="ticket-type">
            ราคา <?= number_format($t['price']) ?> บาท
        </div>
        <div class="ticket-qty">
            เหลือ <?= number_format($t['available_seats']) ?> ใบ
        </div>
    </div>

    <a class="buy" href="<?= BASE_URL ?>/pages/buy_ticket.php?event_id=<?= $event['id'] ?>&ticket_id=<?= $t['id'] ?>">
        ซื้อบัตร
    </a>
</div>
<?php endforeach; ?>

<a class="artist-bio" href="<?= BASE_URL ?>/pages/artist.php?id=<?= $event['id'] ?>">
    <div class="artist-bio-header">
        <div class="artist-avatar">
            <img src="<?= htmlspecialchars($artist_img) ?>" alt="Artist Avatar">
        </div>
        <div>
            <h3><?= htmlspecialchars($event['artist']) ?></h3>
            <span>ประวัติศิลปิน / วงดนตรี</span>
        </div>
    </div>

    <div class="artist-bio-body">
        <?= nl2br(htmlspecialchars($event['artist_bio'])) ?>
    </div>

    <div class="artist-bio-footer">
        อ่านประวัติเพิ่มเติม →
    </div>
</a>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>