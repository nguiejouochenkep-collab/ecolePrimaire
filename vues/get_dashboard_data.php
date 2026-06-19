<?php
// get_dashboard_data.php
session_start();

if (!isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

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
    
    // Use selected school year if available
    $annee_id = $_SESSION['annee_scolaire_id'] ?? null;
    $annee_lib = $_SESSION['annee_scolaire_libelle'] ?? null;

    if ($annee_id) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE id_annee = :id");
        $stmt->execute(['id' => $annee_id]);
        $eleves_actifs = $stmt->fetchColumn();

        // Count all available classes (not just those with enrollments)
        $classes_ouvertes = $pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn();
    } else {
        $eleves_actifs = $pdo->query("SELECT COUNT(*) FROM eleve")->fetchColumn();
        $classes_ouvertes = $pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn();
    }

    $total_enseignants = $pdo->query("SELECT COUNT(*) FROM enseignant WHERE statut_activite = 'actif'")->fetchColumn();

    if ($annee_lib) {
        $stmt = $pdo->prepare("SELECT IFNULL(SUM(montant),0) FROM paiement WHERE annee_scolaire = :libelle");
        $stmt->execute(['libelle' => $annee_lib]);
        $total_recettes = $stmt->fetchColumn();
    } else {
        $total_recettes = $pdo->query("SELECT IFNULL(SUM(montant),0) FROM paiement")->fetchColumn();
    }
    
    echo json_encode([
        'success' => true,
        'eleves_actifs' => $eleves_actifs,
        'classes_ouvertes' => $classes_ouvertes,
        'total_enseignants' => $total_enseignants,
        'total_recettes' => $total_recettes ?? 0
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>