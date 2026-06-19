<?php
// vues/notes/bulletin_pdf.php
$note_total = 0;
$coef_total = 0;
$domaines_affiche = [];
$total_seq1 = 0;
$total_seq2 = 0;
$coef_seq1 = 0;
$coef_seq2 = 0;

foreach ($notes as $ligne) {
    $seq1 = strlen($ligne['seq1']) ? floatval($ligne['seq1']) : null;
    $seq2 = strlen($ligne['seq2']) ? floatval($ligne['seq2']) : null;
    $coef = intval($ligne['coefficient']);

    if ($seq1 !== null) {
        $total_seq1 += $seq1 * $coef;
        $coef_seq1 += $coef;
    }
    if ($seq2 !== null) {
        $total_seq2 += $seq2 * $coef;
        $coef_seq2 += $coef;
    }

    $observation = [];
    if (isset($ligne['observation_seq1']) && strlen(trim($ligne['observation_seq1']))) {
        $observation[] = 'S1: ' . trim($ligne['observation_seq1']);
    }
    if (isset($ligne['observation_seq2']) && strlen(trim($ligne['observation_seq2']))) {
        $observation[] = 'S2: ' . trim($ligne['observation_seq2']);
    }

    $domaines_affiche[$ligne['domaine']][] = [
        'matiere' => $ligne['nom_matiere'],
        'coef' => $coef,
        'seq1' => $seq1 !== null ? number_format($seq1, 2, ',', ' ') : '—',
        'seq2' => $seq2 !== null ? number_format($seq2, 2, ',', ' ') : '—',
        'observation' => count($observation) ? implode(' | ', $observation) : '—'
    ];
}

$moyenne_seq1 = $coef_seq1 > 0 ? number_format($total_seq1 / $coef_seq1, 2, ',', ' ') : 'N/A';
$moyenne_seq2 = $coef_seq2 > 0 ? number_format($total_seq2 / $coef_seq2, 2, ',', ' ') : 'N/A';
$moyenne_trimestre = 'N/A';
if ($moyenne_seq1 !== 'N/A' && $moyenne_seq2 !== 'N/A') {
    $moyenne_trimestre = number_format((floatval(str_replace(',', '.', $moyenne_seq1)) + floatval(str_replace(',', '.', $moyenne_seq2))) / 2, 2, ',', ' ');
} elseif ($moyenne_seq1 !== 'N/A') {
    $moyenne_trimestre = $moyenne_seq1;
} elseif ($moyenne_seq2 !== 'N/A') {
    $moyenne_trimestre = $moyenne_seq2;
}

$total_seq1_formatted = $coef_seq1 > 0 ? number_format($total_seq1, 2, ',', ' ') : '—';
$total_seq2_formatted = $coef_seq2 > 0 ? number_format($total_seq2, 2, ',', ' ') : '—';

