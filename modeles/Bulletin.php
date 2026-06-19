<?php
// modeles/Bulletin.php
// Modèle pour bulletins camerounais primaire : trimestriel + annuel

class Bulletin {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupère l'appréciation basée sur la note
     */
    public function getAppreciation($note) {
        if ($note === null || $note === '') return null;
        
        if ($note >= 16) return 'Très bien';
        if ($note >= 14) return 'Bien';
        if ($note >= 12) return 'Assez bien';
        if ($note >= 10) return 'Passable';
        if ($note >= 8) return 'Insuffisant';
        return 'Faible';
    }

    /**
     * Calcule la moyenne d'une matière pour le trimestre (moyenne des 3 mensuels)
     * Formule: (Mensuel1 + Mensuel2 + Mensuel3) / 3
     */
    public function calculerMoyenneMatiereTrimestre($matricule_eleve, $id_matiere, $id_trimestre) {
        // Récupérer les séquences (mensuels) du trimestre
        $stmtSeq = $this->pdo->prepare(
            "SELECT id FROM sequence WHERE id_trimestre = :id_trimestre ORDER BY numero_sequence ASC"
        );
        $stmtSeq->execute(['id_trimestre' => $id_trimestre]);
        $sequences = $stmtSeq->fetchAll();
        
        if (empty($sequences)) return null;
        
        // Récupérer les notes pour chaque séquence
        $stmt = $this->pdo->prepare(
            "SELECT valeur FROM note 
             WHERE matricule_eleve = :matricule 
             AND id_matiere = :id_matiere 
             AND id_trimestre = :id_trimestre
             ORDER BY id_sequence ASC"
        );
        $stmt->execute([
            'matricule' => $matricule_eleve,
            'id_matiere' => $id_matiere,
            'id_trimestre' => $id_trimestre
        ]);
        
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($notes) === 0) return null;
        
