<?php
// Initialisation sécurisée de la session avant toute lecture de données
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité et détection du rôle au sein de la vue épurée
if (isset($_SESSION['role'])) {
    $role_brut = $_SESSION['role']; 
    if (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin' || strtolower($role_brut) === 'administrateur') {
        $role_actuel = 'admin'; 
    } else {
        $role_actuel = 'enseignant'; 
    }
    $nom_affichage = isset($_SESSION['login']) ? $_SESSION['login'] : $role_brut;
} else {
    // Par sécurité, si aucune session n'est détectée
    $role_actuel = 'enseignant';
    $nom_affichage = 'Enseignant';
}
?>

<!DOCTYPE html>
<?php include_once 'donnees_dashboard.php'; ?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EduManage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        overflow: hidden;
    }
    
    body {
        background-image: url("image2.jpeg");
        background-size: cover;
        background-attachment: fixed;
        height: 100%;
        overflow: hidden;
    }
    
    .form-container {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    /* Sidebar fixe */
    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        background-color: #ffffff;
        box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        z-index: 1000;
        transition: all 0.3s;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    /* Scrollbar sidebar */
    .sidebar::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .sidebar::-webkit-scrollbar-thumb {
        background: #3498db;
        border-radius: 5px;
    }
    
    .sidebar-header {
        padding: 20px;
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .sidebar .nav-link {
        color: #5a6a85;
        font-weight: 500;
        padding: 12px 20px;
        border-left: 4px solid transparent;
        display: block;
    }
    
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
        color: #3498db;
        background-color: #f8f9fa;
        border-left-color: #3498db;
        text-decoration: none;
    }
    
    .sidebar .section-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #a5b4fc;
        padding: 15px 20px 5px;
        font-weight: 700;
    }
    
    /* Contenu principal qui défile */
    .main-content {
        margin-left: 260px;
        padding: 30px;
        transition: all 0.3s;
        height: 100vh;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    /* Scrollbar contenu principal */
    .main-content::-webkit-scrollbar {
        width: 8px;
    }
    .main-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .main-content::-webkit-scrollbar-thumb {
        background: #2c3e50;
        border-radius: 10px;
    }
    .main-content::-webkit-scrollbar-thumb:hover {
        background: #3498db;
    }
    
    /* Header sticky */
    .header-status {
        background-color: #2c3e50;
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        position: sticky;
        top: 0;
        z-index: 99;
    }
    
    /* Welcome banner */
    .welcome-banner {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    /* Cartes statistiques */
    .stat-card {
        background: white;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        transition: transform 0.2s;
        padding: 20px;
        text-align: center;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .stat-icon {
        font-size: 2rem;
        color: #6366f1;
        margin-top: 10px;
    }
    
    /* Cartes actions */
    .action-card {
        background: white;
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        padding: 15px 20px;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        width: 100%;
        height: 100%;
    }
    
    .action-card:hover {
        background-color: #f8f9fa;
    }
    
    .action-card i {
        font-size: 1.3rem;
        margin-right: 15px;
    }
    
    .action-card-link:hover {
        text-decoration: none;
    }
    
    /* Sidebar fermée */
    .sidebar.collapsed {
        left: -260px;
    }
    
    .main-content.expanded {
        margin-left: 0;
    }
    
    .toggle-btn {
        background-color: #2c3e50;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 8px 15px;
        cursor: pointer;
    }
</style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-graduation-cap mr-2"></i>EduManage</h5>
            <button class="btn btn-sm text-white d-md-none" id="close-sidebar-btn"><i class="fas fa-times"></i></button>
        </div>
        <div class="d-flex flex-column mt-3">
            <div class="section-title">Gestion Scolaire</div>
            <a href="index.php?action=dashboard" class="nav-link active"><i class="fas fa-tachometer-alt mr-3"></i>Tableau de bord</a>
            
            <?php if ($role_actuel === 'admin'): ?>
                <a href="index.php?action=formulaire_ajout" class="nav-link"><i class="fas fa-user-plus mr-3"></i>Inscrire un élève</a>
                <a href="index.php?action=liste_eleves" class="nav-link">Liste des élèves</a>
                <a href="index.php?action=formulaire_enseignant" class="nav-link"><i class="fas fa-chalkboard-teacher mr-3"></i> Ajouter un enseignant</a>
                <a href="index.php?action=gerer_classes" class="nav-link"><i class="fas fa-school mr-3"></i> Gérer les classes</a>
                <a href="index.php?action=annees" class="nav-link"><i class="fas fa-cog mr-3"></i>Paramètres</a>
                <div class="pl-4">
                    <a href="index.php?action=annees" class="nav-link" style="font-size:0.95rem;"><i class="fas fa-calendar-alt mr-2"></i>Session</a>
                </div>
                <a href="index.php?action=liste_enseignants" class="nav-link"><i class="fas fa-chalkboard-teacher mr-3"></i>Liste des enseignants</a>
            <?php endif; ?>
            
            <div class="section-title">Pédagogie & Notes</div>
            <a href="index.php?action=saisie_note" class="nav-link"><i class="fas fa-edit mr-3"></i>Saisir les notes</a>
            <a href="index.php?action=bulletins" class="nav-link"><i class="fas fa-file-invoice mr-3"></i>Bulletins trimestriels</a>
            <?php if ($role_actuel === 'admin'): ?>
                <a href="index.php?action=promotion" class="nav-link"><i class="fas fa-arrow-up mr-3"></i>Promouvoir</a>
            <?php endif; ?>
            
            
            <?php if ($role_actuel === 'admin'): ?>
                <div class="section-title">Finance</div>
                <a href="index.php?action=formulaire_versement" class="nav-link"><i class="fas fa-wallet mr-3"></i>Nouveau Versement</a>
                <a href="index.php?action=historique_caisses" class="nav-link"><i class="fas fa-history mr-3"></i>Historique Caisses</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content" id="main-content">
        
        <div class="header-status d-flex justify-content-between align-items-center shadow-sm">
            <div class="d-flex align-items-center">
                <button class="toggle-btn mr-3" id="toggle-sidebar-btn"><i class="fas fa-bars"></i></button>
                <div>
                    <h4 class="mb-0 font-weight-bold">Tableau de bord</h4>
                    <small class="text-light">
                        Système de gestion intégré • 
                        <span id="current-date">18/05/2026</span> à 
                        <span id="current-time">00:00:00</span>
                    </small>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="badge badge-light p-2 mr-3">
                    <i class="fas <?php echo ($role_actuel === 'admin') ? 'fa-user-shield' : 'fa-chalkboard-teacher'; ?> mr-1"></i> 
                    <?php echo ($role_actuel === 'admin') ? 'Directeur' : 'Enseignant'; ?>
                </span>
                <span class="mr-3">
                    <i class="fas fa-calendar-alt text-white mr-2"></i>
                    <strong id="annee-selected"><?php echo htmlspecialchars($_SESSION['annee_scolaire_libelle'] ?? (date('Y')-1 . '-' . date('Y'))); ?></strong>
                </span>
                <button class="btn btn-sm btn-outline-light mr-2" id="btnAnnees" title="Gérer années scolaires"><i class="fas fa-cog"></i></button>
                <a href="deconnexion.php" class="btn btn-sm btn-outline-light"><i class="fas fa-sign-out-alt mr-1"></i> Déconnexion</a>
            </div>
        </div>

        <div id="dynamic-content">
            
            <?php
            // Charger le contenu spécifique si demandé par le contrôleur
            if (isset($contenu_actif) && $contenu_actif === 'promotion') {
                // Charger la vue promotion avec les variables du contrôleur
                require_once __DIR__ . '/eleves/promotion.php';
            } else {
                // Sinon afficher le welcome banner par défaut
            ?>
            <div class="welcome-banner shadow-sm">
                <h3 class="font-weight-bold">Bienvenue, <?php echo htmlspecialchars($nom_affichage); ?> !</h3>
                <?php if ($role_actuel === 'admin'): ?>
                    <p class="mb-0 text-white-50">Gérez efficacement votre établissement scolaire depuis cette plateforme centralisée.</p>
                <?php else: ?>
                    <p class="mb-0 text-white-50">Consultez vos classes, saisissez les notes et suivez la progression de vos élèves.</p>
                <?php endif; ?>
            </div>

            <div class="row mb-5">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number" id="count-eleves"><h3><?php echo $eleves_actifs; ?></h3></div>
                        <div class="stat-label">Élèves actifs</div>
                        <div class="stat-icon text-primary"><i class="fas fa-user-graduate"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number" id="count-classes"><h3><?php echo $classes_ouvertes; ?></h3></div>
                        <div class="stat-label">Classes ouvertes</div>
                        <div class="stat-icon text-success"><i class="fas fa-school"></i></div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number" id="count-enseignants"><h3><?php echo $total_enseignants; ?></h3></div>
                        <div class="stat-label">Enseignants</div>
                        <div class="stat-icon text-warning"><i class="fas fa-chalkboard-teacher"></i></div>
                    </div>
                </div>
                
                <?php if ($role_actuel === 'admin'): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card">
                        <div class="stat-number" id="sum-scolarite"><h3><?php echo number_format($total_recettes, 0, ',', ' '); ?> FCFA</h3></div>
                        <div class="stat-label">Recettes (en FCFA)</div>
                        <div class="stat-icon text-danger"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <h5 class="mb-3 font-weight-bold text-secondary">Raccourcis d'opérations</h5>
            <div class="row mb-5">
                <?php if ($role_actuel === 'admin'): ?>
                <div class="col-md-3 col-sm-6 mb-3">
                   <a href="index.php?action=formulaire_ajout" class="action-card-link text-decoration-none">
                        <div class="action-card text-primary">
                            <i class="fas fa-plus-circle"></i>
                            <span class="font-weight-bold">Inscrire un élève</span>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="index.php?action=formulaire_enseignant" class="action-card-link text-decoration-none">
                        <div class="action-card text-success">
                            <i class="fas fa-user-plus"></i>
                            <span class="font-weight-bold">Nouveau Maître</span>
                        </div>
                    </a>
                </div>
                <?php endif; ?>

                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="index.php?action=saisie_note" class="action-card-link text-decoration-none">
                        <div class="action-card text-warning">
                            <i class="fas fa-pen-alt"></i>
                            <span class="font-weight-bold">Ajouter une Note</span>
                        </div>
                    </a>
                </div>
               
                
                <?php if ($role_actuel === 'admin'): ?>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="index.php?action=gerer_classes" class="action-card-link text-decoration-none">
                        <div class="action-card text-info">
                            <i class="fas fa-school"></i>
                            <span class="font-weight-bold">Gérer les classes</span>
                        </div>
                    </a>
                </div>
                <!-- Nouveau Versement déplacé vers la barre de navigation (Finance) -->
<?php endif; ?>
            </div>
              <div id="js-error-display" class="d-none"></div>
            <h5 class="mb-3 font-weight-bold text-secondary">Occupation des salles</h5>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm p-3" style="border-radius: 12px; border: none;">
                        <h6 class="card-title font-weight-bold text-muted mb-3">
                            <i class="fas fa-chart-pie mr-2 text-primary"></i>Effectifs par classe
                        </h6>
                        <div id="liste-repartition-classes">
                            <p class="text-center text-muted m-0 p-3">
                                <i class="fas fa-spinner fa-spin mr-2"></i>Calcul des effectifs en cours...
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        <?php } ?>

        </div>
     </div> 
      <script>
    // Fonction pour charger du contenu dynamique (existante)
    window.loadContent = function(action, params = {}) {
        $('#loadingOverlay').fadeIn(200);
        
        var data = { action: action, ajax: 1, ...params };
        
        $.ajax({
            url: 'index.php',
            type: 'GET',
            data: data,
            success: function(response) {
                $('#dynamic-content').html(response);
                var url = new URL(window.location.href);
                url.searchParams.set('action', action);
                window.history.pushState({ action: action }, '', url);
            },
            complete: function() {
                $('#loadingOverlay').fadeOut(200);
            }
        });
    };

    // Fonction pour actualiser date et heure (existante)
    function actualiserDateEtHeure() {
        const maintenant = new Date();
        const jour = String(maintenant.getDate()).padStart(2, '0');
        const mois = String(maintenant.getMonth() + 1).padStart(2, '0');
        const annee = maintenant.getFullYear();
        const dateFormatee = `${jour}/${mois}/${annee}`;

        const heures = String(maintenant.getHours()).padStart(2, '0');
        const minutes = String(maintenant.getMinutes()).padStart(2, '0');
        const secondes = String(maintenant.getSeconds()).padStart(2, '0');
        const heureFormatee = `${heures}:${minutes}:${secondes}`;

        document.getElementById('current-date').textContent = dateFormatee;
        document.getElementById('current-time').textContent = heureFormatee;
    }
    
    actualiserDateEtHeure();
    setInterval(actualiserDateEtHeure, 1000);

    // NOUVEAU : Gestion des clics sur les liens de la sidebar
    $(document).ready(function() {
        // Intercepter les clics sur les liens de la sidebar
        $('.sidebar .nav-link').on('click', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            
            // Extraire l'action de l'URL
            var urlParams = new URLSearchParams(href.split('?')[1]);
            var action = urlParams.get('action');
            
            if (action) {
                // Utiliser la fonction existante loadContent
                window.loadContent(action);
            } else {
                // Fallback : charger la page normalement
                window.location.href = href;
            }

        });
    });
    
    // Ouvrir le gestionnaire d'années scolaires
    document.getElementById('btnAnnees')?.addEventListener('click', function() {
        fetch('index.php?action=annees', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.text())
            .then(html => {
                // Insérer le modal au body
                const div = document.createElement('div');
                div.innerHTML = html;
                document.body.appendChild(div);
            }).catch(e => alert('Erreur lors du chargement des années : ' + e.message));
    });
    
</script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="interface.js"></script>
</body>
</html>
