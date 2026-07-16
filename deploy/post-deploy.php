<?php
/**
 * post-deploy.php
 *
 * Triggered by GitHub Actions after FTP upload completes.
 * Handles all server-side tasks that require PHP CLI:
 *   - Run database migrations
 *   - Optionally run database seeders when requested by GitHub Actions
 *   - Clear & rebuild caches
 *   - Ensure storage directories exist with correct permissions
 *   - Create storage symlink (if missing)
 *
 * NOTE: .env is managed entirely on the server — it is never uploaded by CI.
 * Create it once via cPanel File Manager from .env.example and set your
 * production values. This script only reads DEPLOY_SECRET from it to authenticate.
 *
 * SECURITY:
 *   - Only accepts POST requests with a valid DEPLOY_SECRET
 *   - DEPLOY_SECRET must be set in the server's .env file
 *   - This file lives in /deploy/ which is outside /public/
 *     See deploy/README.md for how to make it reachable via HTTP.
 */

declare(strict_types=1);

// ── Bootstrap ──────────────────────────────────────────────────────────────

$laravelRoot = realpath(__DIR__ . '/..');

if ($laravelRoot === false || !file_exists($laravelRoot . '/artisan')) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot locate Laravel root']);
    exit(1);
}

// ── Auth ───────────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit(1);
}

$body    = file_get_contents('php://input');
$payload = json_decode($body, true);
if (! is_array($payload)) {
    $payload = [];
}
$secret  = $payload['secret'] ?? ($_POST['secret'] ?? '');

// Read DEPLOY_SECRET from the server's .env
$expectedSecret = '';
$envFile        = $laravelRoot . '/.env';

if (!file_exists($envFile)) {
    http_response_code(500);
    echo json_encode(['error' => '.env not found on server — create it via cPanel File Manager first']);
    exit(1);
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), 'DEPLOY_SECRET=')) {
        $expectedSecret = trim(substr($line, strpos($line, '=') + 1), " \t\"'");
        break;
    }
}

if ($expectedSecret === '' || !hash_equals($expectedSecret, $secret)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit(1);
}

// ── Helpers ────────────────────────────────────────────────────────────────

$log   = [];
$error = false;

function run(string $command, string $cwd): array
{
    $output     = [];
    $returnCode = 0;
    exec(sprintf('cd %s && %s 2>&1', escapeshellarg($cwd), $command), $output, $returnCode);

    return [
        'output' => implode("\n", $output),
        'ok'     => $returnCode === 0,
    ];
}

function step(string $name, string $command, string $cwd, bool $required = true): void
{
    global $log, $error;
    $result = run($command, $cwd);
    $status = $result['ok'] ? '✓' : '✗';
    $log[]  = "[{$status}] {$name}" . ($result['output'] ? ": {$result['output']}" : '');
    if (!$result['ok'] && $required) {
        $error = true;
    }
}

// ── Step 1: Ensure storage directories exist ───────────────────────────────

$storageDirs = [
    $laravelRoot . '/storage',
    $laravelRoot . '/storage/app',
    $laravelRoot . '/storage/app/public',
    $laravelRoot . '/storage/framework',
    $laravelRoot . '/storage/framework/cache',
    $laravelRoot . '/storage/framework/sessions',
    $laravelRoot . '/storage/framework/views',
    $laravelRoot . '/storage/logs',
    $laravelRoot . '/bootstrap/cache',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        $log[] = "[✓] Created: {$dir}";
    }
    chmod($dir, 0755);
}

$log[] = '[✓] Storage permissions set';

// ── Step 2: Detect PHP CLI binary ─────────────────────────────────────────
// cPanel shared hosts often have PHP CLI at a versioned path.

$phpBinaries = [
    // EasyApache (cPanel) — exact binary for this server (PHP 8.3, domain: api-kmd.kimmex.com.kh)
    '/usr/local/bin/ea-php83',
    // Fallbacks
    'ea-php83',
    '/opt/cpanel/ea-php83/root/usr/bin/php',
    'php8.3',
    'php83',
    '/usr/local/bin/php83',
    'php',
    '/usr/bin/php',
    '/usr/local/bin/php',
];

$phpBin = null;
foreach ($phpBinaries as $bin) {
    $test = shell_exec(sprintf('%s -r "echo \'ok\';" 2>/dev/null', escapeshellcmd($bin)));
    if (trim((string) $test) === 'ok') {
        $phpBin = $bin;
        break;
    }
}

if ($phpBin === null) {
    $log[]  = '[✗] PHP CLI binary not found — artisan commands skipped';
    $error  = true;
    respondAndExit($log, $error);
}

$log[] = "[✓] PHP CLI: {$phpBin}";

// ── Step 3: Run artisan commands ───────────────────────────────────────────

$artisan = "{$phpBin} artisan";

step('Maintenance ON',   "{$artisan} down --secret=deploy-bypass",              $laravelRoot);
step('Migrate',          "{$artisan} migrate --force --no-interaction",          $laravelRoot);
step('Settings migrate', "{$artisan} settings:migrate --force --no-interaction", $laravelRoot, required: false);

if (($payload['seed'] ?? false) === true) {
    step('Seed database', "{$artisan} db:seed --force --no-interaction", $laravelRoot);
}

step('Config clear',     "{$artisan} config:clear",                              $laravelRoot);
step('Route clear',      "{$artisan} route:clear",                               $laravelRoot);
step('View clear',       "{$artisan} view:clear",                                $laravelRoot);
step('Event clear',      "{$artisan} event:clear",                               $laravelRoot);
step('Config cache',     "{$artisan} config:cache",                              $laravelRoot);
step('Route cache',      "{$artisan} route:cache",                               $laravelRoot);
step('View cache',       "{$artisan} view:cache",                                $laravelRoot);
step('Storage link',     "{$artisan} storage:link --force",                      $laravelRoot, required: false);
step('Filament upgrade', "{$artisan} filament:upgrade",                          $laravelRoot, required: false);
step('Queue restart',    "{$artisan} queue:restart",                             $laravelRoot, required: false);
step('Maintenance OFF',  "{$artisan} up",                                        $laravelRoot);

// ── Respond ────────────────────────────────────────────────────────────────

respondAndExit($log, $error);

function respondAndExit(array $log, bool $error): never
{
    http_response_code($error ? 500 : 200);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $error ? 'error' : 'DEPLOY_OK',
        'log'    => $log,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit($error ? 1 : 0);
}
