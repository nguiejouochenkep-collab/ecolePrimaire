<?php
require_once 'connexion.php';

try {
    $query = "SELECT matricule, nom, prenom, date_naissance FROM eleve ORDER BY nom ASC";
    $stmt = $pdo->query($query);
    $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduManage - Liste des élèves</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
        /* Ajustement esthétique pour la barre de recherche Bootstrap 5 */
        .dataTables_filter {
            text-align: right;
            margin-bottom: 15px;
        }
        .dataTables_filter input {
            display: inline-block;
            width: auto;
            margin-left: 10px;
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 4px 8px;
        }
    </style>
</head>
<body class="bg-light p-5">

    <div class="container bg-white p-4 rounded shadow-sm">
        <h2 class="mb-4 text-primary fw-bold">Liste des Élèves Inscrits</h2>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle display" id="liste" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Date de Naissance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($eleves)): ?>
                        <?php foreach ($eleves as $eleve): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($eleve['matricule']); ?></td>
                                <td><?php echo htmlspecialchars($eleve['nom']); ?></td>
                                <td><?php echo htmlspecialchars($eleve['prenom']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($eleve['date_naissance'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td>-</td>
                            <td colspan="3" class="text-muted font-italic">Aucun élève trouvé dans la base de données.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    $(document).ready(function() {
        // Destruction de toute instance précédente pour éviter les conflits de rechargement
        if ($.fn.DataTable.isDataTable('#liste')) {
            $('#liste').DataTable().destroy();
        }

        // Initialisation propre
        $('#liste').DataTable({
            "searching": true, // Force l'activation de la recherche
            "ordering": true,  // Active le tri par colonne
            "paging": true,    // Active la pagination
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json"
            }
        });
    });
    </script>
</body>
</html>