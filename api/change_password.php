<?php
require __DIR__ . '/common.php';
$me = require_login();
verify_csrf();

$data = read_json_body();
$currentPass = (string)($data['current_password'] ?? '');
$newPass     = (string)($data['new_password'] ?? '');

if ($currentPass === '' || $newPass === '') {
  json_out(['ok'=>false,'error'=>'missing_fields'], 400);
}
if (strlen($newPass) < 8) {
  json_out(['ok'=>false,'error'=>'password_too_short'], 400);
}

$users = load_users();
$idx = null;
foreach ($users as $i => $u) {
  if (strtolower((string)($u['username'] ?? '')) === strtolower((string)$me['username'])) {
    $idx = $i;
    break;
  }
}

if ($idx === null) json_out(['ok'=>false,'error'=>'user_not_found'], 404);

$hash = (string)($users[$idx]['password_hash'] ?? '');
if (!password_verify($currentPass, $hash)) {
  json_out(['ok'=>false,'error'=>'wrong_password'], 401);
}

$users[$idx]['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
save_users($users);

json_out(['ok'=>true]);