$moyenne_general = $moyenne_trimestre !== 'N/A' ? $moyenne_trimestre : 'N/A';
$est_promu = ($moyenne_general !== 'N/A' && floatval(str_replace(',', '.', $moyenne_general)) >= 10) ? 'Oui' : 'Non';
$trimestre = $trimestre ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin de <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background: #ffffff; color: #0b0b0b; font-family: 'Times New Roman', serif; }
        .bulletin-paper { max-width: 900px; margin: 20px auto; background: white; padding: 32px; border: 2px solid #000; box-shadow: 0 0 0 1px rgba(0,0,0,0.05); }
        .bulletin-title { font-size: 1.45rem; letter-spacing: 1px; }
        .table-bulletin { border-collapse: collapse; width: 100%; }
        .table-bulletin th, .table-bulletin td { border: 1px solid #000 !important; padding: 0.55rem; }
        .table-bulletin th { background: #e9e9e9; }
        .table-bulletin tbody tr:nth-child(odd) td { background: #ffffff; }
        .table-bulletin .domain-row td { background: #d9d9d9 !important; font-weight: 700; }
        .summary-table td { padding: 0.55rem 0.75rem; }
        .save-pdf-btn { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        @media print { .save-pdf-btn { display: none; } body { margin: 0; } }
    </style>
    <script>
        function savePDF() {
            const element = document.getElementById('bulletin-paper');
            const eleveNom = element.getAttribute('data-eleve');
            const options = {
                margin: 5,
                filename: 'bulletin_' + eleveNom + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a5' }
            };
            html2pdf().set(options).from(element).save();
        }
    </script>
</head>
<body>
<div id="content-to-load">
    <button class="btn btn-primary save-pdf-btn no-print" onclick="savePDF()">Enregistrer sous format PDF</button>
    <div class="bulletin-paper" id="bulletin-paper" data-eleve="<?php echo htmlspecialchars(str_replace([' ', '/'], '_', $eleve['nom'] . '_' . $eleve['prenom'])); ?>">
        <div class="d-flex justify-content-between align-items-start mb-4">
            <div>
                <div class="font-weight-bold" style="font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;">République du Cameroun</div>
                <div class="font-weight-bold" style="font-size: 0.9rem; text-transform: uppercase;">Paix - Travail - Patrie</div>
                <div class="mt-3" style="font-size: 1rem;">Ministère de l'Éducation de Base</div>
                <div class="font-weight-bold" style="font-size: 1.2rem;">Groupe Scolaire EDUMANAGE</div>
            </div>
            <div class="text-right">
                <div class="font-weight-bold" style="font-size: 1.3rem;">Bulletin</div>
                <div class="small text-uppercase"><?php echo htmlspecialchars($trimestre); ?></div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <p class="mb-1"><strong>Nom de l'élève :</strong> <?php echo htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']); ?></p>
                <p class="mb-1"><strong>Matricule :</strong> <?php echo htmlspecialchars($eleve['matricule']); ?></p>
                <p class="mb-1"><strong>Professeur titulaire :</strong> <?php echo htmlspecialchars($enseignant_titulaire); ?></p>
            </div>
            <div class="col-md-6 text-md-right">
                <p class="mb-1"><strong>Classe :</strong> <?php echo htmlspecialchars($eleve['nom_classe']); ?></p>
                <p class="mb-1"><strong>Trimestre :</strong> <?php echo htmlspecialchars($trimestre); ?></p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-bulletin table-sm">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th class="text-center" style="width: 90px;">Coeff.</th>
                        <th class="text-center" style="width: 90px;">Séqu. 1</th>
                        <th class="text-center" style="width: 90px;">Séqu. 2</th>
                        <th>Observation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($domaines_affiche as $domaine => $matieres): ?>
                        <tr style="background-color: #e9ecef; font-weight: bold;">
                            <td colspan="5"><?php echo htmlspecialchars($domaine); ?></td>
                        </tr>
                        <?php foreach ($matieres as $ligne): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ligne['matiere']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($ligne['coef']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($ligne['seq1']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($ligne['seq2']); ?></td>
                                <td><?php echo htmlspecialchars($ligne['observation']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                        <td colspan="4" class="text-right">Moyenne trimestre</td>
                        <td class="text-center"><?php echo htmlspecialchars($moyenne_general); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <table class="table table-bordered table-sm summary-table mb-0">
                    <tbody>
                        <tr>
                            <td><strong>Moyenne Séq1</strong></td>
                            <td class="text-right"><?php echo htmlspecialchars($moyenne_seq1); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Moyenne Séq2</strong></td>
                            <td class="text-right"><?php echo htmlspecialchars($moyenne_seq2); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Moyenne <?php echo htmlspecialchars($trimestre); ?></strong></td>
                            <td class="text-right"><?php echo htmlspecialchars($moyenne_trimestre); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-sm summary-table mb-0">
                    <tbody>
                        <tr>
                            <td><strong>Rang</strong></td>
                            <td class="text-right">N/A</td>
                        </tr>
                        <tr>
                            <td><strong>Promu(e)</strong></td>
                            <td class="text-right"><?php echo htmlspecialchars($est_promu); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mt-5 text-center text-uppercase font-weight-bold" style="font-size: 0.8rem;">
            <div class="col-4 border-right">Visa de l'Enseignant</div>
            <div class="col-4 border-right">Conseil des Maîtres</div>
            <div class="col-4">Visa du Directeur</div>
        </div>
    </div>
</div>
</body>
</html>
