<?php
// Activation ou récupération de la session globale (indispensable pour gérer les rôles de ton stage)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Informations de connexion à WampServer
$host = "localhost";
$user = "root";          // Par défaut sur WampServer
$password = "";          // Par défaut vide sur WampServer
$dbname = "gestion_ecole"; // Le nom exact de ta base de données

try {
    // Création de la connexion avec PDO et activation du support UTF-8 (pour les accents)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    
    // Configuration des alertes en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Si la connexion échoue, on affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>