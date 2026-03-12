<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

if (!isset($_FILES['file'])) json_out(['ok'=>false,'error'=>'missing_file'], 400);

$f = $_FILES['file'];
if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_out(['ok'=>false,'error'=>'upload_error'], 400);

$name = (string)($f['name'] ?? '');
if (!preg_match('/\.csv$/i', $name)) json_out(['ok'=>false,'error'=>'only_csv'], 400);

$tmp = (string)($f['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) json_out(['ok'=>false,'error'=>'bad_upload'], 400);

$mode    = (string)($_POST['mode'] ?? 'replace');
$uploads = group_uploads_dir();
$dest    = $uploads . '/day_pairs.csv';

/**
 * Parsa un file CSV in array di ['lunch','dinner','season'].
 * Gestisce header opzionale e righe senza colonna season.
 */
function parse_pairs_csv(string $path): array {
  $pairs = [];
  $fh = @fopen($path, 'r');
  if (!$fh) return $pairs;

  $valid_seasons = ['PRI','EST','AUT','INV',''];
  $firstRow = true;

  while (($row = fgetcsv($fh)) !== false) {
    if (!is_array($row) || count($row) < 2) continue;

    $lunch  = trim($row[0] ?? '');
    $dinner = trim($row[1] ?? '');
    $season = strtoupper(trim($row[2] ?? ''));

    // Salta header
    if ($firstRow && strtolower($lunch) === 'lunch') { $firstRow = false; continue; }
    $firstRow = false;

    if ($lunch === '' && $dinner === '') continue;
    if (!in_array($season, $valid_seasons, true)) $season = '';

    $pairs[] = ['lunch' => $lunch, 'dinner' => $dinner, 'season' => $season];
  }
  fclose($fh);
  return $pairs;
}

function write_pairs_csv(string $path, array $pairs): bool {
  $lines = ['lunch,dinner,season'];
  foreach ($pairs as $p) {
    $l = str_replace('"', '""', $p['lunch']);
    $d = str_replace('"', '""', $p['dinner']);
    $s = str_replace('"', '""', $p['season']);
    $lines[] = '"' . $l . '","' . $d . '","' . $s . '"';
  }
  return file_put_contents($path, implode("\n", $lines) . "\n") !== false;
}

if ($mode === 'append' && is_file($dest)) {
  // Legge le coppie esistenti + nuove, deduplica su lunch+dinner+season
  $existing = parse_pairs_csv($dest);
  $newPairs = parse_pairs_csv($tmp);

  // Indice per deduplicazione rapida
  $keys = [];
  foreach ($existing as $p) {
    $keys[strtolower($p['lunch'] . '|||' . $p['dinner'] . '|||' . $p['season'])] = true;
  }

  $added = 0;
  foreach ($newPairs as $p) {
    $k = strtolower($p['lunch'] . '|||' . $p['dinner'] . '|||' . $p['season']);
    if (!isset($keys[$k])) {
      $existing[] = $p;
      $keys[$k]   = true;
      $added++;
    }
  }

  if (!write_pairs_csv($dest, $existing)) json_out(['ok'=>false,'error'=>'write_failed'], 500);

  log_activity($me['group'], $me['username'], 'piano_alimentare_aggiunto', [
    'filename' => $name, 'added' => $added, 'total' => count($existing)
  ]);
  json_out(['ok'=>true, 'mode'=>'append', 'added'=>$added, 'total'=>count($existing)]);

} else {
  // Sostituisce tutto (comportamento originale)
  if (!move_uploaded_file($tmp, $dest)) json_out(['ok'=>false,'error'=>'move_failed'], 500);

  $total = count(parse_pairs_csv($dest));
  log_activity($me['group'], $me['username'], 'piano_alimentare_sostituito', [
    'filename' => $name, 'total' => $total
  ]);
  json_out(['ok'=>true, 'mode'=>'replace', 'total'=>$total]);
}
