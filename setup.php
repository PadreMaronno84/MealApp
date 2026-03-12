<?php
// setup.php — esegui una volta per inizializzare storage/users.json
// Accedi via browser o CLI: php setup.php
header('Content-Type: text/plain; charset=utf-8');

$storageDir = __DIR__ . '/storage';
if (!is_dir($storageDir)) mkdir($storageDir, 0775, true);

$usersFile = $storageDir . '/users.json';
if (file_exists($usersFile)) {
  echo "users.json esiste già. Nessuna modifica.\n";
  echo "Percorso: $usersFile\n";
  exit;
}

// Superadmin di sistema (non appartiene a nessun gruppo)
$superadmin = [
  'username'      => 'superadmin',
  'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
  'role'          => 'superadmin',
  'group'         => null,
];

$ok = file_put_contents($usersFile, json_encode(['users' => [$superadmin]], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
if ($ok === false) {
  echo "ERRORE: impossibile scrivere $usersFile\n";
  exit(1);
}

echo "Creato: $usersFile\n";
echo "Superadmin: superadmin / admin123\n";
echo "IMPORTANTE: cambia la password dopo il primo accesso!\n";
echo "Dal pannello di sistema puoi creare i tuoi gruppi e i loro admin.\n";
