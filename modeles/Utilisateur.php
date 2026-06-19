<?php
// modeles/Utilisateur.php

require_once __DIR__ . '/bdd.php';

class Utilisateur {
    private $db;

    public function __construct() {
        $this->db = Bdd::connexion();
    }

    public function verifierConnexion($login, $mot_de_passe) {
        try {
            $query = "SELECT * FROM utilisateur WHERE login = :login LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || empty($user['mot_de_passe'])) {
                return false;
            }

            // Vérification standard des hashs actuels
            if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                return $user;
            }

            // Ancien format : mot_de_passe hashé avec password_hash(password + salt)
            if (!empty($user['salt']) && password_verify($mot_de_passe . $user['salt'], $user['mot_de_passe'])) {
                $this->reparerHashMotDePasseAncien($user['id_utilisateur'] ?? $user['id'], $mot_de_passe);
                return $user;
            }

            // Ancien format : mot_de_passe stocké en clair
            if ($user['mot_de_passe'] === $mot_de_passe) {
                $this->reparerHashMotDePasseAncien($user['id_utilisateur'] ?? $user['id'], $mot_de_passe);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            die("<div style='color:red; padding:10px; border:1px solid red; background-color:#fee;'>
                    <strong>Erreur SQL Authentification :</strong> " . $e->getMessage() . "
                 </div>");
        }
    }

    /**
     * Récupère l'ensemble des statistiques pour le tableau de bord
     */
    public function recupererStatsDashboard() {
        try {
            // 1. Compte sécurisé des élèves avec fetchColumn
            $stmtEleves = $this->db->query("SELECT COUNT(*) AS total FROM eleve");
            $totalEleves = $stmtEleves->fetchColumn();

            // 2. Compte sécurisé des classes
            $stmtClasses = $this->db->query("SELECT COUNT(*) AS total FROM classe");
            $totalClasses = $stmtClasses->fetchColumn();

            // 3. Compte sécurisé des enseignants
            $stmtEnseignants = $this->db->query("SELECT COUNT(*) AS total FROM enseignant");
            $totalEnseignants = $stmtEnseignants->fetchColumn();

            // 4. Somme des recettes (en gérant le cas où la table est vide -> renvoie 0 au lieu de NULL)
            $stmtRecettes = $this->db->query("SELECT COALESCE(SUM(montant), 0) AS total FROM paiement");
            $totalRecettes = $stmtRecettes->fetchColumn();

            // 5. Répartition des élèves par classe via la table eleve
            $stmtRepartition = $this->db->query(
                "SELECT c.nom_classe, COUNT(e.matricule) AS effectif " .
                "FROM classe c " .
                "LEFT JOIN eleve e ON c.id = e.id_classe " .
                "GROUP BY c.id, c.nom_classe " .
                "ORDER BY c.id ASC"
            );
            $repartition = $stmtRepartition ? $stmtRepartition->fetchAll(PDO::FETCH_ASSOC) : [];

            return [
                "total_eleves" => $totalEleves ?: 0,
                "total_classes" => $totalClasses ?: 0,
                "total_enseignants" => $totalEnseignants ?: 0,
                "total_recettes" => $totalRecettes ?: 0,
                "repartition" => $repartition
            ];

        } catch (Exception $e) {
            return [
                "total_eleves" => 0,
                "total_classes" => 0,
                "total_enseignants" => 0,
                "total_recettes" => 0,
                "repartition" => []
            ];
        }
    }

      /**
 * Vérifie si le mot de passe respecte les règles de sécurité
 * Règles : 
 * - Au moins 8 caractères
 * - Au moins 1 lettre majuscule
 * - Au moins 1 lettre minuscule
 * - Au moins 1 chiffre
 * - Au moins 1 caractère spécial
 */
public function validerMotDePasse($mot_de_passe) {
    $erreurs = [];
    
    if (strlen($mot_de_passe) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    if (!preg_match('/[A-Z]/', $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins une lettre majuscule";
    }
    if (!preg_match('/[a-z]/', $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins une lettre minuscule";
    }
    if (!preg_match('/[0-9]/', $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    if (!preg_match('/[^a-zA-Z0-9]/', $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins un caractère spécial (@, #, $, %, !, etc.)";
    }
    
    return $erreurs;
}

/**
 * Hashage sécurisé du mot de passe
 */
public function hacherMotDePasse($mot_de_passe) {
    // Utilise password_hash (inclut un salt sécurisé automatiquement)
    return password_hash($mot_de_passe, PASSWORD_DEFAULT);
}

/**
 * Vérifier un mot de passe
 */
public function verifierMotDePasse($mot_de_passe, $hash) {
    return password_verify($mot_de_passe, $hash);
}

public function reparerHashMotDePasseAncien($id_utilisateur, $mot_de_passe) {
    try {
        $nouveauHash = $this->hacherMotDePasse($mot_de_passe);
        $update = $this->db->prepare("UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id");
        $update->execute([
            ':mot_de_passe' => $nouveauHash,
            ':id' => $id_utilisateur
        ]);
    } catch (PDOException $e) {
        error_log("Erreur mise à jour hash mot de passe utilisateur #{$id_utilisateur} : " . $e->getMessage());
    }
}

    /**
     * CRÉATION SÉCURISÉE ENTRÉE UTILISATEUR + PROFIL ENSEIGNANT (Résout l'erreur 1452)
     */
    public function sauvegarderEnseignantComplexe($donnees) {
        try {
            // Début de la transaction pour lier les deux insertions
            $this->db->beginTransaction();

            // ÉTAPE 1 : Insertion automatique dans la table 'utilisateur'
            $queryUser = "INSERT INTO utilisateur (login, mot_de_passe, role) VALUES (:login, :mdp, 'enseignant')";
            $stmtUser = $this->db->prepare($queryUser);
            
            // Génération dynamique du login (Ex: jean.tankam) et d'un mot de passe par défaut
            $login = strtolower($donnees['prenom'] . '.' . $donnees['nom']);
            $mdp_par_defaut = 'ChangeMe123!';
            $mdp_hash = $this->hacherMotDePasse($mdp_par_defaut);

            $stmtUser->execute([
                ':login' => $login,
                ':mot_de_passe' => $mdp_hash
            ]);

            // ÉTAPE 2 : Récupération instantanée de l'id_utilisateur généré par MySQL
            $id_utilisateur = $this->db->lastInsertId();

            // ÉTAPE 3 : Insertion dans la table 'enseignant' avec la bonne clé étrangère
            $queryEnseignant = "INSERT INTO enseignant (id_utilisateur, nom, prenom, telephone, statut, statut_activite) 
                                VALUES (:id_util, :nom, :prenom, :tel, :statut, 'actif')";
            
            $stmtEnseignant = $this->db->prepare($queryEnseignant);
            $stmtEnseignant->execute([
                ':id_util'   => $id_utilisateur,
                ':nom'       => $donnees['nom'],
                ':prenom'    => $donnees['prenom'],
                ':tel'       => $donnees['telephone'],
                ':statut'    => $donnees['statut']
            ]);

            // Si aucune erreur n'est levée, on applique définitivement les changements
            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            // En cas d'anomalie, on annule tout pour éviter les données orphelines
            $this->db->rollBack();
            error_log("Erreur inscription enseignant : " . $e->getMessage());
            return false;
        }
    }
}
?>