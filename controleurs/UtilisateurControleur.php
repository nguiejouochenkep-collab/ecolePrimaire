<?php
// controleurs/UtilisateurControleur.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../modeles/Utilisateur.php';
require_once __DIR__ . '/../modeles/Eleve.php';

class UtilisateurControleur {
    private $utilisateurModele;
    private $eleveModele;
    private $pdo;

    public function __construct() {
        $this->utilisateurModele = new Utilisateur();
        $this->eleveModele = new Eleve();
        $this->initialiserConnexion();
    }

    private function initialiserConnexion() {
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Exception $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }

        $this->initialiserDonneesBulletinParDefaut();
    }

    private function initialiserDonneesBulletinParDefaut() {
        try {
            $countTrimestres = $this->pdo->query("SELECT COUNT(*) FROM trimestre")->fetchColumn();
            if (!$countTrimestres) {
                $stmt = $this->pdo->prepare("INSERT INTO trimestre (nom) VALUES (:nom)");
                foreach (['1er Trimestre', '2e Trimestre', '3e Trimestre'] as $nom) {
                    $stmt->execute([':nom' => $nom]);
                }
            }

            $countSequences = $this->pdo->query("SELECT COUNT(*) FROM sequence")->fetchColumn();
            if (!$countSequences) {
                $seqStmt = $this->pdo->prepare("INSERT INTO sequence (id_trimestre, numero_sequence, nom) VALUES (:id_trimestre, :numero_sequence, :nom)");
                $sequenceRows = [
                    ['id_trimestre' => 1, 'numero_sequence' => 1, 'nom' => 'Mensuel 1'],
                    ['id_trimestre' => 1, 'numero_sequence' => 2, 'nom' => 'Mensuel 2'],
                    ['id_trimestre' => 1, 'numero_sequence' => 3, 'nom' => 'Mensuel 3'],
                    ['id_trimestre' => 2, 'numero_sequence' => 4, 'nom' => 'Mensuel 4'],
                    ['id_trimestre' => 2, 'numero_sequence' => 5, 'nom' => 'Mensuel 5'],
                    ['id_trimestre' => 2, 'numero_sequence' => 6, 'nom' => 'Mensuel 6'],
                    ['id_trimestre' => 3, 'numero_sequence' => 7, 'nom' => 'Mensuel 7'],
                    ['id_trimestre' => 3, 'numero_sequence' => 8, 'nom' => 'Mensuel 8'],
                    ['id_trimestre' => 3, 'numero_sequence' => 9, 'nom' => 'Mensuel 9'],
                ];
                foreach ($sequenceRows as $row) {
                    $seqStmt->execute($row);
                }
            }
        } catch (Exception $e) {}
    }

    private function demarrerSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function estAdmin() {
        $this->demarrerSession();
        return in_array(strtolower($_SESSION['role'] ?? ''), ['directeur', 'admin', 'administrateur'], true);
    }

    private function recupererClassesEnseignant($id_utilisateur) {
        if (empty($id_utilisateur)) return [];
        $stmt = $this->pdo->prepare("SELECT DISTINCT c.id, c.nom_classe FROM classe c JOIN affectation_enseignant a ON a.id_classe = c.id WHERE a.id_utilisateur = :id_user ORDER BY c.nom_classe ASC");
        $stmt->execute(['id_user' => $id_utilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function verifierClasseAutorisee($id_classe) {
        if ($this->estAdmin() || empty($id_classe)) return true;
        $this->demarrerSession();
        $classes = $_SESSION['teacher_classes'] ?? [];
        return in_array($id_classe, $classes, true);
    }

    private function verifierAuthentification() {
        $this->demarrerSession();
        if (!isset($_SESSION['role'])) {
            header("Location: index.php?action=connexion");
            exit();
        }
    }
 

public function sauvegarder() {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $login = trim($_POST['login'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $role = $_POST['role'] ?? 'enseignant';
        
        // Validation du mot de passe
        $erreurs = $this->utilisateurModele->validerMotDePasse($mot_de_passe);
        
        if (!empty($erreurs)) {
            $message_erreur = implode("<br>", $erreurs);
            $this->afficherFormulaireUtilisateur($message_erreur);
            return;
        }
        
        // Vérifier si le login existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM utilisateur WHERE login = :login");
        $stmt->execute(['login' => $login]);
        if ($stmt->fetch()) {
            $this->afficherFormulaireUtilisateur("Ce nom d'utilisateur existe déjà");
            return;
        }
        
        // Hacher le mot de passe
        $mot_hache = $this->utilisateurModele->hacherMotDePasse($mot_de_passe);
        
        // Insérer l'utilisateur
        $stmt = $this->pdo->prepare("
            INSERT INTO utilisateur (login, mot_de_passe, role) 
            VALUES (:login, :mot_de_passe, :role)
        ");
        
        $stmt->execute([
            'login' => $login,
            'mot_de_passe' => $mot_hache,
            'role' => $role
        ]);
        
        $_SESSION['message'] = "Utilisateur créé avec succès";
        header("Location: index.php?action=liste_utilisateurs");
        exit();
    }
}

  public function changerMotDePasse() {
    $this->verifierAuthentification();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $ancien_mdp = $_POST['ancien_mot_de_passe'] ?? '';
        $nouveau_mdp = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmer_mdp = $_POST['confirmer_mot_de_passe'] ?? '';
        
        // Vérifier que les nouveaux mots de passe correspondent
        if ($nouveau_mdp !== $confirmer_mdp) {
            $message_erreur = "Les nouveaux mots de passe ne correspondent pas";
            require_once 'vues/utilisateurs/changer_mot_de_passe.php';
            return;
        }
        
        // Valider la force du nouveau mot de passe
        $erreurs = $this->utilisateurModele->validerMotDePasse($nouveau_mdp);
        if (!empty($erreurs)) {
            $message_erreur = implode("<br>", $erreurs);
            require_once 'vues/utilisateurs/changer_mot_de_passe.php';
            return;
        }
        
        // Récupérer l'utilisateur
        $stmt = $this->pdo->prepare("SELECT mot_de_passe FROM utilisateur WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Vérifier l'ancien mot de passe
        if (!$this->utilisateurModele->verifierMotDePasse($ancien_mdp, $user['mot_de_passe'])) {
            $message_erreur = "Ancien mot de passe incorrect";
            require_once 'vues/utilisateurs/changer_mot_de_passe.php';
            return;
        }
        
        // Hacher le nouveau mot de passe
        $mot_hache = $this->utilisateurModele->hacherMotDePasse($nouveau_mdp);
        
        // Mettre à jour
        $stmt = $this->pdo->prepare("\n            UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id = :id\n        ");
        $stmt->execute([
            'mot_de_passe' => $mot_hache,
            'id' => $_SESSION['user_id']
        ]);
        
        $message_succes = "Votre mot de passe a été changé avec succès";
        require_once 'vues/utilisateurs/changer_mot_de_passe.php';
    } else {
        require_once 'vues/utilisateurs/changer_mot_de_passe.php';
    }
}
    public function afficherConnexion($erreur = "") {
        require_once __DIR__ . '/../vues/authentification/connexion.php';
    }

    public function connecter() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $login = $_POST['login'] ?? '';
            $mot_de_passe = $_POST['password'] ?? '';

            if (!empty($login) && !empty($mot_de_passe)) {
                $user = $this->utilisateurModele->verifierConnexion($login, $mot_de_passe);
                if ($user) {
                    $this->demarrerSession();
                    $userId = $user['id'] ?? $user['id_utilisateur'] ?? null;
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['login'] = $user['login'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['derniere_activite'] = time();

                    if (strtolower($user['role']) === 'enseignant') {
                        $stmtEns = $this->pdo->prepare("SELECT statut, statut_activite FROM enseignant WHERE id_utilisateur = :id_user LIMIT 1");
                        $stmtEns->execute(['id_user' => $userId]);
                        $enseignant = $stmtEns->fetch(PDO::FETCH_ASSOC);
                        if (!$enseignant || strtolower($enseignant['statut']) !== 'titulaire' || strtolower($enseignant['statut_activite']) !== 'actif') {
                            session_unset();
                            session_destroy();
                            $this->afficherConnexion("Accès réservé aux enseignants titulaires et actifs.");
                            exit();
                        }
                        $classes = $this->recupererClassesEnseignant($userId);
                        $_SESSION['teacher_classes'] = array_column($classes, 'id');
                    }
                    header("Location: index.php?action=dashboard");
                    exit();
                } else {
                    $this->afficherConnexion("Identifiant ou mot de passe incorrect !");
                }
            } else {
                $this->afficherConnexion("Veuillez remplir tous les champs !");
            }
        }
    }

    public function ouvrirDashboard() {
        $this->verifierAuthentification();
        $temps_inactivite_max = 300;
        if (isset($_SESSION['derniere_activite'])) {
            $temps_ecoule = time() - $_SESSION['derniere_activite'];
            if ($temps_ecoule > $temps_inactivite_max) {
                session_unset();
                session_destroy();
                header("Location: index.php?action=connexion&erreur=session_expiree");
                exit();
            }
        }
        $_SESSION['derniere_activite'] = time();
        $role_brut = $_SESSION['role'];
        $role_actuel = (in_array(strtolower($role_brut), ['directeur', 'admin', 'administrateur'])) ? 'admin' : 'enseignant';
        $nom_affichage = $_SESSION['login'] ?? $role_brut;
        require_once __DIR__ . '/../vues/dashboard.php';
    }

    public function obtenirStatistiques() {
        $this->demarrerSession();
        try {
            // If a school year is selected, filter counts by that year
            $annee_id = $_SESSION['annee_scolaire_id'] ?? null;
            $annee_lib = $_SESSION['annee_scolaire_libelle'] ?? null;

            if ($annee_id) {
                $elevesStmt = $this->pdo->prepare("SELECT COUNT(DISTINCT matricule_eleve) FROM inscription WHERE id_annee = :id");
                $elevesStmt->execute(['id' => $annee_id]);
                $eleves = $elevesStmt->fetchColumn();

                // Count all available classes (not just those with enrollments)
                $classes = $this->pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn();

                $enseignants = $this->pdo->query("SELECT COUNT(*) FROM enseignant WHERE statut_activite = 'actif'")->fetchColumn();

                $recettesStmt = $this->pdo->prepare("SELECT IFNULL(SUM(montant), 0) FROM paiement WHERE annee_scolaire = :libelle");
                $recettesStmt->execute(['libelle' => $annee_lib]);
                $recettes = $recettesStmt->fetchColumn();

                $repStmt = $this->pdo->prepare("SELECT c.nom_classe, COUNT(i.matricule_eleve) AS effectif FROM classe c LEFT JOIN inscription i ON c.id = i.id_classe AND i.id_annee = :id GROUP BY c.id, c.nom_classe ORDER BY c.id ASC");
                $repStmt->execute(['id' => $annee_id]);
                $repartition = $repStmt->fetchAll();
            } else {
                $eleves = $this->pdo->query("SELECT COUNT(*) FROM eleve")->fetchColumn();
                $classes = $this->pdo->query("SELECT COUNT(*) FROM classe")->fetchColumn();
                $enseignants = $this->pdo->query("SELECT COUNT(*) FROM enseignant WHERE statut_activite = 'actif'")->fetchColumn();
                $recettes = $this->pdo->query("SELECT IFNULL(SUM(montant), 0) FROM paiement")->fetchColumn();
                $repartition = $this->pdo->query("SELECT c.nom_classe, COUNT(e.matricule) AS effectif FROM classe c LEFT JOIN eleve e ON c.id = e.id_classe GROUP BY c.id, c.nom_classe ORDER BY c.id ASC")->fetchAll();
            }

            $reponse = ['status' => 'success', 'stats' => ['total_eleves' => (int)$eleves, 'total_classes' => (int)$classes, 'total_enseignants' => (int)$enseignants, 'total_recettes' => (float)$recettes], 'graphique' => $repartition];
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($reponse);
            exit;
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => "Erreur interne : " . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Affiche la liste des années scolaires et l'interface de sélection (AJAX ou page complète)
     */
    public function afficherAnnees() {
        $this->demarrerSession();
        if (!$this->estAdmin()) {
            die("<div class='alert alert-danger m-3'>Accès refusé.</div>");
        }

        // Récupérer les années scolaires
        try {
            $stmt = $this->pdo->query("SELECT id, libelle, statut FROM annee_scolaire ORDER BY id DESC");
            $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $annees = [];
        }

        // Si requête AJAX, renvoyer juste la vue partielle
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        if ($is_ajax) {
            require_once __DIR__ . '/../vues/annees/gestion_annees.php';
        } else {
            $contenu_actif = 'annees';
            require_once __DIR__ . '/../vues/dashboard.php';
        }
    }

    /**
     * Change l'année scolaire sélectionnée (POST)
     */
    public function changerAnnee() {
        $this->demarrerSession();
        if (!$this->estAdmin()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => 'Accès refusé']);
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=dashboard');
            exit();
        }

        // Logging pour diagnostic
        $logFile = __DIR__ . '/../logs/annee_logs.txt';
        $postData = print_r($_POST, true);
        $sessionData = print_r([ 'session_role' => $_SESSION['role'] ?? null, 'session_user' => $_SESSION['login'] ?? null ], true);
        @file_put_contents($logFile, "\n---\n" . date('Y-m-d H:i:s') . "\nPOST: " . $postData . "SESSION: " . $sessionData, FILE_APPEND);

        $id = $_POST['id_annee'] ?? null;
        $action = $_POST['action_type'] ?? 'select';

        try {
            if ($action === 'create') {
                $libelle = trim($_POST['libelle'] ?? '');
                if (empty($libelle)) throw new Exception('Libellé obligatoire');
                
                $this->pdo->beginTransaction();
                
                // Créer la nouvelle année
                $stmt = $this->pdo->prepare("INSERT INTO annee_scolaire (libelle, statut) VALUES (:libelle, 'inactive')");
                $stmt->execute(['libelle' => $libelle]);
                $id = $this->pdo->lastInsertId();

                // Récupérer l'année scolaire précédente active
                $stmt = $this->pdo->prepare("SELECT id, libelle FROM annee_scolaire WHERE statut = 'active' ORDER BY id DESC LIMIT 1");
                $stmt->execute();
                $prev_annee = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$prev_annee) {
                    $stmt = $this->pdo->prepare("SELECT id, libelle FROM annee_scolaire WHERE id != :id ORDER BY id DESC LIMIT 1");
                    $stmt->execute(['id' => $id]);
                    $prev_annee = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                if ($prev_annee) {
                    $prev_annee_id = $prev_annee['id'];
                    $prev_annee_libelle = $prev_annee['libelle'];

                    // Copier uniquement les élèves de l'année précédente ayant une moyenne annuelle strictement inférieure à 9.5
                    $stmt = $this->pdo->prepare(
                        "SELECT DISTINCT i.matricule_eleve, i.id_classe
                         FROM inscription i
                         JOIN eleve e ON e.matricule = i.matricule_eleve
                         WHERE i.id_annee = :prev_annee_id"
                    );
                    $stmt->execute(['prev_annee_id' => $prev_annee_id]);
                    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $insertInscription = $this->pdo->prepare(
                        "INSERT INTO inscription (matricule_eleve, id_classe, id_annee, date_inscription) VALUES (:matricule_eleve, :id_classe, :id_annee, CURDATE())"
                    );

                    foreach ($inscriptions as $inscription) {
                        $moyenneAnnuel = $this->eleveModele->calculerMoyenneAnnuellePourAnnee($inscription['matricule_eleve'], $prev_annee_libelle);
                        if ($moyenneAnnuel !== null && $moyenneAnnuel < 9.5) {
                            $insertInscription->execute([
                                'matricule_eleve' => $inscription['matricule_eleve'],
                                'id_classe' => $inscription['id_classe'],
                                'id_annee' => $id,
                            ]);
                        }
                    }

                    // Supprimer toute recette existante pour la nouvelle année afin de commencer à 0
                    $stmt = $this->pdo->prepare("DELETE FROM paiement WHERE annee_scolaire = :annee_libelle");
                    $stmt->execute(['annee_libelle' => $libelle]);
                }

                $this->pdo->commit();

                // Après création, poursuivre la sélection automatiquement
                $action = 'select';
            }

            if ($action === 'select' && $id) {
                // Récupérer le libellé
                $stmt = $this->pdo->prepare("SELECT libelle FROM annee_scolaire WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $id]);
                $libelle = $stmt->fetchColumn();

                $_SESSION['annee_scolaire_id'] = (int)$id;
                $_SESSION['annee_scolaire_libelle'] = $libelle ?: null;

                // Optionnel: marquer en base comme active (désactiver les autres)
                $this->pdo->beginTransaction();
                $this->pdo->prepare("UPDATE annee_scolaire SET statut = 'inactive'")->execute();
                $this->pdo->prepare("UPDATE annee_scolaire SET statut = 'active' WHERE id = :id")->execute(['id' => $id]);
                $this->pdo->commit();

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['succes' => true, 'message' => 'Année sélectionnée: ' . $libelle, 'libelle' => $libelle]);
                exit();
            }

            if ($action === 'update' && $id) {
                $libelle = trim($_POST['libelle'] ?? '');
                $statut = in_array($_POST['statut'] ?? '', ['active', 'inactive']) ? $_POST['statut'] : 'inactive';
                
                if (empty($libelle)) throw new Exception('Libellé obligatoire');

                $this->pdo->beginTransaction();
                $stmt = $this->pdo->prepare("UPDATE annee_scolaire SET libelle = :libelle, statut = :statut WHERE id = :id");
                $stmt->execute(['libelle' => $libelle, 'statut' => $statut, 'id' => $id]);
                $this->pdo->commit();

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['succes' => true, 'message' => 'Année modifiée avec succès']);
                exit();
            }

            if ($action === 'delete' && $id) {
                // Vérifier qu'on ne supprime pas l'année active
                $stmt = $this->pdo->prepare("SELECT statut FROM annee_scolaire WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch();
                if ($row && $row['statut'] === 'active') {
                    throw new Exception('Impossible de supprimer l\'année active');
                }

                $this->pdo->beginTransaction();
                // Supprimer les inscriptions liées
                $stmt = $this->pdo->prepare("DELETE FROM inscription WHERE id_annee = :id");
                $stmt->execute(['id' => $id]);
                // Supprimer l'année
                $stmt = $this->pdo->prepare("DELETE FROM annee_scolaire WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $this->pdo->commit();

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['succes' => true, 'message' => 'Année supprimée avec succès']);
                exit();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => 'Paramètres invalides']);
            exit();

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['succes' => false, 'message' => $e->getMessage()]);
            exit();
        }
    }

    // ==================== SAISIE DES NOTES AVEC MENSUELS ====================
    public function afficherSaisieNotes() {
        $classe_selectionnee = $_GET['id_classe'] ?? null;
        $id_trimestre_selectionne = $_GET['id_trimestre'] ?? null;
        $eleve_index = isset($_GET['eleve_idx']) ? (int)$_GET['eleve_idx'] : 0;
        
        // Récupération des classes
        $classes = $this->pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC")->fetchAll();
        
        // Nom de la classe
        $nom_classe = '';
        if ($classe_selectionnee) {
            $stmt = $this->pdo->prepare("SELECT nom_classe FROM classe WHERE id = :id");
            $stmt->execute(['id' => $classe_selectionnee]);
            $nom_classe = $stmt->fetchColumn();
        }
        
        // Professeur principal
        $prof_principal = 'Non assigné';
        if ($classe_selectionnee) {
            $stmt = $this->pdo->prepare("SELECT e.nom, e.prenom FROM enseignant e JOIN affectation_enseignant a ON e.id_utilisateur = a.id_utilisateur WHERE a.id_classe = :id_classe AND e.statut = 'Titulaire' LIMIT 1");
            $stmt->execute(['id_classe' => $classe_selectionnee]);
            $ens = $stmt->fetch();
            if ($ens) $prof_principal = $ens['nom'] . ' ' . $ens['prenom'];
        }
        
        // Récupérer les mensuels du trimestre
        $mensuels = [];
        if ($id_trimestre_selectionne) {
            $stmt = $this->pdo->prepare("SELECT id, numero_sequence, nom FROM sequence WHERE id_trimestre = :id_trimestre ORDER BY numero_sequence ASC");
            $stmt->execute(['id_trimestre' => $id_trimestre_selectionne]);
            $mensuels = $stmt->fetchAll();
        }
        
        // Domaines officiels
        $domaines_officiels = ["I. LANGUES ET COMMUNICATION", "II. SCIENCE ET TECHNOLOGIE", "III. SCIENCES HUMAINES", "IV. L'AVENTURE HUMAINE"];
        $matieres_par_domaine = [];
        foreach ($domaines_officiels as $dom) $matieres_par_domaine[$dom] = [];
        
        $liste_eleves = [];
        $eleve_courant = null;
        $total_eleves = 0;
        $notes_par_matiere = [];
        $noteObsExistantes = [];
        $message_succes = "";
        $message_erreur = "";
        
        // Traitement POST - Enregistrement des notes
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_bulletin_eleve'])) {
            $id_classe = $_POST['id_classe'] ?? '';
            $matricule_eleve = $_POST['matricule_eleve'] ?? '';
            $id_trimestre = $_POST['id_trimestre'] ?? '';
            $current_idx = (int)($_POST['eleve_idx'] ?? 0);
            $notes = $_POST['notes'] ?? [];
            $observations = $_POST['observations'] ?? [];
            $suivant = isset($_POST['suivant']);

            if (!empty($matricule_eleve) && !empty($id_trimestre)) {
                try {
                    $this->pdo->beginTransaction();

                    foreach ($notes as $id_matiere => $sequences) {
                        foreach ($sequences as $id_sequence => $valeur_note) {
                            if ($valeur_note !== '') {
                                $this->sauvegarderNote($matricule_eleve, $id_matiere, $id_trimestre, $id_sequence, $valeur_note);
                            }
                        }
                    }

                    foreach ($observations as $id_matiere => $obs) {
                        if ($obs !== '') {
                            $this->sauvegarderObservation($matricule_eleve, $id_matiere, $id_trimestre, $obs);
                        }
                    }

                    $this->pdo->commit();

                    $next_idx = $current_idx + 1;
                    header("Location: index.php?action=saisie_note&id_classe=$id_classe&id_trimestre=$id_trimestre&eleve_idx=$next_idx&success=1");
                    exit;
                } catch (Exception $e) {
                    $this->pdo->rollBack();
                    $message_erreur = "Erreur : " . $e->getMessage();
                }
            }
        }
        
        if (isset($_GET['success']) && $_GET['success'] == 1) {
            $message_succes = "Notes enregistrées avec succès !";
        }
        
        // Chargement des données
        if ($classe_selectionnee && $id_trimestre_selectionne) {
            $stmt_el = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom ASC, prenom ASC");
            $stmt_el->execute(['id_classe' => $classe_selectionnee]);
            $liste_eleves = $stmt_el->fetchAll();
            $total_eleves = count($liste_eleves);
            
            if ($total_eleves > 0 && $eleve_index >= $total_eleves) {
                echo "<script>alert('Félicitations ! Tous les élèves ont été évalués.'); window.location.href='index.php?action=dashboard';</script>";
                exit;
            }
            
            if (!empty($liste_eleves) && $eleve_index < $total_eleves) {
                $eleve_courant = $liste_eleves[$eleve_index];
            }
            
            if ($eleve_courant) {
                $stmt_note = $this->pdo->prepare("SELECT id_matiere, id_sequence, valeur, observation FROM note WHERE matricule_eleve = :matricule AND id_trimestre = :trimestre");
                $stmt_note->execute(['matricule' => $eleve_courant['matricule'], 'trimestre' => $id_trimestre_selectionne]);
                while ($row = $stmt_note->fetch()) {
                    $notes_par_matiere[$row['id_matiere']][$row['id_sequence']] = $row['valeur'];
                    if ($row['observation']) $noteObsExistantes[$row['id_matiere']] = $row['observation'];
                }
            }
            
            $stmt_mat = $this->pdo->prepare("SELECT id, nom_matiere, coefficient, domaine FROM matiere WHERE id_classe = :id_classe ORDER BY domaine ASC, nom_matiere ASC");
            $stmt_mat->execute(['id_classe' => $classe_selectionnee]);
            $matieres_classe = $stmt_mat->fetchAll();
            
            foreach ($matieres_classe as $mat) {
                $dom = $mat['domaine'];
                $matieres_par_domaine[$dom][] = $mat;
            }
        }
        
        // Calculs pour l'affichage
        $stats_classe = $this->calculerStatsClasse($classe_selectionnee, $id_trimestre_selectionne);
        $rang_eleve = $this->calculerRangEleve($eleve_courant['matricule'] ?? null, $classe_selectionnee, $id_trimestre_selectionne);
        $moyennes_mensuelles = $this->calculerMoyennesMensuelles($classe_selectionnee, $id_trimestre_selectionne, $mensuels);
        $trimestre_nom = $this->getTrimestreNom($id_trimestre_selectionne);
        
        require_once 'vues/notes/saisie.php';
    }

    // ==================== MÉTHODES D'AIDE ====================
    
    private function sauvegarderNote($matricule, $id_matiere, $id_trimestre, $id_sequence, $valeur) {
        $check = $this->pdo->prepare("SELECT id FROM note WHERE matricule_eleve = :mat AND id_matiere = :mat_id AND id_trimestre = :trim AND id_sequence = :seq");
        $check->execute(['mat' => $matricule, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence]);
        
        if ($check->fetch()) {
            $update = $this->pdo->prepare("UPDATE note SET valeur = :val WHERE matricule_eleve = :mat AND id_matiere = :mat_id AND id_trimestre = :trim AND id_sequence = :seq");
            $update->execute(['val' => $valeur, 'mat' => $matricule, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence]);
        } else {
            $insert = $this->pdo->prepare("INSERT INTO note (matricule_eleve, id_matiere, id_trimestre, id_sequence, valeur) VALUES (:mat, :mat_id, :trim, :seq, :val)");
            $insert->execute(['mat' => $matricule, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence, 'val' => $valeur]);
        }
    }

    private function sauvegarderObservation($matricule, $id_matiere, $id_trimestre, $obs) {
        $update = $this->pdo->prepare("UPDATE note SET observation = :obs WHERE matricule_eleve = :mat AND id_matiere = :mat_id AND id_trimestre = :trim");
        $update->execute(['obs' => $obs, 'mat' => $matricule, 'mat_id' => $id_matiere, 'trim' => $id_trimestre]);
    }

    private function calculerStatsClasse($id_classe, $id_trimestre) {
        if (!$id_classe || !$id_trimestre) return ['moyenne_classe' => 0];
        
        $stmt = $this->pdo->prepare("SELECT AVG(n.valeur) as moyenne FROM note n JOIN eleve e ON e.matricule = n.matricule_eleve WHERE e.id_classe = :id_classe AND n.id_trimestre = :trimestre AND n.valeur IS NOT NULL");
        $stmt->execute(['id_classe' => $id_classe, 'trimestre' => $id_trimestre]);
        $moyenne_classe = $stmt->fetchColumn() ?: 0;
        
        return ['moyenne_classe' => $moyenne_classe];
    }

    private function calculerRangEleve($matricule, $id_classe, $id_trimestre) {
        if (!$matricule) return 'N/A';
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM eleve WHERE id_classe = :id_classe");
        $stmt->execute(['id_classe' => $id_classe]);
        $total = $stmt->fetchColumn();
        
        $stmt = $this->pdo->prepare("SELECT AVG(n.valeur) as moyenne FROM note n WHERE n.matricule_eleve = :matricule AND n.id_trimestre = :trimestre AND n.valeur IS NOT NULL");
        $stmt->execute(['matricule' => $matricule, 'trimestre' => $id_trimestre]);
        $moyenne = $stmt->fetchColumn();
        
        if (!$moyenne) return 'N/A';
        
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT e.matricule) FROM eleve e JOIN note n ON n.matricule_eleve = e.matricule WHERE e.id_classe = :id_classe AND n.id_trimestre = :trimestre AND n.valeur IS NOT NULL GROUP BY e.matricule HAVING AVG(n.valeur) > :moyenne");
        $stmt->execute(['id_classe' => $id_classe, 'trimestre' => $id_trimestre, 'moyenne' => $moyenne]);
        $rang = $stmt->rowCount() + 1;
        
        return $rang . 'e/' . $total;
    }

    private function calculerMoyennesMensuelles($id_classe, $id_trimestre, $mensuels) {
        $moyennes = [];
        if (empty($mensuels)) return $moyennes;
        
        foreach ($mensuels as $mensuel) {
            $stmt = $this->pdo->prepare("SELECT AVG(n.valeur) as moyenne FROM note n JOIN eleve e ON e.matricule = n.matricule_eleve WHERE e.id_classe = :id_classe AND n.id_trimestre = :trimestre AND n.id_sequence = :sequence AND n.valeur IS NOT NULL");
            $stmt->execute(['id_classe' => $id_classe, 'trimestre' => $id_trimestre, 'sequence' => $mensuel['id']]);
            $moyennes[] = ['nom' => $mensuel['nom'], 'moyenne' => $stmt->fetchColumn() ?: 0];
        }
        return $moyennes;
    }

    private function getTrimestreNom($id) {
        if (!$id) return 'PREMIER TRIMESTRE';
        $stmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $nom = $stmt->fetchColumn();
        return $nom ? strtoupper($nom) : 'PREMIER TRIMESTRE';
    }

    // ==================== GESTION DES NOTES ====================
    
    public function enregistrerNotes() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $id_classe = $_POST['id_classe'] ?? null;
            $id_matiere = $_POST['id_matiere'] ?? null;
            $id_trimestre = $_POST['id_trimestre'] ?? null;
            $notes = $_POST['notes'] ?? [];
             
            if (!$id_classe || !$id_matiere || !$id_trimestre || empty($notes)) {
                $this->afficherSaisieNotes("Veuillez remplir correctement tous les champs.", "danger");
                exit();
            }

            if (!$this->verifierClasseAutorisee($id_classe)) {
                $this->afficherConnexion("Accès refusé pour cette classe.");
                exit();
            }

            try {
                $sql = "INSERT INTO note (matricule_eleve, id_matiere, id_trimestre, valeur, date_saisie) 
                        VALUES (:matricule, :id_matiere, :id_trimestre, :valeur, NOW())
                        ON DUPLICATE KEY UPDATE valeur = :valeur, date_saisie = NOW()";
                
                $stmt = $this->pdo->prepare($sql);

                foreach ($notes as $matricule => $valeur_note) {
                    if ($valeur_note === '') continue; 

                    $stmt->execute([
                        ':matricule'    => $matricule,
                        ':id_matiere'   => $id_matiere,
                        ':id_trimestre' => $id_trimestre,
                        ':valeur'       => floatval($valeur_note)
                    ]);
                }

                $this->afficherSaisieNotes("Les notes ont été enregistrées avec succès !", "success");
                exit();
            } catch (Exception $e) {
                $this->afficherSaisieNotes("Erreur lors de l'enregistrement : " . $e->getMessage(), "danger");
                exit();
            }
        }
    }

    // ==================== BULLETINS ====================
    
    public function listeBulletins() {
        $this->verifierAuthentification();

        try {
            if ($this->estAdmin()) {
                $classes = $this->pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC")->fetchAll();
            } else {
                $classes = $this->recupererClassesEnseignant($_SESSION['user_id']);
            }
            $trimestres = $this->pdo->query("SELECT id, nom FROM trimestre ORDER BY id ASC")->fetchAll();

            $eleves = [];
            $matieres = [];
            $classe_selectionnee = $_GET['id_classe'] ?? null;
            $trimestre_selectionne = $_GET['id_trimestre'] ?? null;
            $id_sequence_selectionnee = $_GET['id_sequence'] ?? 1;
            $eleve_index = isset($_GET['eleve_idx']) ? (int)$_GET['eleve_idx'] : 0;

            if (!$this->estAdmin() && $classe_selectionnee && !$this->verifierClasseAutorisee($classe_selectionnee)) {
                $classe_selectionnee = $classes[0]['id'] ?? null;
            }

            if (!$classe_selectionnee && !$this->estAdmin()) {
                $classe_selectionnee = $classes[0]['id'] ?? null;
            }

            $sequences = [];
            if ($trimestre_selectionne) {
                $stmt = $this->pdo->prepare("SELECT id, numero_sequence, nom FROM sequence WHERE id_trimestre = :id_trimestre ORDER BY numero_sequence ASC");
                $stmt->execute(['id_trimestre' => $trimestre_selectionne]);
                $sequences = $stmt->fetchAll();
            }

            if ($classe_selectionnee && $trimestre_selectionne) {
                $stmt = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom ASC, prenom ASC");
                $stmt->execute(['id_classe' => $classe_selectionnee]);
                $eleves = $stmt->fetchAll();

                $stmt = $this->pdo->prepare("SELECT id, nom_matiere, coefficient, domaine FROM matiere WHERE id_classe = :id_classe ORDER BY domaine ASC, nom_matiere ASC");
                $stmt->execute(['id_classe' => $classe_selectionnee]);
                $matieres = $stmt->fetchAll();
            }

            $eleve_courant = isset($eleves[$eleve_index]) ? $eleves[$eleve_index] : null;
            $bulletin_data = [];
            $bulletin_annuel = false;
            
            if ($eleve_courant && $classe_selectionnee && $trimestre_selectionne) {
                $noteColValeur = 'valeur';
                $noteColObservation = 'observation';

                if ($trimestre_selectionne == 3) {
                    $bulletin_annuel = true;
                    $stmt = $this->pdo->prepare(
                        "SELECT m.nom_matiere, m.coefficient, m.domaine, AVG(n.valeur) AS valeur, GROUP_CONCAT(DISTINCT COALESCE(n.observation, '') SEPARATOR ' / ') AS observation
                         FROM matiere m
                         LEFT JOIN note n ON n.id_matiere = m.id AND n.matricule_eleve = :matricule
                         WHERE m.id_classe = :id_classe
                         GROUP BY m.id
                         ORDER BY m.domaine ASC, m.nom_matiere ASC"
                    );
                    $stmt->execute([
                        'matricule' => $eleve_courant['matricule'],
                        'id_classe' => $classe_selectionnee
                    ]);
                    $bulletin_data = $stmt->fetchAll();
                } else {
                    $stmt = $this->pdo->prepare(
                        "SELECT m.nom_matiere, m.coefficient, COALESCE(n.valeur, '') AS valeur, COALESCE(n.observation, '') AS observation, m.domaine
                         FROM matiere m
                         LEFT JOIN note n ON n.id_matiere = m.id AND n.matricule_eleve = :matricule AND n.id_trimestre = :id_trimestre AND n.id_sequence = :id_sequence
                         WHERE m.id_classe = :id_classe
                         ORDER BY m.domaine ASC, m.nom_matiere ASC"
                    );
                    $stmt->execute([
                        'matricule' => $eleve_courant['matricule'],
                        'id_trimestre' => $trimestre_selectionne,
                        'id_sequence' => $id_sequence_selectionnee,
                        'id_classe' => $classe_selectionnee
                    ]);
                    $bulletin_data = $stmt->fetchAll();
                }
            }
        } catch (Exception $e) {
            die("Erreur lors du chargement des bulletins : " . $e->getMessage());
        }

        require_once __DIR__ . '/../vues/notes/bulletins.php';
    }

    public function modifierMatieres() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=bulletins');
            exit();
        }

        $this->verifierAuthentification();

        $id_classe = $_POST['id_classe'] ?? null;
        if (!$this->verifierClasseAutorisee($id_classe)) {
            die('Accès refusé : classe non autorisée.');
        }
        $matiere_ids = $_POST['matiere_id'] ?? [];
        $noms = $_POST['matiere_nom'] ?? [];
        $coeffs = $_POST['matiere_coef'] ?? [];

        if (!$id_classe || empty($matiere_ids)) {
            header("Location: index.php?action=bulletins&id_classe={$id_classe}");
            exit();
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE matiere SET nom_matiere = :nom, coefficient = :coef WHERE id = :id AND id_classe = :id_classe");
            foreach ($matiere_ids as $id) {
                $nom = trim($noms[$id] ?? '');
                $coef = intval($coeffs[$id] ?? 1);
                if ($nom === '') continue;

                $stmt->execute([
                    ':nom' => $nom,
                    ':coef' => max(1, $coef),
                    ':id' => $id,
                    ':id_classe' => $id_classe
                ]);
            }
        } catch (Exception $e) {
            die("Erreur lors de la mise à jour des matières : " . $e->getMessage());
        }

        header("Location: index.php?action=bulletins&id_classe={$id_classe}&id_trimestre=" . urlencode($_POST['id_trimestre'] ?? ''));
        exit();
    }

    public function genererBulletinsClasse() {
        $this->verifierAuthentification();

        $id_classe = $_GET['id_classe'] ?? null;
        $id_trimestre = $_GET['id_trimestre'] ?? null;

        if (!$id_classe || !$id_trimestre) {
            header('Location: index.php?action=bulletins');
            exit();
        }

        if (!$this->verifierClasseAutorisee($id_classe)) {
            die('Accès refusé : classe non autorisée.');
        }

        try {
            $stmt = $this->pdo->prepare("SELECT nom_classe FROM classe WHERE id = :id_classe");
            $stmt->execute(['id_classe' => $id_classe]);
            $classe = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("SELECT id, nom_matiere, coefficient, domaine FROM matiere WHERE id_classe = :id_classe ORDER BY domaine ASC, nom_matiere ASC");
            $stmt->execute(['id_classe' => $id_classe]);
            $matieres = $stmt->fetchAll();

            $stmt = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom ASC, prenom ASC");
            $stmt->execute(['id_classe' => $id_classe]);
            $eleves = $stmt->fetchAll();

            $listeMatieres = [];
            foreach ($matieres as $m) {
                $listeMatieres[$m['id']] = $m;
            }

            if ($id_trimestre == 3) {
                $sqlNotes = "SELECT n.matricule_eleve, n.id_matiere, AVG(n.valeur) AS valeur, GROUP_CONCAT(DISTINCT COALESCE(n.observation, '') SEPARATOR ' / ') AS observation 
                             FROM note n
                             JOIN eleve e ON e.matricule = n.matricule_eleve
                             WHERE e.id_classe = :id_classe
                             GROUP BY n.matricule_eleve, n.id_matiere";
                $notesStmt = $this->pdo->prepare($sqlNotes);
                $notesStmt->execute(['id_classe' => $id_classe]);
            } else {
                $sqlNotes = "SELECT n.matricule_eleve, n.id_matiere, n.valeur, n.observation 
                             FROM note n
                             JOIN eleve e ON e.matricule = n.matricule_eleve
                             WHERE e.id_classe = :id_classe AND n.id_trimestre = :id_trimestre";
                $notesStmt = $this->pdo->prepare($sqlNotes);
                $notesStmt->execute(['id_classe' => $id_classe, 'id_trimestre' => $id_trimestre]);
            }
            $notesBrutes = $notesStmt->fetchAll();

            $notesLookup = [];
            foreach ($notesBrutes as $note) {
                $notesLookup[$note['matricule_eleve']][$note['id_matiere']] = [
                    'valeur' => $note['valeur'],
                    'observation' => $note['observation'] ?? ''
                ];
            }

            $bulletins = [];
            foreach ($eleves as $eleve) {
                $matieresEleve = [];
                $somme = 0;
                $coefTotal = 0;

                foreach ($listeMatieres as $matiere) {
                    $note = $notesLookup[$eleve['matricule']][$matiere['id']] ?? null;
                    $valeur = ($note && $note['valeur'] !== null && $note['valeur'] !== '') ? floatval($note['valeur']) : null;

                    if ($valeur !== null) {
                        $somme += $valeur * intval($matiere['coefficient']);
                        $coefTotal += intval($matiere['coefficient']);
                    }

                    $matieresEleve[] = [
                        'nom_matiere' => $matiere['nom_matiere'],
                        'coefficient' => intval($matiere['coefficient']),
                        'valeur' => $valeur,
                        'observation' => $note['observation'] ?? ''
                    ];
                }

                $bulletins[] = [
                    'eleve' => $eleve,
                    'matieres' => $matieresEleve,
                    'moyenne' => $coefTotal > 0 ? $somme / $coefTotal : null,
                    'coef_total' => $coefTotal
                ];
            }

            usort($bulletins, function ($a, $b) {
                if ($a['moyenne'] === $b['moyenne']) {
                    return strcmp($a['eleve']['nom'] . $a['eleve']['prenom'], $b['eleve']['nom'] . $b['eleve']['prenom']);
                }
                if ($a['moyenne'] === null) return 1;
                if ($b['moyenne'] === null) return -1;
                return ($b['moyenne'] <=> $a['moyenne']);
            });

            $rang = 1;
            foreach ($bulletins as $index => $bulletin) {
                $bulletins[$index]['rang'] = $bulletin['moyenne'] !== null ? $rang++ : null;
            }

            $trimestreStmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id_trimestre");
            $trimestreStmt->execute(['id_trimestre' => $id_trimestre]);
            $trimestre = $trimestreStmt->fetchColumn() ?: "Trimestre $id_trimestre";

        } catch (Exception $e) {
            die("Erreur lors de la génération des bulletins : " . $e->getMessage());
        }

        require_once __DIR__ . '/../vues/notes/bulletins_classe.php';
    }

    public function visualiserBulletin() {
        $id_classe = $_GET['id_classe'] ?? null;
        $id_trimestre = $_GET['id_trimestre'] ?? null;
        $matricule = $_GET['matricule'] ?? null;

        if (!$id_classe || !$id_trimestre || !$matricule) {
            header('Location: index.php?action=bulletins');
            exit();
        }

        try {
            if (!$this->verifierClasseAutorisee($id_classe)) {
                die('Accès refusé : classe non autorisée.');
            }

            $stmt = $this->pdo->prepare("SELECT e.matricule, e.nom, e.prenom, c.nom_classe FROM eleve e JOIN classe c ON e.id_classe = c.id WHERE e.matricule = :matricule AND e.id_classe = :id_classe");
            $stmt->execute(['matricule' => $matricule, 'id_classe' => $id_classe]);
            $eleve = $stmt->fetch();

            if (!$eleve) {
                die("Élève introuvable.");
            }

            $stmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id_trimestre");
            $stmt->execute(['id_trimestre' => $id_trimestre]);
            $trimestre = $stmt->fetchColumn() ?: "Trimestre $id_trimestre";

            $enseignantStmt = $this->pdo->prepare(
                "SELECT e.nom, e.prenom
                 FROM enseignant e
                 JOIN affectation_enseignant a ON e.id_utilisateur = a.id_utilisateur
                 WHERE a.id_classe = :id_classe AND LOWER(e.statut) = 'titulaire'
                 LIMIT 1"
            );
            $enseignantStmt->execute(['id_classe' => $id_classe]);
            $enseignant = $enseignantStmt->fetch();
            $enseignant_titulaire = $enseignant ? trim($enseignant['prenom'] . ' ' . $enseignant['nom']) : 'Aucun titulaire';

            $stmt = $this->pdo->prepare(
                "SELECT m.domaine, m.nom_matiere, m.coefficient,
                    MAX(CASE WHEN n.id_sequence = 1 THEN COALESCE(n.valeur, '') END) AS seq1,
                    MAX(CASE WHEN n.id_sequence = 2 THEN COALESCE(n.valeur, '') END) AS seq2,
                    MAX(CASE WHEN n.id_sequence = 3 THEN COALESCE(n.valeur, '') END) AS seq3,
                    MAX(CASE WHEN n.id_sequence = 4 THEN COALESCE(n.valeur, '') END) AS seq4,
                    MAX(CASE WHEN n.id_sequence = 5 THEN COALESCE(n.valeur, '') END) AS seq5,
                    MAX(CASE WHEN n.id_sequence = 6 THEN COALESCE(n.valeur, '') END) AS seq6,
                    MAX(CASE WHEN n.id_sequence = 7 THEN COALESCE(n.valeur, '') END) AS seq7,
                    MAX(CASE WHEN n.id_sequence = 8 THEN COALESCE(n.valeur, '') END) AS seq8,
                    MAX(CASE WHEN n.id_sequence = 9 THEN COALESCE(n.valeur, '') END) AS seq9
                 FROM matiere m
                 LEFT JOIN note n ON n.id_matiere = m.id
                    AND n.matricule_eleve = :matricule
                    AND n.id_trimestre = :id_trimestre
                 WHERE m.id_classe = :id_classe
                 GROUP BY m.id, m.domaine, m.nom_matiere, m.coefficient
                 ORDER BY m.domaine ASC, m.nom_matiere ASC"
            );
            $stmt->execute([
                'matricule' => $matricule,
                'id_trimestre' => $id_trimestre,
                'id_classe' => $id_classe
            ]);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            die("Erreur lors de la préparation du bulletin : " . $e->getMessage());
        }

        require_once __DIR__ . '/../vues/notes/bulletin_pdf.php';
    }

    // ==================== AJOUT MATIERE AJAX ====================
    // Ajouter/modifier/supprimer des matières (AJAX)
