<?php
// donnees_dashboard.php - Version simplifiée
// Ces données ne sont plus nécessaires car chargées via AJAX
// Mais on les garde pour le chargement initial

$host = 'localhost';
$db   = 'gestion_ecole';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // La session est déjà démarrée par le contrôleur avant l'inclusion de ce fichier
    $annee_id = $_SESSION['annee_scolaire_id'] ?? null;
    $annee_lib = $_SESSION['annee_scolaire_libelle'] ?? null;

    if ($annee_id) {
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT matricule_eleve) FROM inscription WHERE id_annee = :id");
        $stmt->execute(['id' => $annee_id]);
        $eleves_actifs = $stmt->fetchColumn() ?? 0;

        // Count all available classes (not just those with enrollments)
        $classes_ouvertes = $pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn() ?? 0;
    } else {
        $eleves_actifs = $pdo->query("SELECT COUNT(*) FROM eleve")->fetchColumn() ?? 0;
        $classes_ouvertes = $pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn() ?? 0;
    }

    $total_enseignants = $pdo->query("SELECT COUNT(*) FROM enseignant WHERE statut_activite = 'actif'")->fetchColumn() ?? 0;
    if ($annee_lib) {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(montant),0) FROM paiement WHERE annee_scolaire = :libelle");
        $stmt->execute(['libelle' => $annee_lib]);
        $total_recettes = $stmt->fetchColumn() ?? 0;
    } else {
        $total_recettes = $pdo->query("SELECT IFNULL(SUM(montant),0) FROM paiement")->fetchColumn() ?? 0;
    }
    
    // Données pour le graphique
    $stmt5 = $pdo->query("SELECT c.nom_classe, COUNT(e.matricule) AS effectif 
                         FROM classe c 
                         LEFT JOIN eleve e ON c.id = e.id_classe 
                         GROUP BY c.id, c.nom_classe
                         ORDER BY c.id ASC");
    $donnees_graphique = $stmt5->fetchAll();
    
} catch (PDOException $e) {
    $eleves_actifs = 0;
    $classes_ouvertes = 0;
    $total_enseignants = 0;
    $total_recettes = 0;
    $donnees_graphique = [];
}
?>