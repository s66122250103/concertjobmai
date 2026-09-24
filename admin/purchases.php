<?php
require __DIR__ . '/_bootstrap.php';

/* ---------- บันทึก / ลบ ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $typeId = (int)$_POST['ticket_type_id'];
        $qty    = (int)$_POST['quantity'];
        $face   = (float)$_POST['face_price'];
        $cost   = (float)$_POST['unit_cost'];
        $sup    = (int)$_POST['supplier_id'];
        $date   = $_POST['purchased_at'] ?: date('Y-m-d');
        $stock  = isset($_POST['add_stock']) ? 1 : 0;

        $t = $pdo->prepare('SELECT event_id FROM ticket_types WHERE id = ?');
        $t->execute([$typeId]);
        $eventId = $t->fetchColumn();

        if (!$eventId || $qty <= 0 || $cost < 0 || !$sup) {
            flash('ข้อมูลไม่ครบ กรุณาเลือกโซน ผู้ขาย และใส่จำนวน/ทุนให้ถูกต้อง');
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO ticket_lots
                (event_id, ticket_type_id, supplier_id, quantity, face_price, unit_cost, added_to_stock, purchased_at, note)
                VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$eventId, $typeId, $sup, $qty, $face, $cost, $stock, $date, trim($_POST['note'] ?? '') ?: null]);
            if ($stock) {
                $pdo->prepare('UPDATE ticket_types SET total_seats = total_seats + ?, available_seats = available_seats + ? WHERE id = ?')
                    ->execute([$qty, $qty, $typeId]);
            }
            $pdo->commit();
            flash("บันทึกการซื้อเข้า $qty ใบเรียบร้อย" . ($stock ? ' และเพิ่มจำนวนบัตรบนเว็บแล้ว' : ''));
        }
    }

    if ($action === 'delete') {
        $id  = (int)$_POST['id'];
        $lot = $pdo->prepare('SELECT * FROM ticket_lots WHERE id = ?');
        $lot->execute([$id]);
        if ($l = $lot->fetch()) {
            $pdo->beginTransaction();
            if ($l['added_to_stock']) {
                $pdo->prepare('UPDATE ticket_types
                               SET total_seats = GREATEST(total_seats - ?, 0),
                                   available_seats = GREATEST(available_seats - ?, 0)
                               WHERE id = ?')
                    ->execute([$l['quantity'], $l['quantity'], $l['ticket_type_id']]);
            }
            $pdo->prepare('DELETE FROM ticket_lots WHERE id = ?')->execute([$id]);
            $pdo->commit();
            flash('ลบรายการซื้อเข้าแล้ว');
        }
    }
    header('Location: purchases.php'); exit;
}

/* ---------- ข้อมูลสำหรับฟอร์ม ---------- */
$events    = $pdo->query('SELECT id, title, event_date FROM events ORDER BY event_date')->fetchAll();
$types     = $pdo->query('SELECT id, event_id, type_name, price, face_price, markup FROM ticket_types ORDER BY price DESC')->fetchAll();
$suppliers = $pdo->query('SELECT id, name, channel FROM suppliers ORDER BY name')->fetchAll();
foreach ($types as &$t) $t['label'] = type_label($t) . ' · ฿' . number_format($t['price']);
unset($t);

