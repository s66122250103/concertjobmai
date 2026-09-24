<?php
require __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $data = [trim($_POST['name'] ?? ''), trim($_POST['channel'] ?? '') ?: null,
             trim($_POST['contact'] ?? '') ?: null, trim($_POST['note'] ?? '') ?: null];

    if ($action === 'save' && $data[0] !== '') {
        if ($id = (int)($_POST['id'] ?? 0)) {
            $pdo->prepare('UPDATE suppliers SET name=?, channel=?, contact=?, note=? WHERE id=?')->execute([...$data, $id]);
            flash('แก้ไขผู้ขายแล้ว');
        } else {
            $pdo->prepare('INSERT INTO suppliers (name, channel, contact, note) VALUES (?,?,?,?)')->execute($data);
            flash('เพิ่มผู้ขายแล้ว');
        }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $used = $pdo->prepare('SELECT COUNT(*) FROM ticket_lots WHERE supplier_id = ?');
        $used->execute([$id]);
        if ($used->fetchColumn() > 0) {
            flash('ลบไม่ได้ เพราะมีประวัติการซื้อบัตรจากผู้ขายรายนี้อยู่');
        } else {
            $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
            flash('ลบผู้ขายแล้ว');
        }
    }
    header('Location: suppliers.php'); exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM suppliers WHERE id = ?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$rows = $pdo->query("
    SELECT s.*, COUNT(l.id) AS lots, COALESCE(SUM(l.quantity),0) AS qty,
           COALESCE(SUM(l.quantity*l.unit_cost),0) AS cost, MAX(l.purchased_at) AS last_buy
    FROM suppliers s LEFT JOIN ticket_lots l ON l.supplier_id = s.id
    GROUP BY s.id ORDER BY s.name
")->fetchAll();

admin_header('ผู้ขาย / แหล่งที่ซื้อบัตร', 'suppliers.php');
?>

<div class="card">
  <h2><?= $edit ? '✏️ แก้ไขผู้ขาย' : '➕ เพิ่มผู้ขาย' ?></h2>
  <form method="post" class="grid">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <label>ชื่อ (คน / ร้าน / เว็บ) <input name="name" required maxlength="150" value="<?= e($edit['name'] ?? '') ?>"></label>
    <label>ช่องทาง
      <input name="channel" list="channels" maxlength="100" value="<?= e($edit['channel'] ?? '') ?>" placeholder="เช่น ThaiTicketMajor">
      <datalist id="channels">
        <option>ThaiTicketMajor</option><option>TheConcert</option><option>Ticketmelon</option>
        <option>Facebook กลุ่มซื้อขายบัตร</option><option>X (Twitter)</option><option>LINE</option><option>หน้างาน</option>
      </datalist>
    </label>
    <label>ติดต่อ <input name="contact" maxlength="150" value="<?= e($edit['contact'] ?? '') ?>" placeholder="เบอร์ / LINE ID / ลิงก์"></label>
    <label>หมายเหตุ <input name="note" value="<?= e($edit['note'] ?? '') ?>"></label>
    <div class="actions">
      <button class="btn"><?= $edit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ขาย' ?></button>
      <?php if ($edit): ?><a class="tag" href="suppliers.php">ยกเลิก</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h2>👤 รายชื่อผู้ขาย</h2>
  <div class="scroll"><table>
    <thead><tr><th>ชื่อ</th><th>ช่องทาง</th><th>ติดต่อ</th><th class="n">ครั้งที่ซื้อ</th><th class="n">บัตร</th><th class="n">จ่ายไปรวม</th><th>ซื้อล่าสุด</th><th></th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="8" class="mut">ยังไม่มีผู้ขาย</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?><?php if ($r['note']): ?><br><span class="mut" style="font-size:12px"><?= e($r['note']) ?></span><?php endif; ?></td>
        <td class="mut"><?= e($r['channel']) ?></td>
        <td class="mut"><?= e($r['contact']) ?></td>
        <td class="n"><?= $r['lots'] ?></td>
        <td class="n"><?= number_format($r['qty']) ?></td>
        <td class="n"><?= baht($r['cost']) ?></td>
        <td class="mut"><?= $r['last_buy'] ? date('d/m/Y', strtotime($r['last_buy'])) : '–' ?></td>
        <td style="white-space:nowrap">
          <a class="btn ghost" href="?edit=<?= $r['id'] ?>" style="text-decoration:none">แก้ไข</a>
          <?php if (!$r['lots']): ?>
          <form method="post" style="display:inline" onsubmit="return confirm('ลบผู้ขายนี้?')">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="btn ghost">ลบ</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<?php admin_footer();
