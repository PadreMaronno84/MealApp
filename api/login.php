<?php
require __DIR__ . '/common.php';

// Accetta sia JSON che form-urlencoded
$username = '';
$password = '';

// 1) Se arrivano parametri POST (form-urlencoded o multipart)
if (!empty($_POST)) {
  $username = trim((string)($_POST['username'] ?? ''));
  $password = (string)($_POST['password'] ?? '');
} else {
  // 2) Altrimenti prova JSON
  $data = read_json_body();
  $username = trim((string)($data['username'] ?? ''));
  $password = (string)($data['password'] ?? '');
}

if ($username === '' || $password === '') {
  json_out(['ok' => false, 'error' => 'missing'], 400);
}

$u = find_user($username);
if (!$u) json_out(['ok' => false, 'error' => 'bad_credentials'], 401);

$hash = (string)($u['password_hash'] ?? '');
if ($hash === '' || !password_verify($password, $hash)) {
  json_out(['ok' => false, 'error' => 'bad_credentials'], 401);
}

$role  = (string)($u['role'] ?? 'user');
$group = (string)($u['group'] ?? '');

if ($group === '') {
  $group = (string)($u['username'] ?? $username);
}

$_SESSION['user'] = [
  'username' => (string)($u['username'] ?? $username),
  'role'     => $role,
  'group'    => $group
];

json_out(['ok' => true, 'user' => $_SESSION['user']]);
