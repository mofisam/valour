<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

/* ---------- Environment & config ---------- */

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;
    static $vars = [];
    if (!$loaded) {
        $loaded = true;
        $file = BASE_PATH . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim(trim($v), "\"'");
            }
        }
    }
    $val = $vars[$key] ?? getenv($key);
    return ($val === false || $val === null || $val === '') ? $default : $val;
}

function config(string $key, mixed $default = null): mixed
{
    static $cache = [];
    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
    if (!isset($cache[$file])) {
        $path = BASE_PATH . "/config/{$file}.php";
        $cache[$file] = is_file($path) ? require $path : [];
    }
    return $item === null ? $cache[$file] : ($cache[$file][$item] ?? $default);
}

/* ---------- URLs ---------- */

function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $forced = (string) config('app.base_path', '');
        if ($forced !== '') {
            $base = '/' . trim($forced, '/');
        } else {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
            $base = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
        }
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = BASE_PATH . '/public/assets/' . $path;
    $v = is_file($file) ? '?v=' . filemtime($file) : '';   // changes whenever the file changes, so browsers refetch it
    return url('assets/' . $path) . $v;
}

function redirect(string $path): never
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === explode(':', $host)[0]) {
        redirect($ref);
    }
    redirect($fallback);
}

/* ---------- Output helpers ---------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float|int|string|null $amount): string
{
    return config('app.currency', '₦') . number_format((float) $amount, 2);
}

function fdate(?string $date, string $format = 'd M Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function csrf_token(): string
{
    return Csrf::token();
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::old($key, $default);
}

function error(string $key): ?string
{
    return Session::error($key);
}

function auth(): ?array
{
    return Auth::user();
}

function is_admin(): bool
{
    return Auth::is('admin');
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return '/' . trim($path, '/');
}

function is_active(string $prefix): bool
{
    $cur = current_path();
    return $prefix === '/' ? $cur === '/' : str_starts_with($cur, $prefix);
}

function setting(string $key, mixed $default = null): mixed
{
    return \App\Models\Setting::get($key, $default);
}

/** Inline SVG icon (24x24 outline set). Usage: <?= icon('wallet', 'h-5 w-5') ?> */
function icon(string $name, string $class = 'h-5 w-5'): string
{
    static $paths = [
        'wallet'      => 'M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 000 4h4v-4z',
        'trending-up' => 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
        'clipboard'   => 'M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2M9 2h6v4H9z',
        'check-circle'=> 'M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3',
        'clock'       => 'M12 6v6l4 2M22 12a10 10 0 11-20 0 10 10 0 0120 0z',
        'bar-chart'   => 'M12 20V10M18 20V4M6 20v-4',
        'credit-card' => 'M1 4h22v16H1zM1 10h22',
        'users'       => 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
        'file-text'   => 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8',
        'percent'     => 'M19 5L5 19M6.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM17.5 20a2.5 2.5 0 100-5 2.5 2.5 0 000 5z',
        'package'     => 'M16.5 9.4l-9-5.19M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16zM3.27 6.96L12 12.01l8.73-5.05M12 22.08V12',
        'alert'       => 'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01',
        'plus'        => 'M12 5v14M5 12h14',
        'calendar'    => 'M3 4h18v18H3zM16 2v4M8 2v4M3 10h18',
        'download'    => 'M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3',
        'printer'     => 'M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z',
        'bank'        => 'M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M12 3l9 5H3z',
        'tag'         => 'M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82zM7 7h.01',
        'droplet'     => 'M12 2.69l5.66 5.66a8 8 0 11-11.31 0z',
        'shield'      => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'activity'    => 'M22 12h-4l-3 9L9 3l-3 9H2',
        'layers'      => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
        'arrow-right' => 'M5 12h14M12 5l7 7-7 7',
        'smartphone'  => 'M7 2h10a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2zM12 18h.01',
        'banknote'    => 'M2 6h20v12H2zM12 15a3 3 0 100-6 3 3 0 000 6zM6 12h.01M18 12h.01',
    ];
    $d = $paths[$name] ?? $paths['file-text'];
    return '<svg class="' . e($class) . '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="' . $d . '"/></svg>';
}

/** Round icon badge used on stat cards. $tone: sea | brass | red | green | ink */
function icon_chip(string $name, string $tone = 'sea'): string
{
    $tones = [
        'sea'   => 'bg-sea/10 text-sea dark:bg-teal-400/10 dark:text-teal-300',
        'brass' => 'bg-brass/15 text-brass-600 dark:text-brass',
        'red'   => 'bg-red-500/10 text-red-700 dark:text-red-300',
        'green' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        'ink'   => 'bg-ink/10 text-fg2 dark:bg-white/10',
    ];
    return '<span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ' . ($tones[$tone] ?? $tones['sea']) . '">' . icon($name, 'h-[18px] w-[18px]') . '</span>';
}

