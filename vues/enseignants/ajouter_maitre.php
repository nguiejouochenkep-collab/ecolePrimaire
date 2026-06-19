<?php
// vues/enseignants/ajouter_maitre.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "gestion_ecole"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// --- Récupération des infos si un matricule est passé dans l'URL (Clic sur Réaffecter) ---
$info_enseignant = [
    'matricule' => '',
    'nom' => '',
    'prenom' => '',
    'statut' => 'Titulaire',
    'telephone' => ''
];

if (isset($_GET['matricule']) && !empty(trim($_GET['matricule']))) {
    $stmt_get = $pdo->prepare("SELECT * FROM enseignant WHERE id_utilisateur = :id_user");
    $stmt_get->execute(['id_user' => trim($_GET['matricule'])]);
    $res_get = $stmt_get->fetch();
    if ($res_get) {
        $res_get['matricule'] = $res_get['id_utilisateur'];
        $info_enseignant = $res_get;
    }
}

// Charger les classes pour la liste déroulante
$stmt_classes = $pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC");
$classes = $stmt_classes->fetchAll();

$message_succes = "";
$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $matieres = isset($_POST['matiere']) && is_array($_POST['matiere']) ? $_POST['matiere'] : array();
    $telephone = trim($_POST['telephone'] ?? '');
    $statut = trim($_POST['statut'] ?? 'Titulaire'); 
    $classes_post = isset($_POST['id_classe']) && is_array($_POST['id_classe']) ? $_POST['id_classe'] : array();

    // Nouveaux champs récupérés
    $login = trim($_POST['login'] ?? '');
    $mdp_brut = trim($_POST['mot_de_passe'] ?? '');

    if (!empty($matricule) && !empty($nom) && !empty($prenom)) {
        try {
            $pdo->beginTransaction();

            // 1. Vérifier si l'utilisateur existe déjà dans la table parente
            $id_user = $matricule;
            $check = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = :id_user");
            $check->execute(['id_user' => $id_user]);
            $enseignant_existant = $check->fetch();

            if ($enseignant_existant) {
                // Mise à jour de la fiche enseignant existante
                $sql_up = "UPDATE enseignant SET nom = :nom, prenom = :prenom, telephone = :telephone, statut = :statut, statut_activite = 'actif' WHERE id_utilisateur = :id_user";
                $pdo->prepare($sql_up)->execute([
                    'nom'     => $nom,
                    'prenom'  => $prenom,
                    'telephone' => $telephone,
                    'statut'  => $statut,
                    'id_user' => $id_user
                ]);

                if (!empty($login)) {
                    $sql_up_user = "UPDATE utilisateur SET login = :login WHERE id_utilisateur = :id_user";
                    $pdo->prepare($sql_up_user)->execute([
                        'login'   => $login,
                        'id_user' => $id_user
                    ]);
                }
            } else {
                // Nouvel enseignant : création du compte utilisateur puis du profil enseignant
                if (empty($login) || empty($mdp_brut)) {
                    throw new Exception("Erreur : Pour un nouvel enseignant, vous devez définir un Login et un Mot de passe.");
                }

                $check_login = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE login = :login");
                $check_login->execute(['login' => $login]);
                if ($check_login->fetch()) {
                    throw new Exception("Le nom d'utilisateur (Login) '$login' est déjà utilisé par un autre membre.");
                }

                $mdp_hash = password_hash($mdp_brut, PASSWORD_DEFAULT);

                $sql_ins_user = "INSERT INTO utilisateur (id_utilisateur, login, mot_de_passe, role) VALUES (:id_user, :login, :mot_de_passe, :role)";
                $pdo->prepare($sql_ins_user)->execute([
                    'id_user'      => $id_user,
                    'login'        => $login,
                    'mot_de_passe' => $mdp_hash,
                    'role'         => 'enseignant'
                ]);

                $sql_ins_ens = "INSERT INTO enseignant (id_utilisateur, nom, prenom, telephone, statut, statut_activite) VALUES (:id_user, :nom, :prenom, :telephone, :statut, 'actif')";
                $pdo->prepare($sql_ins_ens)->execute([
                    'id_user'   => $id_user,
                    'nom'       => $nom,
                    'prenom'    => $prenom,
                    'telephone' => !empty($telephone) ? $telephone : null,
                    'statut'    => $statut
                ]);
            }

            // 2. Traitement des affectations multiples
            $affectations_ajoutees = 0;
            $matieres_affectations = $matieres;
            $classes_affectations = $classes_post;

            if (!is_array($matieres_affectations)) {
                $matieres_affectations = [];
            }

            if (!is_array($classes_affectations)) {
                $classes_affectations = [];
            }

            foreach ($classes_affectations as $idx => $classe_item) {
                $matiere_iter = trim($matieres_affectations[$idx] ?? '');
                
                if (empty($matiere_iter)) {
                    continue;
                }

                // Pour les Titulaires: $classe_item est un scalaire (l'ID de la classe)
                // Pour les Vacataires/Secondaires: $classe_item est un array (liste des classes)
                $classes_to_process = is_array($classe_item) ? $classe_item : [$classe_item];
                $classes_to_process = array_filter(array_map('trim', $classes_to_process));

                // Règle : Titulaire = max 1 classe au total
                if (strtolower($statut) === 'titulaire' && $affectations_ajoutees > 0) {
                    throw new Exception("Opération impossible : Un enseignant **Titulaire** ne peut être affecté qu'à **une seule salle de classe** !");
                }

                foreach ($classes_to_process as $id_classe_iter) {
                    if (empty($id_classe_iter)) {
                        continue;
                    }

                    $check_double = $pdo->prepare("SELECT id FROM affectation_enseignant WHERE id_utilisateur = :id_user AND id_classe = :id_classe AND nom_matiere = :matiere");
                    $check_double->execute([
                        'id_user'   => $id_user,
                        'id_classe' => $id_classe_iter,
                        'matiere'   => $matiere_iter
                    ]);

                    if (!$check_double->fetch()) {
                        $sql_aff = "INSERT INTO affectation_enseignant (id_utilisateur, id_classe, nom_matiere) VALUES (:id_user, :id_classe, :matiere)";
                        $pdo->prepare($sql_aff)->execute([
                            'id_user'   => $id_user,
                            'id_classe' => $id_classe_iter,
                            'matiere'   => $matiere_iter
                        ]);
                        $affectations_ajoutees++;
                    }
                }
            }

            if ($affectations_ajoutees > 0) {
                $message_succes = "Enseignant et affectations enregistrés avec succès ! ($affectations_ajoutees affectation(s) créée(s)/mise(s) à jour)";
            } else {
                $message_succes = "Fiche de l'enseignant enregistrée avec succès (sans affectation).";
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $message_erreur = $e->getMessage();
        }
    } else {
        $message_erreur = "Veuillez remplir les champs obligatoires (Matricule, Nom, Prénom).";
    }

    // Gestion des réponses AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'succes' => !empty($message_succes),
            'message' => !empty($message_succes) ? $message_succes : $message_erreur
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter/Réaffecter un enseignant</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .affectation-row { background-color: #f9f9f9; border-left: 3px solid #17a2b8; }
        .affectation-row:hover { background-color: #f0f0f0; }
        .checkbox-group { max-height: 250px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 10px; background-color: #fff; }
        .checkbox-group .form-check { margin-bottom: 8px; }
        .note-box { background-color: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin-top: 10px; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4 mb-4">
    <div class="mb-3">
        <a href="index.php?action=liste_enseignants" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
        </a>
    </div>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-chalkboard-teacher mr-2"></i> Gestion des Enseignants & Affectations
            </h4>
        </div>

        <div class="card-body">
            <div id="ajax-response-zone">
                <?php if(!empty($message_succes)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $message_succes; ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if(!empty($message_erreur)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $message_erreur; ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>
            </div>

            <form id="form-maitre" method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="needs-validation">
                
                <!-- Informations de base de l'enseignant -->
                <div class="form-section mb-4">
                    <h5 class="text-secondary mb-3">
                        <i class="fas fa-user mr-2"></i> Informations Personnelles
                    </h5>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Matricule d'État <span class="text-danger">*</span></label>
                            <input type="text" name="matricule" class="form-control" placeholder="Entrez le matricule officiel" 
                                   value="<?php echo htmlspecialchars($info_enseignant['matricule'] ?? ''); ?>" 
                                   required <?php echo !empty($info_enseignant['matricule']) ? 'readonly' : ''; ?>>
                            <small class="text-muted">Identifiant unique de l'État</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: Tankam" 
                                   value="<?php echo htmlspecialchars($info_enseignant['nom'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" placeholder="Ex: Jean" 
                                   value="<?php echo htmlspecialchars($info_enseignant['prenom'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Type d'Enseignant (Statut) <span class="text-danger">*</span></label>
                            <select name="statut" id="selectStatut" class="form-control" required>
                                <option value="Titulaire" <?php echo ($info_enseignant['statut'] ?? '') === 'Titulaire' ? 'selected' : ''; ?>>
                                    Titulaire (Une seule classe max)
                                </option>
                                <option value="Vacataire" <?php echo ($info_enseignant['statut'] ?? '') === 'Vacataire' ? 'selected' : ''; ?>>
                                    Vacataire (Multi-classes)
                                </option>
                                <option value="Secondaire" <?php echo ($info_enseignant['statut'] ?? '') === 'Secondaire' ? 'selected' : ''; ?>>
                                    Secondaire (Multi-classes)
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control" placeholder="Ex: 6XXXXXXXX" 
                                   value="<?php echo htmlspecialchars($info_enseignant['telephone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Affectations -->
                <div class="form-section mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="text-secondary mb-0">
                            <i class="fas fa-tasks mr-2"></i> Affectations aux Salles & Matières
                        </h5>
                        <button type="button" id="btn-add-affectation" class="btn btn-sm btn-success">
                            <i class="fas fa-plus mr-1"></i> Ajouter une affectation
                        </button>
                    </div>

                    <div id="affectations-list" class="border rounded p-3 bg-light">
                        <div class="affectation-row row mb-2 p-2 bg-white border rounded" data-row-index="0">
                            <div class="form-group col-md-6 mb-0">
                                <label class="small font-weight-bold">Matière <span class="text-danger">*</span></label>
                                <input type="text" name="matiere[]" class="form-control form-control-sm" placeholder="Ex: Mathématiques" required>
                            </div>
                            <div class="class-selection form-group col-md-5 mb-0">
                                <label class="small font-weight-bold">Salle(s) de classe <span class="text-danger">*</span></label>
                                <select name="id_classe[0]" class="form-control form-control-sm" required>
                                    <option value="">-- Sélectionner une salle --</option>
                                    <?php foreach ($classes as $classe): ?>
                                        <option value="<?php echo $classe['id']; ?>">
                                            <?php echo htmlspecialchars($classe['nom_classe']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-1 mb-0 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-danger btn-remove-affectation w-100" style="display: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="note-box">
                        <strong>Note :</strong>
                        <span id="statut-info">Un enseignant Titulaire peut être affecté à <strong>une seule salle</strong>.</span>
                    </div>
                </div>

                <!-- Identifiants de connexion -->
                <div class="form-section mb-4">
                    <h5 class="text-secondary mb-3">
                        <i class="fas fa-lock mr-2"></i> Identifiants de connexion (Nouveaux enseignants)
                    </h5>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Nom d'utilisateur (Login)</label>
                            <input type="text" name="login" class="form-control" placeholder="Ex: t.jean">
                            <small class="text-muted">Laisser vide si l'enseignant possède déjà un compte.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Mot de passe provisoire</label>
                            <input type="password" name="mot_de_passe" class="form-control" placeholder="••••••••">
                            <small class="text-muted">Laisser vide si l'enseignant possède déjà un compte.</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success btn-block btn-lg font-weight-bold">
                        <i class="fas fa-save mr-2"></i> Valider l'Enregistrement et l'Affectation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const availableClasses = <?php echo json_encode($classes, JSON_HEX_TAG); ?>;

function renderClassInput(rowIndex) {
    const statut = $('#selectStatut').val().toLowerCase();
    let html = '<div class="class-selection form-group col-md-5 mb-0">';
    html += '<label class="small font-weight-bold">Salle(s) de classe <span class="text-danger">*</span></label>';
    
    if (statut === 'titulaire') {
        html += '<select name="id_classe[' + rowIndex + ']" class="form-control form-control-sm" required>';
        html += '<option value="">-- Sélectionner une salle --</option>';
        availableClasses.forEach(classe => {
            html += '<option value="' + classe.id + '">' + classe.nom_classe + '</option>';
        });
        html += '</select>';
    } else {
        html += '<div class="checkbox-group">';
        availableClasses.forEach(classe => {
            const inputId = 'classe_' + rowIndex + '_' + classe.id;
            html += '<div class="form-check">';
            html += '<input class="form-check-input" type="checkbox" id="' + inputId + '" name="id_classe[' + rowIndex + '][]" value="' + classe.id + '">';
            html += '<label class="form-check-label" for="' + inputId + '">' + classe.nom_classe + '</label>';
            html += '</div>';
        });
        html += '</div>';
    }
    html += '</div>';
    return html;
}

function updateRowIndices() {
    $('.affectation-row').each(function(index) {
        $(this).attr('data-row-index', index);
        $(this).find('input[name^="matiere"]').attr('name', 'matiere[]');

        const statut = $('#selectStatut').val().toLowerCase();
        const classContainer = $(this).find('.class-selection');

        if (statut === 'titulaire') {
            classContainer.find('select').attr('name', 'id_classe[' + index + ']');
        } else {
            classContainer.find('input[type="checkbox"]').each(function() {
                const classeId = $(this).val();
                const inputId = 'classe_' + index + '_' + classeId;
                $(this).attr('name', 'id_classe[' + index + '][]');
                $(this).attr('id', inputId);
                $(this).next('label').attr('for', inputId);
            });
        }
    });
}

function updateAffectationUI() {
    const statut = $('#selectStatut').val().toLowerCase();
    const affectationRows = $('.affectation-row').length;

    const infoText = (statut === 'titulaire')
        ? 'Un enseignant Titulaire peut être affecté à <strong>une seule salle</strong>.'
        : 'Un enseignant ' + statut.charAt(0).toUpperCase() + statut.slice(1) + ' peut être affecté à <strong>plusieurs salles</strong>.';
    $('#statut-info').html(infoText);

    $('.affectation-row').each(function() {
        const rowIndex = $(this).data('row-index');
        const classContainer = $(this).find('.class-selection');
        const hasSelect = classContainer.find('select').length > 0;
        const shouldBeSelect = statut === 'titulaire';

        if (hasSelect !== shouldBeSelect) {
            classContainer.replaceWith(renderClassInput(rowIndex));
        }
    });

    if (statut === 'titulaire' && affectationRows >= 1) {
        $('#btn-add-affectation').prop('disabled', true).hide();
    } else {
        $('#btn-add-affectation').prop('disabled', false).show();
    }

    updateRowIndices();
    updateDeleteButtons();
}

function updateDeleteButtons() {
    const affectationRows = $('.affectation-row').length;
    if (affectationRows > 1) {
        $('.btn-remove-affectation').show();
    } else {
        $('.btn-remove-affectation').hide();
    }
}

function createAffectationRow(rowIndex) {
    return `
        <div class="affectation-row row mb-2 p-2 bg-white border rounded" data-row-index="${rowIndex}">
            <div class="form-group col-md-6 mb-0">
                <label class="small font-weight-bold">Matière <span class="text-danger">*</span></label>
                <input type="text" name="matiere[]" class="form-control form-control-sm" placeholder="Ex: Mathématiques" required>
            </div>
            ${renderClassInput(rowIndex)}
            <div class="form-group col-md-1 mb-0 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger btn-remove-affectation w-100">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

$('#btn-add-affectation').on('click', function() {
    const statut = $('#selectStatut').val().toLowerCase();
    if (statut === 'titulaire' && $('.affectation-row').length >= 1) {
        alert('Un enseignant Titulaire ne peut être affecté qu\'à une seule salle !');
        return;
    }

    const rowIndex = $('.affectation-row').length;
    $('#affectations-list').append(createAffectationRow(rowIndex));
    updateRowIndices();
    updateAffectationUI();
});

$(document).on('click', '.btn-remove-affectation', function(e) {
    e.preventDefault();
    $(this).closest('.affectation-row').remove();
    updateDeleteButtons();
    updateAffectationUI();
});

$('#selectStatut').on('change', function() {
    updateAffectationUI();
});

$(document).ready(function() {
    updateAffectationUI();
});

$('#form-maitre').on('submit', function(e){
    e.preventDefault();
    const form = $(this);
    const zone = $('#ajax-response-zone');
    const statut = $('#selectStatut').val().toLowerCase();
    
    const affectationRows = $('.affectation-row').length;
    if (statut === 'titulaire' && affectationRows > 1) {
        zone.removeClass('alert alert-success').addClass('alert alert-danger').html(
            '<i class="fas fa-exclamation-circle mr-2"></i>Erreur : Un enseignant Titulaire ne peut être affecté qu\'à une seule salle !'
        );
        return;
    }

    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize(),
        dataType: 'json',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(data){
            zone.removeClass('alert alert-success alert-danger');
            if(data.succes){
                zone.addClass('alert alert-success alert-dismissible fade show').html(
                    '<i class="fas fa-check-circle mr-2"></i>' + data.message +
                    '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
                );
                const currentMatricule = $('input[name="matricule"]').val();
                form[0].reset();
                $('input[name="matricule"]').val(currentMatricule).prop('readonly', true);
                updateAffectationUI();
            } else {
                zone.addClass('alert alert-danger alert-dismissible fade show').html(
                    '<i class="fas fa-exclamation-circle mr-2"></i>' + data.message +
                    '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
                );
            }
        },
        error: function(){
            zone.removeClass('alert alert-success').addClass('alert alert-danger alert-dismissible fade show').html(
                '<i class="fas fa-exclamation-circle mr-2"></i>Une erreur technique est survenue ou restriction enfreinte.' +
                '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
            );
        }
    });
});
</script>

</body>
</html>