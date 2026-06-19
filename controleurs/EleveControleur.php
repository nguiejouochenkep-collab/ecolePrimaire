<?php
// controleurs/EleveControleur.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../modeles/Eleve.php';
require_once __DIR__ . '/../modeles/bdd.php';

class EleveControleur {
    private $eleveModele;

    public function __construct() {
        $this->eleveModele = new Eleve();
    }

    /**
     * Affiche le formulaire d'inscription d'un élève
     */
    public function afficherFormulaire($message_succes = "", $message_erreur = "") {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $role_brut = $_SESSION['role'] ?? '';
        $role_actuel = (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin') ? 'admin' : 'enseignant';
        
        if ($role_actuel !== 'admin') {
            die("<div class='alert alert-danger m-3'>Accès refusé. Seul le Directeur peut inscrire un élève.</div>");
        }

        $page_active = 'inscription';
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $classes = $this->eleveModele->listerLesClasses();

        require_once __DIR__ . '/../vues/eleves/inscrire_eleve.php';
    }

    /**
     * Affiche l'interface de choix de classe et liste des élèves
     */
    public function lister() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role_brut = $_SESSION['role'] ?? '';
        $is_admin = (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin');

        $id_classe_selectionnee = $_GET['id_classe'] ?? null;
        $search_term = trim($_GET['search'] ?? '');
        $classes = $this->eleveModele->listerLesClasses(); 
        $id_annee = $_SESSION['annee_scolaire_id'] ?? null;

        $eleves = [];
        $info_message = '';

        if ($search_term !== '') {
            $eleves = $this->eleveModele->chercherParNomOuMatricule($search_term, $id_classe_selectionnee, $id_annee);
            $info_message = "Résultats de la recherche pour \"" . htmlspecialchars($search_term) . "\"";
        } elseif (!empty($id_classe_selectionnee)) {
            $eleves = $this->eleveModele->listerParClasse($id_classe_selectionnee, $id_annee);
        }

        require_once __DIR__ . '/../vues/eleves/liste_eleves.php';
    }

    /**
     * Promeut tous les élèves et archive les élèves de CM2
     */
    public function promouvoir() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role_brut = $_SESSION['role'] ?? '';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => 'Accès refusé. Seul le Directeur peut activer la promotion.']);
            exit();
        }

        $succes = $this->eleveModele->promouvoirTousLesEleves();
        $message = $succes ? 'La promotion des élèves a été effectuée avec succès.' : 'Erreur lors de la promotion des élèves. Vérifiez les fichiers journaux.';

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => $succes, 'message' => $message]);
        exit();
    }

    /**
     * Traite la soumission du formulaire d'inscription
     */
    public function sauvegarder() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log(print_r($_POST, true));
            $champs = ['nom', 'prenom', 'date_naissance', 'sexe', 'id_classe', 'id_annee', 'telephone_parent', 'adresse', 'matricule'];
            $data = [];
            foreach ($champs as $champ) {
                $data[$champ] = isset($_POST[$champ]) ? trim($_POST[$champ]) : '';
            }

            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            if (!empty($data['nom']) && !empty($data['prenom']) && !empty($data['date_naissance']) && !empty($data['sexe']) && !empty($data['id_classe'])) {
                $resultat = $this->eleveModele->inscrire($data);
                $message_succes = $resultat['succes'] ? $resultat['message'] : "";
                $message_erreur = !$resultat['succes'] ? $resultat['message'] : "";
            } else {
                $message_succes = "";
                $message_erreur = "Veuillez remplir tous les champs obligatoires.";
            }

            if ($is_ajax) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'succes' => !empty($message_succes),
                    'message' => !empty($message_succes) ? $message_succes : $message_erreur
                ]);
                exit();
            } else {
                $this->afficherFormulaire($message_succes, $message_erreur);
            }
        }
    }

    /**
     * Supprime un élève via AJAX
     */
    public function supprimer() {
        $matricule = $_GET['matricule'] ?? '';
        $succes = false;
        $message = "Impossible de supprimer l'élève.";

        if (!empty($matricule)) {
            $succes = $this->eleveModele->supprimer($matricule);
            if ($succes) $message = "L'élève a été retiré avec succès.";
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => $succes, 'message' => $message]);
        exit();
    }

    /**
     * Modifie un élève via POST (Appel AJAX)
     */
    public function modifier() {
        $matricule = $_POST['matricule'] ?? '';
        $succes = false;
        $message = "Erreur lors de la modification.";

        if (!empty($matricule) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nom' => trim($_POST['nom'] ?? ''),
                'prenom' => trim($_POST['prenom'] ?? ''),
                'date_naissance' => trim($_POST['date_naissance'] ?? ''),
                'sexe' => trim($_POST['sexe'] ?? ''),
                'telephone_parent' => trim($_POST['telephone_parent'] ?? '')
            ];

            $succes = $this->eleveModele->modifier($matricule, $data);
            if ($succes) $message = "Informations de l'élève mises à jour.";
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['succes' => $succes, 'message' => $message]);
        exit();
    }

    /**
     * Récupère toutes les statistiques consolidées du tableau de bord au format JSON
     */
    public function obtenerStatistiques() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['role'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé']);
            exit();
        }

        try {
            $db = Bdd::connexion();
            
            $queryClasses = "SELECT COUNT(*) AS total_classes FROM classe";
            $stmtClasses = $db->query($queryClasses);
            $resClasses = $stmtClasses->fetch(PDO::FETCH_ASSOC);

            $queryEleves = "SELECT COUNT(*) AS total_eleves FROM eleve";
            $stmtEleves = $db->query($queryEleves);
            $resEleves = $stmtEleves->fetch(PDO::FETCH_ASSOC);

            $queryRepartition = "SELECT c.nom_classe, COUNT(e.matricule) AS effectif 
                                 FROM classe c 
                                 LEFT JOIN eleve e ON c.id = e.id_classe 
                                 GROUP BY c.id, c.nom_classe";
            $stmtRepartition = $db->query($queryRepartition);
            $repartitionClasses = $stmtRepartition->fetchAll(PDO::FETCH_ASSOC);
            
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            
            echo json_encode([
                'status' => 'success',
                'succes' => true,
                'stats' => [
                    'total_classes' => $resClasses['total_classes'] ?? 0,
                    'total_eleves' => $resEleves['total_eleves'] ?? 0,
                    'total_enseignants' => 0, 
                    'total_recettes' => 0 
                ],
                'repartition' => $repartitionClasses
            ]);
            exit();
            
        } catch (PDOException $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'error',
                'succes' => false,
                'message' => $e->getMessage()
            ]);
            exit();
        }
    }

    /**
     * Gère l'affichage de la page de configuration des classes
     */
    public function gererClasses() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $role_brut = $_SESSION['role'] ?? '';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            die("<div class='alert alert-danger m-3'>Accès refusé.</div>");
        }

        $classes = $this->eleveModele->listerLesClasses();
        require_once __DIR__ . '/../vues/eleves/gerer_classes.php';
    }

    /**
     * Action AJAX pour ajouter une classe
     */
    public function sauvegarderClasse() {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            if (session_status() === PHP_SESSION_NONE) { session_start(); }
            
            $role_actuel = $_SESSION['role'] ?? 'AUCUN_ROLE_DETECTE';
            
            if (strtolower($role_actuel) !== 'directeur' && strtolower($role_actuel) !== 'admin') {
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'succes' => false, 
                    'message' => "Erreur Sécurité : Votre session contient le rôle [ " . $role_actuel . " ]. Ce rôle n'est pas autorisé."
                ]);
                exit();
            }

            // Traitement de l'ajout effectif
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nom_classe = trim($_POST['nom_classe'] ?? '');
                if (!empty($nom_classe)) {
                    // Vérifier d'abord si la classe existe déjà
                    try {
                        $db = Bdd::connexion();
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM classe WHERE nom_classe = ?");
                        $stmt->execute([$nom_classe]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result['count'] > 0) {
                            $succes = false;
                            $message = "Une classe avec le nom '" . htmlspecialchars($nom_classe) . "' existe déjà. Veuillez choisir un autre nom.";
                        } else {
                            $succes = $this->eleveModele->ajouterClasse($nom_classe);
                            $message = $succes ? "La classe '" . htmlspecialchars($nom_classe) . "' a été ajoutée avec succès !" : "Erreur lors de l'enregistrement en base de données.";
                        }
                    } catch (Exception $e) {
                        $succes = false;
                        $message = "Erreur base de données : " . $e->getMessage();
                    }
                } else {
                    $succes = false;
                    $message = "Le nom de la classe ne peut pas être vide.";
                }
                
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['succes' => $succes, 'message' => $message]);
                exit();
            }
        }
    }

    /**
     * Action AJAX pour renommer une classe
     */
    /**
     * Action AJAX pour renommer une classe
     */
    public function modifierClasse() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $role_brut = $_SESSION['role'] ?? 'AUCUN';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => "Erreur Sécurité : Rôle actuel [ " . $role_brut . " ] non autorisé à renommer."]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_classe = $_POST['id_classe'] ?? '';
            $nouveau_nom = trim($_POST['nom_classe'] ?? '');

            if (!empty($id_classe) && !empty($nouveau_nom)) {
                // Vérifier d'abord si le nouveau nom existe déjà
                try {
                    $db = Bdd::connexion();
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM classe WHERE nom_classe = ? AND id != ?");
                    $stmt->execute([$nouveau_nom, $id_classe]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        $succes = false;
                        $message = "Une classe avec le nom '" . htmlspecialchars($nouveau_nom) . "' existe déjà.";
                    } else {
                        $succes = $this->eleveModele->renommerClasse($id_classe, $nouveau_nom);
                        $message = $succes ? "La classe a été renommée avec succès !" : "Erreur lors de la modification en base de données.";
                    }
                } catch (Exception $e) {
                    $succes = false;
                    $message = "Erreur base de données : " . $e->getMessage();
                }
            } else {
                $succes = false;
                $message = "Veuillez remplir tous les champs.";
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => $succes, 'message' => $message]);
            exit();
        }
    }

    /**
     * Action AJAX pour supprimer une classe
     */
    public function supprimerClasse() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $role_brut = $_SESSION['role'] ?? 'AUCUN';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => "Erreur Sécurité : Rôle actuel [ " . $role_brut . " ] non autorisé à supprimer."]);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_classe = $_POST['id_classe'] ?? '';

            if (!empty($id_classe)) {
                // Vérifier d'abord si la classe contient des élèves
                try {
                    $db = Bdd::connexion();
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM eleve WHERE id_classe = ?");
                    $stmt->execute([$id_classe]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        $succes = false;
                        $message = "Impossible de supprimer cette classe car elle contient " . $result['count'] . " élève(s). Veuillez d'abord transférer ou supprimer ces élèves.";
                    } else {
                        $succes = $this->eleveModele->supprimerClasse($id_classe);
                        $message = $succes ? "La classe a été supprimée avec succès." : "Erreur lors de la suppression.";
                    }
                } catch (Exception $e) {
                    $succes = false;
                    $message = "Erreur base de données : " . $e->getMessage();
                }
            } else {
                $succes = false;
                $message = "ID de classe manquant.";
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => $succes, 'message' => $message]);
            exit();
        }
    }

