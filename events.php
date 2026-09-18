<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM events ORDER BY event_date ASC");
$events = $stmt->fetchAll();

$pageTitle = 'อีเวนต์ทั้งหมด';
include __DIR__ . '/../includes/header.php';
?>

<style>
    body { background-color: #0f0f17; color: #fff; font-family: 'Kanit', sans-serif; }

    .section-header {
        padding: 2rem 1rem 0.5rem;
        font-weight: 800;
        font-size: 1.5rem;
    }

    .horizontal-scroll-container {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 20px;
        padding: 1rem;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
    }

    .horizontal-scroll-container::-webkit-scrollbar { display: none; }

    .concert-card {
        flex: 0 0 350px;
        background-color: #16161e;
        border-radius: 24px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(255,255,255,0.05);
        scroll-snap-align: start;
        transition: 0.3s;
    }

    .concert-card:hover {
        transform: translateY(-5px);
        border-color: #7c4dff;
    }

    .card-hero {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
        background: #111;
    }

    /* Badge Sales */
    .badge-sales {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 3;
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: #fff;
        font-size: .75rem;
        font-weight: 800;
        padding: .3rem .8rem;
        border-radius: 50px;
        letter-spacing: 1px;
        box-shadow: 0 4px 12px rgba(255,65,108,.5);
        text-transform: uppercase;
    }

    .card-hero img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center center;
        display: block;
    }

    /* รูปแนวตั้ง เช่น DEPT — ซูมออกให้เห็นทั้ง 2 คนและเต็มกรอบ */
    .card-hero img.portrait {
        object-fit: cover;
        object-position: center 20%; /* เลื่อนขึ้นเล็กน้อยเพื่อเห็นหน้าทั้งคู่ */
        transform: scale(0.85);     /* ซูมออกให้เห็นกว้างขึ้น */
        transform-origin: center top;
    }

    .card-hero::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(to top, #16161e 0%, rgba(0,0,0,0) 60%);
        pointer-events: none;
    }

    .hero-info {
        position: absolute;
        bottom: 0; left: 0; right: 0;
        padding: 15px;
        z-index: 2;
    }
    .hero-info h3 { font-size: 1.1rem; margin-bottom: 4px; font-weight: 800; }
    .hero-info p { font-size: 0.8rem; color: #ccc; margin: 0; }

    .status-banner-sm {
        background: #7c4dff;
        padding: 8px 15px;
        font-size: 0.85rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ticket-mini-list {
        background: #11111b;
        padding: 12px;
        flex-grow: 1;
        max-height: 400px;
        overflow-y: auto;
    }

    .ticket-row-sm {
        background: #1f1f2b;
        border-radius: 16px;
        padding: 10px 15px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .btn-buy-now {
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: #fff !important;
        text-decoration: none;
        font-size: 0.8rem;
        padding: 6px 16px;
        border-radius: 25px;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(255, 65, 108, 0.3);
    }
</style>

<div class="container-fluid">
    <div class="section-header">🎫 อีเวนต์ทั้งหมด</div>

    <?php if (empty($events)): ?>
        <p style="color:#666; text-align: center; padding: 50px;">ยังไม่มีอีเวนต์ในขณะนี้</p>
    <?php else: ?>

        <div class="horizontal-scroll-container">

            <?php foreach ($events as $event):
                $img_val = $event['event_image'];
                if (filter_var($img_val, FILTER_VALIDATE_URL)) {
                    $img_display = $img_val;
                } else {
                    $img_display = BASE_URL . '/uploads/' . ($img_val ?: 'default.jpg');
                }

                // รูปไหนที่เป็นแนวตั้ง ให้เพิ่มชื่อไฟล์ใน array นี้
               $portrait_files = ['Dept.jpg', 'dept.jpg', 'dept_poster.jpg', 'bts.png'];
                $filename = basename($img_val ?? '');
                $is_portrait = in_array($filename, $portrait_files);

                $stmt_t = $db->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC");
                $stmt_t->execute([$event['id']]);
                $event_tickets = $stmt_t->fetchAll();
            ?>

                <div class="concert-card">
                    <div class="card-hero">
                        <?php if (!empty($event['is_sale'])): ?>
                        <div class="badge-sales">🔥 Sales</div>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($img_display) ?>"
                             alt="<?= htmlspecialchars($event['title']) ?>"
                             class="<?= $is_portrait ? 'portrait' : '' ?>"
                             onerror="this.src='<?= BASE_URL ?>/uploads/default.jpg'">
                        <div class="hero-info">
                            <h3><?= htmlspecialchars($event['title']) ?></h3>
                            <p>📅 <?= date('d/m/Y', strtotime($event['event_date'])) ?> | 📍 <?= htmlspecialchars($event['venue']) ?></p>
                        </div>
                    </div>

                    <div class="status-banner-sm">🎫 เปิดขายบัตร</div>

                    <div class="ticket-mini-list">
                        <?php if(empty($event_tickets)): ?>
                            <p style="color:#444; font-size:0.8rem; text-align:center; padding:20px;">ยังไม่ระบุราคาบัตร</p>
                        <?php else: ?>
                            <?php foreach ($event_tickets as $t): ?>
                                <div class="ticket-row-sm">
                                    <div>
                                        <div style="font-size: 1rem; font-weight: 700; color: #fff;">
                                            <?= number_format($t['price']) ?> บาท
                                        </div>
                                        <div style="font-size: 0.75rem; color: #888;">
                                            เหลือ <?= number_format($t['available_seats']) ?> ใบ
                                        </div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/pages/buy_ticket.php?event_id=<?= $event['id'] ?>&ticket_id=<?= $t['id'] ?>" class="btn-buy-now">
                                        ซื้อบัตร
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $event['id'] ?>"
                       style="text-align: center; padding: 15px; background: #252533; color: #888; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                       ดูรายละเอียดเพิ่มเติม →
                    </a>
                </div>

            <?php endforeach; ?>

        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>