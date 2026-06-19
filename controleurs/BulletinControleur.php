<?php
// controleurs/BulletinControleur.php

define('ROOT_PATH', 'C:/wamp64/www/Projet_de_stage/');

require_once __DIR__ . '/../modeles/Bulletin.php';

class BulletinControleur {
    private $bulletinModele;
    private $pdo;

    public function __construct() {
        $this->initialiserConnexion();
        $this->bulletinModele = new Bulletin($this->pdo);
    }

    private function initialiserConnexion() {
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Exception $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    private function verifierAuthentification() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['role'])) {
            header('Location: index.php?action=connexion');
            exit();
        }
    }

    // Dans BulletinControleur.php
public function saisieNote() {
    // Récupération des données
    $classe_selectionnee = $_GET['id_classe'] ?? null;
    $id_trimestre_selectionne = $_GET['id_trimestre'] ?? null;
    
    if ($classe_selectionnee && $id_trimestre_selectionne) {
        // Récupérer les matières de la classe
        $matieres_par_domaine = $this->getMatieresParDomaine($classe_selectionnee);
        
        // Récupérer les mensuels pour le trimestre
        $mensuels = $this->getMensuels($id_trimestre_selectionne);
        
        // Récupérer les notes existantes
        $notes_par_matiere = $this->getNotesByClasseAndTrimestre($classe_selectionnee, $id_trimestre_selectionne);
        
        // Vérifiez que les données ne sont pas vides
        if (empty($matieres_par_domaine)) {
            $_SESSION['error'] = "Aucune matière trouvée pour cette classe. Veuillez d'abord ajouter des matières.";
        }
        
        if (empty($mensuels)) {
            $_SESSION['error'] = "Aucun mensuel configuré pour ce trimestre.";
        }
    }
    
    // Passer à la vue
    include_once('vues/notes/saisie.php');
}

    public function afficherBulletins() {
        $this->verifierAuthentification();

        $id_classe_selectionnee = $_GET['id_classe'] ?? null;
        $id_trimestre_selectionne = $_GET['id_trimestre'] ?? null;
        $matricule_eleve = $_GET['matricule'] ?? null;

        $classes = $this->pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC")->fetchAll();
        $trimestres = $this->pdo->query("SELECT id, nom FROM trimestre ORDER BY id ASC")->fetchAll();
        $options_trimestre = $trimestres;
        $options_trimestre[] = ['id' => 4, 'nom' => 'BILAN ANNUEL (Tous trimestres)'];

        $eleves_list = [];
        $bulletin_data = null;
        $eleve_data = null;
        $classe = null;
        $trimestre = null;
        $statuts_eleves = [];

        if ($id_classe_selectionnee && $id_trimestre_selectionne) {
            $stmt = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom");
            $stmt->execute(['id_classe' => $id_classe_selectionnee]);
            $eleves_list = $stmt->fetchAll();

            foreach ($eleves_list as &$eleve) {
                $eleve['bulletin_statut'] = $this->bulletinModele->getBulletinStatut($eleve['matricule'], $id_classe_selectionnee, $id_trimestre_selectionne);
                $statuts_eleves[$eleve['bulletin_statut']] = ($statuts_eleves[$eleve['bulletin_statut']] ?? 0) + 1;
            }
            unset($eleve);
            
            $stmt = $this->pdo->prepare("SELECT * FROM classe WHERE id = :id");
            $stmt->execute(['id' => $id_classe_selectionnee]);
            $classe = $stmt->fetch();
            $classe['effectif'] = count($eleves_list);
            
            if ($id_trimestre_selectionne == 4) {
                $trimestre = "BILAN ANNUEL";
            } else {
                $stmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id");
                $stmt->execute(['id' => $id_trimestre_selectionne]);
                $trimestre = $stmt->fetchColumn();
            }

            if ($matricule_eleve && !empty($eleves_list)) {
                $stmt = $this->pdo->prepare("SELECT * FROM eleve WHERE matricule = :matricule");
                $stmt->execute(['matricule' => $matricule_eleve]);
                $eleve_data = $stmt->fetch();
                
                if ($eleve_data) {
                    if ($id_trimestre_selectionne == 4) {
                        $bulletin_data = $this->bulletinModele->getBulletinAnnuel($matricule_eleve, $id_classe_selectionnee);
                    } else {
                        $bulletin_data = $this->bulletinModele->getBulletinTrimestriel($matricule_eleve, $id_trimestre_selectionne, $id_classe_selectionnee);
                    }
                }
            }
        }

        require_once ROOT_PATH . 'vues/bulletins/index.php';
    }

    public function genererPDF() {
        $this->verifierAuthentification();

        $matricule = $_GET['matricule'] ?? null;
        $id_trimestre = $_GET['id_trimestre'] ?? null;
        $id_classe = $_GET['id_classe'] ?? null;

        if (!$matricule || !$id_trimestre || !$id_classe) {
            die('Paramètres manquants');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM eleve WHERE matricule = :matricule");
        $stmt->execute(['matricule' => $matricule]);
        $eleve = $stmt->fetch();
        if (!$eleve) die('Élève non trouvé');

        $stmt = $this->pdo->prepare("SELECT * FROM classe WHERE id = :id");
        $stmt->execute(['id' => $id_classe]);
        $classe = $stmt->fetch();
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM eleve WHERE id_classe = :id_classe");
        $stmt->execute(['id_classe' => $id_classe]);
        $classe['effectif'] = (int)$stmt->fetchColumn();

        if ($id_trimestre == 4) {
            $trimestre = 'BILAN ANNUEL';
            $bulletin = $this->bulletinModele->getBulletinAnnuel($matricule, $id_classe);
        } else {
            $stmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id");
            $stmt->execute(['id' => $id_trimestre]);
            $trimestre = $stmt->fetchColumn();
            $bulletin = $this->bulletinModele->getBulletinTrimestriel($matricule, $id_trimestre, $id_classe);
        }

        require_once ROOT_PATH . 'vues/bulletins/pdf_template.php';
        exit();
    }

    public function genererBulletinsClassePDF() {
        $this->verifierAuthentification();
        
        $id_classe = $_GET['id_classe'] ?? null;
        $id_trimestre = $_GET['id_trimestre'] ?? null;
        
        if (!$id_classe || !$id_trimestre) {
            die('Paramètres manquants');
        }
        
        $stmt = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom");
        $stmt->execute(['id_classe' => $id_classe]);
        $eleves = $stmt->fetchAll();
        
        if (empty($eleves)) {
            die('Aucun élève dans cette classe');
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM classe WHERE id = :id");
        $stmt->execute(['id' => $id_classe]);
        $classe = $stmt->fetch();
        $classe['effectif'] = count($eleves);
        
        if ($id_trimestre == 4) {
            $trimestre = 'BILAN ANNUEL';
        } else {
            $stmt = $this->pdo->prepare("SELECT nom FROM trimestre WHERE id = :id");
            $stmt->execute(['id' => $id_trimestre]);
            $trimestre = $stmt->fetchColumn();
        }
        
        $tous_bulletins = [];
        foreach ($eleves as $eleve) {
            if ($id_trimestre == 4) {
                $bulletin = $this->bulletinModele->getBulletinAnnuel($eleve['matricule'], $id_classe);
            } else {
                $bulletin = $this->bulletinModele->getBulletinTrimestriel($eleve['matricule'], $id_trimestre, $id_classe);
            }
            $tous_bulletins[] = [
                'eleve' => $eleve,
                'bulletin' => $bulletin
            ];
        }
        
        require_once ROOT_PATH . 'vues/bulletins/pdf_classe_template.php';
        exit();
    }
    public function getStatistiquesBulletin() {
        $this->verifierAuthentification();

        $id_classe = $_GET['id_classe'] ?? null;
        if (!$id_classe) {
            echo json_encode(['status' => 'error', 'message' => 'Classe non spécifiée']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom");
        $stmt->execute(['id_classe' => $id_classe]);
        $eleves = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'eleves' => $eleves,
            'count' => count($eleves)
        ]);
    }
}
?>