<?php
// includes/mailer.php
// ส่งอีเมลผ่าน Brevo API (HTTPS) ใช้ได้ทั้งบน XAMPP และ Render
//
// ค่าที่ต้องตั้ง:
// - บน Render: Environment Variables ชื่อ BREVO_API_KEY, MAIL_FROM, MAIL_FROM_NAME
// - ในเครื่อง: ไฟล์ config/mail_secret.php (ห้ามขึ้น GitHub)

function mailConfig(): array {
    $local = [];
    $file = __DIR__ . '/../config/mail_secret.php';
    if (file_exists($file)) {
        $loaded = require $file;
        if (is_array($loaded)) $local = $loaded;
    }
    return [
        'api_key'   => getenv('BREVO_API_KEY')  ?: ($local['BREVO_API_KEY']  ?? ''),
        'from'      => getenv('MAIL_FROM')      ?: ($local['MAIL_FROM']      ?? ''),
        'from_name' => getenv('MAIL_FROM_NAME') ?: ($local['MAIL_FROM_NAME'] ?? 'ConcertBook'),
    ];
}

/**
 * ส่งอีเมล คืนค่า true ถ้าสำเร็จ
 * ถ้าไม่สำเร็จ ข้อความ error จะถูกเขียนลง error log ของ PHP
 */
function sendMail(string $toEmail, string $toName, string $subject, string $html): bool {
    $cfg = mailConfig();
    if ($cfg['api_key'] === '' || $cfg['from'] === '') {
        error_log('sendMail: ยังไม่ได้ตั้งค่า BREVO_API_KEY หรือ MAIL_FROM');
        return false;
    }

    $payload = [
        'sender'      => ['email' => $cfg['from'], 'name' => $cfg['from_name']],
        'to'          => [['email' => $toEmail, 'name' => $toName !== '' ? $toName : $toEmail]],
        'subject'     => $subject,
        'htmlContent' => $html,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $cfg['api_key'],
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code < 200 || $code >= 300) {
        error_log("sendMail: ส่งไม่สำเร็จ (HTTP $code) $err $body");
        return false;
    }
    return true;
}
