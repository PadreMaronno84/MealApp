<?php
// backup.php — esporta tutti i dati del gruppo (piani, piano alimentare, impostazioni)
// Solo admin. Restituisce un file JSON da scaricare.
require __DIR__ . '/common.php';
$me = require_admin();

$group = safe_name((string)($me['group'] ?? ''));
$base  = storage_base();

// Raccolta piani
$savedDir = $base . '/saved/' . $group;
$plans = [];
if (is_dir($savedDir)) {
  foreach (glob($savedDir . '/*.json') ?: [] as $f) {
    $raw = @file_get_contents($f);
    $j   = json_decode($raw ?: '', true);
    if (is_array($j)) $plans[] = $j;
  }
}

// CSV coppie
$csvPath    = $base . '/uploads/' . $group . '/day_pairs.csv';
$pairsCsv   = is_file($csvPath) ? @file_get_contents($csvPath) : null;

// Impostazioni
$settingsPath = $base . '/settings/' . $group . '.json';
$settings     = null;
if (is_file($settingsPath)) {
  $settings = json_decode(@file_get_contents($settingsPath), true);
}

$backup = [
  'version'     => 1,
  'group'       => $group,
  'exported_at' => (new DateTime())->format(DateTime::ATOM),
  'exported_by' => $me['username'],
  'plans'       => $plans,
  'pairs_csv'   => $pairsCsv,
  'settings'    => $settings,
];

$filename = 'backup-' . $group . '-' . date('Ymd_His') . '.json';
$json     = json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

log_activity($group, $me['username'], 'backup_esportato', ['plans_count' => count($plans)]);

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($json));
echo $json;
exit;