$lots = $pdo->query("
    SELECT l.*, e.title, tt.type_name, tt.id AS tid, tt.color, tt.price AS sell_price, s.name AS supplier
    FROM ticket_lots l
    JOIN events e        ON e.id = l.event_id
    JOIN ticket_types tt ON tt.id = l.ticket_type_id
    JOIN suppliers s     ON s.id = l.supplier_id
    ORDER BY l.purchased_at DESC, l.id DESC
")->fetchAll();

admin_header('บันทึกการซื้อบัตรเข้า', 'purchases.php');
?>

<div class="card">
  <h2>➕ ซื้อบัตรเข้ามาใหม่</h2>
  <?php if (!$suppliers): ?>
    <p class="mut">ยังไม่มีผู้ขายในระบบ — <a href="suppliers.php">เพิ่มผู้ขายก่อน</a></p>
  <?php else: ?>
  <form method="post" class="grid" id="lotForm">
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <label>คอนเสิร์ต
      <select id="ev" required>
        <option value="">— เลือก —</option>
        <?php foreach ($events as $ev): ?>
          <option value="<?= $ev['id'] ?>"><?= e(preg_replace('/\s+/', ' ', $ev['title'])) ?> (<?= date('d/m/Y', strtotime($ev['event_date'])) ?>)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>โซน / ประเภทบัตร
      <select name="ticket_type_id" id="tt" required><option value="">— เลือกคอนเสิร์ตก่อน —</option></select>
    </label>
    <label>ซื้อมาจาก (ผู้ขาย)
      <select name="supplier_id" required>
        <option value="">— เลือก —</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>"><?= e($s['name']) ?><?= $s['channel'] ? ' · ' . e($s['channel']) : '' ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>วันที่ซื้อ <input type="date" name="purchased_at" value="<?= date('Y-m-d') ?>"></label>
    <label>จำนวน (ใบ) <input type="number" name="quantity" id="qty" min="1" value="1" required></label>
    <label>ราคากลาง / หน้าบัตร (ต่อใบ) <input type="number" name="face_price" id="face" min="0" step="0.01" required></label>
    <label>ทุนที่จ่ายจริง (ต่อใบ) <input type="number" name="unit_cost" id="cost" min="0" step="0.01" required></label>
    <label>หมายเหตุ <input type="text" name="note" maxlength="255" placeholder="เช่น แถว C, กดบัตรรอบแรก"></label>
    <div class="preview" id="preview">เลือกโซนและกรอกราคาเพื่อดูสรุป</div>
    <div class="actions">
      <label class="check"><input type="checkbox" name="add_stock" checked> เพิ่มจำนวน "เหลือ" บนหน้าเว็บตามจำนวนที่ซื้อ</label>
      <span style="flex:1"></span>
      <button class="btn">บันทึก</button>
    </div>
  </form>
  <?php endif; ?>
</div>

<div class="card">
  <h2>🧾 ประวัติการซื้อเข้า</h2>
  <div class="scroll"><table>
    <thead><tr>
      <th>วันที่</th><th>คอนเสิร์ต / โซน</th><th>ซื้อจาก</th><th class="n">จำนวน</th>
      <th class="n">ราคากลาง</th><th class="n">ทุน/ใบ</th><th class="n">ทุนรวม</th><th class="n">ขายอยู่/ใบ</th><th>หมายเหตุ</th><th></th>
    </tr></thead>
    <tbody>
    <?php if (!$lots): ?><tr><td colspan="10" class="mut">ยังไม่มีรายการ</td></tr><?php endif; ?>
    <?php foreach ($lots as $l): ?>
      <tr>
        <td><?= date('d/m/Y', strtotime($l['purchased_at'])) ?></td>
        <td><span class="dot" style="background:<?= e($l['color']) ?>"></span><?= e(type_label(['id'=>$l['tid'],'type_name'=>$l['type_name']])) ?>
            <br><span class="mut" style="font-size:12px"><?= e(mb_strimwidth(preg_replace('/\s+/', ' ', $l['title']), 0, 40, '…')) ?></span></td>
        <td><?= e($l['supplier']) ?></td>
        <td class="n"><?= number_format($l['quantity']) ?></td>
        <td class="n"><?= baht($l['face_price']) ?></td>
        <td class="n"><?= baht($l['unit_cost']) ?></td>
        <td class="n"><?= baht($l['quantity'] * $l['unit_cost']) ?></td>
        <td class="n"><?= baht($l['sell_price']) ?></td>
        <td class="mut"><?= e($l['note']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('ลบรายการนี้?<?= $l['added_to_stock'] ? '\nจำนวนบัตรบนเว็บจะถูกลดลงตามด้วย' : '' ?>')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $l['id'] ?>">
            <button class="btn ghost">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<script>
const TYPES = <?= json_encode($types, JSON_UNESCAPED_UNICODE) ?>;
const ev = document.getElementById('ev'), tt = document.getElementById('tt');
const qty = document.getElementById('qty'), face = document.getElementById('face'), cost = document.getElementById('cost');
const pv = document.getElementById('preview');
const fmt = n => (n < 0 ? '-' : '') + '฿' + Math.abs(Math.round(n)).toLocaleString('th-TH');

ev && ev.addEventListener('change', () => {
  tt.innerHTML = '<option value="">— เลือก —</option>';
  TYPES.filter(t => t.event_id == ev.value).forEach(t => tt.add(new Option(t.label, t.id)));
  update();
});
tt && tt.addEventListener('change', () => {
  const t = TYPES.find(t => t.id == tt.value);
  if (t) { face.value = t.face_price ?? t.price; if (!cost.value) cost.value = face.value; }
  update();
});
[qty, face, cost].forEach(el => el && el.addEventListener('input', update));

function update() {
  const t = TYPES.find(t => t.id == tt.value);
  const q = +qty.value || 0, f = +face.value || 0, c = +cost.value || 0;
  if (!t || !c) { pv.textContent = 'เลือกโซนและกรอกราคาเพื่อดูสรุป'; return; }
  const over = c - f, sell = +t.price, prof = sell - c;
  pv.innerHTML =
    `ทุนรวม <b>${fmt(q * c)}</b> · ` +
    `ทุนเทียบราคากลาง <b style="color:${over > 0 ? 'var(--red)' : 'var(--green)'}">${over >= 0 ? '+' : ''}${fmt(over)}</b>/ใบ · ` +
    `ราคาขายบนเว็บตอนนี้ ${fmt(sell)} → กำไร <b style="color:${prof >= 0 ? 'var(--green)' : 'var(--red)'}">${fmt(prof)}</b>/ใบ ` +
    `(รวม ${fmt(prof * q)} ถ้าขายหมด)`;
}
</script>

<?php admin_footer();
