<?php
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';

// En-têtes de sécurité applicatifs compatibles avec l'hébergement mutualisé.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

function installed(): bool {
    return is_file(APP_ROOT . '/config/database.php') && is_file(APP_ROOT . '/config/installed.lock');
}
function start_app_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (!$secure && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        // Only rely on trusted proxy headers when configured at the webserver; do not infer HTTPS here.
    }
    session_name('hydroaktif_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
start_app_session();

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!installed()) throw new RuntimeException('Installation requise.');
    $cfg = require APP_ROOT . '/config/database.php';
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $cfg['host'], (int)$cfg['port'], $cfg['database']);
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 8
    ]);
    return $pdo;
}
function e(?string $text): string { return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function check_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(419); exit('Session expirée ou jeton de sécurité invalide. Actualisez la page.');
    }
}
function go(string $page = 'dashboard'): never {
    header('Location: index.php?page=' . rawurlencode($page), true, 303); exit;
}
function flash(string $text, string $kind = 'success'): void { $_SESSION['flash'] = ['text'=>$text,'kind'=>$kind]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!current_user()) go('login'); }
function can_edit(): bool { return in_array(current_user()['role'] ?? '', ['admin','editor'], true); }
function require_editor(): void { if (!can_edit()) { http_response_code(403); exit('Accès interdit.'); } }
function val(string $name, int $max = 255): string { return trim(mb_substr((string)($_POST[$name] ?? ''), 0, $max)); }
function numeric(string $name, float $max = 100000000): float {
    $s = trim((string)($_POST[$name] ?? '0'));
    if ($s === '') $s = '0';
    if (!is_numeric($s) || (float)$s < 0 || (float)$s > $max || !is_finite((float)$s)) throw new InvalidArgumentException('Valeur numérique invalide : '. $name);
    return (float)$s;
}
function option_rows(PDO $pdo, string $table): array {
    return $pdo->query('SELECT id, label FROM ' . $table . ' ORDER BY label')->fetchAll();
}
function setting(string $key, string $fallback = ''): string {
    static $settings = [];
    if (!array_key_exists($key,$settings)) {
        $q = db()->prepare('SELECT value FROM settings WHERE `key` = ?'); $q->execute([$key]);
        $settings[$key] = $q->fetchColumn();
    }
    return is_string($settings[$key]) ? $settings[$key] : $fallback;
}
function count_query(string $sql): int { return (int) db()->query($sql)->fetchColumn(); }
function money(float $x): string { return number_format($x,2,',',' ') . ' €'; }
