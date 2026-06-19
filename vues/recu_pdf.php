<?php
// Initialisation de la connexion à la base de données (Ajustez le chemin si nécessaire)
require_once __DIR__ . '/../connexion.php';

// Récupération sécurisée de l'ID du paiement depuis l'URL
$id_paiement = $_GET['id'] ?? 0;

// Requête SQL corrigée avec la collation explicite pour éviter l'erreur de "mix of collations"
$query = "SELECT p.*, e.nom, e.prenom FROM paiement p 
          JOIN eleve e ON p.matricule_eleve = e.matricule COLLATE utf8mb4_unicode_ci 
          WHERE p.id_paiement = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_paiement]); // Correction effectuée ici ($id_paiement)
$paiement = $stmt->fetch(PDO::FETCH_ASSOC);

// Sécurité si l'ID ne correspond à aucun enregistrement
if (!$paiement) {
    die("Reçu introuvable.");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu_<?php echo htmlspecialchars($paiement['numero_recu']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { 
            background: #f4f6f9; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        .ticket { 
            background: #fff; 
            max-width: 210mm; /* Largeur maximale d'un format A5 paysage */
            margin: 20px auto; 
            padding: 20px; 
            border: 1px solid #dee2e6; 
            border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
        }
        .school-header { 
            border-bottom: 2px dashed #4e8af3; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
        }
        .amount-box { 
            background: #e9ecef; 
            font-size: 1.3rem; 
            font-weight: bold; 
            padding: 8px; 
            border-radius: 6px; 
            text-align: center; 
            color: #4284f4; 
            border: 1px solid #ced4da;
        }
        
        /* Configuration d'impression stricte pour forcer l'enregistrement en A5 Paysage */
        @media print {
            @page { 
                size: A5 landscape; /* Force le format A5 horizontal */
                margin: 5mm;        /* Marges minimales */
            }
            body { 
                background: #fff; 
                margin: 0; 
                padding: 0; 
            }
            .ticket { 
                border: none; 
                box-shadow: none; 
                margin: 0; 
                padding: 0; 
                width: 100%; 
                max-width: 100%;
            }
            .btn-actions { 
                display: none !important; /* Cache les boutons sur le PDF final */
            }
        }
    </style>
</head>
<body>
<div id="content-to-load">
<div class="container">
    <div class="ticket">
        <div class="school-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold text-uppercase m-0 text-dark" style="letter-spacing: 1px;">EduManage</h4>
                <small class="text-muted font-weight-bold"><i class="fas fa-graduation-cap mr-1"></i> Établissement Scolaire</small>
            </div>
            <div class="text-right">
                <span class="badge badge-primary p-2 text-uppercase font-weight-bold" style="font-size: 0.9rem;">Reçu de Caisse</span>
                <div class="mt-1"><small class="font-weight-bold text-secondary">N° <?php echo htmlspecialchars($paiement['numero_recu']); ?></small></div>
            </div>
        </div>

        <div class="row my-3">
            <div class="col-6">
                <span class="text-muted small">Date d'émission :</span>
                <p class="m-0 font-weight-bold"><i class="far fa-calendar-alt mr-1"></i> <?php echo date('d/m/Y', strtotime($paiement['date_paiement'])); ?></p>
            </div>
            <div class="col-6 text-right">
                <span class="text-muted small">Année Académique :</span>
                <p class="m-0 font-weight-bold text-primary"><i class="fas fa-bookmark mr-1"></i> <?php echo htmlspecialchars($paiement['annee_scolaire']); ?></p>
            </div>
        </div>

        <table class="table table-sm table-bordered my-3" style="font-size: 0.95rem;">
            <tbody>
                <tr>
                    <td class="bg-light font-weight-bold text-secondary" style="width: 25%;">Matricule Élève</td>
                    <td class="font-weight-bold"><?php echo htmlspecialchars($paiement['matricule_eleve']); ?></td>
                </tr>
                <tr>
                    <td class="bg-light font-weight-bold text-secondary">Nom & Prénom(s)</td>
                    <td class="text-uppercase font-weight-bold text-dark"><?php echo htmlspecialchars($paiement['nom'] . ' ' . $paiement['prenom']); ?></td>
                </tr>
                <tr>
                    <td class="bg-light font-weight-bold text-secondary">Motif du Versement</td>
                    <td class="text-info font-weight-bold"><?php echo htmlspecialchars($paiement['motif']); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="amount-box my-3">
            SOMME VERSÉE : <?php echo number_format($paiement['montant'], 0, ',', ' '); ?> FCFA
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4">
            <small class="text-muted font-italic">Document généré électroniquement.</small>
            <div class="text-center" style="border-top: 1px solid #6c757d; width: 180px; padding-top: 3px;">
                <small class="font-weight-bold text-secondary">Le Caissier / Intendant</small>
            </div>
        </div>

        <div class="text-center mt-4 btn-actions">
            <button onclick="window.print();" class="btn btn-primary font-weight-bold mr-2 shadow-sm">
                <i class="fas fa-file-pdf mr-2"></i>Enregistrer en PDF (A5)
            </button>
            <a href="index.php?action=dashboard" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Tableau de bord
            </a>
        </div>
    </div>
</div>
</div>

</body>
</html>