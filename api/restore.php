<?php
require __DIR__ . '/common.php';
$me = require_admin();
verify_csrf();

$group = safe_name((string)($me['group'] ?? ''));
$base  = storage_base();

// Accetta sia upload file sia JSON body
$raw = null;
if (!empty($_FILES['file'])) {
  $f = $_FILES['file'];
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) json_out(['ok'=>false,'error'=>'upload_error'], 400);
  if (!is_uploaded_file($f['tmp_name'] ?? ''))           json_out(['ok'=>false,'error'=>'bad_upload'], 400);
  $raw = @file_get_contents($f['tmp_name']);
} else {
  $raw = file_get_contents('php://input');
}

if (!$raw) json_out(['ok'=>false,'error'=>'empty_payload'], 400);

$backup = json_decode($raw, true);
if (!is_array($backup) || ($backup['version'] ?? 0) !== 1) {
  json_out(['ok'=>false,'error'=>'invalid_backup_format'], 400);
}

// Sicurezza: il backup deve essere dello stesso gruppo
$backupGroup = safe_name((string)($backup['group'] ?? ''));
if ($backupGroup !== $group) {
  json_out(['ok'=>false,'error'=>'group_mismatch', 'backup_group'=>$backupGroup, 'current_group'=>$group], 400);
}

$restored = ['plans'=>0, 'plans_skipped'=>0];

// Ripristino piani (merge: non sovrascrive ID già esistenti)
if (!empty($backup['plans']) && is_array($backup['plans'])) {
  $savedDir = $base . '/saved/' . $group;
  ensure_dir($savedDir);
  foreach ($backup['plans'] as $plan) {
    if (!is_array($plan) || empty($plan['id'])) continue;
    $id   = safe_name((string)$plan['id']);
    $dest = $savedDir . '/' . $id . '.json';
    if (is_file($dest)) { $restored['plans_skipped']++; continue; }
    @file_put_contents($dest, json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $restored['plans']++;
  }
}

// Ripristino CSV coppie (sovrascrive)
if (isset($backup['pairs_csv']) && is_string($backup['pairs_csv'])) {
  $uploadsDir = $base . '/uploads/' . $group;
  ensure_dir($uploadsDir);
  @file_put_contents($uploadsDir . '/day_pairs.csv', $backup['pairs_csv']);
  $restored['csv'] = true;
}

// Ripristino impostazioni (sovrascrive, ma preserva invite_code corrente)
if (!empty($backup['settings']) && is_array($backup['settings'])) {
  $settingsDir  = $base . '/settings';
  @mkdir($settingsDir, 0775, true);
  $settingsPath = $settingsDir . '/' . $group . '.json';

  $newSettings = $backup['settings'];
  // Preserva l'invite_code esistente per non invalidare chi ha già il codice
  if (is_file($settingsPath)) {
    $cur = json_decode(@file_get_contents($settingsPath), true);
    if (!empty($cur['invite_code'])) $newSettings['invite_code'] = $cur['invite_code'];
  }
  @file_put_contents($settingsPath, json_encode($newSettings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  $restored['settings'] = true;
}

log_activity($group, $me['username'], 'backup_ripristinato', $restored);
json_out(['ok'=>true, 'restored'=>$restored]);
