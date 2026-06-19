<?php
// Test simple de promotion
session_start();
$_SESSION['annee_scolaire_id'] = 1;
$_SESSION['annee_scolaire_libelle'] = '2025-2026';
$_SESSION['role'] = 'directeur';

require_once('modeles/Eleve.php');
require_once('modeles/bdd.php');

$bdd = new Bdd();
$pdo = $bdd->connexion();
$eleveModele = new Eleve($pdo);

echo "=== TEST DE PROMOTION TMP017 ===\n\n";

// Étape 1: Vérifier la moyenne
$moyenne = $eleveModele->calculerMoyenneAnnuelle('TMP017');
echo "1. Moyenne de TMP017: $moyenne\n";

if ($moyenne < 10) {
    echo "   ✗ Non admissible (< 10)\n";
    exit(1);
}
echo "   ✓ Admissible (>= 10)\n\n";

// Étape 2: Ajouter inscription année suivante
echo "2. Ajout d'inscription en CE1 (id=3) pour année 2026_2027 (id=2)...\n";
$inscOK = $eleveModele->ajouterInscriptionDansAnnee('TMP017', 3, 2);
echo "   Résultat: " . ($inscOK ? "✓ OK" : "✗ ERREUR") . "\n\n";

// Étape 3: Mettre à jour la classe
echo "3. Mise à jour de classe en CE1 (id=3)...\n";
$classOK = $eleveModele->updateClasse('TMP017', 3);
echo "   Résultat: " . ($classOK ? "✓ OK" : "✗ ERREUR") . "\n\n";

// Étape 4: Vérifier les résultats
echo "4. Vérification des résultats:\n";
$stmt = $pdo->prepare("SELECT i.id_annee, i.id_classe, c.nom_classe FROM inscription i LEFT JOIN classe c ON i.id_classe = c.id WHERE i.matricule_eleve = 'TMP017' ORDER BY i.id_annee");
$stmt->execute();
$inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($inscriptions as $row) {
    echo "   - Année " . $row['id_annee'] . ": " . $row['nom_classe'] . "\n";
}

$stmt = $pdo->prepare("SELECT id_classe FROM eleve WHERE matricule = 'TMP017'");
$stmt->execute();
$classe_eleve = $stmt->fetchColumn();
echo "   - Classe dans eleve: $classe_eleve\n\n";

// Étape 5: Compter les élèves
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT matricule_eleve) FROM inscription WHERE id_annee = 2");
$stmt->execute();
$count = $stmt->fetchColumn();
echo "5. Total élèves année 2026_2027: $count\n";
?>
