<?php
require __DIR__ . '/common.php';
require_login();

$dir = group_saved_dir();

// pulizia automatica
cleanup_expired_plans($dir);

$files = glob($dir . '/*.json') ?: [];

$out = [];
foreach ($files as $f) {
  $raw = @file_get_contents($f);
  $j = json_decode($raw ?: '', true);
  if (!is_array($j)) continue;

  $r = plan_range($j);
  $out[] = [
    'id'        => basename($f, '.json'),
    'label'     => $j['displayLabel'] ?? basename($f, '.json'),
    'createdAt' => $j['createdAt'] ?? null,
    'createdBy' => $j['createdBy'] ?? null,
    'startISO'  => $r ? $r['startISO'] : null,
    'endISO'    => $r ? $r['endISO']   : null,
  ];
}

usort($out, function($a,$b){
  return strcmp(($b['createdAt'] ?? ''), ($a['createdAt'] ?? ''));
});

json_out(['ok'=>true,'items'=>$out]);
