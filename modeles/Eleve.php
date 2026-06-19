<?php
// modeles/Eleve.php

 class Eleve {
    private $db;

    public function __construct() {
        // Récupération de la connexion via ta classe Bdd
        $this->db = Bdd::connexion();
    }

    /**
     * Inscrit un nouvel élève dans la base de données avec génération de matricule
     * @param array $data Données de l'élève issues du formulaire
     * @return array Résultat de l'opération (succès et message)
     */
    public function inscrire($data) {
        try {
            // 1. GÉNÉRATION AUTOMATIQUE D'UN MATRICULE UNIQUE (Ex: 26EL942)
            $annee = date('y'); // Récupère "26" pour 2026
            $random = rand(100, 999); // Un nombre aléatoire à 3 chiffres
            $matricule = $annee . "EL" . $random; 

            // 2. Requête d'insertion incluant le matricule généré
            $hasStatutActivite = false;
            try {
                $stmtCheck = $this->db->query("SHOW COLUMNS FROM eleve LIKE 'statut_activite'");
                $hasStatutActivite = $stmtCheck->rowCount() > 0;
            } catch (PDOException $e) {
                // Si la colonne n'existe pas, on continue sans elle
                $hasStatutActivite = false;
            }

            if ($hasStatutActivite) {
                $query = "INSERT INTO eleve (matricule, nom, prenom, date_naissance, sexe, id_classe, telephone_parent, statut_activite) 
                          VALUES (:matricule, :nom, :prenom, :date_naissance, :sexe, :classe, :telephone_parent, :statut_activite)";
                $params = [
                    ':matricule'        => $matricule,
                    ':nom'              => $data['nom'],
                    ':prenom'           => $data['prenom'],
                    ':date_naissance'   => $data['date_naissance'],
                    ':sexe'             => $data['sexe'],
                    ':classe'           => $data['id_classe'],
                    ':telephone_parent' => $data['telephone_parent'],
                    ':statut_activite'  => 'actif'
                ];
            } else {
                $query = "INSERT INTO eleve (matricule, nom, prenom, date_naissance, sexe, id_classe, telephone_parent) 
                          VALUES (:matricule, :nom, :prenom, :date_naissance, :sexe, :classe, :telephone_parent)";
                $params = [
                    ':matricule'        => $matricule,
                    ':nom'              => $data['nom'],
                    ':prenom'           => $data['prenom'],
                    ':date_naissance'   => $data['date_naissance'],
                    ':sexe'             => $data['sexe'],
                    ':classe'           => $data['id_classe'],
                    ':telephone_parent' => $data['telephone_parent']
                ];
            }

            $stmt = $this->db->prepare($query);
            
            $execution = $stmt->execute($params);

            if ($execution) {
                return [
                    'succes' => true,
                    'message' => "L'élève " . htmlspecialchars($data['nom']) . " a été inscrit avec le matricule " . $matricule
                ];
            } else {
                return [
                    'succes' => false,
                    'message' => "Une erreur est survenue lors de l'inscription."
                ];
            }

        } catch (PDOException $e) {
            error_log("Erreur inscription élève : " . $e->getMessage());
            return [
                'succes' => false,
                'message' => "Erreur base de données : " . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère toutes les classes pour remplir la liste déroulante
     */
    public function listerLesClasses() {
        $query = "SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les élèves actifs d'une classe spécifique pour une année scolaire donnée
     */
    public function listerParClasse($id_classe, $id_annee = null) {
        if (!empty($id_annee)) {
            // Filtrer par inscription pour l'année scolaire sélectionnée
            $query = "SELECT DISTINCT e.*, c.nom_classe FROM eleve e
                      JOIN inscription i ON e.matricule = i.matricule_eleve
                      JOIN classe c ON i.id_classe = c.id
                      WHERE i.id_classe = :id_classe AND i.id_annee = :id_annee
                      ORDER BY e.nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id_classe' => $id_classe, 'id_annee' => $id_annee]);
        } else {
            // Fallback : pas d'année sélectionnée, utiliser le champ statique
            $query = "SELECT e.*, c.nom_classe FROM eleve e
                      JOIN classe c ON e.id_classe = c.id
                      WHERE e.id_classe = :id_classe
                      ORDER BY e.nom ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id_classe' => $id_classe]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function chercherParNomOuMatricule($search, $id_classe = null, $id_annee = null) {
        $term = '%' . str_replace(' ', '%', $search) . '%';
        
        if (!empty($id_annee)) {
            // Filtrer par année scolaire via table inscription
            $sql = "SELECT DISTINCT e.*, c.nom_classe FROM eleve e
                    JOIN inscription i ON e.matricule = i.matricule_eleve
                    JOIN classe c ON i.id_classe = c.id
                    WHERE (e.nom LIKE :term OR e.prenom LIKE :term OR e.matricule LIKE :term)
                    AND i.id_annee = :id_annee";
            if (!empty($id_classe)) {
                $sql .= " AND i.id_classe = :id_classe";
            }
            $sql .= " ORDER BY e.nom ASC";
            
            $stmt = $this->db->prepare($sql);
            $params = [':term' => $term, ':id_annee' => $id_annee];
            if (!empty($id_classe)) {
                $params[':id_classe'] = $id_classe;
            }
        } else {
            // Fallback : pas d'année, utiliser champ statique
            $sql = "SELECT e.*, c.nom_classe FROM eleve e
                    JOIN classe c ON e.id_classe = c.id
                    WHERE (e.nom LIKE :term OR e.prenom LIKE :term OR e.matricule LIKE :term)";
            if (!empty($id_classe)) {
                $sql .= " AND e.id_classe = :id_classe";
            }
            $sql .= " ORDER BY e.nom ASC";
            
            $stmt = $this->db->prepare($sql);
            $params = [':term' => $term];
            if (!empty($id_classe)) {
                $params[':id_classe'] = $id_classe;
            }
        }

        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère l'identifiant de la classe suivante pour la promotion
     */
    public function obtenirIdClasseSuivante($id_classe_actuelle) {
        $stmt = $this->db->prepare("SELECT nom_classe FROM classe WHERE id = :id_classe LIMIT 1");
        $stmt->execute([':id_classe' => $id_classe_actuelle]);
        $classe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$classe || empty($classe['nom_classe'])) {
            return null;
        }

        $mapPromotion = [
            'SIL' => 'CP',
            'CP' => 'CE1',
            'CE1' => 'CE2',
            'CE2' => 'CM1',
            'CM1' => 'CM2'
        ];

        $nom_suivant = $mapPromotion[strtoupper($classe['nom_classe'])] ?? null;
        if ($nom_suivant === null) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT id FROM classe WHERE UPPER(nom_classe) = :nom_classe LIMIT 1");
        $stmt->execute([':nom_classe' => strtoupper($nom_suivant)]);
        $classeSuivante = $stmt->fetch(PDO::FETCH_ASSOC);

        return $classeSuivante['id'] ?? null;
    }

    /**
     * Calcule la moyenne pondérée d'un élève pour un trimestre donné
     */
    public function calculerMoyenneTrimestre($matricule, $id_trimestre) {
        try {
            $sql = "SELECT SUM(tm.note_matiere * m.coefficient) / SUM(m.coefficient) AS moyenne
                    FROM (
                        SELECT id_matiere, AVG(valeur) AS note_matiere
                        FROM note
                        WHERE matricule_eleve = :matricule AND id_trimestre = :id_trimestre
                        GROUP BY id_matiere
                    ) tm
                    JOIN matiere m ON m.id = tm.id_matiere";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':matricule' => $matricule,
                ':id_trimestre' => $id_trimestre
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ($result && $result['moyenne'] !== null) ? round((float)$result['moyenne'], 2) : null;
        } catch (PDOException $e) {
            error_log('Erreur calcul moyenne trimestre : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calcule la moyenne annuelle d'un élève sur les 3 trimestres
     * Retourne null si un trimestre manque ou si la moyenne ne peut pas être calculée
     */
    public function calculerMoyenneAnnuelle($matricule) {
        $total = 0;
        $count = 0;

        for ($trimestre = 1; $trimestre <= 3; $trimestre++) {
            $moyenneTrimestre = $this->calculerMoyenneTrimestre($matricule, $trimestre);
            if ($moyenneTrimestre === null) {
                return null;
            }
            $total += $moyenneTrimestre;
            $count++;
        }

        return $count > 0 ? round($total / $count, 2) : null;
    }

    /**
     * Calcule la moyenne annuelle d'un élève pour une année scolaire donnée.
     * Note : le modèle des notes actuel ne conserve pas explicitement l'année scolaire dans la table note.
     * Cette fonction utilise donc la même logique que calculerMoyenneAnnuelle, mais elle est fournie
     * pour clarifier l'intention et pouvoir évoluer si l'année scolaire est ajoutée plus tard.
     */
    public function calculerMoyenneAnnuellePourAnnee($matricule, $id_annee = null) {
        // En l'état actuel des données, les notes ne stockent pas l'année scolaire.
        // La sélection par année ne peut pas être appliquée directement ici sans changement de schéma.
        return $this->calculerMoyenneAnnuelle($matricule);
    }

    /**
     * Archive un élève (par exemple un CM2 admis en fin d'année)
     */
    public function archiverEleve($matricule) {
        try {
            $query = "UPDATE eleve SET statut_activite = 'archivé' WHERE matricule = :matricule";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':matricule' => $matricule]);
        } catch (PDOException $e) {
            error_log('Erreur archive élève : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour la classe d'un élève
     */
    public function mettreAJourClasse($matricule, $id_classe) {
        try {
            $query = "UPDATE eleve SET id_classe = :id_classe WHERE matricule = :matricule";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id_classe' => $id_classe,
                ':matricule' => $matricule
            ]);
        } catch (PDOException $e) {
            error_log('Erreur mise à jour classe élève : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Promeut tous les élèves actifs vers la classe supérieure si la moyenne annuelle est >= 10.
     * Les élèves de CM2 admis (moyenne >= 10) sont archivés, sinon ils restent dans la même classe.
     */
    public function promouvoirTousLesEleves() {
        try {
            $query = "SELECT e.matricule, e.id_classe, c.nom_classe
                      FROM eleve e
                      JOIN classe c ON c.id = e.id_classe
                      WHERE e.id_classe > 0";
            $stmt = $this->db->query($query);
            $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $succesTotal = true;
            foreach ($eleves as $eleve) {
                $moyenneAnnuelle = $this->calculerMoyenneAnnuelle($eleve['matricule']);

                if ($moyenneAnnuelle === null) {
                    // Pas assez de données pour décider : maintien dans la même classe
                    continue;
                }

                if ($moyenneAnnuelle >= 10) {
                    $classeSuivante = $this->obtenirIdClasseSuivante($eleve['id_classe']);
                    if ($classeSuivante !== null) {
                        $succes = $this->mettreAJourClasse($eleve['matricule'], $classeSuivante);
                    } elseif (strtoupper($eleve['nom_classe']) === 'CM2') {
                        $succes = $this->archiverEleve($eleve['matricule']);
                    } else {
                        // Classe inconnue ou classe finale non encore définie, on maintient
                        $succes = true;
                    }
                } else {
                    // Moyenne annuelle < 10 : maintien dans la même classe
                    $succes = true;
                }

                if (!$succes) {
                    $succesTotal = false;
                }
            }

            return $succesTotal;
        } catch (PDOException $e) {
            error_log('Erreur promotion élèves : ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les élèves archivés
     */
    public function listerArchives() {
        $query = "SELECT * FROM eleve WHERE statut_activite = 'archivé' ORDER BY nom ASC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Supprime un élève (Renvoyé) via son matricule
     */
    public function supprimer($matricule) {
        try {
            $query = "DELETE FROM eleve WHERE matricule = :matricule";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':matricule' => $matricule]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Modifie les informations d'un élève
     */
    public function modifier($matricule, $data) {
        try {
            $query = "UPDATE eleve 
                      SET nom = :nom, prenom = :prenom, date_naissance = :date_naissance, 
                          sexe = :sexe, telephone_parent = :telephone_parent 
                      WHERE matricule = :matricule";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':matricule' => $matricule,
                ':nom' => $data['nom'],
                ':prenom' => $data['prenom'],
                ':date_naissance' => $data['date_naissance'],
                ':sexe' => $data['sexe'],
                ':telephone_parent' => $data['telephone_parent']
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère la liste de tous les élèves inscrits
     * @return array Tableau associatif contenant les élèves
     */
    public function listerTous() {
        try {
            $query = "SELECT matricule, nom, prenom, date_naissance FROM eleve ORDER BY nom ASC";
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération liste élèves : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute une nouvelle classe dans la base de données
     */
    public function ajouterClasse($nom_classe) {
        try {
            $query = "INSERT INTO classe (nom_classe) VALUES (:nom_classe)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':nom_classe' => $nom_classe]);
        } catch (PDOException $e) {
            error_log("Erreur ajouterClasse : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Renomme une classe existante via son ID
     */
    public function renommerClasse($id_classe, $nouveau_nom) {
        try {
            $query = "UPDATE classe SET nom_classe = :nouveau_nom WHERE id = :id_classe";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':nouveau_nom' => $nouveau_nom,
                ':id_classe'   => $id_classe
            ]);
        } catch (PDOException $e) {
            error_log("Erreur renommerClasse : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les détails d'une classe spécifique par son ID
     */
    public function obtenirClasseParId($id) {
        try {
            $query = "SELECT * FROM classe WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur obtenirClasseParId : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime définitivement une classe de la base de données via son ID
     * @param int $id_classe Identifiant de la classe à supprimer
     * @return bool True en cas de succès, False en cas d'échec
     */
    public function supprimerClasse($id_classe) {
        try {
            $query = "DELETE FROM classe WHERE id = :id_classe";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id_classe' => $id_classe]);
        } catch (PDOException $e) {
            error_log("Erreur supprimerClasse : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les élèves avec leur moyenne annuelle pour la promotion
     */
    public function getElevesAvecMoyenneAnnuelle($id_classe = null, $id_annee = null) {
        try {
            if (!empty($id_annee)) {
                $sql = "SELECT e.*, c.nom_classe as classe_actuelle, ROUND(AVG(n.valeur), 2) as moyenne_annuelle
                        FROM eleve e
                        JOIN inscription i ON e.matricule = i.matricule_eleve AND i.id_annee = :id_annee
                        LEFT JOIN note n ON n.matricule_eleve = e.matricule
                        LEFT JOIN classe c ON i.id_classe = c.id
                        WHERE 1=1";
                $params = ['id_annee' => $id_annee];
            } else {
                $sql = "SELECT e.*, c.nom_classe as classe_actuelle, ROUND(AVG(n.valeur), 2) as moyenne_annuelle
                        FROM eleve e
                        LEFT JOIN inscription i ON e.matricule = i.matricule_eleve
                        LEFT JOIN note n ON n.matricule_eleve = e.matricule
                        LEFT JOIN classe c ON i.id_classe = c.id
                        WHERE 1=1";
                $params = [];
            }

            if ($id_classe) {
                $sql .= " AND i.id_classe = :id_classe";
                $params['id_classe'] = $id_classe;
            }

            $sql .= " GROUP BY e.matricule
                      ORDER BY moyenne_annuelle DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erreur getElevesAvecMoyenneAnnuelle: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère l'année scolaire suivante à partir de l'année courante
     */
    public function obtenirAnneeSuivante($id_annee_courante) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM annee_scolaire WHERE id > :id ORDER BY id ASC LIMIT 1");
            $stmt->execute(['id' => $id_annee_courante]);
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            return $annee['id'] ?? null;
        } catch (PDOException $e) {
            error_log('Erreur obtenirAnneeSuivante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ajoute une inscription pour un élève dans une année scolaire donnée
     */
    public function ajouterInscriptionDansAnnee($matricule, $id_classe, $id_annee) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM inscription WHERE matricule_eleve = :matricule AND id_annee = :id_annee");
            $stmt->execute(['matricule' => $matricule, 'id_annee' => $id_annee]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            $stmt = $this->db->prepare("INSERT INTO inscription (matricule_eleve, id_classe, id_annee, date_inscription) VALUES (:matricule, :id_classe, :id_annee, CURDATE())");
            return $stmt->execute(['matricule' => $matricule, 'id_classe' => $id_classe, 'id_annee' => $id_annee]);
        } catch (PDOException $e) {
            error_log('Erreur ajouterInscriptionDansAnnee: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère la classe suivante (basée sur l'ordre SIL -> CP -> CE1 -> CE2 -> CM1 -> CM2)
     */
    public function getClasseSuivante($id_classe_actuelle) {
        try {
            // Récupérer le nom de la classe actuelle
            $stmt = $this->db->prepare("SELECT nom_classe FROM classe WHERE id = :id");
            $stmt->execute(['id' => $id_classe_actuelle]);
            $classe = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$classe) return null;
            
            // Ordre des classes
            $ordre = [
                'SIL' => 1,
                'CP' => 2,
                'CE1' => 3,
                'CE2' => 4,
                'CM1' => 5,
                'CM2' => 6
            ];
            
            $nom_classe = strtoupper($classe['nom_classe']);
            
            // Si c'est CM2, il n'y a pas de classe suivante
            if ($nom_classe === 'CM2') {
                return null;
            }
            
            // Trouver la classe suivante dans l'ordre
            $ordre_actuel = $ordre[$nom_classe] ?? null;
            if (!$ordre_actuel) return null;
            
            $ordre_suivant = $ordre_actuel + 1;
            $nom_suivant = array_search($ordre_suivant, $ordre);
            
            if (!$nom_suivant) return null;
            
            // Récupérer l'ID de la classe suivante
            $stmt = $this->db->prepare("SELECT id FROM classe WHERE UPPER(nom_classe) = :nom");
            $stmt->execute(['nom' => $nom_suivant]);
            $classe_suivante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $classe_suivante['id'] ?? null;
        } catch (PDOException $e) {
            error_log('Erreur getClasseSuivante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Met à jour la classe d'un élève (promotion)
     */
    public function updateClasse($matricule, $id_nouvelle_classe) {
        try {
            $stmt = $this->db->prepare("UPDATE eleve SET id_classe = :id_classe WHERE matricule = :matricule");
            return $stmt->execute([
                'id_classe' => $id_nouvelle_classe,
                'matricule' => $matricule
            ]);
        } catch (PDOException $e) {
            error_log('Erreur updateClasse: ' . $e->getMessage());
            return false;
        }
    }

}


?>