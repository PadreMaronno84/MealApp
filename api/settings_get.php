<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function current_user() {
  $candidates = ['user', 'auth_user', 'mealapp_user'];
  foreach ($candidates as $k) {
    if (isset($_SESSION[$k]) && is_array($_SESSION[$k])) return $_SESSION[$k];
  }
  return null;
}

$u = current_user();
if (!$u) {
  echo json_encode(['ok'=>true,'logged'=>false]);
  exit;
}

$group = $u['group'] ?? null;
if (!$group) {
  echo json_encode(['ok'=>false,'error'=>'missing_group']);
  exit;
}

$baseDir = realpath(__DIR__ . '/../data/settings');
if (!$baseDir) {
  @mkdir(__DIR__ . '/../data/settings', 0777, true);
  $baseDir = realpath(__DIR__ . '/../data/settings');
}
$path = $baseDir . DIRECTORY_SEPARATOR . $group . '.json';

$default = [
  'version' => 1,
  'rules' => [
    'pizza' => [
      'enabled' => true,
      'dayIndex' => 5,
      'meal' => 'dinner',
      'text' => 'Pizza, insalata mista'
    ],
    'freeMeal' => [
      'enabled' => true,
      'dayIndex' => 6,
      'meal' => 'lunch',
      'text' => 'LIBERO'
    ],
  ]
];

if (!file_exists($path)) {
  echo json_encode(['ok'=>true,'logged'=>true,'settings'=>$default]);
  exit;
}

$raw = @file_get_contents($path);
if ($raw === false) {
  echo json_encode(['ok'=>false,'error'=>'read_failed']);
  exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
  echo json_encode(['ok'=>true,'logged'=>true,'settings'=>$default,'warning'=>'invalid_json_reset_default']);
  exit;
}

echo json_encode(['ok'=>true,'logged'=>true,'settings'=>$data]);
