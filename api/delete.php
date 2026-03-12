<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$data = read_json_body();
$id = safe_name((string)($data['id'] ?? ''));
if ($id === '') json_out(['ok'=>false,'error'=>'missing_id'], 400);

$dir = group_saved_dir();
$file = $dir . '/' . $id . '.json';

if (!is_file($file)) json_out(['ok'=>false,'error'=>'not_found'], 404);

@unlink($file);
log_activity(get_effective_group($me), $me['username'], 'piano_eliminato', ['id' => $id]);
json_out(['ok'=>true]);
