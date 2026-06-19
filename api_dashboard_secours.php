<?php
// Désactiver l'affichage des avertissements parasites qui pourraient casser le JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// 1. Connexion à ta base de données active
$host = 'localhost';
$dbname = 'gestion_ecole';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 2. Calcul des compteurs globaux
    // Nombre d'élèves inscrits
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM eleve");
    $total_eleves = $stmt->fetch()['total'] ?? 0;

    // Nombre de classes
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM classe");
    $total_classes = $stmt->fetch()['total'] ?? 0;

    // Nombre d'enseignants
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM enseignant");
    $total_enseignants = $stmt->fetch()['total'] ?? 0;

    // Total des recettes de scolarité
    $stmt = $pdo->query("SELECT SUM(montant) AS total FROM paiement");
    $total_recettes = $stmt->fetch()['total'] ?? 0;

    // 3. Récupération de la répartition par classe pour le graphique Chart.js
    // On fait une jointure entre la table classe et eleve pour compter les effectifs
    $sql_graphique = "SELECT c.nom_classe, COUNT(e.matricule) AS effectif 
                      FROM classe c 
                      LEFT JOIN eleve e ON c.id = e.id_classe 
                      GROUP BY c.id, c.nom_classe";
    
    $stmt_graph = $pdo->query($sql_graphique);
    $donnees_graphique = $stmt_graph->fetchAll();

    // 4. Envoi de la réponse JSON structurée
    echo json_encode([
        "status" => "success",
        "total_eleves" => $total_eleves,
        "total_classes" => $total_classes,
        "total_enseignants" => $total_enseignants,
        "total_recettes" => $total_recettes,
        "graphique" => $donnees_graphique
    ]);

} catch (Exception $e) {
    // Si une erreur survient (colonne manquante, mauvaise table), on la renvoie proprement en JSON
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}