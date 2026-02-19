<?php
require __DIR__ . '/common.php';
require_admin();

if (!isset($_FILES['file'])) json_out(['ok'=>false,'error'=>'missing_file'], 400);

$f = $_FILES['file'];
if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_out(['ok'=>false,'error'=>'upload_error'], 400);

$name = (string)($f['name'] ?? '');
if (!preg_match('/\.csv$/i', $name)) json_out(['ok'=>false,'error'=>'only_csv'], 400);

$tmp = (string)($f['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) json_out(['ok'=>false,'error'=>'bad_upload'], 400);

$uploads = group_uploads_dir();
$dest = $uploads . '/day_pairs.csv';

if (!move_uploaded_file($tmp, $dest)) json_out(['ok'=>false,'error'=>'move_failed'], 500);

json_out(['ok'=>true]);
