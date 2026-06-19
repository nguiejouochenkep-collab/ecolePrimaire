<?php
// vues/historique_paiements.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../connexion.php';

$matricule = $_GET['matricule'] ?? null;
if (!$matricule) { header('Location: index.php?action=historique_caisses'); exit; }

try {
    $pdo = $GLOBALS['pdo'] ?? new PDO('mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die('Erreur DB: ' . $e->getMessage());
}

$stmt = $pdo->prepare("SELECT p.*, e.nom, e.prenom FROM paiement p JOIN eleve e ON e.matricule = p.matricule_eleve WHERE p.matricule_eleve = :mat ORDER BY p.date_paiement DESC");
$stmt->execute(['mat' => $matricule]);
$versements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eleveInfo = null;
if (!empty($versements)) {
    $eleveInfo = ['nom' => $versements[0]['nom'], 'prenom' => $versements[0]['prenom']];
} else {
    $s = $pdo->prepare("SELECT nom, prenom FROM eleve WHERE matricule = :mat"); $s->execute(['mat' => $matricule]); $eleveInfo = $s->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Versements de <?php echo htmlspecialchars($eleveInfo['nom'] . ' ' . $eleveInfo['prenom']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>.form-control{background:#fff!important}</style>
</head>
<body class="p-4">
    <div class="container">
        <h4 class="mb-3">Historique des versements de <?php echo htmlspecialchars($eleveInfo['nom'] . ' ' . $eleveInfo['prenom']); ?> (<?php echo htmlspecialchars($matricule); ?>)</h4>
        <a href="index.php?action=historique_caisses" class="btn btn-sm btn-secondary mb-3">← Retour</a>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="thead-light"><tr><th>ID</th><th>Date</th><th>Montant</th><th>Motif</th><th>Année scolaire</th><th>Numéro reçu</th></tr></thead>
                <tbody>
                    <?php if (empty($versements)): ?>
                        <tr><td colspan="6" class="text-center text-muted">Aucun versement trouvé pour cet élève.</td></tr>
                    <?php else: ?>
                        <?php foreach ($versements as $v): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($v['id_paiement'] ?? $v['id']); ?></td>
                                <td><?php echo htmlspecialchars($v['date_paiement']); ?></td>
                                <td><?php echo number_format($v['montant'], 0, ',', ' '); ?> FCFA</td>
                                <td><?php echo htmlspecialchars($v['motif']); ?></td>
                                <td><?php echo htmlspecialchars($v['annee_scolaire']); ?></td>
                                <td><?php echo htmlspecialchars($v['numero_recu'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
