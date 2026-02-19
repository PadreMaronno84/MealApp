<?php
require __DIR__ . '/common.php';
$me = require_admin();

$data = read_json_body();
$target = normalize_username((string)($data['username'] ?? ''));

if ($target === '') json_out(['ok'=>false,'error'=>'bad_username'], 400);
if (strtolower($target) === strtolower((string)$me['username'])) {
  json_out(['ok'=>false,'error'=>'cannot_delete_self'], 400);
}

$group = safe_name((string)$me['group']);
$users = load_users();

$found = false;
$new = [];
$adminsLeft = 0;

foreach ($users as $u) {
  $uName = (string)($u['username'] ?? '');
  $uGroup = safe_name((string)($u['group'] ?? ''));

  if ($uGroup !== $group) {
    $new[] = $u;
    continue;
  }

  if (strtolower($uName) === strtolower($target)) {
    $found = true;
    // skip = delete
    continue;
  }

  $new[] = $u;
}

if (!$found) json_out(['ok'=>false,'error'=>'not_found'], 404);

// ricontrollo admin rimasti nel gruppo
foreach ($new as $u) {
  if (safe_name((string)($u['group'] ?? '')) !== $group) continue;
  if ((string)($u['role'] ?? 'user') === 'admin') $adminsLeft++;
}
if ($adminsLeft < 1) json_out(['ok'=>false,'error'=>'must_keep_one_admin'], 400);

save_users($new);
json_out(['ok'=>true]);
