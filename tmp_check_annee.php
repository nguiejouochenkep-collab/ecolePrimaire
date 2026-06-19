<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}

$stmt = $pdo->query('SELECT id, libelle, statut FROM annee_scolaire ORDER BY id');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo '<pre>Années scolaires:' . "\n";
foreach ($rows as $row) {
    echo '  ID=' . $row['id'] . ', LIBELLE=' . $row['libelle'] . ', STATUT=' . ($row['statut'] ?? 'N/A') . "\n";
}
echo '</pre>';
