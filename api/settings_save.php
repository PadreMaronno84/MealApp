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
if (!$u) { echo json_encode(['ok'=>false,'error'=>'not_logged']); exit; }

if (($u['role'] ?? '') !== 'admin') {
  echo json_encode(['ok'=>false,'error'=>'forbidden']);
  exit;
}

$group = $u['group'] ?? null;
if (!$group) { echo json_encode(['ok'=>false,'error'=>'missing_group']); exit; }

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) { echo json_encode(['ok'=>false,'error'=>'bad_json']); exit; }

$settings = $payload['settings'] ?? null;
if (!is_array($settings)) { echo json_encode(['ok'=>false,'error'=>'missing_settings']); exit; }

$baseDir = realpath(__DIR__ . '/../data/settings');
if (!$baseDir) {
  @mkdir(__DIR__ . '/../data/settings', 0777, true);
  $baseDir = realpath(__DIR__ . '/../data/settings');
}
$path = $baseDir . DIRECTORY_SEPARATOR . $group . '.json';

$settings['version'] = $settings['version'] ?? 1;

$rules = $settings['rules'] ?? [];
foreach (['pizza','freeMeal'] as $k) {
  if (!isset($rules[$k])) continue;
  $day = $rules[$k]['dayIndex'] ?? 0;
  if (!is_int($day)) $day = intval($day);
  if ($day < 0) $day = 0;
  if ($day > 6) $day = 6;
  $rules[$k]['dayIndex'] = $day;

  $meal = $rules[$k]['meal'] ?? 'dinner';
  $rules[$k]['meal'] = ($meal === 'lunch') ? 'lunch' : 'dinner';

  $rules[$k]['enabled'] = !empty($rules[$k]['enabled']);
  $rules[$k]['text'] = trim((string)($rules[$k]['text'] ?? ''));
}
$settings['rules'] = $rules;

$ok = @file_put_contents($path, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
if ($ok === false) { echo json_encode(['ok'=>false,'error'=>'write_failed']); exit; }

echo json_encode(['ok'=>true]);
