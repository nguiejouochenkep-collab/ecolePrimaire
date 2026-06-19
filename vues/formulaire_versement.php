<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Versement - EduManage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { 
            background-image: url("image2.jpeg"); 
            background-size: cover; 
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .form-container { 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 12px; 
            padding: 30px; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.2); 
        }
    </style>
</head>
<body>
<div id="content-to-load">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="form-container">
                <div class="text-center mb-4">
                    <h3 class="text-primary font-weight-bold">
                        <i class="fas fa-hand-holding-usd mr-2"></i>EduManage
                    </h3>
                    <p class="text-muted">Enregistrement d'un versement</p>
                </div>

                <?php if (isset($_GET['erreur'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <?php 
                            if ($_GET['erreur'] === 'eleve_inconnu') {
                                echo "<strong>Erreur :</strong> Ce matricule d'élève n'existe pas.";
                            } elseif ($_GET['erreur'] === 'champs_invalides') {
                                echo "<strong>Erreur :</strong> Veuillez remplir tous les champs correctement.";
                            } elseif ($_GET['erreur'] === 'deja_paye') {
                                echo "<strong>Opération refusée :</strong> Cet élève a déjà effectué un versement pour ce motif sur l'année scolaire en cours.";
                            } else {
                                echo "<strong>Erreur :</strong> Une erreur indéterminée est survenue.";
                            }
                        ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form action="index.php?action=enregistrer_versement" method="POST">
                    
                    <div class="form-group">
                        <label for="matricule_eleve" class="font-weight-bold text-secondary">Matricule de l'élève</label>
                        <input type="text" name="matricule_eleve" id="matricule_eleve" class="form-control form-control-lg" placeholder="Ex: 26EL282" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="montant" class="font-weight-bold text-secondary">Montant (FCFA)</label>
                        <input type="number" name="montant" id="montant" class="form-control form-control-lg" placeholder="Ex: 50000" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="motif" class="font-weight-bold text-secondary">Motif du paiement</label>
                        <select name="motif" id="motif" class="form-control form-control-lg" required>
                            <option value="">-- Choisir --</option>
                            <option value="Frais d'inscription">Frais d'inscription</option>
                            <option value="Scolarité - Tranche 1">Scolarité - APE</option>
                            <option value="Scolarité - Tranche 2">Scolarité - FRAIS EXIGIBLES</option>
                            
                        </select>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold shadow-sm">
                            <i class="fas fa-check-circle mr-2"></i>Valider le paiement
                        </button>
                        <a href="index.php?action=dashboard" class="btn btn-light btn-block border mt-2">
                            Annuler et retourner au menu
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>