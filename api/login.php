<?php
require __DIR__ . '/common.php';

$data = read_json_body();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') json_out(['ok'=>false,'error'=>'missing'], 400);

$u = find_user($username);
if (!$u) json_out(['ok'=>false,'error'=>'bad_credentials'], 401);

$hash = (string)($u['password_hash'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
  json_out(['ok'=>false,'error'=>'bad_credentials'], 401);
}

$role  = (string)($u['role'] ?? 'user');
$group = (string)($u['group'] ?? '');

if ($group === '') {
  // fallback: se dimentichi group sugli admin, lo metto uguale a username
  $group = (string)$u['username'];
}

$_SESSION['user'] = [
  'username' => (string)$u['username'],
  'role'     => $role,
  'group'    => $group
];

json_out(['ok'=>true, 'user'=>$_SESSION['user']]);
