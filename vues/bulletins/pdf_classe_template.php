<?php
// vues/bulletins/pdf_classe_template.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins de la classe <?php echo htmlspecialchars($classe['nom_classe']); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11px; margin: 20px; }
        .page-break { page-break-after: always; }
        .bulletin-container { max-width: 100%; margin: 0 auto; padding: 15px; border: 1px solid #000; margin-bottom: 20px; }
        .entete { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .entete h4 { margin: 5px 0; }
        .entete h2 { margin: 10px 0; }
        .info-eleve { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-eleve td { border: 1px solid #000; padding: 6px; }
        .main-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 4px; }
        .main-table th { background-color: #d4d9e2; text-align: center; }
        .domaine-row { background-color: #e9ecef; font-weight: bold; }
        .signature-box { display: flex; justify-content: space-between; margin-top: 20px; border-top: 1px solid #000; padding-top: 15px; text-align: center; }
        .signature-item { width: 30%; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; margin-bottom: 8px; }
        .footer { text-align: center; font-size: 9px; margin-top: 10px; }
    </style>
</head>
<body>
<?php foreach ($tous_bulletins as $index => $item): ?>
<div class="bulletin-container <?= ($index < count($tous_bulletins) - 1) ? 'page-break' : ''; ?>">
    <div class="entete">
        <h4>MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</h4>
        <h4>GROUPE SCOLAIRE EDUMANAGE</h4>
        <h2><?= ($id_trimestre == 4) ? 'BULLETIN ANNUEL' : 'BULLETIN DE NOTES'; ?></h2>
        <h4><?= htmlspecialchars($trimestre); ?> - Année scolaire 2025-2026</h4>
    </div>

    <table class="info-eleve">
        <tr><td width="30%"><strong>Nom et prénom</strong></td><td><?= htmlspecialchars($item['eleve']['nom'] . ' ' . $item['eleve']['prenom']); ?></td><td width="30%"><strong>Matricule</strong></td><td><?= htmlspecialchars($item['eleve']['matricule']); ?></td></tr>
        <tr><td width="30%"><strong>Classe</strong></td><td><?= htmlspecialchars($classe['nom_classe']); ?></td><td width="30%"><strong>Effectif</strong></td><td><?= $classe['effectif']; ?></td></tr>
    </table>

    <table class="main-table">
        <thead>
            <tr><th style="width: 35%">MATIÈRES</th>
                <?php if ($id_trimestre == 4): ?>
                    <th>M1</th><th>M2</th><th>M3</th><th>M4</th><th>M5</th><th>M6</th><th>M7</th><th>M8</th><th>M9</th>
                <?php else: ?>
                    <th>M1</th><th>M2</th><th>M3</th>
                <?php endif; ?>
                <th>COEF</th><th>MOY</th><th>APPR.</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_points = 0;
            $total_coef = 0;
            if (!empty($item['bulletin']['domaines'])):
                foreach ($item['bulletin']['domaines'] as $domaine):
            ?>
                <tr class="domaine-row"><td colspan="<?= ($id_trimestre == 4) ? 13 : 7; ?>"><strong><?= htmlspecialchars($domaine['nom']); ?></strong></td></tr>
                <?php foreach ($domaine['matieres'] as $matiere): ?>
                    <?php if ($matiere['n_x_c'] !== null) { $total_points += $matiere['n_x_c']; $total_coef += $matiere['coefficient']; } ?>
                    <tr>
                        <td><?= htmlspecialchars($matiere['nom']); ?></td>
                        <?php if ($id_trimestre == 4): ?>
                            <td class="text-center"><?= $matiere['notes']['seq1'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq2'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq3'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq4'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq5'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq6'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq7'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq8'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq9'] ?? '-'; ?></td>
                        <?php else: ?>
                            <td class="text-center"><?= $matiere['notes']['seq1'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq2'] ?? '-'; ?></td>
                            <td class="text-center"><?= $matiere['notes']['seq3'] ?? '-'; ?></td>
                        <?php endif; ?>
                        <td class="text-center"><?= $matiere['coefficient']; ?></td>
                        <td class="text-center"><strong><?= $matiere['moyenne'] ?? '-'; ?></strong></td>
                        <td><?= htmlspecialchars($matiere['appreciation'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; endif; ?>
            <tr style="background-color: #2c3e50; color: white;">
                <td class="text-right"><strong>MOYENNE GÉNÉRALE</strong></td>
                <?php for($i=0; $i<($id_trimestre == 4 ? 9 : 3); $i++): ?><td class="text-center">-</td><?php endfor; ?>
                <td class="text-center"><?= $total_coef; ?></td>
                <td class="text-center"><strong><?= ($total_coef > 0) ? number_format($total_points / $total_coef, 2) : '0.00'; ?> / 20</strong></td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-box">
        <div class="signature-item"><div class="signature-line"></div>Visa Enseignant</div>
        <div class="signature-item"><div class="signature-line"></div>Conseil des Maîtres</div>
        <div class="signature-item"><div class="signature-line"></div>Visa Directeur</div>
    </div>
    <div class="footer">Bulletin généré le <?= date('d/m/Y'); ?></div>
</div>
<?php endforeach; ?>
</body>
</html>