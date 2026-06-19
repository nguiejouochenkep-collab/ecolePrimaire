<?php
// vues/eleves/classe_liste.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Effectifs par classe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Effectifs par classe</h2>
            <a href="index.php?action=dashboard" class="btn btn-secondary">Retour au tableau de bord</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col">Classe</th>
                                <th scope="col">Effectif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($effectifs)): ?>
                                <?php foreach ($effectifs as $ligne): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ligne['nom_classe']); ?></td>
                                        <td><?php echo htmlspecialchars($ligne['effectif']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center">Aucune classe trouvée ou aucun élève inscrit.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>