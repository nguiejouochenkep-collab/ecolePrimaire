<?php
// liste_enseignants.php (À la racine du projet)
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

$message_succes = "";
$message_erreur = "";

// --- TRAITEMENT DES ACTIONS DU DIRECTEUR ---

// 1. Action : Archiver/Supprimer temporairement un enseignant (Mutation/Départ)
if (isset($_GET['action_maitre']) && $_GET['action_maitre'] === 'supprimer' && !empty($_GET['matricule'])) {
    try {
        // Au lieu d'un DELETE physique, on fait un soft delete
        $stmt = $pdo->prepare("UPDATE enseignant SET statut_activite = 'archivé' WHERE id_utilisateur = :id_user");
        $stmt->execute(['id_user' => $_GET['matricule']]);
        $message_succes = "L'enseignant a été retiré du personnel actif et déplacé dans les archives.";
    } catch (Exception $e) {
        $message_erreur = "Erreur lors de l'archivage : " . $e->getMessage();
    }
}

// 2. Action : Restaurer un enseignant archivé
if (isset($_GET['action_maitre']) && $_GET['action_maitre'] === 'restaurer' && !empty($_GET['matricule'])) {
    try {
        $stmt = $pdo->prepare("UPDATE enseignant SET statut_activite = 'actif' WHERE id_utilisateur = :id_user");
        $stmt->execute(['id_user' => $_GET['matricule']]);
        $message_succes = "L'enseignant a été réintégré avec succès dans le personnel actif.";
    } catch (Exception $e) {
        $message_erreur = "Erreur lors de la restauration : " . $e->getMessage();
    }
}

// 3. Action : Libérer/Muter (supprimer une affectation précise de matière/classe)
if (isset($_GET['action_maitre']) && $_GET['action_maitre'] === 'liberer' && !empty($_GET['id_affectation'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM `affectation_enseignant` WHERE id = :id");
        $stmt->execute(['id' => $_GET['id_affectation']]);
        $message_succes = "L'affectation de cette matière a été retirée.";
    } catch (Exception $e) {
        $message_erreur = "Erreur lors de la mutation : " . $e->getMessage();
    }
}

// 4. Action : Modifier le statut rapidement (Via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_statut'])) {
    $matricule = trim($_POST['matricule'] ?? '');
    $nouveau_statut = trim($_POST['statut'] ?? '');
    
    if (!empty($matricule) && !empty($nouveau_statut)) {
        try {
            if (strtolower($nouveau_statut) === 'titulaire') {
                $check = $pdo->prepare("SELECT COUNT(DISTINCT id_classe) as nb_classes FROM affectation_enseignant WHERE id_utilisateur = :id_user");
                $check->execute(['id_user' => $matricule]);
                $res = $check->fetch();
                if ($res && $res['nb_classes'] > 1) {
                    throw new Exception("Impossible : Cet enseignant possède des cours dans plusieurs classes. Modifiez ses affectations avant de le passer Titulaire.");
                }
            }
            
            $stmt = $pdo->prepare("UPDATE enseignant SET statut = :statut WHERE id_utilisateur = :id_user");
            $stmt->execute(['statut' => $nouveau_statut, 'id_user' => $matricule]);
            $message_succes = "Le statut de l'enseignant a été mis à jour.";
        } catch (Exception $e) {
            $message_erreur = $e->getMessage();
        }
    }
}

// --- CHARGEMENT DES DONNÉES ---
$sql = "SELECT 
            e.id_utilisateur AS matricule, e.nom, e.prenom, e.telephone, e.statut, e.statut_activite,
            a.id as id_affectation, a.nom_matiere,
            c.nom_classe
        FROM enseignant e
        LEFT JOIN affectation_enseignant a ON e.id_utilisateur = a.id_utilisateur
        LEFT JOIN classe c ON a.id_classe = c.id
        ORDER BY e.nom ASC, e.prenom ASC";

$stmt = $pdo->query($sql);
$lignes = $stmt->fetchAll();

// Séparation des enseignants actifs et archivés (anciens)
$enseignants_actifs = [];
$anciens_enseignants = [];

