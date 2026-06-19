<?php
// controleurs/ClasseControleur.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../modeles/Classe.php';

class ClasseControleur {
    private $classeModele;

    public function __construct() {
        $this->classeModele = new Classe();
    }

    public function listerEffectifs() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role'])) {
            header('Location: index.php?action=connexion');
            exit();
        }

        $effectifs = $this->classeModele->getEffectifsParClasse();
        require_once __DIR__ . '/../vues/eleves/classe_liste.php';
    }
}
?>