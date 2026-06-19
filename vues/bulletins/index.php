<?php
// vues/bulletins/index.php
// Vue pour afficher bulletins primaire camerounais avec sidebar élèves

// S'assurer que la session est démarrée et déterminer le rôle courant
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['role'])) {
    $role_brut = $_SESSION['role'];
    if (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin' || strtolower($role_brut) === 'administrateur') {
        $role_actuel = 'admin';
    } else {
        $role_actuel = 'enseignant';
    }
} else {
    $role_actuel = 'enseignant';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $bulletin_data ? ($id_trimestre_selectionne == 4 ? 'Bulletin Annuel' : 'Bulletin Trimestriel') : 'Bulletins'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html, body {
            min-height: 100%;
            background: white !important;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
            padding: 20px;
        }
        #content-to-load {
            background-color: #ffffff !important;
            padding: 20px !important;
            border-radius: 12px !important;
            max-width: 1440px;
            margin: 0 auto;
            min-height: 100vh;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .page-content {
            background-color: #ffffff !important;
            padding: 0;
            margin: 0;
        }
        .control-panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            max-width: 1440px;
            margin-left: auto;
            margin-right: auto;
        }
        .bulletin-layout {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            margin-right: -15px;
            margin-left: -15px;
        }
        .bulletin-layout > [class*='col-'] {
            position: relative;
            min-height: 1px;
            padding-right: 15px;
            padding-left: 15px;
        }
        @media (min-width: 768px) {
            .bulletin-layout > .col-md-3 { flex: 0 0 25%; max-width: 25%; }
            .bulletin-layout > .col-md-9 { flex: 0 0 75%; max-width: 75%; }
        }
        .bulletin-frame {
            background: #ffffff !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 16px !important;
            box-shadow: 0 12px 32px rgba(0,0,0,0.12) !important;
            padding: 20px !important;
            margin-top: 20px !important;
            width: 100% !important;
        }
        .eleve-sidebar {
            width: 100%;
            background: white !important;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            min-height: 100%;
        }
        .eleve-sidebar .eleve-list {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
        }
        .bulletin-container {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            min-width: 0;
        }
        .eleve-sidebar-header { background: #2c3e50; color: white; padding: 10px 12px; }
        .eleve-sidebar-header h5 { font-size: 0.9rem; margin-bottom: 3px; }
        .eleve-sidebar-header small { font-size: 0.75rem; }
        .eleve-list { max-height: 70vh; overflow-y: auto; }
        .eleve-item { padding: 8px 10px; border-bottom: 1px solid #e9ecef; cursor: pointer; transition: all 0.2s; text-decoration: none; display: block; color: #333; font-size: 0.85rem; }
        .eleve-item strong { font-size: 0.85rem; display: block; }
        .eleve-item small { font-size: 0.7rem; display: block; color: #666; }
        .eleve-item:hover { background-color: #e9ecef; text-decoration: none; }
        .eleve-item.active { background-color: #3498db; color: white; }
        .eleve-item.active small { color: rgba(255,255,255,0.8); }
        .bulletin-container {
            flex: 1;
            width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .bulletin-header { text-align: center; padding-bottom: 12px; margin-bottom: 18px; border-bottom: 2px solid #000; }
        .bulletin-header h5 { font-size: 0.85rem; text-transform: uppercase; margin: 0; letter-spacing: 0.05em; }
        .bulletin-header h3 { font-size: 1.3rem; font-weight: bold; margin: 10px 0; }
        .bulletin-meta { background: #ffffff; border: 1px solid #dee2e6; padding: 12px; margin-bottom: 20px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; font-size: 0.85rem; }
        .bulletin-meta div { background: #f8f9fa; border-radius: 8px; padding: 10px; }
        .meta-label { font-weight: bold; display: block; }
        .meta-value { color: #333; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; margin-bottom: 20px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 8px; vertical-align: middle; }
        .main-table th { background-color: #2c3e50; color: white; text-align: center; font-weight: bold; }
        .domaine-row { background-color: #d4d9e2; font-weight: bold; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .note-cell { text-align: center; }
        .summary-box { margin-top: 20px; border-top: 2px solid #000; padding-top: 15px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; text-align: center; }
        .summary-card .label { font-weight: bold; font-size: 0.8rem; margin-bottom: 8px; }
        .summary-card .value { font-size: 1.3rem; font-weight: bold; color: #007bff; }
        .progression-row { display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; }
        .progression-item { border: 1px solid #000; padding: 8px 12px; background: #f1f5f9; font-size: 0.75rem; }
        .signature-box { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 25px; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; margin-bottom: 8px; width: 80%; margin-left: auto; margin-right: auto; }
        .btn-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-top: 20px; }
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .control-panel, .eleve-sidebar, .btn-actions { display: none; }
            .bulletin-container { box-shadow: none; padding: 15px; margin: 0; border: 1px solid #ddd; }
        }
    </style>
</head>
<body style="background: white !important;">
<div id="content-to-load" style="background: white !important; padding: 20px !important; border-radius: 12px !important; box-shadow: 0 12px 32px rgba(0,0,0,0.12) !important;">
    <div class="page-content" style="background: white !important;">
        <div class="container-fluid py-4" style="background: white !important;">
    <!-- PANNEAU DE CONTRÔLE -->
    <div class="control-panel">
        <h3 class="mb-4"><i class="fas fa-file-alt mr-2"></i> Gestion des Bulletins</h3>
        
        <form method="GET" action="index.php" class="form-row" data-ajax="true">
            <input type="hidden" name="action" value="bulletins">
            
            <div class="form-group col-md-4">
                <label for="classe" class="font-weight-bold">Classe</label>
                <select name="id_classe" id="classe" class="form-control form-control-sm">
                    <option value="">-- Sélectionner une classe --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= ($id_classe_selectionnee == $c['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['nom_classe']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label for="trimestre" class="font-weight-bold">Trimestre</label>
                <select name="id_trimestre" id="trimestre" class="form-control form-control-sm">
                    <option value="">-- Sélectionner un trimestre --</option>
                    <?php foreach ($options_trimestre as $t): ?>
                        <option value="<?= $t['id']; ?>" <?= ($id_trimestre_selectionne == $t['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($t['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm btn-block">Afficher le bulletin</button>
            </div>
        </form>
    </div>

    <!-- AFFICHAGE DU BULLETIN AVEC SIDEBAR -->
    <?php if ($id_classe_selectionnee && !empty($options_trimestre)): ?>
        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Choix du bulletin">
                <?php foreach ($options_trimestre as $option): ?>
                    <a href="index.php?action=bulletins&id_classe=<?= urlencode($id_classe_selectionnee); ?>&id_trimestre=<?= urlencode($option['id']); ?>" 
                       class="btn btn-sm ajax-link <?= ($id_trimestre_selectionne == $option['id']) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?= htmlspecialchars($option['nom']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($id_classe_selectionnee && $id_trimestre_selectionne && !empty($eleves_list)): ?>
    <div class="bulletin-frame">
        <div class="row bulletin-layout no-gutters">
            <!-- SIDEBAR LISTE DES ÉLÈVES -->
            <div class="col-12 col-md-3 no-print">
                <div class="eleve-sidebar">
                    <div class="eleve-sidebar-header">
                        <h5 class="mb-0"><i class="fas fa-users mr-2"></i> Liste des élèves</h5>
                        <small><?= count($eleves_list); ?> élève(s)</small>
                    </div>
                    <div class="eleve-list">
                        <?php foreach ($eleves_list as $e): ?>
                            <?php
                                $statut = $e['bulletin_statut'] ?? 'Non renseigné';
                                $badgeClass = 'badge-secondary';
                                if ($statut === 'Rempli') {
                                    $badgeClass = 'badge-success';
                                } elseif ($statut === 'Partiellement rempli') {
                                    $badgeClass = 'badge-warning';
                                } elseif ($statut === 'Non rempli') {
                                    $badgeClass = 'badge-danger';
                                }
                            ?>
                            <a href="index.php?action=bulletins&id_classe=<?= $id_classe_selectionnee; ?>&id_trimestre=<?= $id_trimestre_selectionne; ?>&matricule=<?= $e['matricule']; ?>" 
                               class="eleve-item ajax-link <?= (isset($matricule_eleve) && $matricule_eleve == $e['matricule']) ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']); ?></strong>
                                        <br>
                                        <small>Matricule: <?= htmlspecialchars($e['matricule']); ?></small>
                                    </div>
                                    <div>
                                        <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars($statut); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- BULLETIN DE L'ÉLÈVE SÉLECTIONNÉ -->
            <div class="col-12 col-md-9">
                <div class="bulletin-container">
                    <?php if ($bulletin_data && $eleve_data): ?>
                        <!-- EN-TÊTE -->
                        <div class="bulletin-header">
                            <h5>MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</h5>
                            <h5>--------</h5>
                            <h5>GROUPE SCOLAIRE EDUMANAGE</h5>
                            <h3><?= ($id_trimestre_selectionne == 4) ? 'BILAN ANNUEL' : 'BULLETIN DE NOTES'; ?></h3>
                            <h5><?= htmlspecialchars($trimestre); ?> - Année scolaire 2025-2026</h5>
                        </div>
                    <div><span class="meta-label">Matricule</span><span class="meta-value"><?= htmlspecialchars($eleve_data['matricule']); ?></span></div>
                    <div><span class="meta-label">Classe</span><span class="meta-value"><?= htmlspecialchars($classe['nom_classe']); ?></span></div>
                    <div><span class="meta-label">Effectif</span><span class="meta-value"><?= count($eleves_list); ?></span></div>
                    <div><span class="meta-label">Année scolaire</span><span class="meta-value">2025-2026</span></div>
                    <div><span class="meta-label">Période</span><span class="meta-value"><?= htmlspecialchars($trimestre); ?></span></div>
                </div>

                <!-- TABLEAU DES NOTES -->
                <div class="table-responsive">
                    <table class="main-table">
                        <thead>
                            <tr>
                                <th style="width: 35%">MATIÈRES / DOMAINES</th>
                                <?php if ($id_trimestre_selectionne == 4): ?>
                                    <th>M1</th><th>M2</th><th>M3</th><th>M4</th><th>M5</th><th>M6</th><th>M7</th><th>M8</th><th>M9</th>
                                <?php else: ?>
                                    <th>M1</th><th>M2</th><th>M3</th>
                                <?php endif; ?>
                                <th>COEF</th>
                                <th>MOY</th>
                                <th>N×C</th>
                                <th>RANG</th>
                                <th>APPRÉCIATIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_general_points = 0;
                            $total_general_coef = 0;
                            
                            if (!empty($bulletin_data['domaines'])):
                                foreach ($bulletin_data['domaines'] as $domaine):
                                    $total_domaine_points = 0;
                                    $total_domaine_coef = 0;
                            ?>
                                <tr class="domaine-row">
                                    <td colspan="<?= ($id_trimestre_selectionne == 4) ? 13 : 7; ?>">
                                        <strong><?= htmlspecialchars($domaine['nom']); ?></strong>
                                    </td>
                                </tr>
                                
                                <?php foreach ($domaine['matieres'] as $matiere): ?>
                                    <?php 
                                    if ($matiere['n_x_c'] !== null) {
                                        $total_domaine_points += $matiere['n_x_c'];
                                        $total_domaine_coef += $matiere['coefficient'];
                                    }
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($matiere['nom']); ?></strong></td>
                                        <?php if ($id_trimestre_selectionne == 4): ?>
                                            <td class="note-cell"><?= $matiere['notes']['seq1'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq2'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq3'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq4'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq5'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq6'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq7'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq8'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq9'] ?? '-'; ?></td>
                                        <?php else: ?>
                                            <td class="note-cell"><?= $matiere['notes']['seq1'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq2'] ?? '-'; ?></td>
                                            <td class="note-cell"><?= $matiere['notes']['seq3'] ?? '-'; ?></td>
                                        <?php endif; ?>
                                        <td class="note-cell"><?= $matiere['coefficient']; ?></td>
                                        <td class="note-cell"><strong><?= $matiere['moyenne'] ?? '-'; ?></strong></td>
                                        <td class="note-cell"><?= $matiere['n_x_c'] ?? '-'; ?></td>
                                        <td class="note-cell"><?= $matiere['rang'] ?? '-'; ?></td>
                                        <td><?= htmlspecialchars($matiere['appreciation'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <tr class="total-row">
                                    <td class="text-right"><strong>TOTAL DOMAINE</strong></td>
                                    <?php for($i=0; $i<($id_trimestre_selectionne == 4 ? 9 : 3); $i++): ?>
                                        <td class="note-cell">-</td>
                                    <?php endfor; ?>
                                    <td class="note-cell"><strong><?= $total_domaine_coef; ?></strong></td>
                                    <td class="note-cell">-</td>
                                    <td class="note-cell"><strong><?= number_format($total_domaine_points, 1); ?></strong></td>
                                    <td class="note-cell">-</td>
                                    <td class="note-cell">-</td>
                                </tr>
                                <tr class="total-row">
                                    <td class="text-right"><strong>MOYENNE DOMAINE</strong></td>
                                    <?php for($i=0; $i<($id_trimestre_selectionne == 4 ? 9 : 3); $i++): ?>
                                        <td class="note-cell">-</td>
                                    <?php endfor; ?>
                                    <td class="note-cell"><?= $total_domaine_coef; ?></td>
                                    <td class="note-cell">
                                        <strong><?= ($total_domaine_coef > 0) ? number_format($total_domaine_points / $total_domaine_coef, 2) : '0.00'; ?></strong>
                                    </td>
                                    <td class="note-cell">-</td>
                                    <td class="note-cell">-</td>
                                    <td class="note-cell">-</td>
                                </tr>
                                
                                <?php 
                                $total_general_points += $total_domaine_points;
                                $total_general_coef += $total_domaine_coef;
                                ?>
                            <?php endforeach; endif; ?>
                            
                            <tr style="background-color: #2c3e50; color: white;">
                                <td class="text-right"><strong>TOTAUX GÉNÉRAUX</strong></td>
                                <?php for($i=0; $i<($id_trimestre_selectionne == 4 ? 9 : 3); $i++): ?>
                                    <td class="note-cell">-</td>
                                <?php endfor; ?>
                                <td class="note-cell"><strong><?= $total_general_coef; ?></strong></td>
                                <td class="note-cell">-</td>
                                <td class="note-cell"><strong><?= number_format($total_general_points, 1); ?></strong></td>
                                <td class="note-cell">-</td>
                                <td class="note-cell">-</td>
                            </tr>
                            
                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                <td class="text-right"><strong>MOYENNE GÉNÉRALE</strong></td>
                                <?php for($i=0; $i<($id_trimestre_selectionne == 4 ? 9 : 3); $i++): ?>
                                    <td class="note-cell">-</td>
                                <?php endfor; ?>
                                <td class="note-cell"><?= $total_general_coef; ?></td>
                                <td class="note-cell">
                                    <strong><?= ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?> / 20</strong>
                                </td>
                                <td class="note-cell">-</td>
                                <td class="note-cell"><strong><?= $bulletin_data['rang'] ?? '-'; ?>e / <?= count($eleves_list); ?></strong></td>
                                <td class="note-cell">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- RÉSUMÉ -->
                <div class="summary-box">
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="label">Moyenne Générale</div>
                            <div class="value"><?= ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?> / 20</div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Rang</div>
                            <div class="value"><?= $bulletin_data['rang'] ?? '-'; ?>e / <?= count($eleves_list); ?></div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Appréciation</div>
                            <div class="value">
                                <?php 
                                $moy = ($total_general_coef > 0) ? $total_general_points / $total_general_coef : 0;
                                if ($moy >= 16) echo 'Excellent';
                                elseif ($moy >= 14) echo 'Très Bien';
                                elseif ($moy >= 12) echo 'Bien';
                                elseif ($moy >= 10) echo 'Assez Bien';
                                else echo 'À améliorer';
                                ?>
                            </div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Statut</div>
                            <div class="value"><?= ($moy >= 10) ? 'Admis' : 'Non admis'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- SIGNATURES -->
                <div class="signature-box">
                    <div><div class="signature-line"></div><strong>Visa de l'Enseignant(e)</strong></div>
                    <div><div class="signature-line"></div><strong>Conseil des Maîtres</strong></div>
                    <div><div class="signature-line"></div><strong>Visa du Directeur</strong></div>
                </div>

                <!-- BOUTONS D'ACTION -->
                <div class="btn-actions">
                    <a href="index.php?action=generer_bulletin_pdf&matricule=<?= urlencode($eleve_data['matricule']); ?>&id_trimestre=<?= urlencode($id_trimestre_selectionne); ?>&id_classe=<?= urlencode($id_classe_selectionnee); ?>" target="_blank" class="btn btn-danger">
                        <i class="fas fa-file-pdf mr-2"></i> PDF (élève)
                    </a>
                    <a href="index.php?action=generer_bulletins_classe_pdf&id_classe=<?= urlencode($id_classe_selectionnee); ?>&id_trimestre=<?= urlencode($id_trimestre_selectionne); ?>" target="_blank" class="btn btn-warning">
                        <i class="fas fa-files-pdf mr-2"></i> PDF (toute la classe)
                    </a>
                    <button class="btn btn-primary" onclick="window.print();">
                        <i class="fas fa-print mr-2"></i> Imprimer
                    </button>
                    <a href="index.php?action=bulletins" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i> Retour
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h5>Sélectionnez un élève dans la liste de gauche</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <?php elseif ($id_classe_selectionnee && $id_trimestre_selectionne): ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle fa-3x mb-3"></i>
        <h5>Aucun élève trouvé dans cette classe</h5>
        <a href="index.php?action=formulaire_ajout" class="btn btn-primary mt-3">Inscrire un élève</a>
    </div>
    <?php else: ?>
    <div class="alert alert-warning text-center">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
        <h5>Sélectionnez une classe et un trimestre pour commencer</h5>
    </div>
    <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    (function(){
        try {
            var role = '<?= isset($role_actuel) ? $role_actuel : 'enseignant'; ?>';
            // Si l'utilisateur est un enseignant, on replie la sidebar pour maximiser l'espace du bulletin
            if (role === 'enseignant') {
                var sidebar = document.getElementById('sidebar');
                var main = document.getElementById('main-content');
                if (sidebar) sidebar.classList.add('collapsed');
                if (main) main.classList.add('expanded');
            }
        } catch (e) {
            console.warn('Impossible d\'appliquer le repli automatique de la sidebar:', e);
        }
    })();
</script>
</body>
</html>