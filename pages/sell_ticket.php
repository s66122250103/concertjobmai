<?php
// pages/sell_ticket.php - ตั้งขายบัตรต่อ (resale)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();
$event_id = intval($_GET['event_id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: ' . BASE_URL . '/pages/events.php');
    exit;
}

function get_img_url($img_path, $default) {
    if (empty($img_path)) return BASE_URL . '/uploads/' . $default;
    if (filter_var($img_path, FILTER_VALIDATE_URL)) return $img_path;
    return BASE_URL . '/uploads/' . $img_path;
}
$event_img = get_img_url($event['event_image'], 'default.jpg');

// ค่าธรรมเนียมการขายต่อ (บาท ต่อ 1 ใบ)
define('RESALE_FEE', 50);

// บัตรของผู้ใช้สำหรับงานนี้ ที่จ่ายเงินแล้ว และยังไม่ถูกตั้งขาย
$ticketsStmt = $db->prepare("
    SELECT t.id AS ticket_id, t.ticket_code, tt.type_name, tt.price AS face_price
    FROM tickets t
    JOIN order_items oi ON oi.id = t.order_item_id
    JOIN orders o ON o.id = oi.order_id
    JOIN ticket_types tt ON tt.id = oi.ticket_type_id
    WHERE o.user_id = ?
      AND o.event_id = ?
      AND o.status = 'paid'
      AND t.id NOT IN (
          SELECT ticket_id FROM resale_listings WHERE status = 'active'
      )
    ORDER BY tt.type_name ASC, t.id ASC
");
$ticketsStmt->execute([$_SESSION['user_id'], $event_id]);
$myTickets = $ticketsStmt->fetchAll();

// จัดกลุ่มบัตรตามโซน/ประเภท (type_name)
$zones = [];
foreach ($myTickets as $mt) {
    $zoneName = $mt['type_name'];
    if (!isset($zones[$zoneName])) {
        $zones[$zoneName] = [
            'face_price' => $mt['face_price'],
            'tickets'    => [],
        ];
    }
    $zones[$zoneName]['tickets'][] = $mt['ticket_id'];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zoneName        = $_POST['zone'] ?? '';
    $qty             = max(1, intval($_POST['quantity'] ?? 1));
    $asking_price    = floatval($_POST['asking_price'] ?? 0);
    $sell_separately = isset($_POST['sell_separately']) ? 1 : 0;

    if (!isset($zones[$zoneName])) {
        $error = 'ไม่พบโซนบัตรที่เลือก';
    } elseif ($qty > count($zones[$zoneName]['tickets'])) {
        $error = 'จำนวนที่เลือกมากกว่าบัตรที่คุณมีในโซนนี้';
    } elseif ($asking_price <= RESALE_FEE) {
        $error = 'ราคาขายต้องมากกว่าค่าธรรมเนียม (' . RESALE_FEE . ' บาท)';
    } else {
        $facePrice   = $zones[$zoneName]['face_price'];
        $payout      = $asking_price - RESALE_FEE;
        $bundleId    = $sell_separately ? null : bin2hex(random_bytes(16));
        $ticketIdsToList = array_slice($zones[$zoneName]['tickets'], 0, $qty);

        $insert = $db->prepare("
            INSERT INTO resale_listings
                (ticket_id, seller_id, event_id, face_price, asking_price, fee_amount, payout_amount, sell_separately, bundle_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");

        foreach ($ticketIdsToList as $tid) {
            $insert->execute([
                $tid,
                $_SESSION['user_id'],
                $event_id,
                $facePrice,
                $asking_price,
                RESALE_FEE,
                $payout,
                $sell_separately,
                $bundleId,
            ]);
        }

        header('Location: ' . BASE_URL . '/pages/Myticket.php?resale=success');
        exit;
    }
}

$pageTitle = 'รายการขายบัตร';
include __DIR__ . '/../includes/header.php';
?>

<style>
.sell-wrap {
    max-width: 460px;
    margin: auto;
    padding: 1rem 1rem 4rem;
}
.back-link {
    color: #888;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
    font-size: .9rem;
}
.sell-header {
    background: #6C63FF;
    color: #fff;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 1rem 1.2rem;
    border-radius: 14px 14px 0 0;
}
.event-banner {
    background: linear-gradient(135deg, #b7a94a, #8d8032);
    border-radius: 0 0 14px 14px;
    padding: 1rem 1.2rem;
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
}
.event-banner img {
    width: 56px;
    height: 72px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}
.event-banner-info h3 { font-size: 1rem; color: #fff; margin-bottom: .3rem; }
.event-banner-info p { font-size: .85rem; color: rgba(255,255,255,.85); margin: 0; }

.section-title-pill {
    display: inline-block;
    background: linear-gradient(135deg, #c542a3, #8b3fc9);
    color: #fff;
    font-weight: 700;
    padding: .6rem 1.5rem;
    border-radius: 50px;
    margin-bottom: 1.2rem;
    text-align: center;
}
.field-group { margin-bottom: 1.2rem; }
.field-group label {
    display: block;
    color: #aaa;
    font-size: .9rem;
    margin-bottom: .5rem;
}
.zone-select {
    width: 100%;
    padding: .8rem 1rem;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: .95rem;
}
.qty-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: linear-gradient(135deg,#4CAF50,#66bb6a);
    border-radius: 50px;
    padding: .5rem .8rem;
    width: fit-content;
}
.qty-btn {
    background: rgba(255,255,255,.25);
    border: none;
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    line-height: 1;
}
.qty-value {
    color: #fff;
    font-weight: 700;
    min-width: 1.5rem;
    text-align: center;
}
.qty-max-hint {
    color: #888;
    font-size: .8rem;
    margin-top: .4rem;
}
.separate-row {
    display: flex;
    align-items: flex-start;
    gap: .7rem;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    cursor: pointer;
}
.separate-row input[type="checkbox"] {
    width: 20px;
    height: 20px;
    margin-top: .1rem;
    accent-color: #6C63FF;
    flex-shrink: 0;
}
.separate-row .separate-title { color: #fff; font-weight: 600; font-size: .95rem; margin-bottom: .2rem; }
.separate-row .separate-desc { color: #999; font-size: .82rem; line-height: 1.5; }

.price-input {
    width: 100%;
    padding: .9rem 1rem;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 14px;
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: 1rem;
    margin-bottom: 1rem;
}
.price-input::placeholder { color: #999; }

.face-price-tag {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    color: #fff;
    font-weight: 700;
    text-align: center;
    padding: .7rem 1rem;
    border-radius: 50px;
    margin-bottom: 1.2rem;
}
.summary-card {
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 14px;
    padding: 1rem 1.2rem;
    margin-bottom: 1.5rem;
    font-size: .9rem;
    color: #ddd;
    line-height: 1.9;
}
.summary-card strong { color: #fff; }

.next-btn {
    width: 100%;
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    color: #fff;
    border: none;
    padding: .9rem;
    border-radius: 30px;
    font-family: 'Kanit', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
}
.next-btn:hover { opacity: .9; }

.empty-state {
    text-align: center;
    color: #888;
    padding: 3rem 1rem;
}
</style>

<div class="sell-wrap">
    <a class="back-link" href="<?= BASE_URL ?>/pages/event_detail.php?id=<?= $event['id'] ?>">← กลับ</a>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="color: #ff6b6b; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="sell-header">รายการขายบัตร</div>
    <div class="event-banner">
        <img src="<?= htmlspecialchars($event_img) ?>" alt="poster">
        <div class="event-banner-info">
            <h3><?= htmlspecialchars($event['title']) ?></h3>
            <p><?= htmlspecialchars($event['venue']) ?></p>
            <p><?= date('d/m/Y', strtotime($event['event_date'])) ?></p>
        </div>
    </div>

    <?php if (empty($zones)): ?>
        <div class="empty-state">
            คุณไม่มีบัตรที่ชำระเงินแล้วของงานนี้ที่สามารถตั้งขายได้
        </div>
    <?php else: ?>

    <form method="POST" id="sellForm">
        <div class="section-title-pill">🏷️ ระบุรายละเอียดบัตร</div>

        <div class="field-group">
            <label>โซนบัตร</label>
            <select class="zone-select" name="zone" id="zoneSelect" required>
                <?php foreach ($zones as $zoneName => $z): ?>
                <option value="<?= htmlspecialchars($zoneName) ?>"
                        data-face="<?= $z['face_price'] ?>"
                        data-max="<?= count($z['tickets']) ?>">
                    <?= htmlspecialchars($zoneName) ?> (มี <?= count($z['tickets']) ?> ใบ · หน้าบัตร <?= number_format($z['face_price']) ?> บาท)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field-group">
            <label>จำนวนที่จะขาย</label>
            <div class="qty-row">
                <button type="button" class="qty-btn" id="qtyMinus">−</button>
                <span class="qty-value" id="qtyValue">1</span>
                <button type="button" class="qty-btn" id="qtyPlus">+</button>
            </div>
            <div class="qty-max-hint" id="qtyMaxHint"></div>
            <input type="hidden" name="quantity" id="quantityInput" value="1">
        </div>

        <label class="separate-row">
            <input type="checkbox" name="sell_separately" id="sellSeparately" value="1" checked>
            <span>
                <span class="separate-title">ขายแยกใบได้</span><br>
                <span class="separate-desc">ลูกค้าสามารถเลือกซื้อเฉพาะใบที่ต้องการได้</span>
            </span>
        </label>

        <div class="section-title-pill">💰 ตั้งราคาบัตร</div>
        <p class="hint-text" style="color:#ccc;font-size:.95rem;margin-bottom:1rem;">กำหนดราคาต่อใบที่คุณต้องการขาย</p>

        <input
            class="price-input"
            type="number"
            name="asking_price"
            id="askingPrice"
            placeholder="ราคาขาย ที่คุณต้องการตั้ง (ต่อใบ)"
            min="1"
            step="0.01"
            required>

        <div class="face-price-tag" id="facePriceTag">ราคาหน้าบัตร: - บาท</div>

        <div class="summary-card">
            ค่าบัตร <strong id="sumFace">-</strong> บาท / ใบ<br>
            จำนวน <strong id="sumQty">1</strong> ใบ<br>
            ค่าธรรมเนียม <strong><?= number_format(RESALE_FEE) ?></strong> บาท / ใบ<br>
            จำนวนเงินที่คุณจะได้รับ <strong id="sumPayout">-</strong> บาท
        </div>

        <button type="submit" class="next-btn">ถัดไป →</button>
    </form>

    <?php endif; ?>
</div>

<script>
(function () {
    var feeAmount  = <?= RESALE_FEE ?>;
    var zoneSelect = document.getElementById('zoneSelect');
    var qtyValue   = document.getElementById('qtyValue');
    var qtyInput   = document.getElementById('quantityInput');
    var qtyMinus   = document.getElementById('qtyMinus');
    var qtyPlus    = document.getElementById('qtyPlus');
    var qtyHint    = document.getElementById('qtyMaxHint');
    var priceInput = document.getElementById('askingPrice');
    var faceTag    = document.getElementById('facePriceTag');
    var sumFace    = document.getElementById('sumFace');
    var sumQty     = document.getElementById('sumQty');
    var sumPayout  = document.getElementById('sumPayout');

    if (!zoneSelect) return;

    function fmt(n) {
        return Number(n).toLocaleString('th-TH', { maximumFractionDigits: 2 });
    }

    function currentMax() {
        var opt = zoneSelect.options[zoneSelect.selectedIndex];
        return opt ? parseInt(opt.dataset.max, 10) || 1 : 1;
    }

    function setQty(n) {
        var max = currentMax();
        n = Math.max(1, Math.min(n, max));
        qtyValue.textContent = n;
        qtyInput.value = n;
        sumQty.textContent = n;
        updateSummary();
    }

    function updateSummary() {
        var opt  = zoneSelect.options[zoneSelect.selectedIndex];
        var face = opt ? parseFloat(opt.dataset.face) || 0 : 0;
        var max  = currentMax();

        qtyHint.textContent = 'มีบัตรในโซนนี้ทั้งหมด ' + max + ' ใบ';
        faceTag.textContent = 'ราคาหน้าบัตร: ' + fmt(face) + ' บาท';
        sumFace.textContent = fmt(face);

        var price = parseFloat(priceInput.value) || 0;
        var qty   = parseInt(qtyInput.value, 10) || 1;
        var payoutPerTicket = Math.max(price - feeAmount, 0);
        sumPayout.textContent = fmt(payoutPerTicket * qty);
    }

    zoneSelect.addEventListener('change', function () { setQty(1); });
    qtyMinus.addEventListener('click', function () { setQty((parseInt(qtyInput.value, 10) || 1) - 1); });
    qtyPlus.addEventListener('click', function () { setQty((parseInt(qtyInput.value, 10) || 1) + 1); });
    priceInput.addEventListener('input', updateSummary);

    setQty(1);
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>