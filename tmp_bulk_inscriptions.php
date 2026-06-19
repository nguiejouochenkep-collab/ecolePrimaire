<?php
require_once __DIR__ . '/modeles/bdd.php';
$pdo = Bdd::connexion();

// Get 2025-2026 year ID
$year = $pdo->query("SELECT id FROM annee_scolaire WHERE libelle = '2025-2026' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$year) {
    echo json_encode(['error' => 'Année 2025-2026 non trouvée']);
    exit;
}
$year_id = $year['id'];

// Get all students with their classes (no statut filter, that column may not exist)
$eleves = $pdo->query("SELECT DISTINCT e.matricule, e.id_classe FROM eleve e")->fetchAll(PDO::FETCH_ASSOC);

if (empty($eleves)) {
    echo json_encode(['error' => 'Aucun élève actif trouvé']);
    exit;
}

// Check existing inscriptions to avoid duplicates
$existing = $pdo->prepare("SELECT matricule_eleve FROM inscription WHERE id_annee = :year_id");
$existing->execute(['year_id' => $year_id]);
$existingMats = array_column($existing->fetchAll(PDO::FETCH_ASSOC), 'matricule_eleve');

// Insert only non-existing inscriptions
$stmt = $pdo->prepare("INSERT INTO inscription (matricule_eleve, id_classe, id_annee, date_inscription) VALUES (:matricule, :classe, :year_id, CURDATE())");
$inserted = 0;
$skipped = 0;

$pdo->beginTransaction();
foreach ($eleves as $eleve) {
    if (in_array($eleve['matricule'], $existingMats)) {
        $skipped++;
        continue;
    }
    try {
        $stmt->execute([
            'matricule' => $eleve['matricule'],
            'classe' => $eleve['id_classe'],
            'year_id' => $year_id
        ]);
        $inserted++;
    } catch (Exception $e) {
        error_log("Erreur inscription: " . $e->getMessage());
    }
}
$pdo->commit();

// Verify
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE id_annee = :year_id");
$stmtCount->execute(['year_id' => $year_id]);
$total = $stmtCount->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'year_id' => (int)$year_id,
    'year_libelle' => '2025-2026',
    'total_eleves_actifs' => count($eleves),
    'inserted' => $inserted,
    'skipped_existing' => $skipped,
    'total_inscriptions_after' => (int)$total
], JSON_PRETTY_PRINT);
