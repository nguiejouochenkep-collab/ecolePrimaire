<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion École - Liste des Élèves</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Registre des Élèves</h2>
        <a href="index.php?action=formulaire_ajout" class="btn btn-primary">Inscrire un nouvel élève</a>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>Matricule</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Sexe</th>
                <th>Date de Naissance</th>
                <th>Téléphone Parent</th>
                <th>Classe Actuelle</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Diagnostic intégré à la vue pour détecter les erreurs de transfert de données
            if (isset($eleves) && is_array($eleves)): 
                if (count($eleves) > 0):
                    foreach ($eleves as $eleve): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($eleve['matricule'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($eleve['nom'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($eleve['prenom'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($eleve['sexe'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($eleve['date_naissance'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($eleve['telephone_parent'] ?? 'Non renseigné'); ?></td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($eleve['nom_classe'] ?? 'Non inscrit cette année'); ?></span></td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="7" class="text-center">Aucun élève enregistré dans la base de données.</td></tr>
                <?php endif; 
            else: ?>
                <tr>
                    <td colspan="7" class="text-center text-danger font-weight-bold" style="background-color: #fee;">
                        Erreur fatale : Le contrôleur n'a pas fourni la liste des élèves à cette vue.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>