<?php
// vues/notes/bulletins_classe.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletins de classe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background: #f2f5f9; color: #212529; }
        .save-pdf-btn { position: fixed; top: 20px; right: 20px; z-index: 1000; }
        .bulletin-card { background: white; border: 1px solid #ccc; padding: 20px; margin-bottom: 30px; page-break-inside: avoid; page-break-after: always; }
        .bulletin-title { font-size: 1.2rem; letter-spacing: 0.4px; }
        .table-bulletin th, .table-bulletin td { border: 1px solid #000 !important; }
        .table-bulletin th { background: #eceeef; }
    </style>
    <script>
        function savePDFClass() {
            const element = document.getElementById('bulletins-container');
            const className = document.querySelector('h3').textContent;
            const options = {
                margin: 5,
                filename: 'bulletins_' + className.replace(/\s+/g, '_') + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a5' }
            };
            html2pdf().set(options).from(element).save();
        }
    </script>
</head>
<body>
    <button class="btn btn-primary save-pdf-btn no-print" onclick="savePDFClass()">Enregistrer sous format PDF</button>
    <div class="no-print" style="background: white; padding: 20px; margin-bottom: 30px; border-bottom: 2px solid #ccc;">
        <div class="container py-2">
            <div class="mb-2">
                <h3>Bulletins de la classe</h3>
                <p class="text-muted">Classe : <?php echo htmlspecialchars($classe); ?> | Trimestre : <?php echo htmlspecialchars($trimestre); ?></p>
            </div>
        </div>
    </div>
    <div class="container py-4" id="bulletins-container">
        <?php if (empty($bulletins)): ?>
            <div class="alert alert-warning">Aucun bulletin disponible pour cette sélection.</div>
        <?php else: ?>
            <?php foreach ($bulletins as $bulletin): ?>
                <div class="bulletin-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="font-weight-bold text-uppercase" style="font-size: 0.9rem; letter-spacing: 1px;">Bulletin</div>
                            <div class="font-weight-bold" style="font-size: 1.15rem;"><?php echo htmlspecialchars($bulletin['eleve']['nom'] . ' ' . $bulletin['eleve']['prenom']); ?></div>
                            <div class="text-muted">Matricule : <?php echo htmlspecialchars($bulletin['eleve']['matricule']); ?></div>
                        </div>
                        <div class="text-right">
                            <div class="badge badge-pill badge-dark" style="font-size: 0.95rem;">Rang : <?php echo htmlspecialchars($bulletin['rang'] ?? '-'); ?></div>
                            <div class="text-muted" style="font-size: 0.95rem;">Moyenne : <?php echo $bulletin['moyenne'] !== null ? number_format($bulletin['moyenne'], 2, ',', ' ') : 'N/A'; ?></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm table-bulletin mb-0">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th class="text-center" style="width: 90px;">Coeff.</th>
                                    <th class="text-center" style="width: 120px;">Note</th>
                                    <th>Observation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bulletin['matieres'])): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Aucune note saisie pour cet élève.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bulletin['matieres'] as $ligne): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ligne['nom_matiere']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($ligne['coefficient']); ?></td>
                                            <td class="text-center"><?php echo htmlspecialchars($ligne['valeur'] !== null ? number_format($ligne['valeur'], 2, ',', ' ') : '—'); ?></td>
                                            <td><?php echo htmlspecialchars($ligne['observation'] ?: '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
