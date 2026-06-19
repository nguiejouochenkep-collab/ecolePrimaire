<?php
// modeles/bdd.php

// Activation de l'affichage de toutes les erreurs PHP à l'écran
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Bdd {
    private static $instance = null;

    public static function connexion() {
        if (self::$instance === null) {
            $host = 'localhost';
            $dbname = 'gestion_ecole';
            $user = 'root';
            $password = '';

            try {
                // Configuration de PDO avec activation des exceptions d'erreurs
                self::$instance = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8", 
                    $user, 
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Force l'affichage des erreurs SQL
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                // En cas d'erreur de connexion, on l'affiche immédiatement et on arrête le script
                die("<div style='color:red; font-weight:bold; padding:10px; border:1px solid red;'>
                        Erreur de connexion à la base de données : " . $e->getMessage() . "
                     </div>");
            }
        }
        return self::$instance;
    }
}
?>