<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$group = safe_name((string)($me['group'] ?? ''));
$dir   = storage_base() . '/settings';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$path  = $dir . '/' . $group . '.json';

$data = [];
if (is_file($path)) {
  $data = json_decode(@file_get_contents($path), true) ?: [];
}

$data['stats_reset_at'] = gmdate('c');

if (file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) === false) {
  json_out(['ok'=>false,'error'=>'write_failed'], 500);
}

log_activity($group, $me['username'], 'statistiche_azzerate', []);
json_out(['ok'=>true, 'reset_at'=>$data['stats_reset_at']]);
