<?php
// submit_quote.php - backend handler for the quote form (EN + AR)

header('Content-Type: application/json; charset=utf-8');

$language = normalize_language($_REQUEST['language'] ?? 'en');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_json(false, translate('method_not_allowed', $language), 405);
}

$name = sanitize_text($_POST['name'] ?? '');
if (!is_valid_name($name)) {
    respond_json(false, translate('invalid_name', $language), 422);
}

$phoneInput = trim((string) ($_POST['phone'] ?? ''));
$phone = normalize_phone($phoneInput);
if ($phone === null) {
    respond_json(false, translate('invalid_phone', $language), 422);
}

$area = sanitize_text($_POST['area'] ?? '');
if (string_length($area) < 2) {
    respond_json(false, translate('invalid_area', $language), 422);
}

$service = trim((string) ($_POST['service'] ?? ''));
if (!in_array($service, allowed_services(), true)) {
    respond_json(false, translate('invalid_service', $language), 422);
}

$message = sanitize_multiline($_POST['message'] ?? '');
if (string_length($message) < 10) {
    respond_json(false, translate('invalid_message', $language), 422);
}

$sourcePage = sanitize_source_page($_POST['source_page'] ?? '');

$requestMeta = [
    'name' => $name,
    'phone' => $phone,
    'area' => $area,
    'service' => $service,
    'message' => $message,
    'language' => $language,
    'source_page' => $sourcePage,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
];

$conn = require __DIR__ . '/db.php';

if (!$conn instanceof mysqli) {
    log_submission($requestMeta + ['error' => 'connection_failed'], 'db_unavailable');
    respond_json(false, translate('generic_error', $language), 500);
}

ensure_quote_requests_table($conn, $language, $requestMeta);

$stmt = $conn->prepare(
    "INSERT INTO quote_requests (name, phone, area, service, message)
     VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    log_submission($requestMeta + ['error' => $conn->error], 'prepare_failed');
    respond_json(false, translate('prepare_failed', $language), 500);
}

$stmt->bind_param('sssss', $name, $phone, $area, $service, $message);

$executed = $stmt->execute();

if (!$executed) {
    log_submission($requestMeta + ['error' => $stmt->error], 'save_failed');
}

$stmt->close();
$conn->close();

if ($executed) {
    log_submission($requestMeta, 'stored');
    respond_json(true, translate('success', $language));
}

respond_json(false, translate('save_failed', $language), 500);

function respond_json(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_language(?string $value): string
{
    $value = strtolower(trim((string) $value));
    return in_array($value, ['en', 'ar'], true) ? $value : 'en';
}

function translate(string $key, string $language): string
{
    $messages = [
        'en' => [
            'method_not_allowed' => 'Only POST requests are allowed.',
            'missing_fields' => 'Please fill in all required fields.',
            'invalid_name' => 'Please enter your full name using letters only.',
            'invalid_phone' => 'Enter a valid phone or WhatsApp number (e.g., +966 50 420 2782).',
            'invalid_area' => 'Specify the Riyadh district or neighborhood for your project.',
            'invalid_service' => 'Select the service type that best matches your request.',
            'invalid_message' => 'Share more project details so we can prepare your quote.',
            'prepare_failed' => 'Database error (prepare failed).',
            'save_failed' => 'Could not save your request. Please try again later.',
            'success' => 'Thank you, your request has been received. We will contact you soon.',
            'generic_error' => 'An unexpected error occurred. Please try again later.',
            'schema_failed' => 'Could not prepare the storage table. Please try again later.',
            'setup_failed' => 'Failed to initialize the database. Please try again later.',
        ],
        'ar' => [
            'method_not_allowed' => 'يُسمح فقط بطلبات POST.',
            'missing_fields' => 'يرجى تعبئة جميع الحقول المطلوبة.',
            'invalid_name' => 'يرجى إدخال اسمك الكامل باستخدام أحرف فقط.',
            'invalid_phone' => 'أدخل رقم هاتف أو واتساب صالح (مثال: +966 50 420 2782).',
            'invalid_area' => 'اذكر الحي أو المنطقة في الرياض الخاصة بالمشروع.',
            'invalid_service' => 'اختر نوع الخدمة المناسبة لطلبك.',
            'invalid_message' => 'شارك المزيد من تفاصيل المشروع حتى نتمكن من تجهيز العرض.',
            'prepare_failed' => 'حدث خطأ في قاعدة البيانات (فشل التحضير).',
            'save_failed' => 'تعذر حفظ طلبك، يرجى المحاولة لاحقاً.',
            'success' => 'شكرًا لك، تم استلام طلبك وسنتواصل معك قريبًا.',
            'generic_error' => 'حدث خطأ غير متوقع، حاول مرة أخرى لاحقًا.',
            'schema_failed' => 'تعذر تجهيز جدول التخزين. يرجى المحاولة لاحقًا.',
            'setup_failed' => 'فشل تجهيز قاعدة البيانات. حاول مرة أخرى لاحقًا.',
        ],
    ];

    if (!isset($messages[$language][$key])) {
        return $messages[$language]['generic_error'] ?? $messages['en']['generic_error'];
    }

    return $messages[$language][$key];
}

function sanitize_text(?string $value): string
{
    $value = trim((string) $value);
    return preg_replace('/\s+/u', ' ', $value);
}

function sanitize_multiline(?string $value): string
{
    $value = trim((string) $value);
    $value = preg_replace("/\r\n?/", "\n", $value);
    return preg_replace("/\n{3,}/", "\n\n", $value);
}

function sanitize_source_page(?string $value): string
{
    $clean = preg_replace('/[^a-z0-9_-]/i', '', (string) $value);
    return strtolower($clean ?? '');
}

function is_valid_name(string $value): bool
{
    return $value !== '' && (bool) preg_match("/^[\p{L}\s'.-]{2,}$/u", $value);
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

function normalize_phone(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^\+?\d[\d\s-]{7,14}$/', $value)) {
        return null;
    }

    $hasPlusPrefix = str_starts_with($value, '+');
    $digits = preg_replace('/\D/', '', $value);
    $length = strlen($digits);

    if ($length < 8 || $length > 15) {
        return null;
    }

    return ($hasPlusPrefix ? '+' : '') . $digits;
}

function allowed_services(): array
{
    static $services = [
        'ceiling-renovation',
        'wall-renovation',
        'mosque-renovation',
        'custom-gypsum',
        'gypsum-decoration',
        'gypsum-board',
        'renovation',
        'other',
    ];

    return $services;
}

function string_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function ensure_quote_requests_table(mysqli $conn, string $language, array $context = []): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    area VARCHAR(120) NOT NULL,
    service VARCHAR(64) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        log_submission($context + ['error' => $conn->error], 'schema_failed');
        respond_json(false, translate('schema_failed', $language), 500);
    }
}

function log_submission(array $payload, string $status): void
{
    $logDir = __DIR__ . '/storage';
    if (!is_dir($logDir) && !mkdir($logDir, 0775, true) && !is_dir($logDir)) {
        return;
    }

    $entry = [
        'time' => date('c'),
        'status' => $status,
        'payload' => $payload,
    ];

    file_put_contents(
        $logDir . '/quote_requests.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
