<?php
// vues/bulletins/pdf_template.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            margin: 20px;
            background: white;
        }
        .bulletin-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
        }
        .entete {
            text-align: center;
            padding: 15px;
            border-bottom: 3px double #000;
        }
        .entete h4 {
            margin: 5px 0;
            font-size: 12px;
        }
        .entete h2 {
            margin: 10px 0;
            font-size: 16px;
        }
        .info-eleve {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .info-eleve td {
            border: 1px solid #000;
            padding: 8px;
        }
        .info-eleve td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 10px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .main-table th {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            font-weight: bold;
        }
        .main-table td {
            vertical-align: middle;
        }
        .domaine-row {
            background-color: #d4d9e2;
            font-weight: bold;
        }
        .domaine-row td {
            padding: 8px;
        }
        .total-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .note-cell {
            text-align: center;
        }
        .summary-box {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .summary-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .signature-box {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            border-top: 1px solid #000;
            padding-top: 15px;
            text-align: center;
        }
        .signature-item {
            width: 30%;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin: 20px auto 8px auto;
            width: 80%;
        }
        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 15px;
            padding-bottom: 15px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
    </style>
</head>
<body>

<div class="bulletin-container">
    <!-- EN-TÊTE -->
    <div class="entete">
        <h4>MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</h4>
        <h4>GROUPE SCOLAIRE EDUMANAGE</h4>
        <h2><?= ($id_trimestre == 4) ? 'BULLETIN ANNUEL' : 'BULLETIN DE NOTES'; ?></h2>
        <h4><?= htmlspecialchars($trimestre); ?> - Année scolaire 2025-2026</h4>
    </div>

    <!-- INFORMATIONS ÉLÈVE -->
    <table class="info-eleve">
        <tr><td>Nom et prénom</td><td><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></td><td>Matricule</td><td><?= htmlspecialchars($eleve['matricule']); ?></td></tr>
        <tr><td>Classe</td><td><?= htmlspecialchars($classe['nom_classe']); ?></td><td>Effectif</td><td><?= $classe['effectif']; ?></td></tr>
    </table>

    <!-- TABLEAU DES NOTES -->
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 35%">MATIÈRES / DOMAINES</th>
                <?php if ($id_trimestre == 4): ?>
                    <th style="width: 5%">M1</th><th style="width: 5%">M2</th><th style="width: 5%">M3</th>
                    <th style="width: 5%">M4</th><th style="width: 5%">M5</th><th style="width: 5%">M6</th>
                    <th style="width: 5%">M7</th><th style="width: 5%">M8</th><th style="width: 5%">M9</th>
                <?php else: ?>
                    <th style="width: 7%">M1</th><th style="width: 7%">M2</th><th style="width: 7%">M3</th>
                <?php endif; ?>
                <th style="width: 5%">COEF</th>
                <th style="width: 7%">MOY</th>
                <th style="width: 7%">N×C</th>
                <th style="width: 5%">RANG</th>
                <th style="width: 15%">APPR.</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_general_points = 0;
            $total_general_coef = 0;
            
            if (!empty($bulletin['domaines'])):
                foreach ($bulletin['domaines'] as $domaine):
                    $total_domaine_points = 0;
                    $total_domaine_coef = 0;
            ?>
                <tr class="domaine-row">
                    <td colspan="<?= ($id_trimestre == 4) ? 14 : 8; ?>">
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
                        <td class="text-left"><strong><?= htmlspecialchars($matiere['nom']); ?></strong></td>
                        <?php if ($id_trimestre == 4): ?>
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
                        <td><?= htmlspecialchars(substr($matiere['appreciation'] ?? '-', 0, 15)); ?></td>
                    </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td class="text-right"><strong>TOTAL <?= htmlspecialchars($domaine['nom']); ?></strong></td>
                    <?php for($i=0; $i<($id_trimestre == 4 ? 9 : 3); $i++): ?>
                        <td class="note-cell">-</td>
                    <?php endfor; ?>
                    <td class="note-cell"><strong><?= $total_domaine_coef; ?></strong></td>
                    <td class="note-cell">-</td>
                    <td class="note-cell"><strong><?= number_format($total_domaine_points, 1); ?></strong></td>
                    <td class="note-cell">-</td>
                    <td class="note-cell">-</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right"><strong>MOYENNE <?= htmlspecialchars($domaine['nom']); ?></strong></td>
                    <?php for($i=0; $i<($id_trimestre == 4 ? 9 : 3); $i++): ?>
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
                <?php for($i=0; $i<($id_trimestre == 4 ? 9 : 3); $i++): ?>
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
                <?php for($i=0; $i<($id_trimestre == 4 ? 9 : 3); $i++): ?>
                    <td class="note-cell">-</td>
                <?php endfor; ?>
                <td class="note-cell"><?= $total_general_coef; ?></td>
                <td class="note-cell">
                    <strong><?= ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?> / 20</strong>
                </td>
                <td class="note-cell">-</td>
                <td class="note-cell"><strong><?= $bulletin['rang'] ?? '-'; ?>e / <?= $classe['effectif']; ?></strong></td>
                <td class="note-cell">-</td>
            </tr>
        </tbody>
    </table>

    <!-- RÉSUMÉ -->
    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td width="25%"><strong>Moyenne Générale</strong></td>
                <td width="25%"><strong><?= ($total_general_coef > 0) ? number_format($total_general_points / $total_general_coef, 2) : '0.00'; ?> / 20</strong></td>
                <td width="25%"><strong>Rang</strong></td>
                <td width="25%"><strong><?= $bulletin['rang'] ?? '-'; ?>e / <?= $classe['effectif']; ?></strong></td>
            </tr>
            <tr>
                <td><strong>Appréciation</strong></td>
                <td colspan="3">
                    <?php 
                    $moy = ($total_general_coef > 0) ? $total_general_points / $total_general_coef : 0;
                    if ($moy >= 16) echo 'Excellent';
                    elseif ($moy >= 14) echo 'Très Bien';
                    elseif ($moy >= 12) echo 'Bien';
                    elseif ($moy >= 10) echo 'Assez Bien';
                    elseif ($moy >= 8) echo 'Passable';
                    else echo 'Insuffisant';
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- SIGNATURES -->
    <div class="signature-box">
        <div class="signature-item">
            <div class="signature-line"></div>
            <strong>Visa de l'Enseignant(e)</strong>
        </div>
        <div class="signature-item">
            <div class="signature-line"></div>
            <strong>Conseil des Maîtres</strong>
        </div>
        <div class="signature-item">
            <div class="signature-line"></div>
            <strong>Visa du Directeur</strong>
        </div>
    </div>
    
    <div class="footer">Bulletin généré le <?= date('d/m/Y à H:i:s'); ?></div>
</div>

</body>
</html>