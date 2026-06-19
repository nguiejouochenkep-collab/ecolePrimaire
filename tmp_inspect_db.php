<?php
require_once __DIR__ . '/modeles/bdd.php';
$pdo = Bdd::connexion();

// Total élèves
$total = $pdo->query("SELECT COUNT(*) FROM eleve")->fetchColumn();
// Vérifier si la colonne statut_activite existe
$hasStatut = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM eleve LIKE 'statut_activite'")->fetchAll();
    $hasStatut = count($chk) > 0;
} catch (Exception $e) {
    $hasStatut = false;
}

if ($hasStatut) {
    $total_actifs = $pdo->query("SELECT COUNT(*) FROM eleve WHERE statut_activite = 'actif'")->fetchColumn();
} else {
    $total_actifs = $total;
}

// Years and inscription counts
$stmt = $pdo->query("SELECT a.id, a.libelle, a.statut, COUNT(i.matricule_eleve) AS inscriptions
FROM annee_scolaire a
LEFT JOIN inscription i ON i.id_annee = a.id
GROUP BY a.id, a.libelle, a.statut
ORDER BY a.id DESC");
$years = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine which year to inspect (optional GET libelle)
$check_libelle = isset($_GET['libelle']) ? $_GET['libelle'] : null;
$active = null;
if ($check_libelle) {
    $stmt = $pdo->prepare("SELECT id, libelle FROM annee_scolaire WHERE libelle = :libelle LIMIT 1");
    $stmt->execute(['libelle' => $check_libelle]);
    $active = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $active = $pdo->query("SELECT id, libelle FROM annee_scolaire WHERE statut = 'active' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// Sample élèves without inscription for the inspected year
$samples = [];
if ($active) {
    $stmt = $pdo->prepare("SELECT e.matricule, e.nom, e.prenom FROM eleve e
    LEFT JOIN inscription i ON i.matricule_eleve = e.matricule AND i.id_annee = :id
    WHERE i.matricule_eleve IS NULL LIMIT 50");
    $stmt->execute(['id' => $active['id']]);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode([
    'total_eleves' => (int)$total,
    'total_actifs' => (int)$total_actifs,
    'annees' => $years,
    'inspected_annee' => $active,
    'samples_eleves_without_inscription_for_inspected_annee' => $samples
], JSON_PRETTY_PRINT);
