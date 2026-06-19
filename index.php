<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Démarre la session pour TOUTES les requêtes
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/controleurs/EleveControleur.php';
require_once __DIR__ . '/controleurs/UtilisateurControleur.php';
require_once __DIR__ . '/controleurs/EnseignantControleur.php';
require_once __DIR__ . '/controleurs/BulletinControleur.php';

$action = $_GET['action'] ?? 'connexion';

// --- PROTECTION GLOBALE DE LA SESSION ---
// Liste des actions accessibles sans être connecté
$actions_publiques = ['connexion', 'traiter_connexion'];

if (!isset($_SESSION['role']) && !in_array($action, $actions_publiques)) {
    // On vérifie si la requête vient de JavaScript (AJAX)
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($is_ajax) {
        // Bloque la redirection et envoie une erreur interceptable par la modale
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'succes' => false,
            'message' => "Erreur : Votre session a expiré. Veuillez rafraîchir la page et vous reconnecter."
        ]);
        exit();
    } else {
        // Redirection classique uniquement pour les pages normales
        header('Location: index.php?action=connexion');
        exit();
    }
}

$eleveCtrl = new EleveControleur();
$userCtrl = new UtilisateurControleur();
$enseignantCtrl = new EnseignantControleur();
$bulletinCtrl = new BulletinControleur();

switch ($action) {
    // --- AUTHENTIFICATION ---
    case 'connexion':
        $userCtrl->afficherConnexion();
        break;

    case 'traiter_connexion':
        $userCtrl->connecter();
        break;

    // --- TABLEAU DE BORD ---
    case 'dashboard':
        $userCtrl->ouvrirDashboard();
        break;

    case 'dashboard_stats':
        $userCtrl->obtenirStatistiques();
        break;

    case 'modifier_matiere_ajax':
    $userCtrl->modifierMatiereAjax();
    break;
case 'supprimer_matiere_ajax':
    $userCtrl->supprimerMatiereAjax();
    break;

    // --- GESTION DES ELEVES ---
    case 'liste_eleves':
        $eleveCtrl->lister();
        break;
        
    case 'formulaire_ajout':
        $eleveCtrl->afficherFormulaire();
        break;
    
    case 'sauvegarder_eleve':
        $eleveCtrl->sauvegarder();
        break;

    case 'modifier_eleve':
        $eleveCtrl->modifier();
        break;

    case 'supprimer_eleve':
        $eleveCtrl->supprimer();
        break;

    case 'promouvoir_eleves':
        $eleveCtrl->promouvoir();
        break;

    case 'eleves_classe':
        $bulletinCtrl->getStatistiquesBulletin();
        break;

    // --- GESTION DES CLASSES ---
    case 'gerer_classes':
        $eleveCtrl->gererClasses();
        break;

    case 'sauvegarder_classe':
        $eleveCtrl->sauvegarderClasse();
        break;

    case 'modifier_classe':
        $eleveCtrl->modifierClasse();
        break;

    case 'supprimer_classe': 
        $eleveCtrl->supprimerClasse();
        break;
    
    // --- IMPRESSIONS & REÇUS ---
    case 'generer_recu':
         include 'vues/recu_pdf.php';
         exit();
         break;

    // --- GESTION DES ENSEIGNANTS ---
    case 'formulaire_enseignant':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (method_exists($enseignantCtrl, 'sauvegarder')) {
                $enseignantCtrl->sauvegarder();
            } else {
                require_once 'ajouter_maitre.php';
            }
        } else {
            if (method_exists($enseignantCtrl, 'afficherFormulaire')) {
                $enseignantCtrl->afficherFormulaire();
            } else {
                require_once 'ajouter_maitre.php';
            }
        }
        break;

    case 'liste_enseignants':
        if (method_exists($userCtrl, 'listerEnseignants')) {
            $userCtrl->listerEnseignants();
        } else {
            require_once 'liste_enseignants.php';
        }
        break;

    // --- GESTION DES NOTES & MATIERES ---
    case 'saisie_note':
        $userCtrl->afficherSaisieNotes();
        break;

    case 'enregistrer_notes':
         $userCtrl->enregistrerNotes();
         break;

    case 'modifier_matieres':
         $userCtrl->modifierMatieres();
         break;
    
    case 'creer_matiere_ajax':
        $userCtrl->creerMatiereAjax();
        break;

    
    // --- BULLETINS DE NOTES ---
case 'bulletins':
     $bulletinCtrl->afficherBulletins();
     break;

case 'generer_bulletin_pdf':
     $bulletinCtrl->genererPDF();
     break;

case 'generer_bulletins_classe_pdf':
     $bulletinCtrl->genererBulletinsClassePDF();
     break;

case 'stats_bulletin':
     $bulletinCtrl->getStatistiquesBulletin();
     break;

case 'changer_mot_de_passe':
    $userCtrl->changerMotDePasse();
    break;
    // --- SUIVI DE SCOLARITE / VERSEMENTS ---
    case 'formulaire_versement':
        include 'vues/formulaire_versement.php';
        exit(); 
        break;
 
    // Ajoutez ces cas dans votre switch

case 'promotion':
    $eleveCtrl->afficherPromotion();
    break;

case 'annees':
    $userCtrl->afficherAnnees();
    break;

case 'changer_annee':
    $userCtrl->changerAnnee();
    break;

case 'executer_promotion':
    $eleveCtrl->executerPromotion();
    break;
    
    case 'enregistrer_versement':
        include 'controleurs/traitement_versement.php';
        exit();
        break;
    case 'historique_caisses':
        include 'vues/historique_caisses.php';
        exit();
        break;
    case 'historique_paiements':
        include 'vues/historique_paiements.php';
        exit();
        break;
        
    // --- ACTION PAR DEFAUT ---
    default:
        $userCtrl->afficherConnexion();
        break;
}
?>