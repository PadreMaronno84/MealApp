<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$data = read_json_body();
$username = normalize_username((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$role = (string)($data['role'] ?? 'user');

if ($username === '') json_out(['ok'=>false,'error'=>'bad_username'], 400);
if (strlen($password) < 8) json_out(['ok'=>false,'error'=>'password_too_short'], 400);
if ($role !== 'user' && $role !== 'admin') $role = 'user';

$users = load_users();

// username globale unico
foreach ($users as $u) {
  if (strtolower((string)($u['username'] ?? '')) === strtolower($username)) {
    json_out(['ok'=>false,'error'=>'username_exists'], 409);
  }
}

$group = safe_name((string)$me['group']);

$users[] = [
  'username' => $username,
  'password_hash' => password_hash($password, PASSWORD_DEFAULT),
  'role' => $role,
  'group' => $group
];

save_users($users);
log_activity($group, $me['username'], 'utente_creato', ['username' => $username, 'role' => $role]);
json_out(['ok'=>true]);