public function modifierMatiereAjax() {
    header('Content-Type: application/json; charset=utf-8');
    
    $id_matiere = $_POST['id_matiere'] ?? 0;
    $nom_matiere = trim($_POST['nom_matiere'] ?? '');
    $coefficient = intval($_POST['coefficient'] ?? 1);
    
    if (!$id_matiere || !$nom_matiere) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit();
    }
    
    try {
        $stmt = $this->pdo->prepare("UPDATE matiere SET nom_matiere = :nom, coefficient = :coef WHERE id = :id");
        $stmt->execute(['nom' => $nom_matiere, 'coef' => $coefficient, 'id' => $id_matiere]);
        echo json_encode(['success' => true, 'message' => 'Matière modifiée']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

public function supprimerMatiereAjax() {
    header('Content-Type: application/json; charset=utf-8');
    
    $id_matiere = $_POST['id_matiere'] ?? 0;
    
    if (!$id_matiere) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        exit();
    }
    
    try {
        $stmt = $this->pdo->prepare("DELETE FROM matiere WHERE id = :id");
        $stmt->execute(['id' => $id_matiere]);
        echo json_encode(['success' => true, 'message' => 'Matière supprimée']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

    public function creerMatiereAjax() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
            exit();
        }

        $id_classe = $_POST['id_classe'] ?? '';
        if (!$this->verifierClasseAutorisee($id_classe)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé : classe non autorisée.']);
            exit();
        }
        
        $nom_matiere = trim($_POST['nom_matiere'] ?? '');
        $coefficient = intval($_POST['coefficient'] ?? 1);
        $domaine = $_POST['domaine'] ?? 'I. LANGUES ET COMMUNICATION';

        if (empty($id_classe) || empty($nom_matiere)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Veuillez renseigner la classe et le nom de la matière.']);
            exit();
        }

        try {
            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM matiere WHERE nom_matiere = :nom AND id_classe = :id_classe");
            $checkStmt->execute(['nom' => $nom_matiere, 'id_classe' => $id_classe]);

            if ($checkStmt->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Une matière portant ce nom existe déjà dans cette classe.']);
                exit();
            }

            $stmt = $this->pdo->prepare("INSERT INTO matiere (nom_matiere, coefficient, id_classe, domaine) VALUES (:nom, :coeff, :id_cl, :dom)");
            $stmt->execute([
                'nom' => $nom_matiere,
                'coeff' => max(1, $coefficient),
                'id_cl' => $id_classe,
                'dom' => $domaine
            ]);

            echo json_encode(['success' => true, 'message' => 'Matière ajoutée avec succès.']);
        } catch (Exception $e) {
            http_response_code(500);
            $message = $e->getMessage();
            if (strpos($message, 'Duplicate entry') !== false) {
                $message = 'Une matière portant ce nom existe déjà. Choisissez un nom différent.';
            }
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $message]);
        }

        exit();
    }
}
?>