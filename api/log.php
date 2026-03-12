<?php
require __DIR__ . '/common.php';
$me = require_admin();

$group = safe_name((string)($me['group'] ?? ''));
$file  = storage_base() . '/logs/' . $group . '.jsonl';

if (!is_file($file)) {
  json_out(['ok'=>true, 'items'=>[]]);
}

$lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
// ultimi 100 eventi, ordine cronologico inverso
$lines = array_reverse(array_slice($lines, -100));

$items = [];
foreach ($lines as $line) {
  $j = json_decode($line, true);
  if (is_array($j)) $items[] = $j;
}

json_out(['ok'=>true, 'items'=>$items]);