/** 'coloured' -> 'Coloured', 'nil'/empty -> an en dash */
function option_label(?string $value): string
{
    return ($value === null || $value === '' || $value === 'nil') ? '—' : ucfirst($value);
}

/** Business name as entered by the admin in Settings (falls back to a neutral label until set). */
function company_name(): string
{
    return (string) (setting('business_name') ?: config('app.name', 'Laundry Manager'));
}

function brand_initial(): string
{
    return mb_strtoupper(mb_substr(trim(company_name()), 0, 1)) ?: 'L';
}

/**
 * Process steps in order: [key => ['label' => .., 'color' => .., 'locked' => bool]].
 * Managed by the admin; falls back to the standard set if the table does not exist yet.
 */
function job_steps(): array
{
    static $steps = null;
    if ($steps === null) {
        $steps = [];
        try {
            foreach (\App\Models\ProcessStep::ordered() as $r) {
                $steps[$r['step_key']] = ['label' => $r['label'], 'color' => $r['color'], 'locked' => (bool) $r['is_locked'], 'id' => (int) $r['id'], 'sort' => (int) $r['sort_order']];
            }
        } catch (\Throwable) {
            $steps = [];
        }
        if (!$steps) {
            foreach ([['pending', 'Pending', 'amber', 1], ['washing', 'Washing', 'sky', 0], ['ironing', 'Ironing', 'violet', 0], ['ready', 'Ready', 'teal', 0], ['delivered', 'Delivered', 'emerald', 1]] as $n => [$k, $l, $c, $lock]) {
                $steps[$k] = ['label' => $l, 'color' => $c, 'locked' => (bool) $lock, 'id' => 0, 'sort' => ($n + 1) * 10];
            }
        }
    }
    return $steps;
}

/** Every value a job's status can hold: the steps plus the built-in 'cancelled'. */
function job_statuses(): array
{
    return array_merge(array_keys(job_steps()), ['cancelled']);
}

function job_label(string $status): string
{
    if ($status === 'cancelled') {
        return 'Cancelled';
    }
    return job_steps()[$status]['label'] ?? ucfirst(str_replace('_', ' ', $status));
}

/**
 * A status badge that opens a drop-down so the status can be changed in place (jobs list, dashboard).
 * Falls back to a plain badge for drafts, or when the person cannot change statuses.
 */
function status_menu(array $job): string
{
    if (!empty($job['is_draft'])) {
        return status_badge('draft');
    }
    $steps = \App\Models\Service::workflowOf(['workflow' => $job['service_workflow'] ?? null]);
    if (!in_array($job['status'], $steps, true) && $job['status'] !== 'cancelled') {
        $steps[] = $job['status'];     // a status from an older process: still show it so nothing looks broken
    }
    $options = array_map(fn($k) => ['key' => $k, 'label' => job_label($k)], $steps);
    if (is_admin()) {
        $options[] = ['key' => 'cancelled', 'label' => 'Cancelled'];
    }
    return '<button type="button" class="group inline-flex items-center gap-1 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-sea" data-status-menu data-job="' . (int) $job['id'] . '" data-status="' . e($job['status']) . '" data-options="' . e(json_encode($options)) . '" aria-haspopup="menu" title="Change status">'
        . '<span data-badge>' . status_badge($job['status']) . '</span>'
        . '<svg class="h-3.5 w-3.5 text-muted group-hover:text-fg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg></button>';
}

function status_badge(string $status): string
{
    // Payment states, drafts and cancelled keep their fixed colours...
    if (in_array($status, ['unpaid', 'partial', 'paid', 'draft', 'cancelled'], true)) {
        return '<span class="badge badge-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
    }
    // ...process steps use the colour the admin picked
    $color = job_steps()[$status]['color'] ?? 'slate';
    return '<span class="badge ' . (STEP_COLORS[$color] ?? STEP_COLORS['slate']) . '">' . e(job_label($status)) . '</span>';
}

function abort(int $code = 404, string $message = ''): never
{
    http_response_code($code);
    $view = in_array($code, [403, 404], true) ? (string) $code : '500';
    \App\Core\View::render('errors/' . $view, ['message' => $message], 'auth');
    exit;
}

function dd(mixed ...$vars): never
{
    echo '<pre style="background:#111;color:#eee;padding:12px">';
    foreach ($vars as $v) {
        var_dump($v);
    }
    echo '</pre>';
    exit;
}
