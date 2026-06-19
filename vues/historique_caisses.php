<?php
// vues/historique_caisses.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connexion.php';

$stmt = $pdo->prepare("SELECT p.matricule_eleve, e.nom, e.prenom, COUNT(*) AS nb_versements, SUM(p.montant) AS total_montant
    FROM paiement p
    JOIN eleve e ON e.matricule = p.matricule_eleve
    GROUP BY p.matricule_eleve, e.nom, e.prenom
    ORDER BY e.nom ASC, e.prenom ASC");
$stmt->execute();
$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Historique Caisses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>.form-control{background:#fff!important}</style>
</head>
<body class="p-4">
    <div style="background-color: white; padding: 20px; border-radius: 12px; min-height: 100vh;">
    <div class="container">
        <h3 class="mb-4">Élèves ayant effectué au moins un versement</h3>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light"><tr><th>Matricule</th><th>Nom & Prénom</th><th># Versements</th><th>Total (FCFA)</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($eleves)): ?>
                        <tr><td colspan="5" class="text-center text-muted">Aucun versement enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach ($eleves as $el): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($el['matricule_eleve']); ?></td>
                                <td><?php echo htmlspecialchars($el['nom'] . ' ' . $el['prenom']); ?></td>
                                <td><?php echo htmlspecialchars($el['nb_versements']); ?></td>
                                <td><?php echo number_format($el['total_montant'], 0, ',', ' '); ?> FCFA</td>
                                <td><a href="index.php?action=historique_paiements&matricule=<?php echo urlencode($el['matricule_eleve']); ?>" class="btn btn-sm btn-primary">Voir les versements</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</body>
</html>
