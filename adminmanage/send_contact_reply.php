<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['is_admin']) || (int) $_SESSION['is_admin'] !== 1) {
    echo json_encode(['success' => false, 'error' => 'Нет доступа'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '', true);
if (!is_array($input)) {
    echo json_encode(['success' => false, 'error' => 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}

$messageId = isset($input['message_id']) ? (int) $input['message_id'] : 0;
$replyBody = isset($input['reply']) ? trim((string) $input['reply']) : '';

if ($messageId < 1) {
    echo json_encode(['success' => false, 'error' => 'Не указано сообщение'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($replyBody === '') {
    echo json_encode(['success' => false, 'error' => 'Введите текст ответа'], JSON_UNESCAPED_UNICODE);
    exit;
}
$replyLen = function_exists('mb_strlen') ? mb_strlen($replyBody) : strlen($replyBody);
if ($replyLen > 10000) {
    echo json_encode(['success' => false, 'error' => 'Слишком длинный текст ответа'], JSON_UNESCAPED_UNICODE);
    exit;
}

$settingsFile = __DIR__ . '/../includes/mail_settings.php';
if (!is_readable($settingsFile)) {
    echo json_encode([
        'success' => false,
        'error' => 'Создайте файл includes/mail_settings.php (см. includes/mail_settings.example.php)',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @var array<string, mixed> $cfg */
$cfg = require $settingsFile;
$required = ['smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'from_email'];
foreach ($required as $key) {
    if (empty($cfg[$key])) {
        echo json_encode([
            'success' => false,
            'error' => 'Заполните настройки почты в includes/mail_settings.php',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$stmt = mysqli_prepare(
    $link,
    'SELECT id, first_name, last_name, email, phone, message, created_at FROM contact_messages WHERE id = ? LIMIT 1',
);
mysqli_stmt_bind_param($stmt, 'i', $messageId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Сообщение не найдено'], JSON_UNESCAPED_UNICODE);
    exit;
}

$toEmail = trim((string) ($row['email'] ?? ''));
if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'У обращения нет корректного email'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

try {
    $mail->CharSet = PHPMailer::CHARSET_UTF8;
    $mail->isSMTP();
    $mail->Host = (string) $cfg['smtp_host'];
    $mail->Port = (int) $cfg['smtp_port'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $cfg['smtp_user'];
    $mail->Password = (string) $cfg['smtp_pass'];

    $secure = strtolower(trim((string) ($cfg['smtp_secure'] ?? 'tls')));
    if ($secure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($secure === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = '';
    }

    $fromEmail = (string) $cfg['from_email'];
    $fromName = (string) ($cfg['from_name'] ?? 'Администрация');
    $mail->setFrom($fromEmail, $fromName);

    $clientName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
    $mail->addAddress($toEmail, $clientName !== '' ? $clientName : $toEmail);

    $subject = (string) ($cfg['reply_subject'] ?? 'Ответ на ваше обращение');
    $mail->Subject = $subject;

    $created = $row['created_at'] ?? '';
    $original = (string) ($row['message'] ?? '');
    $originalEsc = htmlspecialchars($original, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $replyEsc = htmlspecialchars($replyBody, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $html = '<p>Здравствуйте' . ($clientName !== '' ? ', ' . htmlspecialchars($clientName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '') . '!</p>';
    $html .= '<p>Ответ на ваше обращение' . ($created !== '' ? ' от <strong>' . htmlspecialchars((string) $created, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong>' : '') . ':</p>';
    $html .= '<blockquote style="margin:12px 0;padding:12px;border-left:4px solid #8773ff;background:#f7f6ff;">' . nl2br($replyEsc) . '</blockquote>';
    $html .= '<p style="color:#666;font-size:13px;">Ваш вопрос:</p>';
    $html .= '<blockquote style="margin:12px 0;padding:12px;border-left:4px solid #ccc;background:#fafafa;">' . nl2br($originalEsc) . '</blockquote>';
    $html .= '<p style="color:#888;font-size:12px;">Это письмо отправлено автоматически, отвечать на него не нужно.</p>';

    $mail->isHTML(true);
    $mail->Body = $html;
    $plain = "Здравствуйте!\n\nОтвет:\n\n{$replyBody}\n\n--- Ваш вопрос ---\n{$original}\n";
    $mail->AltBody = $plain;

    $mail->send();
    echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} catch (MailerException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка отправки: ' . $mail->ErrorInfo,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
