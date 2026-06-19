<?php
// vues/notes/bulletins.php
$titre_page = (isset($trimestre_selectionne) && $trimestre_selectionne == 3) ? 'Bulletin annuel' : 'Bulletins trimestriels';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titre_page); ?> - EduManage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: white !important; font-family: 'Times New Roman', Times, serif; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .card { border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: linear-gradient(135deg, #2c3e50, #3498db); color: white; }
        .sidebar-list { max-height: 70vh; overflow-y: auto; }
        .sidebar-item { padding: 10px 15px; border-bottom: 1px solid #dee2e6; cursor: pointer; text-decoration: none; color: #333; display: block; }
        .sidebar-item:hover { background-color: #e9ecef; text-decoration: none; }
        .sidebar-item.active { background-color: #2c3e50; color: white; }
        .table-bulletin { border: 1px solid #000; font-size: 0.75rem; }
        .table-bulletin th { background-color: #2c3e50; color: white; text-align: center; }
        .section-domaine { background-color: #d4d9e2; font-weight: bold; }
        @media print {
            body { padding: 0; margin: 0; }
            .no-print, button, .btn, .card-header, .sidebar-list { display: none !important; }
            .col-md-2, .col-md-10 { width: 100% !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-file-alt mr-2"></i> <?php echo htmlspecialchars($titre_page); ?></h4>
        </div>
        <div class="card-body">
            <!-- Formulaire de sélection -->
            <form method="GET" action="index.php" class="row mb-4 no-print">
                <input type="hidden" name="action" value="bulletins">
                <div class="col-md-4">
                    <label class="font-weight-bold">Classe</label>
                    <select name="id_classe" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($classes as $cl): ?>
                            <option value="<?php echo $cl['id']; ?>" <?php echo ($classe_selectionnee == $cl['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cl['nom_classe']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="font-weight-bold">Trimestre</label>
                    <select name="id_trimestre" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($trimestres as $trim): ?>
                            <option value="<?php echo $trim['id']; ?>" <?php echo ($trimestre_selectionne == $trim['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($trim['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Voir les bulletins</button>
                </div>
            </form>

            <?php if ($classe_selectionnee && $trimestre_selectionne && !empty($eleves)): ?>
                <div class="row">
                    <!-- Liste des élèves -->
                    <div class="col-md-3 no-print">
                        <div class="card">
                            <div class="card-header py-2">
                                <h6 class="mb-0"><i class="fas fa-users mr-2"></i> Élèves (<?php echo count($eleves); ?>)</h6>
                            </div>
                            <div class="sidebar-list">
                                <?php foreach ($eleves as $idx => $elev): ?>
                                    <a href="index.php?action=bulletins&id_classe=<?php echo $classe_selectionnee; ?>&id_trimestre=<?php echo $trimestre_selectionne; ?>&id_sequence=<?php echo $id_sequence_selectionnee ?? 0; ?>&eleve_idx=<?php echo $idx; ?>" 
                                       class="sidebar-item <?php echo ($idx == $eleve_index) ? 'active' : ''; ?>">
                                        <strong><?php echo htmlspecialchars($elev['nom'] . ' ' . $elev['prenom']); ?></strong>
                                        <small class="d-block text-muted">Matricule: <?php echo $elev['matricule']; ?></small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bulletin de l'élève sélectionné -->
                    <div class="col-md-9">
                        <?php if (isset($bulletin_data) && !empty($bulletin_data) && isset($eleve_courant)): ?>
                            <div class="bulletin-print">
                                <!-- En-tête -->
                                <div class="text-center mb-4">
                                    <h5>MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</h5>
                                    <h6>GROUPE SCOLAIRE EDUMANAGE</h6>
                                    <h4 class="mt-3">BULLETIN DE NOTES</h4>
                                    <h5><?php echo htmlspecialchars($trimestre_nom ?? $trimestre_selectionne . 'e Trimestre'); ?></h5>
                                    <hr>
                                </div>

                                <!-- Infos élève -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered">
                                            <tr><td width="40%"><strong>Nom et prénom</strong></td><td><?php echo htmlspecialchars($eleve_courant['nom'] . ' ' . $eleve_courant['prenom']); ?></td></tr>
                                            <tr><td><strong>Matricule</strong></td><td><?php echo htmlspecialchars($eleve_courant['matricule']); ?></td></tr>
                                            <tr><td><strong>Classe</strong></td><td><?php echo htmlspecialchars($nom_classe ?? ''); ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm table-bordered">
                                            <tr><td width="40%"><strong>Effectif</strong></td><td><?php echo count($eleves); ?></td></tr>
                                            <tr><td><strong>Année scolaire</strong></td><td>2025-2026</td></tr>
                                            <tr><td><strong>Prof. principal</strong></td><td><?php echo htmlspecialchars($prof_principal ?? 'Non assigné'); ?></td></tr>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tableau des notes -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-bulletin">
                                        <thead>
                                            <tr>
                                                <th style="width: 40%">MATIÈRES</th>
                                                <th style="width: 10%">COEF</th>
                                                <th style="width: 15%">NOTE</th>
                                                <th style="width: 35%">APPRÉCIATIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_points = 0;
                                            $total_coef = 0;
                                            $domaine_actuel = '';
                                            
                                            foreach ($bulletin_data as $note):
                                                if ($domaine_actuel != $note['domaine']):
                                                    $domaine_actuel = $note['domaine'];
                                            ?>
                                                <tr class="section-domaine">
                                                    <td colspan="4"><strong><?php echo htmlspecialchars($note['domaine']); ?></strong></td>
                                                </tr>
                                            <?php 
                                                endif;
                                                
                                                $valeur = $note['valeur'];
                                                $coeff = $note['coefficient'];
                                                
                                                if ($valeur !== '' && $valeur !== null) {
                                                    $total_points += floatval($valeur) * $coeff;
                                                    $total_coef += $coeff;
                                                }
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($note['nom_matiere']); ?></td>
                                                    <td class="text-center"><?php echo $coeff; ?></td>
                                                    <td class="text-center">
                                                        <?php echo ($valeur !== '' && $valeur !== null) ? number_format($valeur, 2) : '-'; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($note['observation'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <tr style="background-color: #2c3e50; color: white;">
                                                <td class="text-right"><strong>TOTAUX</strong></td>
                                                <td class="text-center"><strong><?php echo $total_coef; ?></strong></td>
                                                <td class="text-center"><strong><?php echo number_format($total_points, 1); ?></strong></td>
                                                <td></td>
                                            </tr>
                                            <tr style="background-color: #f8f9fa; font-weight: bold;">
                                                <td class="text-right"><strong>MOYENNE GÉNÉRALE</strong></td>
                                                <td class="text-center"><?php echo $total_coef; ?></td>
                                                <td class="text-center">
                                                    <strong><?php echo ($total_coef > 0) ? number_format($total_points / $total_coef, 2) : '0.00'; ?></strong>
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Signatures -->
                                <div class="row mt-5 text-center">
                                    <div class="col-4 border-right">
                                        <strong>Visa de l'Enseignant</strong><br><br><br>
                                    </div>
                                    <div class="col-4 border-right">
                                        <strong>Conseil des Maîtres</strong><br><br><br>
                                    </div>
                                    <div class="col-4">
                                        <strong>Visa du Directeur</strong><br><br><br>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3 no-print">
                                <button class="btn btn-success" onclick="window.print();">
                                    <i class="fas fa-print mr-1"></i> Imprimer le bulletin
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-3x mb-3"></i>
                                <h5>Sélectionnez un élève pour voir son bulletin</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($classe_selectionnee && $trimestre_selectionne && empty($eleves)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h5>Aucun élève dans cette classe</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>