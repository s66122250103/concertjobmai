<?php
// pages/payment.php - หน้าชำระเงิน
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/promptpay.php';   // QR พร้อมเพย์แบบใส่ยอดอัตโนมัติ
requireLogin();

const SHIPPING_FEE = 50;   // ค่าส่งไปรษณีย์

$db = getDB();
$order_id = intval($_GET['order_id'] ?? 0);

$order = $db->prepare("
    SELECT o.*, e.title, e.artist, e.venue, e.event_date,
           e.event_image, e.artist_image,
           u.full_name, u.email, u.phone
    FROM orders o
    JOIN events e ON e.id = o.event_id
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ? AND o.user_id = ?
");
$order->execute([$order_id, $_SESSION['user_id']]);
$order = $order->fetch();

if (!$order) { header('Location: ' . BASE_URL . '/pages/Myticket.php'); exit; }

// โค้ดใหม่ (ละการ JOIN ตาราง resale_listings เพื่อป้องกัน Error)
$items = $db->prepare("
    SELECT oi.*, tt.type_name, tt.color, 1 as sell_separately
    FROM order_items oi
    JOIN ticket_types tt ON tt.id = oi.ticket_type_id
    WHERE oi.order_id = ?
");
$items->execute([$order_id]);
$items = $items->fetchAll();

// ยอดค่าบัตรล้วน (ไม่รวมค่าส่ง)
$items_total = array_sum(array_column($items, 'subtotal'));

// จัดการรูปภาพ
function get_img_url($img_path, $default) {
    if (empty($img_path)) return BASE_URL . '/uploads/' . $default;
    if (filter_var($img_path, FILTER_VALIDATE_URL)) return $img_path;
    return BASE_URL . '/uploads/' . $img_path;
}
$event_img  = get_img_url($order['event_image'],  'default.jpg');
$artist_img = get_img_url($order['artist_image'], 'default.jpg');

// ===== ข้อมูลบัญชีที่แสดงใต้ QR =====
$owner_name   = 'นายกษิดิศ ชุมแวงวาปี';
$account_text = 'กสิกรไทย · xxx-x-x7820-x';

$success = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $order['status'] === 'pending') {
    $delivery = ($_POST['delivery_type'] ?? '') === 'post' ? 'post' : 'digital';
    $address  = trim($_POST['shipping_address'] ?? '');
    $fee      = $delivery === 'post' ? SHIPPING_FEE : 0;

    if ($delivery === 'post' && $address === '') {
        $error = 'กรุณากรอกที่อยู่จัดส่ง';
    } elseif (empty($_FILES['slip']['name'])) {
        $error = 'กรุณาแนบสลิปการโอนเงิน';
    } else {
        $ext     = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed) || @getimagesize($_FILES['slip']['tmp_name']) === false) {
            $error = 'ไฟล์สลิปต้องเป็นรูปภาพ (jpg, png, gif, webp)';
        } else {
            $filename   = 'slip_' . $order_id . '_' . time() . '.' . $ext;
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            move_uploaded_file($_FILES['slip']['tmp_name'], $upload_dir . $filename);

            $new_total = $items_total + $fee;
            $db->prepare("UPDATE orders
                          SET status='paid', payment_method='พร้อมเพย์', payment_slip=?, paid_at=NOW(),
                              delivery_type=?, shipping_address=?, shipping_fee=?, total_amount=?
                          WHERE id=?")
               ->execute([$filename, $delivery, $delivery === 'post' ? $address : null, $fee, $new_total, $order_id]);

            $order['status']           = 'paid';
            $order['delivery_type']    = $delivery;
            $order['shipping_address'] = $address;
            $order['shipping_fee']     = $fee;
            $order['total_amount']     = $new_total;
            $success = 'ชำระเงินสำเร็จ! บัตรของคุณพร้อมแล้ว';
        }
    }
}

$shipping_fee = (float)($order['shipping_fee'] ?? 0);

$pageTitle = 'ชำระเงิน - ' . $order['order_number'];
include __DIR__ . '/../includes/header.php';
?>

