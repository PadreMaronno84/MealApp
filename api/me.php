<?php
require __DIR__ . '/common.php';

$u = current_user();
if (!$u) json_out(['ok'=>true,'logged'=>false]);

json_out([
  'ok'=>true,
  'logged'=>true,
  'user'=>[
    'username'=>$u['username'],
    'role'=>$u['role'],
    'group'=>$u['group']
  ],
  'csrf_token' => ensure_csrf_token()
]);
