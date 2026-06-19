<?php
// vues/notes/saisie.php
// Initialisation des variables pour éviter les erreurs
$classe_selectionnee = $classe_selectionnee ?? null;
$id_trimestre_selectionne = $id_trimestre_selectionne ?? null;
$liste_eleves = $liste_eleves ?? [];
$total_eleves = $total_eleves ?? 0;
$eleve_index = $eleve_index ?? 0;
$eleve_courant = $eleve_courant ?? null;
$nom_classe = $nom_classe ?? '';
$prof_principal = $prof_principal ?? '';
$mensuels = $mensuels ?? [];
$matieres_par_domaine = $matieres_par_domaine ?? [];
$notes_par_matiere = $notes_par_matiere ?? [];
$noteObsExistantes = $noteObsExistantes ?? [];
$stats_classe = $stats_classe ?? ['moyenne_classe' => 0];
$rang_eleve = $rang_eleve ?? '';
$trimestre_nom = $trimestre_nom ?? 'PREMIER TRIMESTRE';

// Ensuite votre code HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie des Notes - EduManage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: white !important;
            font-family: 'Times New Roman', Times, serif;
            padding: 20px;
        }
        .bulletin-container {
            background: white;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .entete-bulletin {
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        .entete-gauche, .entete-droite {
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .entete-droite { text-align: right; }
        .titre-bulletin {
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
        }
        .info-eleve {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 15px 0;
            font-size: 0.8rem;
        }
        .table-bulletin {
            border: 1px solid #000 !important;
            font-size: 0.7rem;
        }
        .table-bulletin th {
            background-color: #2c3e50 !important;
            color: white !important;
            border: 1px solid #000 !important;
            text-align: center;
            padding: 5px;
        }
        .table-bulletin td {
            border: 1px solid #000 !important;
            padding: 5px;
            vertical-align: middle;
        }
        .section-domaine {
            background-color: #d4d9e2;
            font-weight: bold;
        }
        .total-groupe {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .note-input {
            width: 50px;
            text-align: center;
            padding: 2px;
            font-size: 0.7rem;
        }
        .eleve-sidebar {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .eleve-item {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            text-decoration: none;
            display: block;
            color: #333;
            transition: all 0.2s;
        }
        .eleve-item:hover {
            background-color: #e9ecef;
            text-decoration: none;
        }
        .eleve-item.active {
            background-color: #2c3e50;
            color: white;
        }
        .selecteur-classe {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 50px auto;
        }
        .btn-matiere {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print, button, .btn, .selecteur-classe, .eleve-sidebar { display: none !important; }
            .bulletin-container { box-shadow: none; padding: 0; margin: 0; border: none; }
            .note-input { border: none; background: transparent; }
            .col-md-3, .col-md-9 { width: 100% !important; }
        }
        @page { size: A4; margin: 10mm; }
    </style>
</head>
<body>


<div style="background-color: white; padding: 20px; border-radius: 12px; min-height: 100vh;">

 

<?php if (!$classe_selectionnee || !$id_trimestre_selectionne): ?>
    <!-- Formulaire de sélection de la classe -->
    <div class="selecteur-classe">
        <div class="text-center mb-4">
            <i class="fas fa-edit fa-3x text-primary mb-3"></i>
            <h4>Saisie des Notes</h4>
            <p class="text-muted">Sélectionnez une classe et un trimestre</p>
        </div>
        
        <form method="GET" action="index.php">
            <input type="hidden" name="action" value="saisie_note">
            
            <div class="form-group mb-3">
                <label class="font-weight-bold">Classe</label>
                <select name="id_classe" class="form-control" required>
                    <option value="">-- Choisir une classe --</option>
                    <?php foreach ($classes as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>"><?php echo htmlspecialchars($cl['nom_classe']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mb-3">
                <label class="font-weight-bold">Trimestre</label>
                <select name="id_trimestre" class="form-control" required>
                    <option value="1">1er Trimestre</option>
                    <option value="2">2e Trimestre</option>
                    <option value="3">3e Trimestre (Bilan Annuel)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary btn-block" id="btnDemarrerSaisie">
                <i class="fas fa-arrow-right mr-2"></i> Démarrer la saisie
             </button>

        </form>
    </div>

<?php elseif (empty($liste_eleves)): ?>
    <!-- Aucun élève dans la classe -->
    <div class="alert alert-warning text-center">
        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
        <h4>Aucun élève dans cette classe</h4>
        <p>Veuillez d'abord inscrire des élèves.</p>
        <a href="index.php?action=formulaire_ajout" class="btn btn-primary">
            <i class="fas fa-user-plus mr-2"></i> Inscrire un élève
        </a>
        <a href="index.php?action=saisie_note" class="btn btn-secondary ml-2">
            <i class="fas fa-arrow-left mr-2"></i> Changer de classe
        </a>
    </div>

<?php else: ?>
    <!-- Interface à 2 colonnes -->
    <div class="row">
        <!-- Colonne gauche : Liste des élèves -->
        <div class="col-md-3 no-print">
            <div class="eleve-sidebar">
                <div class="bg-dark text-white p-3">
                    <h6 class="mb-0"><i class="fas fa-users mr-2"></i> Liste des élèves</h6>
                    <small><?php echo $total_eleves; ?> élève(s)</small>
                </div>
                <?php foreach ($liste_eleves as $idx => $elev): ?>
    <a href="javascript:void(0)" 
       class="eleve-item ajax-eleve-link <?php echo ($idx == $eleve_index) ? 'active' : ''; ?>" 
       data-idx="<?php echo $idx; ?>"
       data-matricule="<?php echo $elev['matricule']; ?>">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><?php echo htmlspecialchars($elev['nom'] . ' ' . $elev['prenom']); ?></strong>
                <br>
                <small class="<?php echo ($idx == $eleve_index) ? 'text-white-50' : 'text-muted'; ?>">
                    Matricule: <?php echo $elev['matricule']; ?>
                </small>
            </div>
            <div class="badge <?php echo ($idx == $eleve_index) ? 'badge-light' : 'badge-secondary'; ?>">
                #<?php echo $idx + 1; ?>
            </div>
        </div>
    </a>
<?php endforeach; ?>
            </div>
        </div>
        
        <!-- Colonne droite : Bulletin -->
        <div class="col-md-9">
            <!-- Boutons de gestion des matières -->
            <div class="mb-3 no-print">
                <button type="button" class="btn btn-success btn-sm btn-matiere" data-toggle="modal" data-target="#modalAjoutMatiere">
                    <i class="fas fa-plus-circle mr-1"></i> Ajouter une matière
                </button>
                <button type="button" class="btn btn-warning btn-sm btn-matiere" data-toggle="modal" data-target="#modalGererMatieres">
                    <i class="fas fa-edit mr-1"></i> Gérer les matières
                </button>
                <a href="index.php?action=saisie_note" class="btn btn-secondary btn-sm">
                    <i class="fas fa-undo mr-1"></i> Changer de classe
                </a>
            </div>
            
            <div class="bulletin-container">
                <div class="entete-bulletin">
                    <div class="row">
                        <div class="col-6 entete-gauche">
                            MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES<br>
                            <strong>GROUPE SCOLAIRE EDUMANAGE</strong>
                        </div>
                        <div class="col-6 entete-droite">
                            MINISTRY OF SECONDARY EDUCATION<br>
                            <strong>EDUMANAGE SCHOOL GROUP</strong>
                        </div>
                    </div>
                    <div class="titre-bulletin">
                        BULLETIN DE NOTES<br>
                        <small><?php echo htmlspecialchars($trimestre_nom ?? 'PREMIER TRIMESTRE'); ?></small>
                    </div>
                </div>
                
                <div class="info-eleve">
                    <div class="row">
                        <div class="col-md-4"><strong>Nom :</strong> <?php echo $eleve_courant ? htmlspecialchars($eleve_courant['nom'] . ' ' . $eleve_courant['prenom']) : 'N/A'; ?></div>
                        <div class="col-md-3"><strong>Classe :</strong> <?php echo htmlspecialchars($nom_classe ?? ''); ?></div>
                        <div class="col-md-3"><strong>Matricule :</strong> <?php echo $eleve_courant ? htmlspecialchars($eleve_courant['matricule']) : 'N/A'; ?></div>
                        <div class="col-md-2"><strong>Effectif :</strong> <?php echo $total_eleves; ?></div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6"><strong>Prof. principal :</strong> <?php echo htmlspecialchars($prof_principal ?? 'Non assigné'); ?></div>
                        <div class="col-md-6"><strong>Année scolaire :</strong> 2025-2026</div>
                    </div>
                </div>
                
                <form method="POST" action="index.php?action=saisie_note">
                    <input type="hidden" name="enregistrer_bulletin_eleve" value="1">
                    <input type="hidden" name="id_classe" value="<?php echo $classe_selectionnee; ?>">
                    <input type="hidden" name="id_trimestre" value="<?php echo $id_trimestre_selectionne; ?>">
                    <input type="hidden" name="matricule_eleve" value="<?php echo $eleve_courant ? $eleve_courant['matricule'] : ''; ?>">
                    <input type="hidden" name="eleve_idx" value="<?php echo $eleve_index; ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-bulletin">
                            <thead>
                                <tr>
                                    <th style="width: 25%">MATIÈRES</th>
                                    <?php foreach ($mensuels as $mensuel): ?>
                                        <th style="width: 7%"><?php echo htmlspecialchars($mensuel['nom']); ?></th>
                                    <?php endforeach; ?>
                                    <th style="width: 5%">COEF</th>
                                    <th style="width: 7%">MOY</th>
                                    <th style="width: 7%">N×C</th>
                                    <th style="width: 18%">APPRÉCIATIONS</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php 
    $total_general_points = 0;
    $total_general_coef = 0;
    
    // Tableau pour stocker les moyennes par mensuel de l'élève
    $moyennes_eleve_par_mensuel = [];
    foreach ($mensuels as $idx => $mensuel) {
        $moyennes_eleve_par_mensuel[$idx] = ['total' => 0, 'count' => 0];
    }
    
    foreach ($matieres_par_domaine as $nom_domaine => $matieres): 
        $total_groupe_points = 0;
        $total_groupe_coef = 0;
    ?>
        <tr class="section-domaine">
            <td colspan="<?php echo 6 + count($mensuels); ?>"><strong><?php echo htmlspecialchars($nom_domaine); ?></strong></td>
        </tr>
        
        <?php if (empty($matieres)): ?>
            <tr>
                <td colspan="<?php echo 6 + count($mensuels); ?>" class="text-center text-muted">
                    Aucune matière - <button type="button" class="btn btn-link btn-sm p-0" data-toggle="modal" data-target="#modalAjoutMatiere">Ajouter une matière</button>
                 </td>
             </tr>
        <?php else: ?>
            <?php foreach ($matieres as $mat): 
                $moyenne_matiere = 0;
                $nb_notes = 0;
                
                foreach ($mensuels as $idx => $mensuel) {
                    $note = isset($notes_par_matiere[$mat['id']][$mensuel['id']]) ? $notes_par_matiere[$mat['id']][$mensuel['id']] : '';
                    if ($note !== '') {
                        $moyenne_matiere += floatval($note);
                        $nb_notes++;
                        // Accumuler pour les moyennes mensuelles de l'élève
                        $moyennes_eleve_par_mensuel[$idx]['total'] += floatval($note);
                        $moyennes_eleve_par_mensuel[$idx]['count']++;
                    }
                }
                
                $moyenne_matiere = ($nb_notes > 0) ? $moyenne_matiere / $nb_notes : null;
                $note_x_coef = ($moyenne_matiere !== null) ? $moyenne_matiere * $mat['coefficient'] : 0;
                
                if ($moyenne_matiere !== null) {
                    $total_groupe_points += $note_x_coef;
                    $total_groupe_coef += $mat['coefficient'];
                }
            ?>
                <tr>
                    <td class="pl-2">
                        <?php echo htmlspecialchars($mat['nom_matiere']); ?>
                        <small class="text-muted">(Coeff: <?php echo $mat['coefficient']; ?>)</small>
                     </td>
                    <?php foreach ($mensuels as $mensuel): ?>
                        <td class="text-center">
                            <input type="number" step="0.25" min="0" max="20" 
                                   name="notes[<?php echo $mat['id']; ?>][<?php echo $mensuel['id']; ?>]" 
                                   value="<?php echo isset($notes_par_matiere[$mat['id']][$mensuel['id']]) ? htmlspecialchars($notes_par_matiere[$mat['id']][$mensuel['id']]) : ''; ?>" 
                                   class="form-control form-control-sm note-input">
                         </td>
                    <?php endforeach; ?>
                    <td class="text-center"><?php echo $mat['coefficient']; ?></td>
                    <td class="text-center"><?php echo ($moyenne_matiere !== null) ? number_format($moyenne_matiere, 2) : '-'; ?></td>
                    <td class="text-center"><?php echo ($moyenne_matiere !== null) ? number_format($note_x_coef, 1) : '-'; ?></td>
                    <td>
                        <input type="text" name="observations[<?php echo $mat['id']; ?>]" 
                               class="form-control form-control-sm" 
                               value="<?php echo htmlspecialchars($noteObsExistantes[$mat['id']] ?? ''); ?>"
                               placeholder="Appréciation">
                     </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <tr class="total-groupe">
            <td class="text-right"><strong>TOTAL GROUPE</strong></td>
            <?php for($i=0; $i<count($mensuels); $i++): ?>
                <td class="text-center">-</td>
            <?php endfor; ?>
            <td class="text-center"><strong><?php echo $total_groupe_coef; ?></strong></td>
            <td class="text-center">-</td>
            <td class="text-center"><strong><?php echo number_format($total_groupe_points, 1); ?></strong></td>
            <td></td>
        </tr>
        
        <?php 
        $total_general_points += $total_groupe_points;
        $total_general_coef += $total_groupe_coef;
        ?>
    <?php endforeach; ?>
    
    <tr style="background-color: #2c3e50; color: white;">
        <td class="text-right"><strong>TOTAUX GÉNÉRAUX</strong></td>
        <?php for($i=0; $i<count($mensuels); $i++): ?>
            <td class="text-center">-</td>
        <?php endfor; ?>
        <td class="text-center"><strong><?php echo $total_general_coef; ?></strong></td>
        <td class="text-center">-</td>
        <td class="text-center"><strong><?php echo number_format($total_general_points, 1); ?></strong></td>
        <td></td>
    </tr>
    
    <tr style="background-color: #f8f9fa; font-weight: bold;">
        <td class="text-right"><strong>MOYENNE GÉNÉRALE</strong></td>
        <?php for($i=0; $i<count($mensuels); $i++): ?>
            <td class="text-center">-</td>
        <?php endfor; ?>
        <td class="text-center"><?php echo $total_general_coef; ?></td>
        <td class="text-center"><strong><?php echo ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?></strong></td>
        <td class="text-center">-</td>
        <td></td>
    </tr>
    
    <!-- LIGNES DES MOYENNES MENSUELLES DE L'ÉLÈVE -->
    <?php 
    $moyennes_mensuelles_eleve = [];
    foreach ($moyennes_eleve_par_mensuel as $idx => $data) {
        $moyennes_mensuelles_eleve[$idx] = ($data['count'] > 0) ? $data['total'] / $data['count'] : 0;
    }
    // Calcul de la moyenne du trimestre de l'élève
    $moyenne_trimestre_eleve = array_sum($moyennes_mensuelles_eleve) / count($mensuels);
    ?>
    
    <?php foreach ($moyennes_mensuelles_eleve as $idx => $moy): ?>
    <tr style="background-color: #e8f4f8;">
        <td class="text-right"><strong>MOYENNE <?php echo htmlspecialchars($mensuels[$idx]['nom'] ?? 'Mensuel '.($idx+1)); ?></strong></td>
        <?php for($i=0; $i<count($mensuels); $i++): ?>
            <td class="text-center">-</td>
        <?php endfor; ?>
        <td class="text-center">-</td>
        <td class="text-center"><strong><?php echo number_format($moy, 2); ?></strong></td>
        <td class="text-center">-</td>
        <td></td>
    </tr>
    <?php endforeach; ?>
    
    <tr style="background-color: #d1ecf1; font-weight: bold;">
        <td class="text-right"><strong>MOYENNE DU TRIMESTRE</strong></td>
        <?php for($i=0; $i<count($mensuels); $i++): ?>
            <td class="text-center">-</td>
        <?php endfor; ?>
        <td class="text-center">-</td>
        <td class="text-center"><strong style="font-size: 1.1rem;"><?php echo number_format($moyenne_trimestre_eleve, 2); ?></strong></td>
        <td class="text-center">-</td>
        <td></td>
    </tr>
</tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-3 no-print">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-save mr-1"></i> Enregistrer
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="window.print();">
                            <i class="fas fa-print mr-1"></i> Imprimer
                        </button>
                        <?php if ($eleve_index + 1 < $total_eleves): ?>
                            <button type="submit" name="suivant" value="1" class="btn btn-primary btn-sm">
                                Enregistrer et suivant <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Statistiques -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6><strong>PROFIL DE LA CLASSE</strong></h6>
                        <table class="table table-sm table-bordered" style="font-size: 0.7rem;">
                            <tr><td style="width: 50%"><strong>Effectif classe</strong></td><td><?php echo $total_eleves; ?></td></tr>
                            <tr><td><strong>Moyenne de la classe</strong></td><td><?php echo number_format($stats_classe['moyenne_classe'] ?? 0, 2); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>RÉSULTATS DE L'ÉLÈVE</strong></h6>
                        <table class="table table-sm table-bordered" style="font-size: 0.7rem;">
                            <tr><td style="width: 50%"><strong>Total des points</strong></td><td><?php echo number_format($total_general_points, 1); ?></td></tr>
                            <tr><td><strong>Total des coefficients</strong></td><td><?php echo $total_general_coef; ?></td></tr>
                            <tr><td><strong>Moyenne de l'élève</strong></td><td><?php echo ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?></td></tr>
                            <tr><td><strong>Rang</strong></td><td><?php echo $rang_eleve ?? '1e/' . $total_eleves; ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL AJOUTER MATIERE -->
<div class="modal fade" id="modalAjoutMatiere" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> Ajouter une matière</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalClasseId" value="<?php echo $classe_selectionnee ?? ''; ?>">
                <div class="form-group">
                    <label>Nom de la matière *</label>
                    <input type="text" id="modalNomMatiere" class="form-control" placeholder="Ex: Mathématiques">
                </div>
                <div class="form-group">
                    <label>Coefficient *</label>
                    <input type="number" id="modalCoefficient" class="form-control" min="1" max="10" value="1">
                </div>
                <div class="form-group">
                    <label>Domaine *</label>
                    <select id="modalDomaine" class="form-control">
                        <option value="I. LANGUES ET COMMUNICATION">I. LANGUES ET COMMUNICATION</option>
                        <option value="II. SCIENCE ET TECHNOLOGIE">II. SCIENCE ET TECHNOLOGIE</option>
                        <option value="III. SCIENCES HUMAINES">III. SCIENCES HUMAINES</option>
                        <option value="IV. L'AVENTURE HUMAINE">IV. L'AVENTURE HUMAINE</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-success" id="btnAjouterMatiere">Ajouter</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GERER MATIERES -->
<div class="modal fade" id="modalGererMatieres" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i> Gérer les matières</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-dark">
                            <tr><th>Matière</th><th>Coeff</th><th>Domaine</th><th>Actions</th></tr>
                        </thead>
                        <tbody id="listeMatieresModal">
                            <?php 
                            foreach ($matieres_par_domaine as $domaine => $matieres):
                                foreach ($matieres as $mat): ?>
                                <tr id="matiere-row-<?php echo $mat['id']; ?>">
                                    <td><input type="text" class="form-control form-control-sm" id="nom_<?php echo $mat['id']; ?>" value="<?php echo htmlspecialchars($mat['nom_matiere']); ?>"></td>
                                    <td><input type="number" class="form-control form-control-sm" id="coef_<?php echo $mat['id']; ?>" value="<?php echo $mat['coefficient']; ?>" min="1" max="10"></td>
                                    <td><?php echo htmlspecialchars($mat['domaine']); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="modifierMatiere(<?php echo $mat['id']; ?>)"><i class="fas fa-save"></i></button>
                                        <button class="btn btn-danger btn-sm" onclick="supprimerMatiere(<?php echo $mat['id']; ?>)"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
</div>
<script>
$(document).ready(function() {
    // Bouton Démarrer la saisie
    $('#btnDemarrerSaisie').click(function() {
        var idClasse = $('select[name="id_classe"]').val();
        var idTrimestre = $('select[name="id_trimestre"]').val();
        
        if (!idClasse) {
            alert('Veuillez sélectionner une classe');
            return;
        }
        
        if (!idTrimestre) {
            alert('Veuillez sélectionner un trimestre');
            return;
        }
        
        // Charger via AJAX
        if (typeof window.loadContent === 'function') {
            window.loadContent('saisie_note', {
                id_classe: idClasse,
                id_trimestre: idTrimestre
            });
        } else {
            // Fallback
            window.location.href = 'index.php?action=saisie_note&id_classe=' + idClasse + '&id_trimestre=' + idTrimestre + '&ajax=1';
        }
    });
    
    // Clic sur un élève
    $('.ajax-eleve-link').click(function(e) {
        e.preventDefault();
        
        var idx = $(this).data('idx');
        var matricule = $(this).data('matricule');
        var idClasse = $('input[name="id_classe"]').val();
        var idTrimestre = $('input[name="id_trimestre"]').val();
        
        if (typeof window.loadContent === 'function') {
            window.loadContent('saisie_note', {
                id_classe: idClasse,
                id_trimestre: idTrimestre,
                eleve_idx: idx,
                matricule: matricule
            });
        }
    });
});
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ajouter une matière
$('#btnAjouterMatiere').on('click', function() {
    var idClasse = $('#modalClasseId').val();
    var nomMatiere = $('#modalNomMatiere').val();
    var coefficient = $('#modalCoefficient').val();
    var domaine = $('#modalDomaine').val();
    
    if (!nomMatiere) {
        alert('Veuillez saisir le nom de la matière');
        return;
    }
    
    $.ajax({
        url: 'index.php?action=creer_matiere_ajax',
        type: 'POST',
        data: { id_classe: idClasse, nom_matiere: nomMatiere, coefficient: coefficient, domaine: domaine },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Erreur : ' + response.message);
            }
        },
        error: function() {
            alert('Erreur lors de l\'ajout');
        }
    });
});

// Modifier une matière
function modifierMatiere(id) {
    var nouveauNom = $('#nom_' + id).val();
    var nouveauCoeff = $('#coef_' + id).val();
    
    $.ajax({
        url: 'index.php?action=modifier_matiere_ajax',
        type: 'POST',
        data: { id_matiere: id, nom_matiere: nouveauNom, coefficient: nouveauCoeff },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert('Matière modifiée avec succès');
                location.reload();
            } else {
                alert('Erreur : ' + response.message);
            }
        }
    });
}

// Supprimer une matière
function supprimerMatiere(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
        $.ajax({
            url: 'index.php?action=supprimer_matiere_ajax',
            type: 'POST',
            data: { id_matiere: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('Matière supprimée avec succès');
                    location.reload();
                } else {
                    alert('Erreur : ' + response.message);
                }
            }
        });
    }
}
</script>
</body>
</html>