foreach ($lignes as $ligne) {
    // Si la ligne ne contient aucun enseignant (au cas où la requête renverrait null)
    if (empty($ligne['matricule'])) {
        continue;
    }

    $m = $ligne['matricule'];
    $cible = ($ligne['statut_activite'] === 'archivé') ? 'anciens_enseignants' : 'enseignants_actifs';
    
    // Utilisation sécurisée des accolades pour la variable dynamique ${$cible}
    if (!isset(${$cible}[$m])) {
        ${$cible}[$m] = [
            'matricule' => $ligne['matricule'],
            'nom' => $ligne['nom'],
            'prenom' => $ligne['prenom'],
            'telephone' => $ligne['telephone'],
            'statut' => $ligne['statut'],
            'affectations' => []
        ];
    }
    if (!empty($ligne['nom_matiere'])) {
        ${$cible}[$m]['affectations'][] = [
            'id_aff' => $ligne['id_affectation'],
            'matiere' => $ligne['nom_matiere'],
            'classe' => $ligne['nom_classe']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Enseignants</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* 1. Fond de page général en blanc pur */
        body { 
            background-color: #ffffff !important; 
        }
        
        /* 2. Bloc du Tableau Principal */
        .table-container { 
            background: #ffffff; 
            border: 1px solid #dee2e6; /* Ajout d'une légère bordure grise claire pour structurer */
            border-radius: 12px; 
            padding: 25px; 
            margin-top: 30px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Ombre plus douce sur fond blanc */
        }
        
        /* Force les champs de sélection à avoir un fond blanc */
        .form-control { 
            background-color: #ffffff !important; 
        }
        
        /* 3. Bloc de la Zone des Archives (Blanc pur avec bordure en pointillés) */
        .archive-container { 
            background: #ffffff; 
            border: 2px dashed #6c757d; 
            border-radius: 12px; 
            padding: 25px; 
            margin-top: 20px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .btn-toggle-archive { 
            font-size: 1.1rem; 
            border-radius: 30px; 
            transition: all 0.3s; 
        }
    </style>
</head>
<body>
    <div style="background-color: white; padding: 20px; border-radius: 12px; min-height: 100vh;">

<div class="container-fluid px-5 mb-5">
    <div class="mb-4 mt-4 d-flex justify-content-between align-items-center">
        <a href="index.php?action=dashboard" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-2"></i> Retour au Tableau de Bord
        </a>
        <a href="index.php?action=formulaire_enseignant" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-2"></i> Ajouter / Affecter un Enseignant
        </a>
    </div>

    <div class="table-container">
        <h3 class="mb-4 font-weight-bold text-dark text-center text-md-left">
            <i class="fas fa-users text-primary mr-2"></i> Registre Personnel Enseignant Actif
        </h3>

        <?php if(!empty($message_succes)): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $message_succes; ?></div>
        <?php endif; ?>
        <?php if(!empty($message_erreur)): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $message_erreur; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Matricule</th>
                        <th>Nom & Prénom</th>
                        <th>Téléphone</th>
                        <th>Statut (Type)</th>
                        <th>Classes & Matières Attribuées</th>
                        <th class="text-center">Actions Globales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enseignants_actifs)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Aucun enseignant actif enregistré.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enseignants_actifs as $ens): ?>
                            <tr>
                                <td class="font-weight-bold text-secondary"><?php echo htmlspecialchars($ens['matricule']); ?></td>
                                <td><?php echo htmlspecialchars($ens['nom'] . ' ' . $ens['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($ens['telephone'] ?: 'N/A'); ?></td>
                                <td>
                                    <form action="" method="POST" class="form-inline">
                                        <input type="hidden" name="matricule" value="<?php echo $ens['matricule']; ?>">
                                        <select name="statut" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                                            <option value="Titulaire" <?php echo $ens['statut'] == 'Titulaire' ? 'selected' : ''; ?>>Titulaire</option>
                                            <option value="Vacataire" <?php echo $ens['statut'] == 'Vacataire' ? 'selected' : ''; ?>>Vacataire</option>
                                            <option value="Secondaire" <?php echo $ens['statut'] == 'Secondaire' ? 'selected' : ''; ?>>Secondaire</option>
                                        </select>
                                        <input type="hidden" name="changer_statut" value="1">
                                    </form>
                                </td>
                                <td>
                                    <?php if (empty($ens['affectations'])): ?>
                                        <span class="text-muted italic">Aucune matière affectée</span>
                                    <?php else: ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach ($ens['affectations'] as $aff): ?>
                                                <li class="mb-2 p-1 border-bottom d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="fas fa-book-open text-muted mr-1"></i> 
                                                        <strong><?php echo htmlspecialchars($aff['matiere']); ?></strong> dans la classe <strong><?php echo htmlspecialchars($aff['classe']); ?></strong>
                                                    </span>
                                                    <a href="liste_enseignants.php?action_maitre=liberer&id_affectation=<?php echo $aff['id_aff']; ?>" 
                                                       class="btn btn-warning btn-xs py-0 px-1 ml-2" 
                                                       onclick="return confirm('Voulez-vous enlever cette matière à l\'enseignant ?');">
                                                        <i class="fas fa-sign-out-alt"></i> Libérer
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?action=formulaire_enseignant&matricule=<?php echo urlencode($ens['matricule']); ?>" class="btn btn-info btn-sm mb-1">
                                        <i class="fas fa-link"></i> Réaffecter
                                    </a>
                                    <a href="liste_enseignants.php?action_maitre=supprimer&matricule=<?php echo $ens['matricule']; ?>" 
                                       class="btn btn-danger btn-sm mb-1" 
                                       onclick="return confirm('Voulez-vous muter/retirer cet enseignant de l\'établissement ?');" title="Déplacer vers les anciens">
                                        <i class="fas fa-user-times"></i>  Muter
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-5">
        <button class="btn btn-dark btn-toggle-archive shadow-lg px-4 py-2" type="button" data-toggle="collapse" data-target="#zoneAnciens" aria-expanded="false" aria-controls="zoneAnciens" id="btnToggleTexte">
            <i class="fas fa-archive mr-2"></i> Voir les anciens enseignants (<?php echo count($anciens_enseignants); ?>)
        </button>
    </div>

    <div class="collapse" id="zoneAnciens">
        <div class="archive-container shadow">
            <h4 class="text-secondary mb-4 font-weight-bold">
                <i class="fas fa-history mr-2"></i> Historique des Anciens Enseignants (Supprimés / Mutés)
            </h4>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="bg-secondary text-white">
                        <tr>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Téléphone</th>
                            <th>Dernier Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($anciens_enseignants)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">Aucun enseignant archivé dans l'historique.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($anciens_enseignants as $anc): ?>
                                <tr>
                                    <td class="font-weight-bold"><?php echo htmlspecialchars($anc['matricule']); ?></td>
                                    <td><?php echo htmlspecialchars($anc['nom'] . ' ' . $anc['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($anc['telephone'] ?: 'N/A'); ?></td>
                                    <td><span class="badge badge-light p-2 border"><?php echo htmlspecialchars($anc['statut']); ?></span></td>
                                    <td class="text-center">
                                        <a href="liste_enseignants.php?action_maitre=restaurer&matricule=<?php echo $anc['matricule']; ?>" 
                                           class="btn btn-success btn-sm" 
                                           onclick="return confirm('Voulez-vous réintégrer cet enseignant dans le personnel actif ?');">
                                            <i class="fas fa-user-plus mr-1"></i> Réintégrer / Restaurer
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Événement Bootstrap pour détecter quand le panneau s'ouvre
    $('#zoneAnciens').on('show.bs.collapse', function () {
        $('#btnToggleTexte').html('<i class="fas fa-times mr-2"></i> Fermer l\'historique des anciens');
        $('#btnToggleTexte').removeClass('btn-dark').addClass('btn-danger');
    });

    // Événement Bootstrap pour détecter quand le panneau se ferme
    $('#zoneAnciens').on('hide.bs.collapse', function () {
        $('#btnToggleTexte').html('<i class="fas fa-archive mr-2"></i> Voir les anciens enseignants (<?php echo count($anciens_enseignants); ?>)');
        $('#btnToggleTexte').removeClass('btn-danger').addClass('btn-dark');
    });
</script>
</div>
</body>
</html>