<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}

$result = $pdo->query('SELECT COUNT(*) as total FROM classe');
$row = $result->fetch(PDO::FETCH_ASSOC);
echo '<pre>Total classes: ' . $row['total'] . "\n\n";

$result3 = $pdo->query('SELECT id, nom_classe, niveau FROM classe ORDER BY id');
echo "Liste des classes:\n";
while ($class = $result3->fetch(PDO::FETCH_ASSOC)) {
  echo '  - ' . $class['id'] . ': ' . $class['nom_classe'] . ' (' . $class['niveau'] . ")\n";
}
echo '</pre>';
