<?php
/**
 * PromptPay QR แบบใส่ยอดเงินอัตโนมัติ (Thai QR Payment / EMVCo)
 *
 * ค่า PROMPTPAY_ID ด้านล่างถอดมาจาก QR K+ ของคุณกษิดิศ
 * (เป็น "e-Wallet ID" 15 หลักของบัญชีกสิกร)
 *
 * วิธีใช้ในหน้าชำระเงิน:
 *   require_once __DIR__ . '/../includes/promptpay.php';
 *   echo promptpay_qr_html($total);   // $total = ยอดที่ต้องโอน เช่น 850
 */

const PROMPTPAY_ID      = '004999097210578';   // e-Wallet ID จาก QR ของคุณ
const PROMPTPAY_ID_TYPE = 'ewallet';           // 'phone' | 'national_id' | 'ewallet'

// ที่อยู่ไฟล์ qrcode.min.js (วางไว้ในโฟลเดอร์ includes/ ของเว็บ)
define('QRCODE_JS_URL', (defined('BASE_URL') ? BASE_URL : '..') . '/includes/qrcode.min.js');

/** สร้างข้อความ payload ที่อยู่ใน QR */
function promptpay_payload(float $amount = 0, string $id = PROMPTPAY_ID, string $type = PROMPTPAY_ID_TYPE): string
{
    $f = fn(string $tag, string $val) => $tag . sprintf('%02d', strlen($val)) . $val;

    $id = preg_replace('/\D/', '', $id);
    switch ($type) {
        case 'phone':        // 0812345678 -> 0066812345678
            $acc = $f('01', '0066' . substr($id, 1));
            break;
        case 'national_id':  // เลขบัตรประชาชน 13 หลัก
            $acc = $f('02', $id);
            break;
        default:             // e-Wallet ID 15 หลัก
            $acc = $f('03', $id);
    }

    $data  = $f('00', '01');
    $data .= $f('01', $amount > 0 ? '12' : '11');           // 12 = QR ใช้ครั้งเดียวมียอด, 11 = ไม่มียอด
    $data .= $f('29', $f('00', 'A000000677010111') . $acc);  // PromptPay
    $data .= $f('53', '764');                                // THB
    if ($amount > 0) {
        $data .= $f('54', number_format($amount, 2, '.', ''));
    }
    $data .= $f('58', 'TH');
    $data .= '6304';
    return $data . promptpay_crc16($data);
}

/** CRC16-CCITT (0x1021, เริ่ม 0xFFFF) ตามมาตรฐาน EMVCo */
function promptpay_crc16(string $s): string
{
    $crc = 0xFFFF;
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        $crc ^= ord($s[$i]) << 8;
        for ($b = 0; $b < 8; $b++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(sprintf('%04X', $crc));
}

/**
 * คืน HTML ของ QR พร้อมวาดด้วย JavaScript (qrcodejs อยู่ใน includes/qrcode.min.js ไม่ต้องใช้เน็ต)
 * เรียกได้หลายครั้งในหน้าเดียว
 */
function promptpay_qr_html(float $amount, int $size = 220): string
{
    static $scriptLoaded = false;
    $payload = htmlspecialchars(promptpay_payload($amount), ENT_QUOTES);
    $id      = 'ppqr_' . bin2hex(random_bytes(4));

    $html = '';
    if (!$scriptLoaded) {
        $html .= '<script src="' . QRCODE_JS_URL . '"></script>';
        $scriptLoaded = true;
    }
    $html .= "<div id=\"$id\" style=\"display:inline-block;background:#fff;padding:10px;border-radius:8px\"></div>";
    $html .= "<script>new QRCode(document.getElementById('$id'),"
           . "{text:'$payload',width:$size,height:$size,correctLevel:QRCode.CorrectLevel.M});</script>";
    return $html;
}
