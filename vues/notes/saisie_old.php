<?php
// vues/notes/saisie.php
// Interface séquentielle - Style Bulletin Officiel Camerounais (MINEDUB)

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion locale sécurisée à la base de données
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
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Définition stricte des 4 domaines officiels du MINEDUB
$domaines_officiels = [
    "I. LANGUES ET COMMUNICATION",
    "II. SCIENCE ET TECHNOLOGIE",
    "III. SCIENCES HUMAINES",
    "IV. L'AVENTURE HUMAINE"
];

// Détection automatique des colonnes de la table note
$noteColsStmt = $pdo->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'note'");
$noteColsStmt->execute();
$noteColumns = $noteColsStmt->fetchAll(PDO::FETCH_COLUMN);

$noteColTrimestre = in_array('id_trimestre', $noteColumns) ? 'id_trimestre' : (in_array('id_examen', $noteColumns) ? 'id_examen' : null);
$noteColValeur = in_array('valeur', $noteColumns) ? 'valeur' : (in_array('valeur_note', $noteColumns) ? 'valeur_note' : null);
$noteColObservation = in_array('observation', $noteColumns) ? 'observation' : null;

if (!$noteColTrimestre || !$noteColValeur) {
    die('La table note ne contient pas les colonnes attendues (id_trimestre / id_examen et valeur / valeur_note).');
}

