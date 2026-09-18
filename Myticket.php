<?php
// pages/my_tickets.php - บัตรของฉัน (ประวัติการซื้อ)
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$db = getDB();

$orders = $db->prepare("
    SELECT o.*, e.title, e.artist, e.venue, e.event_date
    FROM orders o
    JOIN events e ON e.id = o.event_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$orders->execute([$_SESSION['user_id']]);
$orders = $orders->fetchAll();

$pageTitle = 'บัตรของฉัน';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <h1 style="font-size:2rem;font-weight:700;margin-bottom:.5rem;">🎫 บัตรของฉัน</h1>
    <p style="color:#888;margin-bottom:2rem;">ประวัติและบัตรคอนเสิร์ตทั้งหมดของคุณ</p>

    <?php if (empty($orders)): ?>
    <div style="text-align:center;padding:4rem;color:#666;">
        <div style="font-size:4rem;margin-bottom:1rem;">🎵</div>
        <p style="font-size:1.2rem;">ยังไม่มีบัตรคอนเสิร์ต</p>
        <a href="<?= BASE_URL ?>/pages/events.php" class="btn btn-primary" style="margin-top:1rem;">
            ดูอีเวนต์ทั้งหมด
        </a>
    </div>
    <?php endif; ?>

    <?php foreach ($orders as $order):
        $items = $db->prepare("
            SELECT oi.*, tt.type_name, tt.color,
                   GROUP_CONCAT(t.ticket_code SEPARATOR ',') as ticket_codes
            FROM order_items oi
            JOIN ticket_types tt ON tt.id = oi.ticket_type_id
            LEFT JOIN tickets t ON t.order_item_id = oi.id
            WHERE oi.order_id = ?
            GROUP BY oi.id
        ");
        $items->execute([$order['id']]);
        $items = $items->fetchAll();

        $statusInfo = [
            'pending'   => ['bg'=>'rgba(255,193,7,.2)',  'color'=>'#ffc107', 'label'=>'รอชำระเงิน'],
            'paid'      => ['bg'=>'rgba(76,175,80,.2)',  'color'=>'#4CAF50', 'label'=>'ชำระแล้ว'],
            'cancelled' => ['bg'=>'rgba(255,77,109,.2)', 'color'=>'#ff4d6d', 'label'=>'ยกเลิก'],
        ];
        $si = $statusInfo[$order['status']] ?? $statusInfo['pending'];
    ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <!-- Order header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem;">
                <div>
                    <h3 style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($order['title']) ?></h3>
                    <p style="color:#ff6584;"><?= htmlspecialchars($order['artist']) ?></p>
                    <p style="color:#888;font-size:.9rem;margin-top:.3rem;">
                        📅 <?= date('d M Y', strtotime($order['event_date'])) ?>
                        · <?= htmlspecialchars($order['venue']) ?>
                    </p>
                </div>
                <div style="text-align:right;">
                    <span style="background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>;
                                 padding:.3rem 1rem;border-radius:50px;font-size:.85rem;display:block;margin-bottom:.3rem;">
                        <?= $si['label'] ?>
                    </span>
                    <span style="color:#aaa;font-size:.8rem;">#<?= htmlspecialchars($order['order_number']) ?></span>
                </div>
            </div>

            <!-- Items -->
            <?php foreach ($items as $item): ?>
            <div style="background:rgba(255,255,255,.04);border-radius:10px;padding:1rem;margin-bottom:.8rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
                    <span>
                        <span style="background:<?= htmlspecialchars($item['color']) ?>;color:#fff;
                                     padding:.2rem .7rem;border-radius:50px;font-size:.8rem;margin-right:.5rem;">
                            <?= htmlspecialchars($item['type_name']) ?>
                        </span>
                        × <?= $item['quantity'] ?>
                    </span>
                    <span style="color:#6C63FF;font-weight:700;">฿<?= number_format($item['subtotal']) ?></span>
                </div>

                <!-- QR Codes (แสดงเฉพาะบัตรที่ชำระแล้ว) -->
                <?php if ($order['status'] === 'paid' && $item['ticket_codes']): ?>
                <div style="display:flex;flex-wrap:wrap;gap:.8rem;">
                    <?php foreach (explode(',', $item['ticket_codes']) as $idx => $code): ?>
                    <div style="background:#1a1a2e;border:1px solid rgba(108,99,255,.4);
                                border-radius:10px;padding:.8rem;text-align:center;min-width:130px;">
                        <!-- QR via API -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($code) ?>"
                             alt="QR" style="width:100px;height:100px;border-radius:6px;display:block;margin:0 auto .5rem;">
                        <div style="font-size:.75rem;color:#888;word-break:break-all;"><?= htmlspecialchars($code) ?></div>
                        <div style="font-size:.8rem;color:#aaa;margin-top:.3rem;">บัตรที่ <?= $idx + 1 ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;padding-top:.8rem;border-top:1px solid rgba(255,255,255,.08);">
                <span style="color:#888;font-size:.9rem;">
                    สั่งซื้อเมื่อ <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </span>
                <span style="color:#fff;font-weight:700;font-size:1.1rem;">
                    รวม ฿<?= number_format($order['total_amount']) ?>
                </span>
            </div>

            <?php if ($order['status'] === 'pending'): ?>
            <a href="<?= BASE_URL ?>/pages/payment.php?order_id=<?= $order['id'] ?>"
               class="btn btn-primary" style="width:100%;text-align:center;margin-top:.8rem;">
                💳 ชำระเงิน
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>