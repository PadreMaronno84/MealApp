<?php
// set_active_group.php — imposta il gruppo attivo nella sessione del superadmin
// Solo superadmin. Usato dal pannello di sistema per "entrare" in un gruppo.
// Passare group:"" o group:null per uscire dal gruppo attivo.
require __DIR__ . '/common.php';
$me = require_superadmin();
verify_csrf();

$data  = read_json_body();
$group = safe_name((string)($data['group'] ?? ''));

if ($group === '' || $group === 'x') {
  // Scollega dal gruppo attivo
  unset($_SESSION['active_group']);
  json_out(['ok'=>true, 'active_group'=>null]);
}

// Verifica che il gruppo esista (almeno un utente registrato)
$exists = false;
foreach (load_users() as $u) {
  if (safe_name((string)($u['group'] ?? '')) === $group) {
    $exists = true;
    break;
  }
}
if (!$exists) json_out(['ok'=>false,'error'=>'group_not_found'], 404);

$_SESSION['active_group'] = $group;
json_out(['ok'=>true, 'active_group'=>$group]);
