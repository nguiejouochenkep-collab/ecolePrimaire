<!-- vues/bulletins/bulletin_annuel.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin Annuel - <?php echo htmlspecialchars($bulletin['eleve']['nom'] . ' ' . $bulletin['eleve']['prenom']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            padding: 20px;
            background: white;
        }
        .bulletin-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .entete-bulletin {
            border-bottom: 3px solid #000;
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
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            margin: 10px 0;
            color: #2c3e50;
        }
        .sous-titre {
            text-align: center;
            font-size: 1rem;
            margin: 5px 0;
            color: #e74c3c;
        }
        .info-eleve {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 15px 0;
            font-size: 0.8rem;
        }
        .table-bulletin {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.7rem;
            margin-bottom: 20px;
        }
        .table-bulletin th {
            background-color: #2c3e50;
            color: white;
            border: 1px solid #000;
            text-align: center;
            padding: 8px;
        }
        .table-bulletin td {
            border: 1px solid #000;
            padding: 6px;
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
        .moyenne-annuelle {
            background-color: #d1ecf1;
            font-weight: bold;
        }
        .rang {
            background-color: #fff3cd;
        }
        .appreciation {
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid #ddd;
            font-style: italic;
        }
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .no-print { display: none; }
            .bulletin-container { box-shadow: none; padding: 0; margin: 0; border: none; }
        }
        @page { size: A4; margin: 10mm; }
    </style>
</head>
<body>
<div class="bulletin-container">
    <div class="entete-bulletin">
        <div class="row" style="display: flex; justify-content: space-between;">
            <div class="entete-gauche" style="width: 50%;">
                MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES<br>
                <strong>GROUPE SCOLAIRE EDUMANAGE</strong>
            </div>
            <div class="entete-droite" style="width: 50%;">
                MINISTRY OF SECONDARY EDUCATION<br>
                <strong>EDUMANAGE SCHOOL GROUP</strong>
            </div>
        </div>
        <div class="titre-bulletin">
            BULLETIN DE NOTES - BILAN ANNUEL<br>
            <small>Année Scolaire 2025-2026</small>
        </div>
    </div>
    
    <div class="info-eleve">
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
            <div><strong>Nom :</strong> <?php echo htmlspecialchars($bulletin['eleve']['nom'] . ' ' . $bulletin['eleve']['prenom']); ?></div>
            <div><strong>Classe :</strong> <?php echo htmlspecialchars($bulletin['eleve']['nom_classe']); ?></div>
            <div><strong>Matricule :</strong> <?php echo htmlspecialchars($bulletin['eleve']['matricule']); ?></div>
            <div><strong>Rang :</strong> <?php echo $bulletin['rang']; ?></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <div><strong>Prof. principal :</strong> <?php echo htmlspecialchars($bulletin['eleve']['professeur_principal'] ?? 'Non assigné'); ?></div>
            <div><strong>Moyenne Générale Annuelle :</strong> <strong style="color: #e74c3c;"><?php echo number_format($bulletin['moyenne_generale'], 2); ?> / 20</strong></div>
        </div>
    </div>
    
    <table class="table-bulletin">
        <thead>
            <tr>
                <th style="width: 25%">MATIÈRES & DOMAINES</th>
                <th style="width: 10%">Trimestre 1</th>
                <th style="width: 10%">Trimestre 2</th>
                <th style="width: 10%">Trimestre 3</th>
                <th style="width: 8%">Moy. Annuelle</th>
                <th style="width: 5%">COEF</th>
                <th style="width: 8%">N × C</th>
                <th style="width: 24%">APPRÉCIATIONS</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $total_general_points = 0;
        $total_general_coef = 0;
        
        foreach ($bulletin['matieres_par_domaine'] as $nom_domaine => $domaine): 
            $total_groupe_points = 0;
            $total_groupe_coef = 0;
        ?>
            <tr class="section-domaine">
                <td colspan="8"><strong><?php echo htmlspecialchars($nom_domaine); ?></strong></td>
            </tr>
            
            <?php foreach ($domaine['matieres'] as $matiere): 
                $moy_annuelle = $matiere['moyenne_annuelle'];
                $note_x_coef = ($moy_annuelle !== null) ? $moy_annuelle * $matiere['coefficient'] : 0;
                
                if ($moy_annuelle !== null) {
                    $total_groupe_points += $note_x_coef;
                    $total_groupe_coef += $matiere['coefficient'];
                }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($matiere['nom_matiere']); ?></td>
                    <td class="text-center"><?php echo isset($matiere['moyennes_trimestres'][1]) ? number_format($matiere['moyennes_trimestres'][1], 2) : '-'; ?></td>
                    <td class="text-center"><?php echo isset($matiere['moyennes_trimestres'][2]) ? number_format($matiere['moyennes_trimestres'][2], 2) : '-'; ?></td>
                    <td class="text-center"><?php echo isset($matiere['moyennes_trimestres'][3]) ? number_format($matiere['moyennes_trimestres'][3], 2) : '-'; ?></td>
                    <td class="text-center"><strong><?php echo ($moy_annuelle !== null) ? number_format($moy_annuelle, 2) : '-'; ?></strong></td>
                    <td class="text-center"><?php echo $matiere['coefficient']; ?></td>
                    <td class="text-center"><?php echo ($moy_annuelle !== null) ? number_format($note_x_coef, 1) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($matiere['appreciation']); ?></td>
                </tr>
            <?php endforeach; ?>
            
            <tr class="total-groupe">
                <td class="text-right"><strong>TOTAL DOMAINE</strong></td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center"><strong><?php echo $total_groupe_coef; ?></strong></td>
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
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center"><strong><?php echo $total_general_coef; ?></strong></td>
            <td class="text-center"><strong><?php echo number_format($total_general_points, 1); ?></strong></td>
            <td></td>
        </tr>
        
        <tr class="moyenne-annuelle">
            <td class="text-right"><strong>MOYENNE GÉNÉRALE ANNUELLE</strong></td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center">-</td>
            <td class="text-center"><?php echo $total_general_coef; ?></td>
            <td class="text-center"><strong><?php echo number_format($bulletin['moyenne_generale'], 2); ?> / 20</strong></td>
            <td></td>
        </tr>
        </tbody>
    </table>
    
    <div class="appreciation">
        <strong>APPRÉCIATION GÉNÉRALE DE L'ANNÉE :</strong><br>
        <?php echo nl2br(htmlspecialchars($bulletin['appreciation_generale'] ?: 'Félicitations pour votre travail cette année. Continuez ainsi !')); ?>
    </div>
    
    <div style="margin-top: 20px; font-size: 0.7rem; text-align: center; border-top: 1px solid #ddd; padding-top: 10px;">
        <div style="display: flex; justify-content: space-between;">
            <div>Date d'édition : <?php echo date('d/m/Y'); ?></div>
            <div>Signature du Chef d'établissement</div>
        </div>
    </div>
</div>
</body>
</html>