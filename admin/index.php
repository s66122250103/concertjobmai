<?php
require __DIR__ . '/_bootstrap.php';

/* ---------- ข้อมูลรายโซน (ใช้เป็นฐานของทุกตาราง) ---------- */
$zones = $pdo->query("
    SELECT tt.id, tt.event_id, tt.type_name, tt.color, tt.price, tt.face_price, tt.markup,
           tt.available_seats,
           e.title, e.event_date,
           c.bought_qty, c.bought_cost, c.avg_cost,
           COALESCE(s.sold_qty,0) AS sold_qty, COALESCE(s.revenue,0) AS revenue
    FROM ticket_types tt
    JOIN events e       ON e.id = tt.event_id
    JOIN v_type_cost c  ON c.ticket_type_id = tt.id
    LEFT JOIN v_type_sales s ON s.ticket_type_id = tt.id
    ORDER BY e.event_date, tt.price DESC
")->fetchAll();

/* ---------- รวมเป็นรายคอนเสิร์ต + ภาพรวม ---------- */
$events = [];
$tot = ['bought_qty'=>0,'bought_cost'=>0,'sold_qty'=>0,'revenue'=>0,'cogs'=>0,'rev_costed'=>0,'stock_value'=>0,'no_cost_qty'=>0];

foreach ($zones as &$z) {
    $hasCost      = $z['avg_cost'] !== null;
    $z['cogs']    = $hasCost ? $z['sold_qty'] * $z['avg_cost'] : null;
    $z['profit']  = $hasCost ? $z['revenue'] - $z['cogs'] : null;
    $z['unit_profit'] = $hasCost ? $z['price'] - $z['avg_cost'] : null;
    $unsold       = max($z['bought_qty'] - $z['sold_qty'], 0);

    $eid = $z['event_id'];
    $events[$eid] ??= ['title'=>$z['title'],'date'=>$z['event_date'],'bought_qty'=>0,'bought_cost'=>0,
                       'sold_qty'=>0,'revenue'=>0,'cogs'=>0,'rev_costed'=>0,'no_cost_qty'=>0];
    $ev = &$events[$eid];
    $ev['bought_qty']  += $z['bought_qty'];
    $ev['bought_cost'] += $z['bought_cost'];
    $ev['sold_qty']    += $z['sold_qty'];
    $ev['revenue']     += $z['revenue'];
    if ($hasCost) { $ev['cogs'] += $z['cogs']; $ev['rev_costed'] += $z['revenue']; }
    else          { $ev['no_cost_qty'] += $z['sold_qty']; }
    unset($ev);

    foreach (['bought_qty','bought_cost','sold_qty','revenue'] as $k) $tot[$k] += $z[$k];
    if ($hasCost) { $tot['cogs'] += $z['cogs']; $tot['rev_costed'] += $z['revenue']; $tot['stock_value'] += $unsold * $z['avg_cost']; }
    else          { $tot['no_cost_qty'] += $z['sold_qty']; }
}
unset($z);
$tot['profit'] = $tot['rev_costed'] - $tot['cogs'];

/* ---------- รายผู้ขาย ---------- */
$suppliers = $pdo->query("
    SELECT s.id, s.name, s.channel,
           COUNT(l.id)                          AS lots,
           COUNT(DISTINCT l.event_id)           AS events,
           COALESCE(SUM(l.quantity),0)          AS qty,
           COALESCE(SUM(l.quantity*l.unit_cost),0)  AS cost,
           COALESCE(SUM(l.quantity*l.face_price),0) AS face_total
    FROM suppliers s
    LEFT JOIN ticket_lots l ON l.supplier_id = s.id
    GROUP BY s.id
    ORDER BY cost DESC
")->fetchAll();

function pcls($v) { return $v === null ? 'mut' : ($v >= 0 ? 'pos' : 'neg'); }
function margin($profit, $rev) { return ($profit === null || $rev <= 0) ? '–' : number_format($profit / $rev * 100, 1) . '%'; }

$filter = (int)($_GET['event'] ?? 0);

admin_header('สรุปยอดคนกลาง', 'index.php');
?>

<div class="kpis">
  <div class="kpi"><div class="l">ทุนซื้อบัตรเข้าทั้งหมด</div><div class="v"><?= baht($tot['bought_cost']) ?></div><div class="s"><?= number_format($tot['bought_qty']) ?> ใบ</div></div>
  <div class="kpi"><div class="l">ยอดขาย (จ่ายแล้ว)</div><div class="v"><?= baht($tot['revenue']) ?></div><div class="s"><?= number_format($tot['sold_qty']) ?> ใบ</div></div>
  <div class="kpi"><div class="l">ทุนของบัตรที่ขายไป</div><div class="v"><?= baht($tot['cogs']) ?></div><div class="s">คิดจากทุนเฉลี่ยต่อโซน</div></div>
  <div class="kpi"><div class="l">กำไร</div><div class="v <?= pcls($tot['profit']) ?>"><?= baht($tot['profit']) ?></div><div class="s">มาร์จิ้น <?= margin($tot['profit'], $tot['rev_costed']) ?></div></div>
  <div class="kpi"><div class="l">บัตรค้างสต็อก (มูลค่าทุน)</div><div class="v"><?= baht($tot['stock_value']) ?></div><div class="s">ซื้อมาแล้วยังขายไม่ออก</div></div>
</div>

<?php if ($tot['no_cost_qty'] > 0): ?>
<div class="flash" style="background:rgba(255,181,71,.1);border-color:rgba(255,181,71,.4);color:var(--amber)">
  มีบัตรที่ขายไปแล้ว <?= number_format($tot['no_cost_qty']) ?> ใบ ในโซนที่ยังไม่ได้บันทึกทุน จึงยังไม่ได้นับรวมในกำไร
  ไปที่ <a href="purchases.php">ซื้อบัตรเข้า</a> เพื่อบันทึกทุนของโซนนั้น
</div>
<?php endif; ?>

<!-- ============ รายคอนเสิร์ต ============ -->
<div class="card">
  <h2>🎤 กำไรรายคอนเสิร์ต</h2>
  <div class="scroll"><table>
    <thead><tr>
      <th>คอนเสิร์ต</th><th class="n">ซื้อเข้า</th><th class="n">ทุนรวม</th>
      <th class="n">ขายได้</th><th class="n">ยอดขาย</th><th class="n">ทุนที่ขายไป</th>
      <th class="n">กำไร</th><th class="n">มาร์จิ้น</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($events as $eid => $ev): $p = $ev['rev_costed'] - $ev['cogs']; ?>
      <tr>
        <td><?= e(preg_replace('/\s+/', ' ', $ev['title'])) ?><br><span class="mut" style="font-size:12px"><?= date('d/m/Y', strtotime($ev['date'])) ?></span></td>
        <td class="n"><?= number_format($ev['bought_qty']) ?> ใบ</td>
        <td class="n"><?= baht($ev['bought_cost']) ?></td>
        <td class="n"><?= number_format($ev['sold_qty']) ?> ใบ</td>
        <td class="n"><?= baht($ev['revenue']) ?></td>
        <td class="n"><?= baht($ev['cogs']) ?></td>
        <td class="n <?= pcls($p) ?>"><?= baht($p) ?>
          <?php if ($ev['no_cost_qty']): ?><br><span class="tag warn"><?= $ev['no_cost_qty'] ?> ใบยังไม่มีทุน</span><?php endif; ?></td>
        <td class="n"><?= margin($p, $ev['rev_costed']) ?></td>
        <td><a class="tag" href="?event=<?= $eid ?>#zones">ดูโซน</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
      <td>รวม</td>
      <td class="n"><?= number_format($tot['bought_qty']) ?> ใบ</td><td class="n"><?= baht($tot['bought_cost']) ?></td>
      <td class="n"><?= number_format($tot['sold_qty']) ?> ใบ</td><td class="n"><?= baht($tot['revenue']) ?></td>
      <td class="n"><?= baht($tot['cogs']) ?></td>
      <td class="n <?= pcls($tot['profit']) ?>"><?= baht($tot['profit']) ?></td>
      <td class="n"><?= margin($tot['profit'], $tot['rev_costed']) ?></td><td></td>
    </tr></tfoot>
  </table></div>
</div>

<!-- ============ รายโซน ============ -->
<div class="card" id="zones">
  <div class="event-head">
    <h2>🏷️ ราคากลาง · บวกเพิ่ม · กำไรต่อใบ (รายโซน)</h2>
    <form method="get">
      <select name="event" onchange="this.form.submit()">
        <option value="0">ทุกคอนเสิร์ต</option>
        <?php foreach ($events as $eid => $ev): ?>
          <option value="<?= $eid ?>" <?= $filter === $eid ? 'selected' : '' ?>><?= e(preg_replace('/\s+/', ' ', $ev['title'])) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
  <div class="scroll"><table>
    <thead><tr>
      <th>คอนเสิร์ต / โซน</th><th class="n">ราคากลาง</th><th class="n">บวกเพิ่ม</th><th class="n">ราคาขาย</th>
      <th class="n">ทุนเฉลี่ย/ใบ</th><th class="n">กำไร/ใบ</th>
      <th class="n">ซื้อเข้า</th><th class="n">ขายได้</th><th class="n">เหลือบนเว็บ</th><th class="n">กำไรรวม</th>
    </tr></thead>
    <tbody>
    <?php foreach ($zones as $z): if ($filter && $z['event_id'] != $filter) continue; ?>
      <tr>
        <td><span class="dot" style="background:<?= e($z['color']) ?>"></span><?= e(type_label($z)) ?>
            <br><span class="mut" style="font-size:12px"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $z['title']), 0, 40, '…')) ?></span></td>
        <td class="n"><?= baht($z['face_price']) ?></td>
        <td class="n"><?= $z['markup'] > 0 ? '+' . baht($z['markup']) : '<span class="mut">0</span>' ?></td>
        <td class="n"><?= baht($z['price']) ?></td>
        <td class="n"><?= $z['avg_cost'] === null ? '<span class="tag warn">ยังไม่มีทุน</span>' : baht($z['avg_cost']) ?></td>
        <td class="n <?= pcls($z['unit_profit']) ?>"><?= baht($z['unit_profit']) ?></td>
        <td class="n"><?= number_format($z['bought_qty']) ?></td>
        <td class="n"><?= number_format($z['sold_qty']) ?></td>
        <td class="n"><?= number_format($z['available_seats']) ?></td>
        <td class="n <?= pcls($z['profit']) ?>"><?= baht($z['profit']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<!-- ============ รายผู้ขาย ============ -->
<div class="card">
  <h2>👤 สรุปยอดซื้อจากผู้ขายแต่ละราย</h2>
  <div class="scroll"><table>
    <thead><tr>
      <th>ผู้ขาย</th><th>ช่องทาง</th><th class="n">ครั้งที่ซื้อ</th><th class="n">คอนเสิร์ต</th>
      <th class="n">จำนวนบัตร</th><th class="n">จ่ายไปรวม</th><th class="n">ทุนเฉลี่ย/ใบ</th><th class="n">จ่ายเกินราคากลาง</th>
    </tr></thead>
    <tbody>
    <?php if (!$suppliers): ?>
      <tr><td colspan="8" class="mut">ยังไม่มีผู้ขาย — <a href="suppliers.php">เพิ่มผู้ขาย</a></td></tr>
    <?php endif; ?>
    <?php foreach ($suppliers as $s): $over = $s['cost'] - $s['face_total']; ?>
      <tr>
        <td><?= e($s['name']) ?></td>
        <td class="mut"><?= e($s['channel']) ?></td>
        <td class="n"><?= $s['lots'] ?></td>
        <td class="n"><?= $s['events'] ?></td>
        <td class="n"><?= number_format($s['qty']) ?> ใบ</td>
        <td class="n"><?= baht($s['cost']) ?></td>
        <td class="n"><?= $s['qty'] ? baht($s['cost'] / $s['qty']) : '–' ?></td>
        <td class="n <?= $over > 0 ? 'neg' : 'mut' ?>"><?= $s['qty'] ? ($over > 0 ? '+' : '') . baht($over) : '–' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php admin_footer();
