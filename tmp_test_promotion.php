<?php
session_start();

// Configurer la session pour l'année 2025_2026
$_SESSION['annee_scolaire_id'] = 1;
$_SESSION['annee_scolaire_libelle'] = '2025-2026';
$_SESSION['role'] = 'directeur';

try {
    // Inclure les fichiers nécessaires
    require_once('modeles/Eleve.php');
    require_once('modeles/bdd.php');
    
    // Créer la connexion
    $bdd = new Bdd();
    $eleveModele = new Eleve($bdd->connexion());
    
    // Récupérer l'année courante et suivante
    $id_annee_courante = 1;  // 2025_2026
    $id_annee_suivante = $eleveModele->obtenirAnneeSuivante($id_annee_courante);
    
    echo "Année courante: $id_annee_courante\n";
    echo "Année suivante: $id_annee_suivante\n";
    
    if (!$id_annee_suivante) {
        echo "ERREUR: Année suivante non trouvée!\n";
        exit(1);
    }
    
    // Récupérer tous les élèves de la classe SIL (id=1) avec leur moyenne
    $eleves = $eleveModele->getElevesAvecMoyenneAnnuelle(1, $id_annee_courante);
    
    echo "\nÉlèves de SIL pour promotion:\n";
    echo "============================\n";
    
    $count = 0;
    foreach ($eleves as $eleve) {
        $matricule = $eleve['matricule'];
        $moyenne = (float)$eleve['moyenne_annuelle'];
        
        // Vérifier l'éligibilité (moyenne >= 10)
        if ($moyenne < 10) {
            echo "[$matricule] Non admis (moyenne: $moyenne)\n";
            continue;
        }
        
        // Récupérer la classe suivante
        $id_classe_suivante = $eleveModele->getClasseSuivante($eleve['id_classe']);
        
        if (!$id_classe_suivante) {
            echo "[$matricule] Non promotionnable (dernière classe CM2)\n";
            continue;
        }
        
        // Ajouter l'inscription dans la nouvelle année
        $result = $eleveModele->ajouterInscriptionDansAnnee($matricule, $id_classe_suivante, $id_annee_suivante);
        if (!$result) {
            echo "[$matricule] Erreur lors de l'ajout d'inscription\n";
            continue;
        }
        
        // Mettre à jour la classe dans la table eleve
        if (!$eleveModele->updateClasse($matricule, $id_classe_suivante)) {
            echo "[$matricule] Erreur lors de la mise à jour de classe\n";
            continue;
        }
        
        echo "[OK] $matricule promu (moy=$moyenne) -> classe $id_classe_suivante\n";
        $count++;
    }
    
    echo "\n✓ Promotion terminée: $count élève(s) promu(s)\n";
    
    // Vérifier les inscriptions créées pour l'année suivante
    echo "\n--- Vérification des inscriptions ---\n";
    $pdo = $bdd->connexion();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE id_annee = ?");
    $stmt->execute([$id_annee_suivante]);
    $count_inscriptions = $stmt->fetchColumn();
    
    echo "Total d'inscriptions pour l'année suivante (id=$id_annee_suivante): $count_inscriptions\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>

