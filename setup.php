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

$admin = [
  'username'      => 'admin',
  'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
  'role'          => 'admin',
  'group'         => 'A',
];

$ok = file_put_contents($usersFile, json_encode(['users' => [$admin]], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
if ($ok === false) {
  echo "ERRORE: impossibile scrivere $usersFile\n";
  exit(1);
}

echo "Creato: $usersFile\n";
echo "Utente: admin / admin123 / gruppo A\n";
echo "IMPORTANTE: cambia la password dopo il primo accesso!\n";
