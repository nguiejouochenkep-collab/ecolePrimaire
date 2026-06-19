<?php
session_start();

// Configurer la session
$_SESSION['annee_scolaire_id'] = 1;
$_SESSION['annee_scolaire_libelle'] = '2025-2026';
$_SESSION['role'] = 'directeur';

// Simuler la requête POST
$_POST['eleves'] = ['TMP017'];
$_POST['id_classe_source'] = '2';  // CP
$_POST['id_classe_destination'] = '3';  // CE1
$_SERVER['REQUEST_METHOD'] = 'POST';

try {
    require_once('controleurs/EleveControleur.php');
    require_once('modeles/Eleve.php');
    require_once('modeles/bdd.php');
    
    $eleveCtrl = new EleveControleur();
    
    // Appeler directement la fonction de promotion
    echo "DEBUG: Avant promotion\n";
    echo "POST eleves: " . print_r($_POST['eleves'], true) . "\n";
    echo "POST id_classe_source: " . $_POST['id_classe_source'] . "\n";
    echo "POST id_classe_destination: " . $_POST['id_classe_destination'] . "\n";
    echo "SESSION annee_scolaire_id: " . $_SESSION['annee_scolaire_id'] . "\n\n";
    
    // Vérifier le calcul de moyenne
    $bdd = new Bdd();
    $eleveModele = new Eleve($bdd->connexion());
    $moyenne = $eleveModele->calculerMoyenneAnnuelle('TMP017');
    echo "Moyenne de TMP017: $moyenne\n";
    
    if ($moyenne >= 10) {
        echo "✓ TMP017 est admissible (moyenne >= 10)\n\n";
        
        // Tester ajouterInscriptionDansAnnee
        echo "Tentative d'ajout d'inscription en CE1 année 2...\n";
        $result = $eleveModele->ajouterInscriptionDansAnnee('TMP017', 3, 2);
        echo "Résultat ajout inscription: " . ($result ? 'OK' : 'ERREUR') . "\n";
        
        // Tester updateClasse
        echo "Tentative de mise à jour de classe en CE1...\n";
        $result2 = $eleveModele->updateClasse('TMP017', 3);
        echo "Résultat update classe: " . ($result2 ? 'OK' : 'ERREUR') . "\n";
        
        // Vérifier le résultat
        $pdo = $bdd->connexion();
        $stmt = $pdo->prepare("SELECT i.matricule_eleve, i.id_classe, i.id_annee, c.nom_classe, e.id_classe as eleve_classe FROM inscription i LEFT JOIN classe c ON i.id_classe = c.id LEFT JOIN eleve e ON i.matricule_eleve = e.matricule WHERE i.matricule_eleve = 'TMP017' ORDER BY i.id_annee DESC");
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nInscriptions de TMP017 après:\n";
        foreach ($result as $row) {
            echo "  - Année " . $row['id_annee'] . ": " . $row['nom_classe'] . " (classe eleve: " . $row['eleve_classe'] . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
