<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$data = read_json_body();
$target = normalize_username((string)($data['username'] ?? ''));

if ($target === '') json_out(['ok'=>false,'error'=>'bad_username'], 400);
if (strtolower($target) === strtolower((string)$me['username'])) {
  json_out(['ok'=>false,'error'=>'cannot_delete_self'], 400);
}

$isSuperAdmin = ($me['role'] ?? '') === 'superadmin';
$group = get_effective_group($me);
$users = load_users();

$found = false;
$new   = [];
$targetGroup = null;

foreach ($users as $u) {
  $uName  = (string)($u['username'] ?? '');
  $uGroup = safe_name((string)($u['group'] ?? ''));

  // Admin: può eliminare solo nel proprio gruppo
  if (!$isSuperAdmin && $uGroup !== $group) {
    $new[] = $u;
    continue;
  }

  if (strtolower($uName) === strtolower($target)) {
    $found       = true;
    $targetGroup = $uGroup;
    // skip = delete
    continue;
  }

  $new[] = $u;
}

if (!$found) json_out(['ok'=>false,'error'=>'not_found'], 404);

// Admin normale: deve restare almeno un admin nel gruppo
if (!$isSuperAdmin) {
  $adminsLeft = 0;
  foreach ($new as $u) {
    if (safe_name((string)($u['group'] ?? '')) !== $group) continue;
    if ((string)($u['role'] ?? 'user') === 'admin') $adminsLeft++;
  }
  if ($adminsLeft < 1) json_out(['ok'=>false,'error'=>'must_keep_one_admin'], 400);
}

$group = $targetGroup ?? $group; // usa il gruppo dell'utente eliminato per il log

save_users($new);
log_activity($group, $me['username'], 'utente_eliminato', ['username' => $target]);
json_out(['ok'=>true]);
