<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$data = read_json_body();

$rNew = plan_range($data);
if (!$rNew) json_out(['ok'=>false,'error'=>'invalid_payload'], 400);

// Validazione: startMondayISO deve essere un lunedì (N=1 in ISO-8601)
try {
  $checkDay = new DateTime(($data['startMondayISO'] ?? '') . ' 00:00:00');
  if ((int)$checkDay->format('N') !== 1) {
    json_out(['ok'=>false,'error'=>'not_a_monday'], 400);
  }
} catch (Exception $e) {
  json_out(['ok'=>false,'error'=>'invalid_date'], 400);
}

$dir = group_saved_dir();

// pulizia prima di controllare overlap (così non blocca con file vecchi)
cleanup_expired_plans($dir);

// controllo overlap
$files = glob($dir . '/*.json') ?: [];
foreach ($files as $f) {
  $raw = @file_get_contents($f);
  $old = json_decode($raw ?: '', true);
  if (!is_array($old)) continue;

  $rOld = plan_range($old);
  if (!$rOld) continue;

  if (ranges_overlap($rNew['start'], $rNew['end'], $rOld['start'], $rOld['end'])) {
    json_out([
      'ok'=>false,
      'error'=>'date_overlap',
      'details'=>[
        'newStart'=>$rNew['startISO'],
        'newEnd'=>$rNew['endISO'],
        'conflictId'=>basename($f, '.json'),
        'conflictLabel'=>$old['displayLabel'] ?? basename($f, '.json'),
        'conflictStart'=>$rOld['startISO'],
        'conflictEnd'=>$rOld['endISO'],
      ]
    ], 409);
  }
}

// id unico
$unique = (new DateTime())->format('Ymd_His') . '_' . bin2hex(random_bytes(3));
$id = safe_name("DAL_{$rNew['startISO']}_AL_{$rNew['endISO']}_{$unique}");

$file = $dir . '/' . $id . '.json';

$data['id'] = $id;
$data['createdAt'] = (new DateTime())->format(DateTime::ATOM);
$data['createdBy'] = $me['username'];
$data['group'] = get_effective_group($me);

$raw = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
if (@file_put_contents($file, $raw) === false) json_out(['ok'=>false,'error'=>'write_failed'], 500);

log_activity(get_effective_group($me), $me['username'], 'piano_salvato', [
  'id' => $id, 'label' => $data['displayLabel'] ?? '',
  'start' => $rNew['startISO'], 'end' => $rNew['endISO'],
]);

json_out(['ok'=>true,'id'=>$id]);
