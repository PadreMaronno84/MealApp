<?php
require __DIR__ . '/common.php';
require_login();

$uploads = group_uploads_dir();
$file = $uploads . '/day_pairs.csv';

if (!is_file($file)) json_out(['ok'=>true, 'exists'=>false, 'pairs'=>[]]);

$raw = file_get_contents($file) ?: '';
$lines = preg_split("/\r\n|\n|\r/", $raw);

if (!$lines || count($lines) < 2) json_out(['ok'=>true, 'exists'=>true, 'pairs'=>[]]);

$header = str_getcsv($lines[0]);
$idxLunch  = array_search('lunch', $header, true);
$idxDinner = array_search('dinner', $header, true);

if ($idxLunch === false || $idxDinner === false) {
  json_out(['ok'=>false,'error'=>'csv_headers_need_lunch_dinner'], 400);
}

$pairs = [];
for ($i=1; $i<count($lines); $i++){
  $line = trim($lines[$i]);
  if ($line === '') continue;
  $cols = str_getcsv($line);
  $l = trim((string)($cols[$idxLunch] ?? ''));
  $d = trim((string)($cols[$idxDinner] ?? ''));
  if ($l === '' && $d === '') continue;
  $pairs[] = ['lunch'=>$l, 'dinner'=>$d];
}

json_out(['ok'=>true, 'exists'=>true, 'pairs'=>$pairs]);
