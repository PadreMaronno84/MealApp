<?php
// api/common.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start();

function json_out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function safe_name(string $name): string {
  $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name);
  return $name ?: 'x';
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

function storage_base(): string {
  $base = realpath(__DIR__ . '/../storage');
  if ($base === false) json_out(['ok'=>false,'error'=>'storage folder not found'], 500);
  return $base;
}

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  if (!is_dir($dir) || !is_writable($dir)) json_out(['ok'=>false,'error'=>"dir not writable: $dir"], 500);
}

function users_file(): string {
  return storage_base() . '/users.json';
}

function load_users(): array {
  $f = users_file();
  if (!is_file($f)) json_out(['ok'=>false,'error'=>'users.json missing'], 500);
  $raw = file_get_contents($f);
  $j = json_decode($raw ?: '', true);
  if (!is_array($j) || !isset($j['users']) || !is_array($j['users'])) {
    json_out(['ok'=>false,'error'=>'users.json invalid'], 500);
  }
  return $j['users'];
}

function save_users(array $users): void {
  $f = users_file();
  $dir = dirname($f);
  if (!is_dir($dir)) @mkdir($dir, 0775, true);

  $lock = fopen($f, 'c+');
  if (!$lock) json_out(['ok'=>false,'error'=>'users_lock_open_failed'], 500);
  if (!flock($lock, LOCK_EX)) { fclose($lock); json_out(['ok'=>false,'error'=>'users_lock_failed'], 500); }

  $payload = json_encode(['users'=>$users], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

  $tmp = $f . '.tmp';
  if (@file_put_contents($tmp, $payload) === false) {
    flock($lock, LOCK_UN);
    fclose($lock);
    json_out(['ok'=>false,'error'=>'users_write_failed'], 500);
  }

  @rename($tmp, $f);

  flock($lock, LOCK_UN);
  fclose($lock);
}

function find_user(string $username): ?array {
  $username = strtolower(trim($username));
  foreach (load_users() as $u) {
    if (strtolower($u['username'] ?? '') === $username) return $u;
  }
  return null;
}

function current_user(): ?array {
  return (isset($_SESSION['user']) && is_array($_SESSION['user'])) ? $_SESSION['user'] : null;
}

function require_login(): array {
  $u = current_user();
  if (!$u) json_out(['ok'=>false,'error'=>'not_logged'], 401);
  return $u;
}

function require_admin(): array {
  $u = require_login();
  if (($u['role'] ?? '') !== 'admin') json_out(['ok'=>false,'error'=>'forbidden'], 403);
  return $u;
}

function current_group(): string {
  $u = require_login();
  $g = (string)($u['group'] ?? '');
  if ($g === '') json_out(['ok'=>false,'error'=>'missing_group'], 500);
  return safe_name($g);
}

function group_saved_dir(): string {
  $g = current_group();
  $dir = storage_base() . '/saved/' . $g;
  ensure_dir($dir);
  return $dir;
}

function group_uploads_dir(): string {
  $g = current_group();
  $dir = storage_base() . '/uploads/' . $g;
  ensure_dir($dir);
  return $dir;
}

function normalize_username(string $u): string {
  $u = trim($u);
  if ($u === '') return '';
  if (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $u)) return '';
  return $u;
}

/**
 * Calcola range date (start/end) per un piano.
 * startMondayISO: YYYY-MM-DD
 * weeks: array
 */
function plan_range(array $plan): ?array {
  $startISO = (string)($plan['startMondayISO'] ?? '');
  $weeks = $plan['weeks'] ?? null;
  if ($startISO === '' || !is_array($weeks) || count($weeks) < 1) return null;

  try {
    $start = new DateTime($startISO . ' 00:00:00');
  } catch (Exception $e) {
    return null;
  }

  $days = (count($weeks) * 7) - 1;
  $end = clone $start;
  $end->modify('+' . $days . ' day');

  return [
    'start' => $start,
    'end' => $end,
    'startISO' => $start->format('Y-m-d'),
    'endISO' => $end->format('Y-m-d'),
    'weeksLen' => count($weeks)
  ];
}

function ranges_overlap(DateTime $aStart, DateTime $aEnd, DateTime $bStart, DateTime $bEnd): bool {
  // overlap se max(start) <= min(end)
  return ($aStart <= $bEnd) && ($bStart <= $aEnd);
}

/**
 * Cancella file piani scaduti:
 * scade dopo 1 mese dall'ultimo giorno (end + 1 month)
 */
function cleanup_expired_plans(string $dir): int {
  $deleted = 0;
  $files = glob($dir . '/*.json') ?: [];
  $now = new DateTime('now');

  foreach ($files as $f) {
    $raw = @file_get_contents($f);
    $j = json_decode($raw ?: '', true);
    if (!is_array($j)) continue;

    $r = plan_range($j);
    if (!$r) continue;

    $cutoff = clone $r['end'];
    $cutoff->modify('+1 month');

    if ($now >= $cutoff) {
      @unlink($f);
      $deleted++;
    }
  }
  return $deleted;
}
