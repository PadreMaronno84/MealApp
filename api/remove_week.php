<?php
require __DIR__ . '/common.php';
require_admin();
verify_csrf();

$data = read_json_body();
$id = safe_name((string)($data['id'] ?? ''));
$weekIndex = $data['weekIndex'] ?? null;

if ($id === '') json_out(['ok'=>false,'error'=>'missing_id'], 400);
if (!is_int($weekIndex) && !ctype_digit((string)$weekIndex)) json_out(['ok'=>false,'error'=>'bad_week_index'], 400);
$weekIndex = (int)$weekIndex;

$dir = group_saved_dir();
$file = $dir . '/' . $id . '.json';
if (!is_file($file)) json_out(['ok'=>false,'error'=>'not_found'], 404);

$raw = file_get_contents($file) ?: '';
$plan = json_decode($raw, true);
if (!is_array($plan) || !isset($plan['weeks']) || !is_array($plan['weeks'])) {
  json_out(['ok'=>false,'error'=>'invalid_plan'], 400);
}

$weeks = $plan['weeks'];
$n = count($weeks);
if ($n <= 1) json_out(['ok'=>false,'error'=>'cannot_remove_last_week'], 400);
if ($weekIndex < 0 || $weekIndex >= $n) json_out(['ok'=>false,'error'=>'week_out_of_range'], 400);

function make_label(string $startISO, int $weeksLen): string {
  $start = new DateTime($startISO . ' 00:00:00');
  $end = clone $start;
  $end->modify('+' . (($weeksLen * 7) - 1) . ' day');

  $months = ["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"];
  $fmt = function(DateTime $d) use ($months) {
    $dd = $d->format('d');
    $m = $months[((int)$d->format('n')) - 1];
    $y = $d->format('Y');
    return "$dd $m $y";
  };
  return "DAL " . $fmt($start) . " AL " . $fmt($end);
}

function new_id(string $startISO, string $endISO): string {
  $unique = (new DateTime())->format('Ymd_His') . '_' . bin2hex(random_bytes(3));
  return safe_name("DAL_{$startISO}_AL_{$endISO}_{$unique}");
}

$r = plan_range($plan);
if (!$r) json_out(['ok'=>false,'error'=>'invalid_plan_range'], 400);

// caso 1: rimuovo prima o ultima -> aggiorno file
if ($weekIndex === 0 || $weekIndex === $n-1) {
  array_splice($weeks, $weekIndex, 1);
  $plan['weeks'] = $weeks;

  // aggiorna startMondayISO se ho tolto la prima
  if ($weekIndex === 0) {
    $plan['startMondayISO'] = (string)($weeks[0]['weekStartISO'] ?? $plan['startMondayISO']);
  }

  $r2 = plan_range($plan);
  if (!$r2) json_out(['ok'=>false,'error'=>'invalid_after_remove'], 500);

  $plan['displayLabel'] = make_label($r2['startISO'], $r2['weeksLen']);
  $plan['updatedAt'] = (new DateTime())->format(DateTime::ATOM);

  $out = json_encode($plan, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
  if (@file_put_contents($file, $out) === false) json_out(['ok'=>false,'error'=>'write_failed'], 500);

  json_out(['ok'=>true,'mode'=>'updated','id'=>$id]);
}

// caso 2: rimuovo in mezzo -> split in 2 file
$leftWeeks = array_slice($weeks, 0, $weekIndex);
$rightWeeks = array_slice($weeks, $weekIndex + 1);

$created = [];

if (count($leftWeeks) > 0) {
  $p = $plan;
  $p['weeks'] = $leftWeeks;
  $p['startMondayISO'] = (string)($leftWeeks[0]['weekStartISO'] ?? $plan['startMondayISO']);

  $rr = plan_range($p);
  if ($rr) {
    $newId = new_id($rr['startISO'], $rr['endISO']);
    $p['id'] = $newId;
    $p['displayLabel'] = make_label($rr['startISO'], $rr['weeksLen']);
    $p['createdAt'] = (new DateTime())->format(DateTime::ATOM);
    $p['createdBy'] = $plan['createdBy'] ?? null;
    $p['group'] = $plan['group'] ?? null;

    $out = json_encode($p, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    @file_put_contents($dir . '/' . $newId . '.json', $out);
    $created[] = $newId;
  }
}

if (count($rightWeeks) > 0) {
  $p = $plan;
  $p['weeks'] = $rightWeeks;
  $p['startMondayISO'] = (string)($rightWeeks[0]['weekStartISO'] ?? $plan['startMondayISO']);

  $rr = plan_range($p);
  if ($rr) {
    $newId = new_id($rr['startISO'], $rr['endISO']);
    $p['id'] = $newId;
    $p['displayLabel'] = make_label($rr['startISO'], $rr['weeksLen']);
    $p['createdAt'] = (new DateTime())->format(DateTime::ATOM);
    $p['createdBy'] = $plan['createdBy'] ?? null;
    $p['group'] = $plan['group'] ?? null;

    $out = json_encode($p, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    @file_put_contents($dir . '/' . $newId . '.json', $out);
    $created[] = $newId;
  }
}

// cancella originale
@unlink($file);

json_out(['ok'=>true,'mode'=>'split','deleted'=>$id,'created'=>$created]);
