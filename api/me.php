<?php
require __DIR__ . '/common.php';

$u = current_user();
if (!$u) json_out(['ok'=>true,'logged'=>false]);

$activeGroup = ($u['role'] === 'superadmin')
  ? ($_SESSION['active_group'] ?? null)
  : ($u['group'] ?? null);

json_out([
  'ok'=>true,
  'logged'=>true,
  'user'=>[
    'username'     => $u['username'],
    'role'         => $u['role'],
    'group'        => $u['group'],
    'active_group' => $activeGroup,
  ],
  'csrf_token' => ensure_csrf_token()
]);