// controleurs/EleveControleur.php - Ajoutez ces méthodes à la fin de la classe, avant la dernière accolade

    /**
     * Affiche la page de promotion des élèves
     */
    public function afficherPromotion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role_brut = $_SESSION['role'] ?? '';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            die("<div class='alert alert-danger m-3'>Accès refusé. Seul le Directeur peut gérer les promotions.</div>");
        }

        $id_classe = $_GET['id_classe'] ?? null;
        $id_annee = $_SESSION['annee_scolaire_id'] ?? null;
        
        // Récupérer toutes les classes
        $classes = $this->eleveModele->listerLesClasses();
        
        // Récupérer les élèves avec leur moyenne pour l'année sélectionnée
        $eleves = $this->eleveModele->getElevesAvecMoyenneAnnuelle($id_classe, $id_annee);
        
        // Récupérer la classe de destination
        $classe_destination = null;
        $classe_destination_id = null;
        
        if ($id_classe) {
            $classe_destination_id = $this->eleveModele->getClasseSuivante($id_classe);
            if ($classe_destination_id) {
                $classe_destination = $this->eleveModele->obtenirClasseParId($classe_destination_id);
            }
        }
        
        // Déterminer si c'est une requête AJAX
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        // Si c'est une requête AJAX, rendre juste la vue
        if ($is_ajax) {
            require_once __DIR__ . '/../vues/eleves/promotion.php';
        } else {
            // Sinon, charger le dashboard complet qui enveloppe la vue
            // Configurer les variables du dashboard
            $role_actuel = (in_array(strtolower($role_brut), ['directeur', 'admin', 'administrateur'])) ? 'admin' : 'enseignant';
            $nom_affichage = $_SESSION['login'] ?? $role_brut;
            $contenu_actif = 'promotion'; // Variable pour indiquer quelle vue charger
            
            require_once __DIR__ . '/../vues/dashboard.php';
        }
    }

    /**
     * Exécute la promotion des élèves sélectionnés
     */
    public function executerPromotion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $role_brut = $_SESSION['role'] ?? '';
        if (strtolower($role_brut) !== 'directeur' && strtolower($role_brut) !== 'admin') {
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['succes' => false, 'message' => 'Accès refusé']);
                exit();
            }
            die("Accès refusé");
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=promotion');
            exit();
        }

        $eleves_a_promouvoir = $_POST['eleves'] ?? [];
        $id_classe_source = $_POST['id_classe_source'] ?? null;
        $id_classe_destination = $_POST['id_classe_destination'] ?? null;
        $id_annee_courante = $_SESSION['annee_scolaire_id'] ?? null;

        if (empty($id_annee_courante)) {
            $_SESSION['error_promotion'] = "Aucune année scolaire sélectionnée. Sélectionnez d'abord une année.";
            header('Location: index.php?action=promotion&id_classe=' . $id_classe_source);
            exit();
        }

        $id_annee_suivante = $this->eleveModele->obtenirAnneeSuivante($id_annee_courante);
        if (!$id_annee_suivante) {
            $_SESSION['error_promotion'] = "Impossible de promouvoir : l'année scolaire suivante est introuvable.";
            header('Location: index.php?action=promotion&id_classe=' . $id_classe_source);
            exit();
        }

        if (empty($eleves_a_promouvoir) || !$id_classe_destination) {
            $_SESSION['error_promotion'] = "Veuillez sélectionner des élèves et une classe de destination";
            header('Location: index.php?action=promotion&id_classe=' . $id_classe_source);
            exit();
        }
        
        $compteur = 0;
        $erreurs = [];
        
        foreach ($eleves_a_promouvoir as $matricule) {
            // Vérifier la moyenne
            $moyenne = $this->eleveModele->calculerMoyenneAnnuelle($matricule);
            
            if ($moyenne !== null && $moyenne >= 10) {
                $inscriptionOK = $this->eleveModele->ajouterInscriptionDansAnnee($matricule, $id_classe_destination, $id_annee_suivante);
                $classeOK = $this->eleveModele->updateClasse($matricule, $id_classe_destination);
                if ($inscriptionOK && $classeOK) {
                    $compteur++;
                } else {
                    $erreurs[] = $matricule;
                }
            } else {
                $erreurs[] = $matricule . " (moyenne: " . ($moyenne ?? 'N/A') . ")";
            }
        }
        
        $_SESSION['message_promotion'] = "$compteur élève(s) promu(s) avec succès";
        if (!empty($erreurs)) {
            $_SESSION['error_promotion'] = "Non promus: " . implode(', ', $erreurs);
        }
        
        header('Location: index.php?action=promotion&id_classe=' . $id_classe_source);
        exit();
    }
    
    private function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

} 
?>