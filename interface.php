<?php
// 1. DÉMARRAGE DE LA SESSION : Obligatoire pour récupérer le rôle de l'utilisateur connecté
session_start();

// Indication du format JSON au script JavaScript Fetch
header('Content-Type: application/json; charset=utf-8');

// Sécurité : Si l'utilisateur n'est pas connecté (pas de session active), on bloque l'accès
if (!isset($_SESSION['role'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Accès refusé. Veuillez vous connecter."
    ]);
    exit();
}

$role_actuel = $_SESSION['role']; // Récupération du rôle ('admin' ou 'enseignant')

$host = "localhost";
$user = "root";
$password = "";
$dbname = "application_de_gestion_ecole";

try {
    // Connexion à l'aide de l'API PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Comptage des élèves inscrits
    $reqEleves = $pdo->query("SELECT COUNT(*) as total FROM eleve");
    $total_eleves = $reqEleves->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Comptage des classes ouvertes
    $reqClasses = $pdo->query("SELECT COUNT(DISTINCT classe) as total FROM eleve");
    $total_classes = $reqClasses->fetch(PDO::FETCH_ASSOC)['total'];
    if ($total_classes == 0) { $total_classes = 6; } // Valeur par défaut (SIL à CM2)

    // 3. Variables simulées ou issues de vos tables complémentaires
    $total_enseignants = 10; // Nombre d'enseignants titulaires
    
    // CONDITION DE SÉCURITÉ : Seul l'administrateur voit le montant des recettes scolaires
    if ($role_actuel === 'admin') {
        $total_recettes = 750000; // Somme des scolarités perçues en FCFA visible par le Directeur
    } else {
        $total_recettes = 0; // Masqué pour les enseignants
    }

    // Envoi des statistiques nettoyées au script JavaScript
    echo json_encode([
        "status" => "success",
        "stats" => [
            "total_eleves" => $total_eleves,
            "total_classes" => $total_classes,
            "total_enseignants" => $total_enseignants,
            "total_recettes" => $total_recettes // Vaudra 0 si c'est un enseignant
        ]
    ]);

} catch (PDOException $e) {
    // Gestion propre du retour en cas d'absence de la base de données
    echo json_encode([
        "status" => "error",
        "message" => "Impossible de charger les données : " . $e->getMessage()
    ]);
}
?>