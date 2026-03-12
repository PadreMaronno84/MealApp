<?php
require __DIR__ . '/common.php';
$me = require_login();

$group = safe_name((string)($me['group'] ?? ''));

// Legge eventuale timestamp di reset dalle impostazioni gruppo
$settingsPath = storage_base() . '/settings/' . $group . '.json';
$settings     = is_file($settingsPath) ? (json_decode(@file_get_contents($settingsPath), true) ?: []) : [];
$resetAt      = $settings['stats_reset_at'] ?? null;

$dir    = group_saved_dir();
$files  = glob($dir . '/*.json') ?: [];
$counts = [];

foreach ($files as $f) {
  $plan = json_decode(@file_get_contents($f), true);
  if (!is_array($plan)) continue;

  // Salta i piani salvati prima del reset
  if ($resetAt && isset($plan['createdAt']) && $plan['createdAt'] < $resetAt) continue;

  foreach (($plan['weeks'] ?? []) as $week) {
    foreach (($week['days'] ?? []) as $day) {
      $l = trim((string)($day['lunch']  ?? ''));
      $d = trim((string)($day['dinner'] ?? ''));
      if ($l === '' && $d === '') continue;
      $key = $l . '|||' . $d;
      $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
  }
}

arsort($counts);

$items = [];
foreach ($counts as $key => $cnt) {
  [$lunch, $dinner] = explode('|||', $key, 2);
  $items[] = ['lunch' => $lunch, 'dinner' => $dinner, 'count' => $cnt];
}

json_out(['ok' => true, 'items' => $items, 'total_days' => array_sum($counts), 'reset_at' => $resetAt]);
