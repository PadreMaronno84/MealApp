<?php
require __DIR__ . '/common.php';

$me = require_admin();
verify_csrf();
$group = get_effective_group($me);

$payload = read_json_body();
$settings = $payload['settings'] ?? null;
if (!is_array($settings)) json_out(['ok'=>false,'error'=>'missing_settings']);

$dir = storage_base() . '/settings';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$path = $dir . '/' . $group . '.json';

$settings['version'] = 2;

$rules = $settings['rules'] ?? [];

// Valida pizza e freeMeal
foreach (['pizza','freeMeal'] as $k) {
  if (!isset($rules[$k])) continue;
  // days: array di interi 0-6
  $rawDays = is_array($rules[$k]['days'] ?? null) ? $rules[$k]['days'] : [];
  $days = [];
  foreach ($rawDays as $d) {
    $d = intval($d);
    if ($d >= 0 && $d <= 6 && !in_array($d, $days)) $days[] = $d;
  }
  sort($days);
  $rules[$k]['days'] = $days;
  unset($rules[$k]['dayIndex']); // rimuovi campo legacy
  $meal = $rules[$k]['meal'] ?? 'dinner';
  $rules[$k]['meal'] = ($meal === 'lunch') ? 'lunch' : 'dinner';
  $rules[$k]['enabled'] = !empty($rules[$k]['enabled']);
  $rules[$k]['text'] = trim((string)($rules[$k]['text'] ?? ''));
}

// Valida season
$rules['season']['enabled'] = !empty($rules['season']['enabled'] ?? false);

$settings['rules'] = $rules;

$ok = @file_put_contents($path, json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
if ($ok === false) json_out(['ok'=>false,'error'=>'write_failed']);

log_activity($group, $me['username'], 'impostazioni_salvate', []);
json_out(['ok'=>true]);
