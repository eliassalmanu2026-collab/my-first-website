<?php
/**
 * Вспомогательные функции проекта.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------------------------------------------------------------- вывод */

/** Экранирование для HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Адрес корня сайта: работает и в подпапке, и во встроенном сервере PHP. */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = str_replace('\\', '/', dirname($script));
    // /admin/table.php -> /admin -> /
    if (preg_match('#/(admin)$#', $dir)) {
        $dir = substr($dir, 0, -6);
    }
    $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    return $base;
}

/** Ссылка внутри сайта. */
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** Ссылка на статику. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Редирект с завершением работы. */
function redirect(string $path): never
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/* ------------------------------------------------------------- транслит */

/** Транслитерация и slug для ЧПУ. */
function slugify(string $text): string
{
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item-' . substr(md5((string)microtime(true)), 0, 6);
}

/* ----------------------------------------------------------- флеш-сообщения */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'text' => $message];
}

/** @return array<int, array{type:string,text:string}> */
function take_flash(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ------------------------------------------------------------------ CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    $token = $_POST['csrf'] ?? '';
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/* ------------------------------------------------------------ авторизация */

function current_user(): ?array
{
    global $pdo;
    static $cache = null;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    if ($cache !== null && (int)$cache['id'] === (int)$_SESSION['user_id']) {
        return $cache;
    }
    $stmt = $pdo->prepare('SELECT u.*, r.code AS role_code, r.title AS role_title
                           FROM users u JOIN roles r ON r.id = u.role_id
                           WHERE u.id = ? AND u.status = "active" LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $cache = ($user ?: null);
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && $u['role_code'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Чтобы открыть эту страницу, войдите в аккаунт.');
        redirect('login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('<h1 style="font-family:system-ui">403 — доступ только для администратора</h1>');
    }
}

/* -------------------------------------------------------------- запросы */

/** Одна строка или null. */
function db_one(string $sql, array $params = []): ?array
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Все строки. */
function db_all(string $sql, array $params = []): array
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Одно значение. */
function db_value(string $sql, array $params = []): mixed
{
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/* ------------------------------------------------------------- валидация */

function valid_email(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/** Телефон в формате +7 (999) 123-45-67 или пустой. */
function valid_phone(string $phone): bool
{
    if ($phone === '') {
        return true;
    }
    return (bool)preg_match('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/u', $phone);
}

/** Пароль: от 5 символов, есть буквы и цифры (требование ТЗ). */
function valid_password(string $password): bool
{
    if (mb_strlen($password) < 5) {
        return false;
    }
    return (bool)preg_match('/[A-Za-zА-Яа-я]/u', $password)
        && (bool)preg_match('/\d/', $password);
}

/* ----------------------------------------------------------------- утилиты */

function plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 > 1 && $n1 < 5) {
        return $few;
    }
    if ($n1 === 1) {
        return $one;
    }
    return $many;
}

function format_date(?string $datetime, string $format = 'd.m.Y'): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

function minutes_human(?int $minutes): string
{
    if (!$minutes) {
        return '—';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    if ($h === 0) {
        return $m . ' мин';
    }
    return $h . ' ч' . ($m ? ' ' . $m . ' мин' : '');
}

function difficulty_label(string $code): string
{
    return match ($code) {
        'easy'   => 'Просто',
        'medium' => 'Средне',
        'hard'   => 'Сложно',
        default  => '—',
    };
}

/** Безопасное имя текущей страницы для подсветки меню. */
function current_page(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '');
}

/** Запрос к TheMealDB с коротким таймаутом; при недоступности — null. */
function mealdb(string $endpoint, array $query = []): ?array
{
    $url = rtrim(MEALDB_BASE, '/') . '/' . ltrim($endpoint, '/');
    if ($query) {
        $url .= '?' . http_build_query($query);
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 4, 'header' => "Accept: application/json\r\n"],
        'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}
