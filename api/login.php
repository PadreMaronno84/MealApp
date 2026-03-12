<?php
require __DIR__ . '/common.php';

// Accetta sia JSON che form-urlencoded
$username = '';
$password = '';

// 1) Se arrivano parametri POST (form-urlencoded o multipart)
if (!empty($_POST)) {
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
} else {
  // 2) Altrimenti prova JSON
  $data = read_json_body();
  $username = trim((string)($data['username'] ?? ''));
  $password = (string)($data['password'] ?? '');
}

if ($username === '' || $password === '') {
  json_out(['ok' => false, 'error' => 'missing'], 400);
}

// Rate limiting: max 10 tentativi per IP in 60 secondi
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateDir = storage_base() . '/rate_limit';
if (!is_dir($rateDir)) @mkdir($rateDir, 0775, true);
$rateFile = $rateDir . '/' . md5($ip) . '.json';
$now = time();
$attempts = [];
if (is_file($rateFile)) {
  $raw = @file_get_contents($rateFile);
  $attempts = json_decode($raw ?: '', true) ?? [];
}
$attempts = array_values(array_filter($attempts, fn($t) => ($now - $t) < 60));
if (count($attempts) >= 10) {
  json_out(['ok'=>false,'error'=>'rate_limited'], 429);
}

$u = find_user($username);
if (!$u) {
  $attempts[] = $now;
  @file_put_contents($rateFile, json_encode($attempts));
  json_out(['ok' => false, 'error' => 'bad_credentials'], 401);
}

$hash = (string)($u['password_hash'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
  $attempts[] = $now;
  @file_put_contents($rateFile, json_encode($attempts));
  json_out(['ok' => false, 'error' => 'bad_credentials'], 401);
}

// Credenziali valide: resetta rate limit
@unlink($rateFile);

$role  = (string)($u['role'] ?? 'user');
$group = (string)($u['group'] ?? '');

if ($group === '') {
  $group = (string)($u['username'] ?? $username);
}

session_regenerate_id(true);

$_SESSION['user'] = [
  'username' => (string)($u['username'] ?? $username),
  'role'     => $role,
  'group'    => $group
];

json_out(['ok' => true, 'user' => $_SESSION['user']]);
