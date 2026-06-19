<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Sécurité d'authentification
if (!isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

// 2. Gestion automatique du Timeout (300 secondes = 5 mins)
$temps_inactivite_max = 300;
if (isset($_SESSION['derniere_activite']) && (time() - $_SESSION['derniere_activite'] > $temps_inactivite_max)) {
    session_unset();
    session_destroy();
    header("Location: index.php?erreur=session_espiree");
    exit();
}
$_SESSION['derniere_activite'] = time();

// 3. Normalisation de la variable de rôle pour l'application
$role_brut = $_SESSION['role'];
$role_actuel = (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin') ? 'admin' : 'enseignant';
$nom_affichage = isset($_SESSION['login']) ? $_SESSION['login'] : $role_brut;

// 4. Inclusion propre et centralisée de la Base de données
require_once 'connexion.php';

// Fonction utilitaire pour sécuriser les accès de niveau Admin/Directeur
function restrictionAdmin($role) {
    if ($role !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}
?>