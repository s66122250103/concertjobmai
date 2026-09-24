<?php
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $eventId = (int)$_POST['event_id'];
    // ก่อนเปลี่ยนราคา: จำราคากลางเดิมไว้ในบัตรที่ขายไปแล้ว
    ensure_item_face_price($pdo, $eventId);
    $up = $pdo->prepare('UPDATE ticket_types
                         SET type_name = ?, face_price = ?, markup = ?, price = ?
                         WHERE id = ? AND event_id = ?');
    $pdo->beginTransaction();
    foreach ($_POST['types'] ?? [] as $id => $t) {
        $face   = max(0, (float)$t['face_price']);
        $markup = (float)$t['markup'];
        $up->execute([trim($t['type_name']), $face, $markup, $face + $markup, (int)$id, $eventId]);
    }
    $pdo->commit();
    flash('อัปเดตราคาแล้ว ราคาบนหน้าเว็บเปลี่ยนตามทันที');
    header('Location: pricing.php#ev' . $eventId); exit;
}

$events = $pdo->query('SELECT id, title, event_date FROM events ORDER BY event_date')->fetchAll();
$types  = $pdo->query('
    SELECT tt.*, c.avg_cost FROM ticket_types tt
    JOIN v_type_cost c ON c.ticket_type_id = tt.id
    ORDER BY tt.price DESC
')->fetchAll();
$byEvent = [];
foreach ($types as $t) $byEvent[$t['event_id']][] = $t;

admin_header('ราคากลาง / บวกเพิ่ม', 'pricing.php');
?>
<p class="mut" style="margin-top:-8px">ราคาขายบนเว็บ = <b>ราคากลาง</b> + <b>บวกเพิ่ม</b> · กดบันทึกแล้วหน้าแรกจะใช้ราคาใหม่ทันที (ออเดอร์เก่าไม่เปลี่ยน)</p>

<?php foreach ($events as $ev): if (empty($byEvent[$ev['id']])) continue; ?>
<div class="card" id="ev<?= $ev['id'] ?>">
  <form method="post" class="price-form">
    <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
    <div class="event-head">
      <h2><?= e(preg_replace('/\s+/', ' ', $ev['title'])) ?> <span class="mut" style="font-size:14px"><?= date('d/m/Y', strtotime($ev['event_date'])) ?></span></h2>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span class="mut" style="font-size:13px">บวกทุกโซน</span>
        <input type="number" class="sm bulk-baht" placeholder="บาท">
        <input type="number" class="sm bulk-pct" placeholder="%" style="width:80px">
        <button type="button" class="btn ghost bulk-apply">ใช้</button>
      </div>
    </div>
    <div class="scroll"><table>
      <thead><tr>
        <th>ชื่อโซน</th><th class="n">ราคากลาง</th><th class="n">บวกเพิ่ม</th><th class="n">ราคาขาย</th>
        <th class="n">ทุนเฉลี่ย</th><th class="n">กำไร/ใบ</th><th class="n">เหลือ</th>
      </tr></thead>
      <tbody>
      <?php foreach ($byEvent[$ev['id']] as $t): $n = "types[{$t['id']}]"; ?>
        <tr data-cost="<?= $t['avg_cost'] ?? '' ?>">
          <td><span class="dot" style="background:<?= e($t['color']) ?>"></span>
              <input name="<?= $n ?>[type_name]" value="<?= e($t['type_name']) ?>" placeholder="⚠ ยังไม่มีชื่อ" style="width:160px<?= $t['type_name'] === '' ? ';border-color:var(--amber)' : '' ?>"></td>
          <td class="n"><input class="sm face" type="number" step="0.01" min="0" name="<?= $n ?>[face_price]" value="<?= (float)($t['face_price'] ?? $t['price']) ?>"></td>
          <td class="n"><input class="sm mk" type="number" step="0.01" name="<?= $n ?>[markup]" value="<?= (float)$t['markup'] ?>"></td>
          <td class="n sell"></td>
          <td class="n"><?= $t['avg_cost'] === null ? '<span class="tag warn">ยังไม่มีทุน</span>' : baht($t['avg_cost']) ?></td>
          <td class="n prof"></td>
          <td class="n mut"><?= number_format($t['available_seats']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div style="text-align:right;margin-top:12px"><button class="btn">บันทึกราคา</button></div>
  </form>
</div>
<?php endforeach; ?>

<script>
const fmt = n => (n < 0 ? '-' : '') + '฿' + Math.abs(Math.round(n)).toLocaleString('th-TH');
function recalc(tr) {
  const sell = (+tr.querySelector('.face').value || 0) + (+tr.querySelector('.mk').value || 0);
  tr.querySelector('.sell').textContent = fmt(sell);
  const c = tr.dataset.cost, p = tr.querySelector('.prof');
  if (c === '') { p.textContent = '–'; p.className = 'n prof mut'; return; }
  const v = sell - c;
  p.textContent = fmt(v); p.className = 'n prof ' + (v >= 0 ? 'pos' : 'neg');
}
document.querySelectorAll('.price-form tbody tr').forEach(tr => {
  recalc(tr);
  tr.addEventListener('input', () => recalc(tr));
});
document.querySelectorAll('.bulk-apply').forEach(btn => btn.addEventListener('click', () => {
  const f = btn.closest('form');
  const b = f.querySelector('.bulk-baht').value, pct = f.querySelector('.bulk-pct').value;
  f.querySelectorAll('tbody tr').forEach(tr => {
    const face = +tr.querySelector('.face').value || 0;
    tr.querySelector('.mk').value = b !== '' ? +b : (pct !== '' ? Math.round(face * pct / 100) : tr.querySelector('.mk').value);
    recalc(tr);
  });
}));
</script>

<?php admin_footer();