<style>
.delivery-options {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}
.delivery-card {
    flex: 1;
    border: 2px solid rgba(255,255,255,.12);
    border-radius: 14px;
    padding: 1rem;
    cursor: pointer;
    transition: all .2s;
    background: rgba(255,255,255,.03);
    text-align: center;
}
.delivery-card:hover { border-color: #6C63FF; background: rgba(108,99,255,.08); }
.delivery-card.active { border-color: #6C63FF; background: rgba(108,99,255,.15); }
.delivery-card input[type=radio] { display: none; }
.delivery-icon { font-size: 2rem; margin-bottom: .4rem; }
.delivery-title { color: #fff; font-weight: 700; font-size: .95rem; margin-bottom: .2rem; }
.delivery-desc { color: #888; font-size: .78rem; line-height: 1.4; }
.delivery-badge {
    display: inline-block;
    margin-top: .4rem;
    background: rgba(108,99,255,.2);
    color: #a78bfa;
    border-radius: 50px;
    padding: .1rem .6rem;
    font-size: .75rem;
    font-weight: 600;
}
#address-box {
    display: none;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
}
#address-box textarea {
    width: 100%;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 10px;
    color: #fff;
    font-family: 'Kanit', sans-serif;
    font-size: .9rem;
    padding: .7rem;
    resize: none;
}
#address-box textarea:focus { outline: none; border-color: #6C63FF; }

.concert-ticket {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
    margin: 1.5rem auto;
    max-width: 420px;
    position: relative;
}
.ticket-top {
    position: relative;
    height: 180px;
    overflow: hidden;
}
.ticket-top img.ticket-bg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center 20%;
    display: block;
    filter: brightness(0.55);
}
.ticket-top-overlay {
    position: absolute;
    inset: 0;
    padding: 1.2rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}
.ticket-top-overlay h2 {
    color: #fff;
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0 0 .2rem;
    text-shadow: 0 2px 8px rgba(0,0,0,.6);
}
.ticket-top-overlay p {
    color: rgba(255,255,255,.85);
    font-size: .82rem;
    margin: 0;
}
.ticket-divider {
    background: #1a1a2e;
    display: flex;
    align-items: center;
    position: relative;
    height: 28px;
}
.ticket-divider::before,
.ticket-divider::after {
    content: '';
    position: absolute;
    width: 28px;
    height: 28px;
    background: #0f0f17;
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
}
.ticket-divider::before { left: -14px; }
.ticket-divider::after  { right: -14px; }
.ticket-divider-line {
    flex: 1;
    border-top: 2px dashed rgba(255,255,255,.15);
    margin: 0 1.5rem;
}
.ticket-divider-label {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    background: #4CAF50;
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    padding: .2rem .7rem;
    border-radius: 50px;
    letter-spacing: .5px;
}
.ticket-bottom {
    background: #1a1a2e;
    padding: 1.2rem 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}
.ticket-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #6C63FF;
    flex-shrink: 0;
}
.ticket-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.ticket-info { flex: 1; }
.ticket-info .artist-name {
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: .15rem;
}
.ticket-info .ticket-meta {
    color: #888;
    font-size: .78rem;
    line-height: 1.6;
}
.ticket-info .ticket-meta span {
    color: #a78bfa;
    font-weight: 600;
}
.ticket-order-num {
    background: rgba(108,99,255,.15);
    border: 1px solid rgba(108,99,255,.3);
    border-radius: 8px;
    padding: .4rem .7rem;
    text-align: center;
    flex-shrink: 0;
}
.ticket-order-num .order-label { color: #888; font-size: .65rem; }
.ticket-order-num .order-val   { color: #6C63FF; font-weight: 700; font-size: .75rem; }

.split-badge {
    background: rgba(76, 175, 80, 0.2);
    color: #81c784;
    border: 1px solid rgba(76, 175, 80, 0.4);
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 12px;
    margin-left: 6px;
}
.qr-box { display: flex; justify-content: center; margin-bottom: 1rem; }
</style>

<div class="container" style="max-width:700px; padding: 2rem 1rem;">
    <h1 style="font-size:1.8rem;font-weight:700;margin-bottom:1.5rem; color:#fff;">💳 ชำระเงิน</h1>

    <?php if ($success): ?>
    <div class="alert alert-success" style="color: #4CAF50; margin-bottom: 1rem;"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger" style="color: #ff6b6b; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:1.5rem; background: #1a1a2e; border-radius: 16px; padding: 1.2rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h3 style="font-size:1rem;color:#aaa;margin:0;">สรุปคำสั่งซื้อ</h3>
                <span style="background:rgba(108,99,255,.2);color:#6C63FF;border:1px solid #6C63FF55;padding:.2rem .8rem;border-radius:50px;font-size:.85rem;font-weight:600;">
                    #<?= htmlspecialchars($order['order_number']) ?>
                </span>
            </div>
            <p style="font-weight:700;font-size:1.1rem;color:#fff;margin-bottom:.3rem;"><?= htmlspecialchars($order['title']) ?></p>
            <p style="color:#ff6584;margin-bottom:.5rem;"><?= htmlspecialchars($order['artist']) ?></p>
            <p style="color:#888;font-size:.9rem;margin-bottom:1rem;">
                📅 <?= date('d M Y', strtotime($order['event_date'])) ?> · <?= htmlspecialchars($order['venue']) ?>
            </p>
            <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
            <?php foreach ($items as $item): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
                <span>
                    <span style="background:<?= htmlspecialchars($item['color'] ?? '#6C63FF') ?>;color:#fff;padding:.1rem .6rem;border-radius:50px;font-size:.8rem;margin-right:.5rem;">
                        <?= htmlspecialchars($item['type_name'] !== '' ? $item['type_name'] : 'บัตร ฿' . number_format($item['unit_price'])) ?>
                    </span>
                    × <?= $item['quantity'] ?>
                    <?php if (isset($item['sell_separately']) && $item['sell_separately'] == 1): ?>
                        <span class="split-badge">ขายแยกใบได้</span>
                    <?php endif; ?>
                </span>
                <span style="color:#fff;">฿<?= number_format($item['subtotal']) ?></span>
            </div>
            <?php endforeach; ?>

            <!-- ค่าส่ง: ตอนยังไม่จ่ายจะโชว์เมื่อเลือกส่งไปรษณีย์ / ตอนจ่ายแล้วโชว์ถ้ามีค่าส่ง -->
            <div id="ship-row" style="display:<?= $shipping_fee > 0 ? 'flex' : 'none' ?>;justify-content:space-between;margin-bottom:.5rem;color:#ffaa55;">
                <span>📮 ค่าส่งไปรษณีย์</span>
                <span>฿<?= number_format(SHIPPING_FEE) ?></span>
            </div>

            <hr style="border-color:rgba(255,255,255,.1);margin:1rem 0;">
            <div style="display:flex;justify-content:space-between;font-size:1.2rem;font-weight:700;color:#fff;">
                <span>รวมทั้งหมด</span>
                <span style="color:#6C63FF;" id="sum-total">฿<?= number_format($order['status'] === 'pending' ? $items_total : $order['total_amount']) ?></span>
            </div>
        </div>
    </div>

    <?php if ($order['status'] === 'pending'): ?>

    <div class="card" style="margin-bottom:1.5rem; background: #1a1a2e; border-radius: 16px; padding: 1.2rem;">
        <div class="card-body">
            <h3 style="margin-bottom:1rem; color:#fff; font-size:1.1rem;">📦 วิธีรับบัตร</h3>
            <div class="delivery-options">
                <label class="delivery-card active" id="card-digital" onclick="selectDelivery('digital', this)">
                    <input type="radio" name="delivery_type" value="digital" checked>
                    <div class="delivery-icon">📱</div>
                    <div class="delivery-title">บัตร E-Ticket</div>
                    <div class="delivery-desc">รับบัตรดิจิทัลทันทีหลังชำระเงิน</div>
                    <span class="delivery-badge">ฟรี · รับทันที</span>
                </label>
                <label class="delivery-card" id="card-post" onclick="selectDelivery('post', this)">
                    <input type="radio" name="delivery_type" value="post">
                    <div class="delivery-icon">📮</div>
                    <div class="delivery-title">ส่งไปรษณีย์</div>
                    <div class="delivery-desc">จัดส่งบัตรจริงถึงบ้าน ภายใน 3-5 วันทำการ</div>
                    <span class="delivery-badge" style="background:rgba(255,150,50,.15);color:#ffaa55;">+฿<?= SHIPPING_FEE ?> · 3-5 วัน</span>
                </label>
            </div>
            <div id="address-box">
                <p style="color:#aaa;font-size:.85rem;margin-bottom:.6rem;">📍 ที่อยู่จัดส่ง</p>
                <textarea id="shipping_address_input" rows="3" placeholder="บ้านเลขที่ ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด รหัสไปรษณีย์"></textarea>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem; background: #1a1a2e; border-radius: 16px; padding: 1.2rem;">
        <div class="card-body">
            <h3 style="margin-bottom:1.2rem; color:#fff; font-size:1.1rem;">ข้อมูลการโอนเงิน</h3>
            <div style="background:#fff;border-radius:16px;padding:1.5rem;text-align:center;margin-bottom:1.5rem;">
                <div style="background:#1a5276;border-radius:10px;padding:.6rem 1.2rem;margin-bottom:1rem;display:inline-flex;align-items:center;gap:.6rem;">
                    <span style="color:#fff;font-size:1.4rem;">⬛</span>
                    <div style="text-align:left;line-height:1.2;">
                        <div style="color:#fff;font-weight:700;font-size:.9rem;letter-spacing:1px;">THAI QR</div>
                        <div style="color:#aed6f1;font-size:.75rem;letter-spacing:1px;">PAYMENT</div>
                    </div>
                </div>
                <div style="margin-bottom:1rem;">
                    <span style="border:2px solid #1a73e8;border-radius:6px;padding:.25rem .8rem;color:#1a73e8;font-weight:700;font-size:.85rem;">
                        พร้อมเพย์ | PromptPay
                    </span>
                </div>

                <!-- QR 2 อัน: ยอดปกติ กับ ยอด + ค่าส่ง  สลับตามวิธีรับบัตร -->
                <div class="qr-box" id="qr-digital"><?= promptpay_qr_html($items_total, 200) ?></div>
                <div class="qr-box" id="qr-post" style="display:none"><?= promptpay_qr_html($items_total + SHIPPING_FEE, 200) ?></div>

                <p style="color:#1a73e8;font-weight:700;font-size:.95rem;margin-bottom:.4rem;">สแกน QR เพื่อโอนเข้าบัญชี</p>
                <p style="color:#222;font-weight:600;margin-bottom:.2rem;">ชื่อ: <?= $owner_name ?></p>
                <p style="color:#555;font-size:.85rem;margin-bottom:.4rem;"><?= $account_text ?></p>
                <p style="color:#e53935;font-weight:700;font-size:1.15rem;margin:0;">
                    ยอดโอน: <span id="pay-amount">฿<?= number_format($items_total) ?></span>
                </p>
            </div>

            <form method="POST" enctype="multipart/form-data" onsubmit="return syncDeliveryData()">
                <input type="hidden" name="delivery_type" id="hidden_delivery" value="digital">
                <input type="hidden" name="shipping_address" id="hidden_address" value="">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label style="color:#aaa; display:block; margin-bottom:.5rem;">แนบสลิปการโอนเงิน</label>
                    <input type="file" name="slip" class="form-control" accept="image/*" required style="color:#fff;">
                </div>
                <button type="submit" class="btn btn-success" style="width:100%;font-size:1.1rem; padding:.8rem; background:#4CAF50; border:none; border-radius:30px; color:#fff; font-weight:700; cursor:pointer;">
                    ✅ ยืนยันการชำระเงิน
                </button>
            </form>
        </div>
    </div>

    <?php elseif ($order['status'] === 'paid'): ?>

    <div class="card" style="margin-bottom:1.5rem; background: #1a1a2e; border-radius: 16px;">
        <div class="card-body" style="text-align:center;padding:1.5rem 1rem .5rem;">
            <div style="font-size:3rem;margin-bottom:.5rem;">✅</div>
            <h2 style="color:#4CAF50;margin-bottom:.3rem;">ชำระเงินสำเร็จ!</h2>
            <p style="color:#aaa;font-size:.9rem;">บัตรของคุณพร้อมแล้ว</p>
            <?php if (($order['delivery_type'] ?? 'digital') === 'post'): ?>
            <div style="margin:1rem auto 0;max-width:420px;text-align:left;background:rgba(255,150,50,.08);border:1px solid rgba(255,150,50,.3);border-radius:12px;padding:.8rem 1rem;">
                <div style="color:#ffaa55;font-weight:600;font-size:.9rem;margin-bottom:.3rem;">📮 จัดส่งทางไปรษณีย์ ภายใน 3-5 วันทำการ</div>
                <div style="color:#ccc;font-size:.85rem;white-space:pre-line;"><?= htmlspecialchars($order['shipping_address'] ?? '') ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div style="padding:0 1rem 1.5rem;">
            <div class="concert-ticket">
                <div class="ticket-top">
                    <img class="ticket-bg"
                         src="<?= htmlspecialchars($event_img) ?>"
                         alt="<?= htmlspecialchars($order['title']) ?>"
                         onerror="this.src='<?= BASE_URL ?>/uploads/default.jpg'">
                    <div class="ticket-top-overlay">
                        <h2><?= htmlspecialchars($order['title']) ?></h2>
                        <p>📅 <?= date('d M Y', strtotime($order['event_date'])) ?> &nbsp;·&nbsp; 📍 <?= htmlspecialchars($order['venue']) ?></p>
                    </div>
                </div>

                <div class="ticket-divider">
                    <div class="ticket-divider-line"></div>
                    <div class="ticket-divider-label">✓ ยืนยันแล้ว</div>
                </div>

                <div class="ticket-bottom">
                    <div class="ticket-avatar">
                        <img src="<?= htmlspecialchars($artist_img) ?>"
                             alt="<?= htmlspecialchars($order['artist']) ?>"
                             onerror="this.src='<?= BASE_URL ?>/uploads/default.jpg'">
                    </div>
                    <div class="ticket-info">
                        <div class="artist-name"><?= htmlspecialchars($order['artist']) ?></div>
                        <div class="ticket-meta">
                            <?php foreach ($items as $item): ?>
                            <span><?= htmlspecialchars($item['type_name'] !== '' ? $item['type_name'] : 'บัตร ฿' . number_format($item['unit_price'])) ?></span> &nbsp;×<?= $item['quantity'] ?> ใบ<br>
                            <?php endforeach; ?>
                            ชื่อ: <?= htmlspecialchars($order['full_name']) ?>
                        </div>
                    </div>
                    <div class="ticket-order-num">
                        <div class="order-label">ORDER</div>
                        <div class="order-val"><?= substr($order['order_number'], -8) ?></div>
                    </div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/pages/Myticket.php" class="btn btn-primary" style="display:block; text-align:center; text-decoration:none; width:100%; margin-top:1rem; padding:.8rem; background:#6C63FF; color:#fff; border-radius:30px; font-weight:700;">
                🎫 ดูบัตรของฉัน
            </a>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
const ITEMS_TOTAL  = <?= json_encode((float)$items_total) ?>;
const SHIPPING_FEE = <?= SHIPPING_FEE ?>;
const baht = n => '฿' + Math.round(n).toLocaleString('en-US');

function selectDelivery(type, el) {
    document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('hidden_delivery').value = type;
    document.getElementById('address-box').style.display = (type === 'post') ? 'block' : 'none';

    // สลับ QR และยอดเงินให้ตรงกับวิธีรับบัตร
    const isPost = type === 'post';
    const total  = ITEMS_TOTAL + (isPost ? SHIPPING_FEE : 0);
    document.getElementById('qr-digital').style.display = isPost ? 'none' : 'flex';
    document.getElementById('qr-post').style.display    = isPost ? 'flex' : 'none';
    document.getElementById('ship-row').style.display   = isPost ? 'flex' : 'none';
    document.getElementById('pay-amount').textContent   = baht(total);
    document.getElementById('sum-total').textContent    = baht(total);
}

function syncDeliveryData() {
    var addr = document.getElementById('shipping_address_input');
    if (addr) {
        document.getElementById('hidden_address').value = addr.value;
    }
    if (document.getElementById('hidden_delivery').value === 'post' && addr.value.trim() === '') {
        alert('กรุณากรอกที่อยู่จัดส่ง');
        addr.focus();
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
