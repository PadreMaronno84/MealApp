<?php
require __DIR__ . '/common.php';

$me = require_login();
$group = get_effective_group($me);

$dir = storage_base() . '/settings';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$path = $dir . '/' . $group . '.json';

$default = [
  'version' => 2,
  'rules' => [
    'pizza'    => ['enabled'=>true,  'days'=>[5], 'meal'=>'dinner', 'text'=>'Pizza, insalata mista'],
    'freeMeal' => ['enabled'=>true,  'days'=>[6], 'meal'=>'lunch',  'text'=>'LIBERO'],
    'season'   => ['enabled'=>true],
  ],
  'invite_code' => strtoupper(bin2hex(random_bytes(4)))
];

if (!is_file($path)) {
  file_put_contents($path, json_encode($default, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  json_out(['ok'=>true, 'settings'=>$default]);
}

$raw = @file_get_contents($path);
if ($raw === false) json_out(['ok'=>false,'error'=>'read_failed']);

$data = json_decode($raw, true);
if (!is_array($data)) json_out(['ok'=>true,'settings'=>$default,'warning'=>'invalid_json_reset_default']);

// Auto-genera invite_code se mancante
if (empty($data['invite_code'])) {
  $data['invite_code'] = strtoupper(bin2hex(random_bytes(4)));
  file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

// Backward compat: migra dayIndex -> days per pizza e freeMeal
$migrated = false;
foreach (['pizza','freeMeal'] as $k) {
  if (!isset($data['rules'][$k])) continue;
  $rule = &$data['rules'][$k];
  if (!array_key_exists('days', $rule)) {
    $rule['days'] = isset($rule['dayIndex']) ? [intval($rule['dayIndex'])] : [];
    unset($rule['dayIndex']);
    $migrated = true;
  }
}
// Aggiunge regola season se mancante
if (!isset($data['rules']['season'])) {
  $data['rules']['season'] = ['enabled'=>true];
  $migrated = true;
}
if ($migrated) {
  $data['version'] = 2;
  file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
}

json_out(['ok'=>true,'settings'=>$data]);
