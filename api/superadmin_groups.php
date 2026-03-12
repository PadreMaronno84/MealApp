<?php
// superadmin_groups.php — lista tutti i gruppi con statistiche utenti e piani
// Solo superadmin.
require __DIR__ . '/common.php';
$me = require_superadmin();

$users = load_users();
$base  = storage_base();

// Aggrega dati per gruppo
$groups = [];
foreach ($users as $u) {
  $role = (string)($u['role'] ?? 'user');
  if ($role === 'superadmin') continue; // il superadmin non appartiene a nessun gruppo

  $g = safe_name((string)($u['group'] ?? ''));
  if ($g === '' || $g === 'x') continue;

  if (!isset($groups[$g])) {
    $groups[$g] = ['group'=>$g, 'admins'=>0, 'users'=>0, 'plans'=>0];
  }

  if ($role === 'admin') $groups[$g]['admins']++;
  else                   $groups[$g]['users']++;
}

// Conta i piani per ogni gruppo
foreach ($groups as &$gd) {
  $savedDir = $base . '/saved/' . $gd['group'];
  $gd['plans'] = is_dir($savedDir) ? count(glob($savedDir . '/*.json') ?: []) : 0;
}
unset($gd);

usort($groups, fn($a,$b) => strcmp($a['group'], $b['group']));
json_out(['ok'=>true, 'groups'=>array_values($groups)]);
