<?php
require __DIR__ . '/common.php';
require_admin();

$id = safe_name((string)($_GET['id'] ?? ''));
if ($id === '') json_out(['ok'=>false,'error'=>'missing_id'], 400);

$dir = group_saved_dir();
$file = $dir . '/' . $id . '.json';

if (!is_file($file)) json_out(['ok'=>false,'error'=>'not_found'], 404);

@unlink($file);
json_out(['ok'=>true]);