        $somme = array_sum($notes);
        return round($somme / count($notes), 2);
    }

    /**
     * Calcule la moyenne ANNUELLE d'une matière (moyenne des 9 mensuels)
     * Formule: (Mensuel1 + ... + Mensuel9) / 9
     */
    public function calculerMoyenneMatierAnnuelle($matricule_eleve, $id_matiere) {
        $stmt = $this->pdo->prepare(
            "SELECT AVG(valeur) AS moyenne FROM note 
             WHERE matricule_eleve = :matricule 
             AND id_matiere = :id_matiere"
        );
        $stmt->execute([
            'matricule' => $matricule_eleve,
            'id_matiere' => $id_matiere
        ]);
        
        $result = $stmt->fetch();
        return $result['moyenne'] ? round($result['moyenne'], 2) : null;
    }

    /**
     * Calcule la moyenne générale trimestrielle d'un élève
     * Formule: Σ(Moyenne_matière × Coef) / Σ(Coef)
     */
    public function calculerMoyenneTrimestre($matricule_eleve, $id_trimestre, $id_classe) {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.coefficient, m.domaine
             FROM matiere m
             WHERE m.id_classe = :id_classe
             ORDER BY m.domaine ASC, m.nom_matiere ASC"
        );
        $stmt->execute(['id_classe' => $id_classe]);
        $matieres = $stmt->fetchAll();

        $total_points = 0;
        $total_coefs = 0;

        foreach ($matieres as $matiere) {
            $moyenne_mat = $this->calculerMoyenneMatiereTrimestre(
                $matricule_eleve, 
                $matiere['id'], 
                $id_trimestre
            );
            
            if ($moyenne_mat !== null) {
                $total_points += $moyenne_mat * $matiere['coefficient'];
                $total_coefs += $matiere['coefficient'];
            }
        }

        return $total_coefs > 0 ? round($total_points / $total_coefs, 2) : null;
    }

    /**
     * Calcule la moyenne générale ANNUELLE d'un élève
     */
    public function calculerMoyenneAnnuelle($matricule_eleve, $id_classe) {
        $stmt = $this->pdo->prepare(
            "SELECT m.id, m.coefficient
             FROM matiere m
             WHERE m.id_classe = :id_classe
             ORDER BY m.nom_matiere ASC"
        );
        $stmt->execute(['id_classe' => $id_classe]);
        $matieres = $stmt->fetchAll();

        $total_points = 0;
        $total_coefs = 0;

        foreach ($matieres as $matiere) {
            $moyenne_mat = $this->calculerMoyenneMatierAnnuelle($matricule_eleve, $matiere['id']);
            
            if ($moyenne_mat !== null) {
                $total_points += $moyenne_mat * $matiere['coefficient'];
                $total_coefs += $matiere['coefficient'];
            }
        }

        return $total_coefs > 0 ? round($total_points / $total_coefs, 2) : null;
    }

    public function getBulletinStatut($matricule_eleve, $id_classe, $id_trimestre) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total_matiere
             FROM matiere
             WHERE id_classe = :id_classe"
        );
        $stmt->execute(['id_classe' => $id_classe]);
        $nombre_matieres = (int) $stmt->fetchColumn();

        if ($nombre_matieres === 0) {
            return 'Aucune matière';
        }

        $expected_sequences = ($id_trimestre === '4' || $id_trimestre === 4) ? 9 : 3;
        $expected_notes = $nombre_matieres * $expected_sequences;

        if ($id_trimestre === '4' || $id_trimestre === 4) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM note n
                 JOIN matiere m ON n.id_matiere = m.id
                 WHERE n.matricule_eleve = :matricule
                   AND m.id_classe = :id_classe"
            );
            $stmt->execute(['matricule' => $matricule_eleve, 'id_classe' => $id_classe]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM note n
                 JOIN matiere m ON n.id_matiere = m.id
                 WHERE n.matricule_eleve = :matricule
                   AND n.id_trimestre = :id_trimestre
                   AND m.id_classe = :id_classe"
            );
            $stmt->execute(['matricule' => $matricule_eleve, 'id_trimestre' => $id_trimestre, 'id_classe' => $id_classe]);
        }

        $notes_existantes = (int) $stmt->fetchColumn();

        if ($notes_existantes === 0) {
            return 'Non rempli';
        }

        if ($notes_existantes < $expected_notes) {
            return 'Partiellement rempli';
        }

        return 'Rempli';
    }

    /**
     * Calcule le rang d'un élève dans sa classe pour un trimestre
     */
    public function calculerRangTrimestre($matricule_eleve, $id_trimestre, $id_classe) {
        // Récupérer toutes les moyennes des élèves de la classe
        $stmt = $this->pdo->prepare(
            "SELECT e.matricule,
                    SUM(CASE WHEN n.valeur IS NOT NULL THEN n.valeur * m.coefficient ELSE 0 END) / 
                    SUM(CASE WHEN n.valeur IS NOT NULL THEN m.coefficient ELSE 0 END) AS moyenne
             FROM eleve e
             LEFT JOIN note n ON n.matricule_eleve = e.matricule AND n.id_trimestre = :id_trimestre
             LEFT JOIN matiere m ON n.id_matiere = m.id
             WHERE e.id_classe = :id_classe
             GROUP BY e.matricule
             HAVING moyenne IS NOT NULL
             ORDER BY moyenne DESC"
        );
        $stmt->execute([
            'id_trimestre' => $id_trimestre,
            'id_classe' => $id_classe
        ]);
        
        $classement = $stmt->fetchAll();
        
        $moyenne_eleve = $this->calculerMoyenneTrimestre($matricule_eleve, $id_trimestre, $id_classe);
        
        foreach ($classement as $index => $c) {
            if ($c['moyenne'] == $moyenne_eleve) {
                return $index + 1;
            }
        }
        
        return null;
    }

    /**
     * Calcule le rang d'un élève dans une matière pour un trimestre
     */
    public function calculerRangMatiereTrimestre($id_matiere, $id_trimestre, $id_classe, $moyenne_mat) {
        if ($moyenne_mat === null) return null;

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT e.matricule) + 1 AS rang
             FROM eleve e
             JOIN note n ON n.matricule_eleve = e.matricule
             WHERE n.id_matiere = :id_matiere
               AND n.id_trimestre = :id_trimestre
               AND e.id_classe = :id_classe
               AND n.valeur > :moyenne"
        );
        $stmt->execute([
            'id_matiere' => $id_matiere,
            'id_trimestre' => $id_trimestre,
            'id_classe' => $id_classe,
            'moyenne' => $moyenne_mat
        ]);

        return $stmt->fetchColumn() ?: 1;
    }

    /**
     * Calcule le rang d'un élève dans une matière pour le bulletin annuel
     */
    public function calculerRangMatiereAnnuel($id_matiere, $id_classe, $moyenne_annuelle) {
        if ($moyenne_annuelle === null) return null;

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT e.matricule) + 1 AS rang
             FROM eleve e
             JOIN note n ON n.matricule_eleve = e.matricule
             WHERE n.id_matiere = :id_matiere
               AND e.id_classe = :id_classe
               AND n.valeur > :moyenne"
        );
        $stmt->execute([
            'id_matiere' => $id_matiere,
            'id_classe' => $id_classe,
            'moyenne' => $moyenne_annuelle
        ]);

        return $stmt->fetchColumn() ?: 1;
    }

    /**
     * Récupère les données complètes pour un BULLETIN TRIMESTRIEL
     */
    public function getBulletinTrimestriel($matricule_eleve, $id_trimestre, $id_classe) {
        // Les 4 domaines officiels
        $domaines = [
            "I. LANGUES ET COMMUNICATION",
            "II. SCIENCE ET TECHNOLOGIE",
            "III. SCIENCES HUMAINES",
            "IV. L'AVENTURE HUMAINE"
        ];
        
        $bulletin = [
            'type' => 'trimestriel',
            'trimestre' => $id_trimestre,
            'domaines' => [],
            'moyenne_general' => null,
            'rang' => null
        ];

        foreach ($domaines as $domaine) {
            // Récupérer les matières de ce domaine
            $stmt = $this->pdo->prepare(
                "SELECT id, nom_matiere, coefficient
                 FROM matiere
                 WHERE id_classe = :id_classe AND domaine = :domaine
                 ORDER BY nom_matiere ASC"
            );
            $stmt->execute([
                'id_classe' => $id_classe,
                'domaine' => $domaine
            ]);
            $matieres = $stmt->fetchAll();

            if (empty($matieres)) continue;

            $domaine_data = [
                'nom' => $domaine,
                'matieres' => [],
                'total_coef' => 0,
                'total_n_x_c' => 0,
                'moyenne_domaine' => null
            ];

            foreach ($matieres as $matiere) {
                // Récupérer les notes du trimestre (3 mensuels)
                $stmt = $this->pdo->prepare(
                    "SELECT n.id_sequence, n.valeur
                     FROM note n
                     WHERE n.matricule_eleve = :matricule
                     AND n.id_matiere = :id_matiere
                     AND n.id_trimestre = :id_trimestre
                     ORDER BY n.id_sequence ASC"
                );
                $stmt->execute([
                    'matricule' => $matricule_eleve,
                    'id_matiere' => $matiere['id'],
                    'id_trimestre' => $id_trimestre
                ]);
                $notes = $stmt->fetchAll();

                $notes_mensuelles = [];
                $moyenne_mat = null;
                $n_x_c = null;
                $rang_matiere = null;
                
                if (!empty($notes)) {
                    $somme = 0;
                    foreach ($notes as $note) {
                        $notes_mensuelles["seq" . $note['id_sequence']] = $note['valeur'];
                        $somme += $note['valeur'];
                    }
                    $moyenne_mat = round($somme / count($notes), 2);
                    $n_x_c = round($moyenne_mat * $matiere['coefficient'], 2);
                    $rang_matiere = $this->calculerRangMatiereTrimestre(
                        $matiere['id'],
                        $id_trimestre,
                        $id_classe,
                        $moyenne_mat
                    );
                    
                    $domaine_data['total_coef'] += $matiere['coefficient'];
                    $domaine_data['total_n_x_c'] += $n_x_c;
                }

                $matiere_data = [
                    'nom' => $matiere['nom_matiere'],
                    'coefficient' => $matiere['coefficient'],
                    'notes' => $notes_mensuelles,
                    'moyenne' => $moyenne_mat,
                    'n_x_c' => $n_x_c,
                    'rang' => $rang_matiere,
                    'appreciation' => $this->getAppreciation($moyenne_mat)
                ];

                $domaine_data['matieres'][] = $matiere_data;
            }

            if ($domaine_data['total_coef'] > 0) {
                $domaine_data['moyenne_domaine'] = round($domaine_data['total_n_x_c'] / $domaine_data['total_coef'], 2);
            }

            if (!empty($domaine_data['matieres'])) {
                $bulletin['domaines'][] = $domaine_data;
            }
        }

        // Calculer moyenne générale
        $bulletin['moyenne_general'] = $this->calculerMoyenneTrimestre($matricule_eleve, $id_trimestre, $id_classe);
        $bulletin['rang'] = $this->calculerRangTrimestre($matricule_eleve, $id_trimestre, $id_classe);

        return $bulletin;
    }

    /**
     * Récupère les données complètes pour un BULLETIN ANNUEL
     */
    public function getBulletinAnnuel($matricule_eleve, $id_classe) {
        // Les 4 domaines officiels
        $domaines = [
            "I. LANGUES ET COMMUNICATION",
            "II. SCIENCE ET TECHNOLOGIE",
            "III. SCIENCES HUMAINES",
            "IV. L'AVENTURE HUMAINE"
        ];
        
        $bulletin = [
            'type' => 'annuel',
            'domaines' => [],
            'moyenne_general' => null,
            'rang' => null,
            'progression' => [] // Moyennes par mensuel
        ];

        foreach ($domaines as $domaine) {
            // Récupérer les matières de ce domaine
            $stmt = $this->pdo->prepare(
                "SELECT id, nom_matiere, coefficient
                 FROM matiere
                 WHERE id_classe = :id_classe AND domaine = :domaine
                 ORDER BY nom_matiere ASC"
            );
            $stmt->execute([
                'id_classe' => $id_classe,
                'domaine' => $domaine
            ]);
            $matieres = $stmt->fetchAll();

            if (empty($matieres)) continue;

            $domaine_data = [
                'nom' => $domaine,
                'matieres' => [],
                'total_coef' => 0,
                'total_n_x_c' => 0,
                'moyenne_domaine' => null
            ];

            foreach ($matieres as $matiere) {
                // Récupérer toutes les notes de l'année (9 mensuels)
                $stmt = $this->pdo->prepare(
                    "SELECT n.id_sequence, n.valeur
                     FROM note n
                     WHERE n.matricule_eleve = :matricule
                     AND n.id_matiere = :id_matiere
                     ORDER BY n.id_sequence ASC"
                );
                $stmt->execute([
                    'matricule' => $matricule_eleve,
                    'id_matiere' => $matiere['id']
                ]);
                $notes = $stmt->fetchAll();

                $notes_mensuelles = [];
                for ($i = 1; $i <= 9; $i++) {
                    $notes_mensuelles["seq$i"] = null;
                }
                
                $moyenne_annuelle = null;
                $n_x_c = null;
                $rang_matiere = null;
                
                if (!empty($notes)) {
                    $somme = 0;
                    foreach ($notes as $note) {
                        $notes_mensuelles["seq" . $note['id_sequence']] = $note['valeur'];
                        $somme += $note['valeur'];
                    }
                    $moyenne_annuelle = round($somme / count($notes), 2);
                    $n_x_c = round($moyenne_annuelle * $matiere['coefficient'], 2);
                    $rang_matiere = $this->calculerRangMatiereAnnuel(
                        $matiere['id'],
                        $id_classe,
                        $moyenne_annuelle
                    );
                    
                    $domaine_data['total_coef'] += $matiere['coefficient'];
                    $domaine_data['total_n_x_c'] += $n_x_c;
                }

                $matiere_data = [
                    'nom' => $matiere['nom_matiere'],
                    'coefficient' => $matiere['coefficient'],
                    'notes' => $notes_mensuelles,
                    'moyenne_annuelle' => $moyenne_annuelle,
                    'n_x_c' => $n_x_c,
                    'rang' => $rang_matiere,
                    'appreciation' => $this->getAppreciation($moyenne_annuelle)
                ];

                $domaine_data['matieres'][] = $matiere_data;
            }

            if ($domaine_data['total_coef'] > 0) {
                $domaine_data['moyenne_domaine'] = round($domaine_data['total_n_x_c'] / $domaine_data['total_coef'], 2);
            }

            if (!empty($domaine_data['matieres'])) {
                $bulletin['domaines'][] = $domaine_data;
            }
        }

        // Calculer moyenne générale annuelle
        $bulletin['moyenne_general'] = $this->calculerMoyenneAnnuelle($matricule_eleve, $id_classe);

        // Calculer progression (moyennes par mensuel)
        for ($seq = 1; $seq <= 9; $seq++) {
            $stmt = $this->pdo->prepare(
                "SELECT ROUND(AVG(n.valeur), 2) AS moyenne
                 FROM note n
                 JOIN matiere m ON n.id_matiere = m.id
                 WHERE n.matricule_eleve = :matricule
                 AND n.id_sequence = :id_sequence
                 AND m.id_classe = :id_classe"
            );
            $stmt->execute([
                'matricule' => $matricule_eleve,
                'id_sequence' => $seq,
                'id_classe' => $id_classe
            ]);
            $result = $stmt->fetch();
            $bulletin['progression']["mensuel$seq"] = $result['moyenne'] ?? null;
        }

        return $bulletin;
    }
}
?>