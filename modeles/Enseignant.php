<?php
// modeles/Enseignant.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Enseignant {
    private $pdo;

    public function __construct() {
        $host = "localhost";
        $user = "root";
        $password = "";
        $dbname = "gestion_ecole"; 

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
            // FORCE PDO À LEVER DES EXCEPTIONS EN CAS D'ERREUR SQL
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function listerClasses() {
        $stmt = $this->pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function enregistrer($matricule, $nom, $prenom, $matieres, $telephone, $statut, $id_classe, $login = null, $mot_de_passe = null) {
        try {
            $this->pdo->beginTransaction();

            $id_user = $matricule;
            $stmt = $this->pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id_user");
            $stmt->execute(['id_user' => $id_user]);
            $userExistant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userExistant) {
                if (!empty($login)) {
                    $sqlUser = "UPDATE utilisateur SET login = :login WHERE id_utilisateur = :id_user";
                    $this->pdo->prepare($sqlUser)->execute([
                        'login'   => $login,
                        'id_user' => $id_user
                    ]);
                }

                if (!empty($mot_de_passe)) {
                    $sqlPass = "UPDATE utilisateur SET mot_de_passe = :mot_de_passe WHERE id_utilisateur = :id_user";
                    $this->pdo->prepare($sqlPass)->execute([
                        'mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
                        'id_user'      => $id_user
                    ]);
                }

                $sqlEns = "UPDATE enseignant SET nom = :nom, prenom = :prenom, telephone = :telephone, statut = :statut, statut_activite = 'actif' WHERE id_utilisateur = :id_user";
                $this->pdo->prepare($sqlEns)->execute([
                    'nom'       => $nom,
                    'prenom'    => $prenom,
                    'telephone' => !empty($telephone) ? $telephone : null,
                    'statut'    => $statut,
                    'id_user'   => $id_user
                ]);
            } else {
                if (empty($login)) {
                    $login = strtolower($id_user);
                }
                if (empty($mot_de_passe)) {
                    $mot_de_passe = '123456';
                }

                $passwordHash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                $sqlUser = "INSERT INTO utilisateur (id_utilisateur, login, mot_de_passe, role) 
                            VALUES (:id_user, :login, :mot_de_passe, 'enseignant')";
                $this->pdo->prepare($sqlUser)->execute([
                    'id_user'      => $id_user,
                    'login'        => $login,
                    'mot_de_passe' => $passwordHash
                ]);

                $sqlEns = "INSERT INTO enseignant (id_utilisateur, nom, prenom, telephone, statut, statut_activite) 
                            VALUES (:id_user, :nom, :prenom, :telephone, :statut, 'actif')";
                $this->pdo->prepare($sqlEns)->execute([
                    'id_user'   => $id_user,
                    'nom'       => $nom,
                    'prenom'    => $prenom,
                    'telephone' => !empty($telephone) ? $telephone : null,
                    'statut'    => $statut
                ]);
            }

            $deleteAffectations = $this->pdo->prepare("DELETE FROM affectation_enseignant WHERE id_utilisateur = :id_user");
            $deleteAffectations->execute(['id_user' => $id_user]);

            if (!is_array($matieres)) {
                $matieres = [$matieres];
            }
            $matieres = array_values(array_filter(array_map('trim', $matieres), function ($item) {
                return $item !== '';
            }));

            if (!is_array($id_classe)) {
                $id_classe = [$id_classe];
            }

            $allClasseIds = [];
            foreach ($id_classe as $classeRow) {
                if (is_array($classeRow)) {
                    foreach ($classeRow as $classeId) {
                        $classeId = trim($classeId);
                        if ($classeId !== '') {
                            $allClasseIds[] = $classeId;
                        }
                    }
                } else {
                    $classeRow = trim($classeRow);
                    if ($classeRow !== '') {
                        $allClasseIds[] = $classeRow;
                    }
                }
            }
            $allClasseIds = array_values(array_unique($allClasseIds));

            if (strtolower($statut) === 'titulaire' && count($allClasseIds) > 1) {
                throw new Exception("Un enseignant Titulaire ne peut pas être affecté à plusieurs salles de classe !");
            }

            $rowCount = max(count($matieres), count($id_classe));
            for ($i = 0; $i < $rowCount; $i++) {
                $matiere_iter = trim($matieres[$i] ?? '');
                if ($matiere_iter === '') {
                    continue;
                }

                $classeRow = $id_classe[$i] ?? [];
                if (!is_array($classeRow)) {
                    $classeRow = [$classeRow];
                }

                $classeRow = array_values(array_filter(array_map('trim', $classeRow), function ($item) {
                    return $item !== '';
                }));

                foreach ($classeRow as $classe_id) {
                    if ($classe_id === '') {
                        continue;
                    }

                    $check_aff = $this->pdo->prepare("SELECT id FROM affectation_enseignant WHERE id_utilisateur = :id_user AND id_classe = :id_classe AND nom_matiere = :matiere");
                    $check_aff->execute([
                        'id_user'   => $id_user,
                        'id_classe' => $classe_id,
                        'matiere'   => $matiere_iter
                    ]);

                    if (!$check_aff->fetch()) {
                        $sql_aff = "INSERT INTO affectation_enseignant (id_utilisateur, id_classe, nom_matiere) 
                                    VALUES (:id_user, :id_classe, :matiere)";
                        $this->pdo->prepare($sql_aff)->execute([
                            'id_user'   => $id_user,
                            'id_classe' => $classe_id,
                            'matiere'   => $matiere_iter
                        ]);
                    }
                }
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }

    public function muterEnseignant($id_user) {
        try {
            $this->pdo->beginTransaction();
            $sql_del = "DELETE FROM affectation_enseignant WHERE id_utilisateur = :id_user";
            $this->pdo->prepare($sql_del)->execute(['id_user' => $id_user]);

            $sql_up = "UPDATE enseignant SET statut_activite = 'archivé' WHERE id_utilisateur = :id_user";
            $this->pdo->prepare($sql_up)->execute(['id_user' => $id_user]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erreur lors de la mutation : " . $e->getMessage());
        }
    }
}