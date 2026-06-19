<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ==========================================
// DÉFINITION DE LA CLASSE DE GESTION (POO)
// ==========================================
class GestionNotes {
    private $pdo;

    // Le constructeur gère la connexion à la base de données
    public function __construct($host, $dbname, $user, $password) {
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Erreur BDD : " . $e->getMessage());
        }
    }

    // Méthode pour récupérer tous les éléments d'une table
    public function recupererTout($table, $orderBy) {
        $sql = "SELECT * FROM " . $table . " ORDER BY " . $orderBy;
        return $this->pdo->query($sql)->fetchAll();
    }

    // Méthode pour insérer une nouvelle note
    public function ajouterNote($matricule_eleve, $id_examen, $id_matiere, $valeur) {
        // L'ordre des colonnes : matricule_eleve, id_examen, id_matiere, valeur
        $sql = "INSERT INTO note(matricule_eleve, id_examen, id_matiere, valeur) 
                VALUES(?, ?, ?, ?)";
        
        $requete = $this->pdo->prepare($sql);
        
        // Correction cruciale : L'ordre du tableau doit être identique aux (?) du SQL
        return $requete->execute([$matricule_eleve, $id_examen, $id_matiere, $valeur]);
    }
}

// ==========================================
// INITIALISATION ET TRAITEMENT
// ==========================================
$host = "localhost";
$user = "root";
$password = "";
$dbname = "application_de_gestion_ecole";

// Instanciation de l'objet
$gestionnaire = new GestionNotes($host, $dbname, $user, $password);

$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $matricule_eleve = $_POST['matricule_eleve'];
    $id_matiere = $_POST['id_matiere'];
    $id_examen = $_POST['id_examen'];
    $valeur = $_POST['valeur'];

    // Appel de la méthode orientée objet pour ajouter la note
    if($gestionnaire->ajouterNote($matricule_eleve, $id_examen, $id_matiere, $valeur)){
        $message = "
        <div class='alert alert-success'>
            Note enregistrée avec succès.
        </div>";
    } else {
        $message = "
        <div class='alert alert-danger'>
            Une erreur est survenue lors de l'enregistrement.
        </div>";
    }
}

// Récupération des données via les méthodes de l'objet
$eleves = $gestionnaire->recupererTout('eleve', 'nom');
$matieres = $gestionnaire->recupererTout('matiere', 'nom_matiere');
$examens = $gestionnaire->recupererTout('examen', 'nom_examen');

?>

<!DOCTYPE html>
<html lang="fr">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ajout des notes</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>

body{

    background: linear-gradient(135deg,#4f46e5,#7c3aed);
    min-height: 100vh;
    font-family: Arial, Helvetica, sans-serif;
}

.main-container{

    padding: 40px;
}

.note-card{

    background: white;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.title-zone{

    text-align: center;
    margin-bottom: 30px;
}

.title-zone h2{

    font-weight: bold;
    color: #4f46e5;
}

.title-zone p{

    color: gray;
}

.form-control{

    height: 48px;
    border-radius: 10px;
}

label{

    font-weight: bold;
    color: #374151;
}

.btn-save{

    background: linear-gradient(135deg,#4f46e5,#9333ea);
    border: none;
    color: white;
    height: 50px;
    border-radius: 12px;
    font-weight: bold;
    font-size: 17px;
    transition: 0.3s;
}

.btn-save:hover{

    transform: scale(1.02);
}

.icon-box{

    width: 80px;
    height: 80px;
    background: #ede9fe;
    margin: auto;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 15px;
}

.icon-box i{

    font-size: 35px;
    color: #4f46e5;
}

</style>

</head>

<body>

<div class="container main-container">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="note-card">

                <div class="title-zone">

                    <div class="icon-box">
                        <i class="fas fa-edit"></i>
                    </div>

                    <h2>Ajout des Notes</h2>

                    <p>
                        Saisie des notes des élèves
                    </p>

                </div>

                <?php echo $message; ?>

                <form method="POST">

                    <div class="form-group">

                        <label>
                            Élève
                        </label>

                        <select name="matricule_eleve"
                                class="form-control"
                                required>

                            <option value="">
                                Choisir un élève
                            </option>

                            <?php foreach($eleves as $eleve): ?>

                            <option value="<?= $eleve['matricule']; ?>">

                                <?= $eleve['nom']; ?>
                                <?= $eleve['prenom']; ?>
                                -
                                <?= $eleve['matricule']; ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </div>





                    <div class="form-group">

                        <label>
                            Matière
                        </label>

                        <select name="id_matiere"
                                class="form-control"
                                required>

                            <option value="">
                                Choisir une matière
                            </option>

                            <?php foreach($matieres as $matiere): ?>

                            <option value="<?= $matiere['id']; ?>">

                                <?= $matiere['nom_matiere']; ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </div>





                    <div class="form-group">

                        <label>
                            Examen
                        </label>

                        <select name="id_examen"
                                class="form-control"
                                required>

                            <option value="">
                                Choisir un examen
                            </option>

                            <?php foreach($examens as $examen): ?>

                            <option value="<?= $examen['id']; ?>">

                                <?= $examen['nom_examen']; ?>

                            </option>

                            <?php endforeach; ?>

                        </select>

                    </div>





                    <div class="form-group">

                        <label>
                            Note /20
                        </label>

                        <input type="number"
                               name="valeur"
                               class="form-control"
                               min="0"
                               max="20"
                               step="0.25"
                               placeholder="Entrer la note"
                               required>

                    </div>





                    <button type="submit"
                            class="btn btn-save btn-block">

                        <i class="fas fa-save mr-2"></i>

                        Enregistrer la note

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

</body>
</html>