<?php
require __DIR__ . '/common.php';

// Rate limiting: max 5 tentativi di registrazione per IP all'ora
$ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateDir = storage_base() . '/rate_limit';
if (!is_dir($rateDir)) @mkdir($rateDir, 0775, true);
$rateFile = $rateDir . '/reg_' . md5($ip) . '.json';

$now      = time();
$attempts = [];
if (file_exists($rateFile)) {
  $attempts = json_decode(file_get_contents($rateFile), true) ?: [];
}
$attempts = array_values(array_filter($attempts, fn($t) => $now - $t < 3600));
if (count($attempts) >= 5) {
  json_out(['ok'=>false,'error'=>'rate_limited'], 429);
}

$data        = read_json_body();
$username    = normalize_username((string)($data['username']    ?? ''));
$password    = (string)($data['password']    ?? '');
$invite_code = strtoupper(trim((string)($data['invite_code'] ?? '')));

if ($username === '')       json_out(['ok'=>false,'error'=>'bad_username'],       400);
if (strlen($password) < 8) json_out(['ok'=>false,'error'=>'password_too_short'], 400);
if ($invite_code === '')    json_out(['ok'=>false,'error'=>'missing_invite_code'],400);

// Cerca il gruppo con questo codice invito
$settingsDir  = storage_base() . '/settings';
$matchedGroup = null;

if (is_dir($settingsDir)) {
  foreach (glob($settingsDir . '/*.json') as $file) {
    $s    = json_decode(@file_get_contents($file), true);
    $code = strtoupper(trim((string)($s['invite_code'] ?? '')));
    if ($code !== '' && hash_equals($code, $invite_code)) {
      $matchedGroup = basename($file, '.json');
      break;
    }
  }
}

if (!$matchedGroup) {
  $attempts[] = $now;
  file_put_contents($rateFile, json_encode(array_values($attempts)));
  json_out(['ok'=>false,'error'=>'invalid_invite_code'], 400);
}

// Username unico (case-insensitive)
$users = load_users();
foreach ($users as $u) {
  if (strtolower((string)($u['username'] ?? '')) === strtolower($username)) {
    json_out(['ok'=>false,'error'=>'username_exists'], 409);
  }
}

$users[] = [
  'username'      => $username,
  'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  'role'          => 'user',
  'group'         => $matchedGroup
];

save_users($users);
json_out(['ok'=>true, 'group'=>$matchedGroup]);
