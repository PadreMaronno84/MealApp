<?php
require __DIR__ . '/common.php';
$me = require_admin();

$group = get_effective_group($me);
$users = load_users();

$out = [];
foreach ($users as $u) {
  $g = safe_name((string)($u['group'] ?? ''));
  if ($g !== $group) continue;

  $out[] = [
    'username' => (string)($u['username'] ?? ''),
    'role' => (string)($u['role'] ?? 'user'),
    'group' => $group
  ];
}

usort($out, function($a,$b){
  // admin sopra
  if ($a['role'] !== $b['role']) return ($a['role']==='admin') ? -1 : 1;
  return strcmp($a['username'], $b['username']);
});

json_out(['ok'=>true,'items'=>$out]);
