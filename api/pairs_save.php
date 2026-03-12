<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$data = read_json_body();
$pairs = $data['pairs'] ?? null;
if (!is_array($pairs)) json_out(['ok'=>false,'error'=>'missing_pairs'], 400);

$valid_seasons = ['PRI','EST','AUT','INV',''];
$clean = [];
foreach ($pairs as $p) {
  if (!is_array($p)) continue;
  $l = trim((string)($p['lunch']  ?? ''));
  $d = trim((string)($p['dinner'] ?? ''));
  if ($l === '' && $d === '') continue;
  $s = strtoupper(trim((string)($p['season'] ?? '')));
  if (!in_array($s, $valid_seasons, true)) $s = '';
  $clean[] = ['lunch' => $l, 'dinner' => $d, 'season' => $s];
}

if (empty($clean)) json_out(['ok'=>false,'error'=>'no_valid_pairs'], 400);

$uploads = group_uploads_dir();
$file    = $uploads . '/day_pairs.csv';

$lines = ['lunch,dinner,season'];
foreach ($clean as $p) {
  $l = str_replace('"', '""', $p['lunch']);
  $d = str_replace('"', '""', $p['dinner']);
  $s = str_replace('"', '""', $p['season']);
  $lines[] = '"' . $l . '","' . $d . '","' . $s . '"';
}

$ok = file_put_contents($file, implode("\n", $lines) . "\n");
if ($ok === false) json_out(['ok'=>false,'error'=>'write_failed'], 500);

json_out(['ok'=>true, 'count'=>count($clean)]);
