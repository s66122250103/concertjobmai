<?php
require __DIR__ . '/_bootstrap.php';
ensure_item_face_price($pdo);

$filterEvent  = (int)($_GET['event'] ?? 0);
$filterStatus = $_GET['status'] ?? 'paid';
if (!in_array($filterStatus, ['paid', 'pending', 'all'], true)) $filterStatus = 'paid';

$where = [];
$args  = [];
if ($filterStatus !== 'all') { $where[] = 'o.status = ?'; $args[] = $filterStatus; }
else                         { $where[] = "o.status IN ('paid','pending')"; }
if ($filterEvent)            { $where[] = 'o.event_id = ?'; $args[] = $filterEvent; }

$st = $pdo->prepare("
    SELECT o.id AS order_id, o.order_number, o.status, o.created_at, o.paid_at,
           u.full_name, u.email,
           e.title,
           tt.id AS tid, tt.type_name, tt.color,
           oi.quantity, oi.unit_price, oi.face_price, oi.subtotal,
           c.avg_cost
    FROM order_items oi
    JOIN orders o        ON o.id = oi.order_id
    JOIN users u         ON u.id = o.user_id
    JOIN events e        ON e.id = o.event_id
    JOIN ticket_types tt ON tt.id = oi.ticket_type_id
    JOIN v_type_cost c   ON c.ticket_type_id = tt.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY COALESCE(o.paid_at, o.created_at) DESC, oi.id DESC
");
$st->execute($args);
$rows = $st->fetchAll();

$events = $pdo->query('SELECT id, title FROM events ORDER BY event_date')->fetchAll();

$sum = ['qty' => 0, 'revenue' => 0, 'markup' => 0, 'profit' => 0, 'no_cost' => 0];
foreach ($rows as &$r) {
    $r['markup_each'] = $r['unit_price'] - $r['face_price'];
    $r['profit_each'] = $r['avg_cost'] === null ? null : $r['unit_price'] - $r['avg_cost'];
    $sum['qty']     += $r['quantity'];
    $sum['revenue'] += $r['subtotal'];
    $sum['markup']  += $r['markup_each'] * $r['quantity'];
    if ($r['profit_each'] === null) $sum['no_cost'] += $r['quantity'];
    else                            $sum['profit']  += $r['profit_each'] * $r['quantity'];
}
unset($r);

function pc($v) { return $v === null ? 'mut' : ($v > 0 ? 'pos' : ($v < 0 ? 'neg' : 'mut')); }

admin_header('บัตรที่ขาย · บวกเท่าไหร่ต่อใบ', 'sales.php');
?>

<div class="kpis">
  <div class="kpi"><div class="l">บัตรที่ขาย</div><div class="v"><?= number_format($sum['qty']) ?> ใบ</div><div class="s"><?= count($rows) ?> รายการ</div></div>
  <div class="kpi"><div class="l">ยอดขาย</div><div class="v"><?= baht($sum['revenue']) ?></div><div class="s">ไม่รวมค่าส่ง</div></div>
  <div class="kpi"><div class="l">รวมที่บวกเพิ่มจากราคากลาง</div><div class="v <?= pc($sum['markup']) ?>"><?= baht($sum['markup']) ?></div><div class="s">ส่วนที่ลูกค้าจ่ายเกินราคากลาง</div></div>
  <div class="kpi"><div class="l">กำไร (ยอดขาย − ทุน)</div><div class="v <?= pc($sum['profit']) ?>"><?= baht($sum['profit']) ?></div>
    <div class="s"><?= $sum['no_cost'] ? $sum['no_cost'] . ' ใบยังไม่มีทุน' : 'คิดจากทุนเฉลี่ยต่อโซน' ?></div></div>
</div>

<div class="card">
  <div class="event-head">
    <h2>🎫 รายการบัตรที่ขาย</h2>
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
      <select name="event" onchange="this.form.submit()">
        <option value="0">ทุกคอนเสิร์ต</option>
        <?php foreach ($events as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $filterEvent === (int)$ev['id'] ? 'selected' : '' ?>><?= e(preg_replace('/\s+/', ' ', $ev['title'])) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" onchange="this.form.submit()">
        <option value="paid"    <?= $filterStatus === 'paid' ? 'selected' : '' ?>>จ่ายแล้ว</option>
        <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>รอจ่าย</option>
        <option value="all"     <?= $filterStatus === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
      </select>
    </form>
  </div>
  <div class="scroll"><table>
    <thead><tr>
      <th>วันที่</th><th>ออเดอร์ / ลูกค้า</th><th>คอนเสิร์ต / โซน</th><th class="n">จำนวน</th>
      <th class="n">ราคากลาง/ใบ</th><th class="n">บวกเพิ่ม/ใบ</th><th class="n">ขายจริง/ใบ</th>
      <th class="n">ทุน/ใบ</th><th class="n">กำไร/ใบ</th><th class="n">กำไรรวม</th>
    </tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="10" class="mut">ยังไม่มีรายการ</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($r['paid_at'] ?? $r['created_at'])) ?>
            <?php if ($r['status'] === 'pending'): ?><br><span class="tag warn">รอจ่าย</span><?php endif; ?></td>
        <td><?= e($r['order_number']) ?><br><span class="mut" style="font-size:12px"><?= e($r['full_name']) ?></span></td>
        <td><span class="dot" style="background:<?= e($r['color']) ?>"></span><?= e(type_label(['id' => $r['tid'], 'type_name' => $r['type_name']])) ?>
            <br><span class="mut" style="font-size:12px"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $r['title']), 0, 36, '…')) ?></span></td>
        <td class="n"><?= $r['quantity'] ?></td>
        <td class="n"><?= baht($r['face_price']) ?></td>
        <td class="n <?= pc($r['markup_each']) ?>"><?= $r['markup_each'] > 0 ? '+' : '' ?><?= baht($r['markup_each']) ?></td>
        <td class="n"><?= baht($r['unit_price']) ?></td>
        <td class="n"><?= $r['avg_cost'] === null ? '<span class="tag warn">ยังไม่มีทุน</span>' : baht($r['avg_cost']) ?></td>
        <td class="n <?= pc($r['profit_each']) ?>"><?= baht($r['profit_each']) ?></td>
        <td class="n <?= pc($r['profit_each']) ?>"><?= $r['profit_each'] === null ? '–' : baht($r['profit_each'] * $r['quantity']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if ($rows): ?>
    <tfoot><tr>
      <td colspan="3">รวม</td><td class="n"><?= number_format($sum['qty']) ?></td><td></td>
      <td class="n <?= pc($sum['markup']) ?>"><?= baht($sum['markup']) ?></td>
      <td class="n"><?= baht($sum['revenue']) ?></td><td></td><td></td>
      <td class="n <?= pc($sum['profit']) ?>"><?= baht($sum['profit']) ?></td>
    </tr></tfoot>
    <?php endif; ?>
  </table></div>
  <p class="mut" style="font-size:13px;margin:14px 0 0">
    บวกเพิ่ม/ใบ = ราคาที่ลูกค้าจ่ายจริง − ราคากลางตอนที่ขาย · กำไร/ใบ = ราคาที่ลูกค้าจ่ายจริง − ทุนเฉลี่ยของโซน
  </p>
</div>

<?php admin_footer();
