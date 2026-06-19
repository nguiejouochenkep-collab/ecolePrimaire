<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurisation globale de la vue : accepte 'admin' ou 'directeur' sans se soucier des majuscules
$role_brut = $_SESSION['role'] ?? '';
$is_autorise = (strtolower($role_brut) === 'directeur' || strtolower($role_brut) === 'admin'); 
?>

<?php if (!isset($is_ajax) || !$is_ajax): ?>
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="font-weight-bold text-dark"><i class="fas fa-school mr-2 text-info"></i>Configuration des Classes</h3>
            <p class="text-muted mb-0">Ajoutez de nouvelles salles, modifiez les intitulés ou supprimez des classes existantes.</p>
        </div>
        <?php if ($is_autorise): ?>
            <button class="btn btn-info btn-lg" data-toggle="modal" data-target="#modalAjouterClasse">
                <i class="fas fa-plus mr-2"></i>Nouvelle Classe
            </button>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="bg-info text-white">
                        <tr>
                            <th style="width: 15%">ID Classe</th>
                            <th>Nom de la Classe</th>
                            <?php if ($is_autorise): ?>
                                <th style="width: 25%" class="text-center">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($classes)): ?>
                            <tr>
                                <td colspan="<?= $is_autorise ? '3' : '2'; ?>" class="text-center text-muted p-4">Aucune classe configurée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($classes as $c): ?>
                                <tr>
                                    <td class="font-weight-bold text-secondary"># <?= $c['id']; ?></td>
                                    <td class="font-weight-bold" style="font-size: 1.1rem;"><?= htmlspecialchars($c['nom_classe']); ?></td>
                                    
                                    <?php if ($is_autorise): ?>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info text-white btn-renommer" 
                                                    data-id="<?= $c['id']; ?>" 
                                                    data-nom="<?= htmlspecialchars($c['nom_classe']); ?>">
                                                <i class="fas fa-edit mr-1"></i> Renommer
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-supprimer" 
                                                    data-id="<?= $c['id']; ?>" 
                                                    data-nom="<?= htmlspecialchars($c['nom_classe']); ?>">
                                                <i class="fas fa-trash-alt mr-1"></i> Supprimer
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php if ($is_autorise): ?>
<div class="modal fade" id="modalAjouterClasse" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-plus-circle mr-2"></i>Ajouter une classe</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            
            <form id="form-ajouter-classe">
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold text-secondary">Intitulé de la classe :</label>
                        <input type="text" name="nom_classe" class="form-control form-control-lg border-info" placeholder="Ex: SIL A, CM2 B..." required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info px-4">Créer la classe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalModifierClasse" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-edit mr-2"></i>Renommer la classe</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="form-modifier-classe">
                <input type="hidden" name="id_classe" id="mod-id-classe">
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold text-secondary">Nouveau nom de la classe :</label>
                        <input type="text" name="nom_classe" id="mod-nom-classe" class="form-control form-control-lg border-info" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white px-4">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function() {
    // Fonction utilitaire pour afficher proprement un message d'erreur dans un formulaire
    function afficherErreurFormulaire(idFormulaire, message) {
        const form = document.getElementById(idFormulaire);
        if (!form) return;
        
        // Supprime l'ancienne alerte si elle existe
        const ancienneAlerte = form.querySelector('.ajax-debug-alert');
        if (ancienneAlerte) ancienneAlerte.remove();

        const alerte = document.createElement('div');
        alerte.className = 'alert alert-warning mt-3 font-weight-bold text-dark ajax-debug-alert';
        alerte.style.borderLeft = '5px solid #2999e3';
        alerte.style.backgroundColor = '#5abcf9';
        alerte.innerHTML = `<i class="fas fa-exclamation-triangle mr-2 text-warning"></i> ${message}`;
        
        form.prepend(alerte);
    }

    // Ouverture de la modale Modifier
    document.querySelectorAll('.btn-renommer').forEach(btn => {
        btn.addEventListener('click', function() {
            const ancienneAlerte = document.querySelector('#form-modifier-classe .ajax-debug-alert');
            if (ancienneAlerte) ancienneAlerte.remove();
            
            document.getElementById('mod-id-classe').value = this.getAttribute('data-id');
            document.getElementById('mod-nom-classe').value = this.getAttribute('data-nom');
            $('#modalModifierClasse').modal('show');
        });
    });

    // AJAX : Ajouter une classe
    document.getElementById('form-ajouter-classe').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('index.php?action=sauvegarder_classe', {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) {
                return response.json()
                    .then(errData => { throw new Error(errData.message); })
                    .catch(() => { throw new Error("Le serveur a bloqué la requête (Erreur Sécurité ou Droits)."); });
            }
            return response.json();
        })
        .then(data => {
            if (data.succes) {
                $('#modalAjouterClasse').modal('hide');
                alert(data.message);
                location.reload();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            afficherErreurFormulaire('form-ajouter-classe', err.message);
            console.error(err);
        });
    });

    // AJAX : Renommer une classe
    document.getElementById('form-modifier-classe').addEventListener('submit', function(e) {
        e.preventDefault();
        
        fetch('index.php?action=modifier_classe', {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) {
                return response.json()
                    .then(errData => { throw new Error(errData.message); })
                    .catch(() => { throw new Error("Impossible de modifier la classe (Session expirée ou accès refusé)."); });
            }
            return response.json();
        })
        .then(data => {
            if (data.succes) {
                $('#modalModifierClasse').modal('hide');
                alert(data.message);
                location.reload();
            } else {
                throw new Error(data.message);
            }
        })
        .catch(err => {
            afficherErreurFormulaire('form-modifier-classe', err.message);
            console.error(err);
        });
    });

    // AJAX : Supprimer une classe
    document.querySelectorAll('.btn-supprimer').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nom = this.getAttribute('data-nom');
            
            if (confirm(`Voulez-vous vraiment supprimer définitivement la classe "${nom}" ?`)) {
                let formData = new FormData();
                formData.append('id_classe', id);

                fetch('index.php?action=supprimer_classe', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) {
                         throw new Error("Action impossible. Le serveur a retourné une erreur.");
                    }
                    return response.json();
                })
                .then(data => {
                    alert(data.message);
                    if (data.succes) { location.reload(); }
                })
                .catch(err => {
                    alert("Erreur lors de la suppression : " + err.message);
                });
            }
        });
    });
})();
</script>
<?php endif; ?>

<?php if (!isset($is_ajax) || !$is_ajax): ?>
</div>
<?php endif; ?>