// Intercepter la requête AJAX pour renvoyer l'enseignant principal
if (isset($_GET['obtenir_enseignant']) && !empty($_GET['id_classe'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $stmt_ens = $pdo->prepare("SELECT e.nom, e.prenom FROM enseignant e 
                               JOIN affectation_enseignant a ON e.id_utilisateur = a.id_utilisateur 
                               WHERE a.id_classe = :id_classe AND e.statut = 'Titulaire' LIMIT 1");
    $stmt_ens->execute(['id_classe' => $_GET['id_classe']]);
    $enseignant = $stmt_ens->fetch();
    
    if ($enseignant) {
        echo json_encode(['nom' => 'M. / Mme ' . htmlspecialchars($enseignant['nom'] . ' ' . $enseignant['prenom'])]);
    } else {
        echo json_encode(['nom' => 'Aucun enseignant titulaire assigné à cette classe']);
    }
    exit;
}

// Traitement de l'enregistrement des notes
$message_succes = "";
$message_erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_bulletin_eleve'])) {
    $id_classe = $_POST['id_classe'] ?? '';
    $matricule_eleve = $_POST['matricule_eleve'] ?? '';
    $id_trimestre = $_POST['id_trimestre'] ?? '';
    $id_sequence = $_POST['id_sequence'] ?? 1;
    $current_idx = (int)($_POST['eleve_idx'] ?? 0);
    $notes = $_POST['notes'] ?? [];
    $observations = $_POST['observations'] ?? [];

    if (!empty($matricule_eleve) && !empty($id_trimestre)) {
        try {
            $pdo->beginTransaction();
            
            foreach ($notes as $id_matiere => $valeur_note) {
                if ($valeur_note !== '') {
                    $obs = $observations[$id_matiere] ?? '';
                    
                    $check_note_sql = "SELECT id FROM note WHERE matricule_eleve = :mat AND id_matiere = :mat_id AND {$noteColTrimestre} = :trim AND id_sequence = :seq";
                    $check_note = $pdo->prepare($check_note_sql);
                    $check_note->execute(['mat' => $matricule_eleve, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence]);
                    
                    if ($check_note->fetch()) {
                        $update_sql = "UPDATE note SET {$noteColValeur} = :val";
                        if ($noteColObservation) { $update_sql .= ", {$noteColObservation} = :obs"; }
                        $update_sql .= " WHERE matricule_eleve = :mat AND id_matiere = :mat_id AND {$noteColTrimestre} = :trim AND id_sequence = :seq";
                        $up = $pdo->prepare($update_sql);
                        $params = ['val' => $valeur_note, 'mat' => $matricule_eleve, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence];
                        if ($noteColObservation) { $params['obs'] = $obs; }
                        $up->execute($params);
                    } else {
                        $insert_cols = "matricule_eleve, id_matiere, {$noteColTrimestre}, id_sequence, {$noteColValeur}";
                        $insert_values = ":mat, :mat_id, :trim, :seq, :val";
                        if ($noteColObservation) {
                            $insert_cols .= ", {$noteColObservation}";
                            $insert_values .= ", :obs";
                        }
                        $ins = $pdo->prepare("INSERT INTO note ({$insert_cols}) VALUES ({$insert_values})");
                        $params = ['mat' => $matricule_eleve, 'mat_id' => $id_matiere, 'trim' => $id_trimestre, 'seq' => $id_sequence, 'val' => $valeur_note];
                        if ($noteColObservation) { $params['obs'] = $obs; }
                        $ins->execute($params);
                    }
                }
            }
            
            $pdo->commit();
            $next_idx = $current_idx + 1;
            header("Location: index.php?action=saisie_note&id_classe=$id_classe&id_trimestre=$id_trimestre&id_sequence=$id_sequence&eleve_idx=$next_idx&statut=success");
if (isset($_GET['statut']) && $_GET['statut'] === 'success') {
    $message_succes = "Notes enregistrées avec succès. Passage à l'élève suivant.";
}

$classes = $pdo->query("SELECT id, nom_classe FROM classe ORDER BY nom_classe ASC")->fetchAll();

$classe_selectionnee = $_GET['id_classe'] ?? null;
$eleve_index = isset($_GET['eleve_idx']) ? (int)$_GET['eleve_idx'] : 0;
$id_trimestre_selectionne = $_GET['id_trimestre'] ?? null;
$id_sequence_selectionnee = $_GET['id_sequence'] ?? 1;

$liste_eleves = [];
$eleve_courant = null;
$matieres_par_domaine = [];
$sequences = [];

// Initialiser la structure vide avec les 4 domaines pour garantir leur présence
foreach ($domaines_officiels as $dom) {
    $matieres_par_domaine[$dom] = [];
}

if ($classe_selectionnee && $id_trimestre_selectionne) {
    // Charger les séquences pour le trimestre
    $stmt_seq = $pdo->prepare("SELECT id, numero_sequence, nom FROM sequence WHERE id_trimestre = :id_trim ORDER BY numero_sequence ASC");
    $stmt_seq->execute(['id_trim' => $id_trimestre_selectionne]);
    $sequences = $stmt_seq->fetchAll();
    
    // 1. Charger les élèves
    $stmt_el = $pdo->prepare("SELECT matricule, nom, prenom FROM eleve WHERE id_classe = :id_classe ORDER BY nom ASC, prenom ASC");
    $stmt_el->execute(['id_classe' => $classe_selectionnee]);
    $liste_eleves = $stmt_el->fetchAll();
    $total_eleves = count($liste_eleves);
    
    if ($eleve_index >= $total_eleves && $total_eleves > 0) {
        echo "<script>alert('Parfait ! Tous les élèves de cette salle de classe ont été évalués avec succès.'); window.location.href='index.php?action=dashboard';</script>";
        exit;
    }
    
    if (!empty($liste_eleves)) {
        $eleve_courant = $liste_eleves[$eleve_index];
    }

    $noteValeursExistantes = [];
    $noteObsExistantes = [];
    if ($eleve_courant) {
        $sql_note = "SELECT id_matiere, {$noteColValeur} AS valeur, " .
                    ($noteColObservation ? "{$noteColObservation} AS observation" : "'' AS observation") .
                    " FROM note WHERE matricule_eleve = :matricule AND {$noteColTrimestre} = :trimestre AND id_sequence = :sequence";
        $stmt_note = $pdo->prepare($sql_note);
        $stmt_note->execute(['matricule' => $eleve_courant['matricule'], 'trimestre' => $id_trimestre_selectionne, 'sequence' => $id_sequence_selectionnee]);
        while ($row = $stmt_note->fetch()) {
            $noteValeursExistantes[$row['id_matiere']] = $row['valeur'];
            $noteObsExistantes[$row['id_matiere']] = $row['observation'];
        }
    }
    
    // 2. Charger les matières et les ventiler dans les domaines correspondants
    $stmt_mat = $pdo->prepare("SELECT id, nom_matiere, coefficient, domaine FROM matiere WHERE id_classe = :id_classe ORDER BY nom_matiere ASC");
    $stmt_mat->execute(['id_classe' => $classe_selectionnee]);
    $matieres_classe = $stmt_mat->fetchAll();

    foreach ($matieres_classe as $mat) {
        $dom = $mat['domaine'];
        // Si le domaine en BD correspond à un de nos domaines définis, on l'y ajoute
        if (array_key_exists($dom, $matieres_par_domaine)) {
            $matieres_par_domaine[$dom][] = $mat;
        } else {
            // Sécurité au cas où l'orthographe diffère légèrement
            $matieres_par_domaine[$dom][] = $mat;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie des Notes - Système de Bulletins Séquentiels</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f3f5f7; font-family: 'Times New Roman', Times, serif; }
        .config-box { background: white; border-radius: 14px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-top: 50px; }
        .bulletin-container { background: white; border-radius: 4px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #000; margin-top: 20px; }
        .bulletin-header { border-bottom: 4px double #000; padding-bottom: 12px; }
        .republique-bloc { font-size: 0.85rem; font-weight: bold; text-align: right; text-transform: uppercase; line-height: 1.3; }
        .ministere-bloc { font-size: 0.85rem; font-weight: bold; text-align: left; text-transform: uppercase; line-height: 1.3; }
        .table-bulletin { border: 2px solid #000 !important; }
        .table-bulletin th { border: 2px solid #000 !important; background-color: #f2f2f2 !important; color: #000 !important; font-weight: bold; text-align: center; font-size: 0.85rem; text-transform: uppercase; }
        .table-bulletin td { border: 1px solid #000 !important; vertical-align: middle !important; }
        .section-domaine { background-color: #dfe4ea; font-weight: bold; padding-left: 12px; text-transform: uppercase; font-size: 0.9rem; color: #000; letter-spacing: 0.3px; border-top: 2px solid #000 !important; border-bottom: 2px solid #000 !important; }
        .note-input { max-width: 85px; font-weight: bold; text-align: center; border: 1px solid #000; font-size: 1.05rem; }
    </style>
</head>
<body>
    

<div class="container" style="max-width: 1050px;">

    <?php if (!$classe_selectionnee): ?>
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="config-box">
                    <div class="text-center mb-4">
                        <div class="display-4 text-primary mb-2"><i class="fas fa-folder-open"></i></div>
                        <h4 class="font-weight-bold text-secondary text-uppercase">Ouverture de la Session de Notes</h4>
                        <p class="text-muted small">Sélectionnez la salle de classe cible pour charger la grille de saisie</p>
                    </div>

                    <form method="GET" action="index.php">
                        <input type="hidden" name="action" value="saisie_note">
                        <input type="hidden" name="eleve_idx" value="0">

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-uppercase small text-dark">1. Salle de classe d'évaluation :</label>
                            <select name="id_classe" id="rechercheClasseInput" class="form-control form-control-lg border-primary" style="border-width: 2px;" required>
                                <option value="">-- Sélectionner la classe --</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nom_classe']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-4 p-3 bg-light rounded border">
                            <label class="font-weight-bold text-uppercase small text-muted d-block mb-1">Enseignant Principal de la Salle :</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle fa-2x text-secondary mr-3"></i>
                                <span id="blocEnseignantTitulaire" class="font-weight-bold text-dark h6 m-0">En attente du choix de la classe...</span>
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-uppercase small text-dark">2. Période Trimestrielle :</label>
                            <select name="id_trimestre" class="form-control form-control-lg" required>
                                <option value="1">1er Trimestre</option>
                                <option value="2">2e Trimestre</option>
                                <option value="3">3e Trimestre</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold text-uppercase shadow mt-4">
                            <i class="fas fa-check-double mr-2"></i> Initialiser le premier bulletin
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="d-flex justify-content-between align-items-center mt-3 mb-2">
            <a href="index.php?action=saisie_note" class="btn btn-secondary btn-sm">
                <i class="fas fa-undo mr-1"></i> Changer de Classe
            </a>
            <button type="button" class="btn btn-success btn-sm font-weight-bold shadow-sm" data-toggle="modal" data-target="#modalMatiereDirecte">
                <i class="fas fa-plus-circle mr-1"></i> Ajouter une discipline & Coeff
            </button>
        </div>

        <?php if (!empty($message_succes)): ?>
            <div class="alert alert-success p-2 small"><?php echo $message_succes; ?></div>
        <?php endif; ?>

        <div class="row" style="gap: 0;">
            <!-- SIDEBAR GAUCHE - LISTE DES ÉLÈVES -->
            <div class="col-md-2" style="background-color: #f8f9fa; border-right: 2px solid #dee2e6; padding: 0; max-height: 90vh; overflow-y: auto;">
                <div style="padding: 15px; border-bottom: 2px solid #dee2e6;">
                    <h6 class="font-weight-bold text-uppercase text-dark mb-0" style="font-size: 0.85rem;">
                        <i class="fas fa-users mr-1"></i> Élèves (<?php echo count($liste_eleves); ?>)
                    </h6>
                </div>
                <div style="padding: 0;">
                    <?php foreach ($liste_eleves as $idx => $elev): ?>
                        <a href="index.php?action=saisie_note&id_classe=<?php echo $classe_selectionnee; ?>&id_trimestre=<?php echo $id_trimestre_selectionne; ?>&id_sequence=<?php echo $id_sequence_selectionnee; ?>&eleve_idx=<?php echo $idx; ?>" 
                           class="d-block p-2 text-decoration-none border-bottom" 
                           style="background-color: <?php echo ($idx === $eleve_index) ? '#007bff' : '#fff'; ?>; color: <?php echo ($idx === $eleve_index) ? '#fff' : '#000'; ?>; font-size: 0.9rem;">
                            <strong><?php echo htmlspecialchars($elev['nom'] . ' ' . substr($elev['prenom'], 0, 1) . '.'); ?></strong>
                            <div style="font-size: 0.75rem; opacity: 0.8;">
                                <?php echo ($idx + 1) . '/' . count($liste_eleves); ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CONTENU PRINCIPAL -->
            <div class="col-md-10" style="padding-left: 0;">
                <div class="bulletin-container" style="border-radius: 0; box-shadow: none; border-top: 1px solid #dee2e6; border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6;">
                    <div class="row bulletin-header align-items-center">
                        <div class="col-6 ministere-bloc">
                            RÉPUBLIQUE DU CAMEROUN<br>
                            <span class="font-weight-normal text-muted small">Paix - Travail - Patrie</span><br>
                            MINISTÈRE DE L'ÉDUCATION DE BASE<br>
                            <span class="text-primary font-weight-bold">GROUPE SCOLAIRE EDUMANAGE</span>
                        </div>
                        <div class="col-6 republicque-bloc">
                            REPUBLIC OF CAMEROON<br>
                            <span class="font-weight-normal text-muted small">Peace - Work - Fatherland</span><br>
                            MINISTRY OF BASIC EDUCATION<br>
                            <span class="badge badge-dark mt-1">Élève : <?php echo ($eleve_index + 1) . " / " . $total_eleves; ?></span>
                        </div>
                    </div>

                    <div class="alert alert-dark my-3 p-2 rounded-0 d-flex justify-content-between align-items-center" style="background-color: #212529; border: none;">
                        <span class="text-white text-uppercase" style="font-size: 1.1rem; letter-spacing: 0.5px;">
                            ÉVALUATION DE : <strong><?php echo htmlspecialchars($eleve_courant['nom'] . ' ' . $eleve_courant['prenom']); ?></strong>
                        </span>
                        <span class="badge badge-warning font-weight-bold px-3 py-1">MATRICULE : <?php echo htmlspecialchars($eleve_courant['matricule']); ?></span>
                    </div>

                    <?php if (!empty($sequences)): ?>
                        <div class="alert alert-info p-2 mb-3 d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold">Sélectionner la séquence :</span>
                            <div>
                                <?php foreach ($sequences as $seq): ?>
                                    <a href="index.php?action=saisie_note&id_classe=<?php echo $classe_selectionnee; ?>&id_trimestre=<?php echo $id_trimestre_selectionne; ?>&id_sequence=<?php echo $seq['id']; ?>&eleve_idx=<?php echo $eleve_index; ?>" 
                                       class="btn btn-sm <?php echo ($seq['id'] == $id_sequence_selectionnee) ? 'btn-primary' : 'btn-outline-primary'; ?> mr-2">
                                        <?php echo htmlspecialchars($seq['nom']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?action=saisie_note">
                        <input type="hidden" name="enregistrer_bulletin_eleve" value="1">
                        <input type="hidden" name="id_classe" value="<?php echo htmlspecialchars($classe_selectionnee); ?>">
                        <input type="hidden" name="id_trimestre" value="<?php echo htmlspecialchars($id_trimestre_selectionne); ?>">
                        <input type="hidden" name="id_sequence" value="<?php echo htmlspecialchars($id_sequence_selectionnee); ?>">
                        <input type="hidden" name="matricule_eleve" value="<?php echo htmlspecialchars($eleve_courant['matricule']); ?>">
                        <input type="hidden" name="eleve_idx" value="<?php echo $eleve_index; ?>">

                        <table class="table table-bordered table-bulletin table-sm mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 45%; text-align: left; padding-left: 12px;">Disciplines Fondamentales</th>
                                    <th style="width: 10%;">Coeff</th>
                                    <th style="width: 15%;">Note (/20)</th>
                                    <th style="width: 30%;">Observations / Appréciations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Parcours des 4 domaines pré-remplis pour garantir leur affichage continu
                                foreach ($matieres_par_domaine as $nom_domaine => $matieres): 
                                ?>
                                    <tr>
                                        <td colspan="4" class="section-domaine">
                                            <i class="fas fa-folder mr-2 text-dark"></i> <?php echo htmlspecialchars($nom_domaine); ?>
                                        </td>
                                    </tr>
                                    
                                    <?php if (empty($matieres)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted small p-2 bg-light-gradient">
                                                <em>Aucune discipline configurée dans ce domaine pour cette classe.</em>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matieres as $mat): ?>
                                            <tr>
                                                <td class="pl-4 font-weight-bold text-dark"><?php echo htmlspecialchars($mat['nom_matiere']); ?></td>
                                                <td class="text-center font-weight-bold bg-light"><?php echo htmlspecialchars($mat['coefficient']); ?></td>
                                                <td>
                                                    <input type="number" step="0.25" min="0" max="20" name="notes[<?php echo $mat['id']; ?>]" value="<?php echo htmlspecialchars($noteValeursExistantes[$mat['id']] ?? ''); ?>" class="form-control form-control-sm note-input mx-auto" placeholder="0-20" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="observations[<?php echo $mat['id']; ?>]" value="<?php echo htmlspecialchars($noteObsExistantes[$mat['id']] ?? ''); ?>" class="form-control form-control-sm border-0 bg-transparent" placeholder="Saisir appréciation...">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="row mt-4 pt-3 border-top text-center text-uppercase font-weight-bold" style="font-size: 0.8rem;">
                            <div class="col-4 border-right">Visa de l'Enseignant</div>
                            <div class="col-4 border-right">Conseil des Maîtres</div>
                            <div class="col-4">Visa du Directeur</div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-dark btn-block btn-lg font-weight-bold text-uppercase p-3 shadow-sm">
                                Enregistrer et charger l'élève suivant <i class="fas fa-chevron-circle-right ml-2 text-warning"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalMatiereDirecte" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content text-dark">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2 text-warning"></i> Ajouter une discipline</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formAjoutMatiereDirecte">
                <div class="modal-body">
                    <input type="hidden" id="modalClasseId" value="<?php echo htmlspecialchars($classe_selectionnee ?? ''); ?>">
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Intitulé / Nom de la matière :</label>
                        <input type="text" id="modalNomMatiere" class="form-control" placeholder="Ex: Calcul Rapide, Dictée, TICE" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Coefficient :</label>
                        <input type="number" id="modalCoeff" class="form-control" min="1" max="10" value="1" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Domaine d'enseignement :</label>
                        <select id="modalDomaine" class="form-control" required>
                            <option value="">-- Choisir un domaine --</option>
                            <?php foreach ($domaines_officiels as $domaine): ?>
                                <option value="<?php echo $domaine; ?>"><?php echo $domaine; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Fermer</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">Créer et insérer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $('#rechercheClasseInput').on('change', function() {
        var idClasse = $(this).val();
        if (idClasse) {
            $.ajax({
                url: 'index.php?action=saisie_note',
                type: 'GET',
                data: { obtenir_enseignant: 1, id_classe: idClasse },
                dataType: 'json',
                success: function(data) {
                    $('#blocEnseignantTitulaire').text(data.nom).removeClass('text-muted').addClass('text-primary');
                },
                error: function() {
                    $('#blocEnseignantTitulaire').text('Erreur lors de la récupération du titulaire.').addClass('text-danger');
                }
            });
        } else {
            $('#blocEnseignantTitulaire').text('En attente du choix de la classe...').removeClass('text-primary text-danger').addClass('text-muted');
        }
    });

    $('#formAjoutMatiereDirecte').on('submit', function(e) {
        e.preventDefault();
        var idClasse = $('#modalClasseId').val();
        var nomMat = $('#modalNomMatiere').val();
        var coeff = $('#modalCoeff').val();
        var domaine = $('#modalDomaine').val();

        $.ajax({
            url: 'index.php?action=creer_matiere_ajax', 
            type: 'POST',
            data: { id_classe: idClasse, nom_matiere: nomMat, coefficient: coeff, domaine: domaine },
            success: function() {
                window.location.reload();
            },
            error: function() {
                // Secours si la réponse brute n'est pas du JSON valide mais que l'insertion s'est faite
                window.location.reload();
            }
        });
    });
</script>
</body>
</html>