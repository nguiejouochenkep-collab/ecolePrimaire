<?php
// controleurs/EnseignantControleur.php

// --- FORCE PHP À AFFICHER LES ERREURS DE COMPILATION / SYNTAXE ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../modeles/Enseignant.php';

class EnseignantControleur {
    private $modele;

    public function __construct() {
        $this->modele = new Enseignant();
    }

    public function afficherFormulaire() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $role = $_SESSION['role'] ?? '';
        if (strtolower($role) !== 'directeur' && strtolower($role) !== 'admin') {
            echo "<div class='alert alert-danger'>Accès réservé au Directeur.</div>";
            exit();
        }

        $classes = $this->modele->listerClasses();
        require_once __DIR__ . '/../vues/enseignants/ajouter_maitre.php';
    }

    public function sauvegarder() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $matricule = trim($_POST['matricule'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $matieres = $_POST['matiere'] ?? [];
        $matieres = is_array($matieres) ? $matieres : [$matieres];
        $matieres = array_filter(array_map('trim', $matieres), function ($item) {
            return $item !== '';
        });
        $telephone = trim($_POST['telephone'] ?? '');
        $statut = trim($_POST['statut'] ?? 'Titulaire');
        $id_classe = $_POST['id_classe'] ?? [];
        $login = trim($_POST['login'] ?? '');
        $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

        if (!empty($matricule) && !empty($nom) && !empty($prenom) && count($matieres) > 0) {
            try {
                $this->modele->enregistrer($matricule, $nom, $prenom, $matieres, $telephone, $statut, $id_classe, $login, $mot_de_passe);
                $succes = true;
                $message = "L'enseignant (Matricule : $matricule) a été enregistré/réaffecté avec succès.";
            } catch (Exception $e) {
                $succes = false;
                $message = "Erreur SQL / Métier : " . $e->getMessage();
            }
        } else {
            $succes = false;
            $message = "Veuillez remplir tous les champs obligatoires (Matricule, Nom, Prénom, Matière).";
        }

        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => $succes, 'message' => $message]);
        exit();
    }
}