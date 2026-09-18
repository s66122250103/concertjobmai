<?php
// pages/buy_ticket.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();
$event_id  = intval($_GET['event_id'] ?? 0);
$ticket_id = intval($_GET['ticket_id'] ?? 0);

// ดึงข้อมูล event
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) { header('Location: ' . BASE_URL . '/pages/events.php'); exit; }

// ดึงประเภทบัตรทั้งหมดของ event นี้
$stmt2 = $db->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC");
$stmt2->execute([$event_id]);
$ticket_types = $stmt2->fetchAll();
if (!$ticket_types) { header('Location: ' . BASE_URL . '/pages/events.php'); exit; }

// บัตรที่เลือกมา (default = อันแรก)
$selected_ticket = $ticket_types[0];
foreach ($ticket_types as $t) {
    if ($t['id'] === $ticket_id) { $selected_ticket = $t; break; }
}

// จัดการรูปภาพ
function get_img_url($img_path, $default) {
    if (empty($img_path)) return BASE_URL . '/uploads/' . $default;
    if (filter_var($img_path, FILTER_VALIDATE_URL)) return $img_path;
    return BASE_URL . '/uploads/' . $img_path;
}
$event_img = get_img_url($event['event_image'], 'default.jpg');

// POST - สร้าง order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tid = intval($_POST['ticket_type_id'] ?? 0);
    $qty = intval($_POST['quantity'] ?? 1);
    $qty = max(1, min($qty, 10));

    $stmt3 = $db->prepare("SELECT * FROM ticket_types WHERE id = ? AND event_id = ?");
    $stmt3->execute([$tid, $event_id]);
    $chosen = $stmt3->fetch();

    if ($chosen && $chosen['available_seats'] >= $qty) {
        $order_number = 'ORD-' . strtoupper(uniqid());
        $total = $chosen['price'] * $qty;

        $db->prepare("INSERT INTO orders (user_id, event_id, order_number, total_amount, status, created_at)
                      VALUES (?, ?, ?, ?, 'pending', NOW())")
           ->execute([$_SESSION['user_id'], $event_id, $order_number, $total]);
        $order_id = $db->lastInsertId();

        $db->prepare("INSERT INTO order_items (order_id, ticket_type_id, quantity, unit_price, subtotal)
                      VALUES (?, ?, ?, ?, ?)")
           ->execute([$order_id, $tid, $qty, $chosen['price'], $total]);

        $db->prepare("UPDATE ticket_types SET available_seats = available_seats - ? WHERE id = ?")
           ->execute([$qty, $tid]);

        header('Location: ' . BASE_URL . '/pages/Payment.php?order_id=' . $order_id);
        exit;
    }
}

$pageTitle = 'ซื้อบัตร - ' . $event['title'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.buy-wrap {
    max-width: 480px;
    margin: 0 auto;
    padding-bottom: 5rem;
}

/* Header แดง */
.event-header {
    background: linear-gradient(135deg, #e53935, #ff6584);
    border-radius: 16px;
    padding: 1.2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* แก้ใหม่: กล่องรูปภาพ */
.event-header-img {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
    background: rgba(255,255,255,.2);
}
.event-header-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center center;
    display: block;
}

.event-header-info h2 {
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: .2rem;
}
.event-header-info p {
    font-size: .85rem;
    color: rgba(255,255,255,.8);
    margin: 0;
}

/* Section title */
.section-btn {
    background: linear-gradient(135deg, #6C63FF, #a78bfa);
    color: #fff;
    border: none;
    border-radius: 50px;
    padding: .5rem 1.5rem;
    font-family: 'Kanit', sans-serif;
    font-size: .95rem;
    font-weight: 600;
    margin-bottom: 1.2rem;
    display: inline-block;
}

/* Ticket type selector */
.ticket-type-grid {
    display: flex;
    gap: .8rem;
    flex-wrap: wrap;
    margin-bottom: 1.2rem;
}
.ticket-type-btn {
    flex: 1;
    min-width: 130px;
    padding: .7rem 1rem;
    border-radius: 10px;
    border: 2px solid rgba(255,255,255,.15);
    background: rgba(255,255,255,.05);
    color: #ccc;
    font-family: 'Kanit', sans-serif;
    font-size: .9rem;
    cursor: pointer;
    transition: all .2s;
    text-align: center;
}
.ticket-type-btn.active {
    border-color: #6C63FF;
    background: rgba(108,99,255,.15);
    color: #fff;
}

/* Quantity */
.qty-wrap {
    display: flex;
    align-items: center;
    gap: 0;
    background: linear-gradient(135deg, #6C63FF, #a78bfa);
    border-radius: 50px;
    overflow: hidden;
    width: fit-content;
    margin-bottom: 1.2rem;
}
.qty-btn {
    background: none;
    border: none;
    color: #fff;
    font-size: 1.3rem;
    width: 44px;
    height: 44px;
    cursor: pointer;
    font-family: 'Kanit', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
}
.qty-btn:hover { background: rgba(0,0,0,.2); }
.qty-num {
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    min-width: 36px;
    text-align: center;
}

/* Checkbox */
.check-row {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
    background: rgba(255,255,255,.04);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}
.check-row input[type=checkbox] {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #6C63FF;
    flex-shrink: 0;
}
.check-row label {
    color: #ccc;
    font-size: .9rem;
    line-height: 1.5;
}
.check-row label span {
    display: block;
    color: #888;
    font-size: .82rem;
}

/* Price row */
.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: rgba(108,99,255,.1);
    border: 1px solid rgba(108,99,255,.3);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}
.price-row .label { color: #aaa; font-size: .9rem; }
.price-row .amount { color: #6C63FF; font-weight: 700; font-size: 1.3rem; }

/* Submit */
.btn-next {
    width: 100%;
    padding: .9rem;
    background: linear-gradient(135deg, #e53935, #ff6584);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-family: 'Kanit', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;
}
.btn-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(229,57,53,.4);
}
</style>

<div class="buy-wrap container">

    <!-- Back -->
    <a href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $event['id'] ?>"
       style="color:#888;text-decoration:none;display:inline-block;margin-bottom:1rem;">← กลับ</a>

    <!-- Event Header -->
    <div class="event-header">
        <div class="event-header-img">
            <img src="<?= htmlspecialchars($event_img) ?>"
                 alt="<?= htmlspecialchars($event['title']) ?>"
                 onerror="this.style.display='none'; this.parentNode.innerHTML='🎵';">
        </div>
        <div class="event-header-info">
            <h2><?= htmlspecialchars($event['title']) ?></h2>
            <p><?= date('d/m/Y', strtotime($event['event_date'])) ?></p>
            <p><?= htmlspecialchars($event['venue']) ?></p>
        </div>
    </div>

    <form method="POST" id="buyForm">
        <input type="hidden" name="ticket_type_id" id="ticket_type_id" value="<?= $selected_ticket['id'] ?>">
        <input type="hidden" name="quantity" id="quantity_input" value="1">

        <!-- ประเภทบัตร -->
        <div class="section-btn">ระบุรายละเอียดบัตร</div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
            <span style="color:#aaa;font-size:.9rem;">ประเภทบัตร</span>
            <span style="color:#aaa;font-size:.9rem;">โซนบัตร</span>
        </div>

        <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-bottom:1.2rem;">
            <?php foreach ($ticket_types as $t): ?>
            <button type="button"
                    class="ticket-type-btn <?= $t['id'] === $selected_ticket['id'] ? 'active' : '' ?>"
                    onclick="selectTicket(<?= $t['id'] ?>, <?= $t['price'] ?>, this)"
                    <?= $t['available_seats'] <= 0 ? 'disabled style="opacity:.4;cursor:not-allowed;"' : '' ?>>
                <?= htmlspecialchars($t['type_name']) ?>
                <br>
                <small style="color:#6C63FF;font-weight:700;">฿<?= number_format($t['price']) ?></small>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- จำนวน -->
        <div style="margin-bottom:.6rem;color:#aaa;font-size:.9rem;">จำนวนบัตร</div>
        <div class="qty-wrap">
            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
            <span class="qty-num" id="qty_display">1</span>
            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
        </div>

        <!-- Checkbox -->
        <div class="check-row">
            <input type="checkbox" id="split" name="split" checked>
            <label for="split">
                ขายแยกใบได้
                <span>ลูกค้าสามารถ เลือกซื้อเฉพาะใบที่ต้องการได้</span>
            </label>
        </div>

        <!-- ราคารวม -->
        <div class="price-row">
            <span class="label">ราคารวม</span>
            <span class="amount" id="total_price">฿<?= number_format($selected_ticket['price']) ?></span>
        </div>

        <!-- ถัดไป -->
        <button type="submit" class="btn-next">ถัดไป →</button>
    </form>
</div>

<script>
let currentPrice = <?= $selected_ticket['price'] ?>;
let qty = 1;

function selectTicket(id, price, el) {
    document.getElementById('ticket_type_id').value = id;
    currentPrice = price;
    document.querySelectorAll('.ticket-type-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    updateTotal();
}

function changeQty(delta) {
    qty = Math.max(1, Math.min(10, qty + delta));
    document.getElementById('qty_display').textContent = qty;
    document.getElementById('quantity_input').value = qty;
    updateTotal();
}

function updateTotal() {
    const total = currentPrice * qty;
    document.getElementById('total_price').textContent = '฿' + total.toLocaleString('th-TH');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>