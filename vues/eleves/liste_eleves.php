<?php
// vues/eleves/liste_eleves.php
?>
<div id="content-to-load">
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h3 class="text-primary font-weight-bold mb-4">
                    <i class="fas fa-users mr-2"></i> Gestion des Élèves par Classe
                </h3>

                <div class="row align-items-center mb-4">
                    <div class="col-md-5 mb-2">
                        <label for="select-classe" class="font-weight-bold text-secondary">Sélectionnez une classe :</label>
                        <select id="select-classe" class="form-control form-control-lg border-primary">
                            <option value="">-- Choisir une classe --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id']; ?>" <?= ($id_classe_selectionnee == $c['id']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c['nom_classe']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-7 mb-2 text-right mt-4">
                        <form id="form-search-eleve" action="index.php" method="GET" class="form-inline justify-content-end" data-ajax="true">
                            <input type="hidden" name="action" value="liste_eleves">
                            <input type="hidden" name="id_classe" value="<?= htmlspecialchars($id_classe_selectionnee); ?>">
                            <div class="input-group w-100">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                                </div>
                                <input type="text" id="recherche-eleve" name="search" value="<?= htmlspecialchars($search_term ?? ''); ?>" class="form-control form-control-lg border-left-0" placeholder="Rechercher un élève par nom, prénom ou matricule...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary btn-lg">Rechercher</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($info_message)): ?>
                    <div class="alert alert-info">
                        <strong>Recherche :</strong> <?= $info_message; ?>
                    </div>
                <?php endif; ?>

                <div id="zone-tableau-eleves">
                    <?php if (empty($id_classe_selectionnee) && empty($search_term)): ?>
                        <div class="alert alert-info text-center p-4">
                            <i class="fas fa-arrow-left mr-2"></i> Veuillez sélectionner une classe ou saisir un nom / matricule pour afficher les informations de l'élève.
                        </div>
                    <?php elseif (empty($eleves)): ?>
                        <div class="alert alert-warning text-center p-4">
                            <i class="fas fa-folder-open mr-2"></i>
                            <?php if (!empty($search_term)): ?>
                                Aucun élève ne correspond à cette recherche.
                            <?php else: ?>
                                Aucun élève inscrit dans cette classe pour le moment.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle" id="table-eleves">
                                    <thead class="bg-dark text-white">
                                        <tr>
                                            <th>Matricule</th>
                                            <th>Classe</th>
                                            <th>Nom</th>
                                            <th>Prénom</th>
                                            <th>Date de Naissance</th>
                                            <th>Sexe</th>
                                            <th>Téléphone Parent</th>
                                            <th>Adresse</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eleves as $eleve): ?>
                                            <tr class="ligne-eleve">
                                                <td class="font-weight-bold text-primary cell-matricule"><?= htmlspecialchars($eleve['matricule']); ?></td>
                                                <td class="cell-classe"><?= htmlspecialchars($eleve['nom_classe'] ?? '-'); ?></td>
                                                <td class="cell-nom"><?= htmlspecialchars($eleve['nom']); ?></td>
                                                <td class="cell-prenom"><?= htmlspecialchars($eleve['prenom']); ?></td>
                                                <td class="cell-date">
                                                    <?= !empty($eleve['date_naissance']) ? date('d/m/Y', strtotime($eleve['date_naissance'])) : '-'; ?>
                                                </td>
                                                <td><span class="badge bg-secondary text-white p-2"><?= htmlspecialchars($eleve['sexe']); ?></span></td>
                                                <td class="cell-tel"><?= htmlspecialchars($eleve['telephone_parent'] ?? '-'); ?></td>
                                                <td class="cell-adresse"><?= nl2br(htmlspecialchars($eleve['adresse'] ?? '-')); ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-warning btn-sm mr-1 btn-modifier" 
                                                            data-matricule="<?= $eleve['matricule']; ?>"
                                                            data-nom="<?= htmlspecialchars($eleve['nom']); ?>"
                                                            data-prenom="<?= htmlspecialchars($eleve['prenom']); ?>"
                                                            data-date="<?= $eleve['date_naissance']; ?>"
                                                            data-sexe="<?= $eleve['sexe']; ?>"
                                                            data-tel="<?= htmlspecialchars($eleve['telephone_parent'] ?? ''); ?>">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </button>
                                                    <button class="btn btn-danger btn-sm btn-supprimer" data-matricule="<?= $eleve['matricule']; ?>">
                                                        <i class="fas fa-trash-alt"></i> Renvoyer
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalModifierEleve" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="form-modifier-eleve">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-user-edit mr-2"></i> Modifier les informations</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="matricule" id="mod-matricule">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold mb-1">Nom</label>
                            <input type="text" name="nom" id="mod-nom" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold mb-1">Prénom</label>
                            <input type="text" name="prenom" id="mod-prenom" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold mb-1">Date de naissance</label>
                            <input type="date" name="date_naissance" id="mod-date" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold mb-1">Sexe</label>
                            <select name="sexe" id="mod-sexe" class="form-control" required>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="font-weight-bold mb-1">Téléphone Parent</label>
                            <input type="text" name="telephone_parent" id="mod-tel" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const selectClasse = document.getElementById('select-classe');
            const rechercheInput = document.getElementById('recherche-eleve');

            if (document.getElementById('table-eleves')) {
                rechercheInput.disabled = false;
            }

            // 1. Changement de classe en AJAX
            selectClasse.addEventListener('change', function() {
                const idClasse = this.value;
                const dynamicContent = document.getElementById('dynamic-content');
                
                if (idClasse) {
                    fetch('index.php?action=liste_eleves&id_classe=' + idClasse, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, "text/html");
                        const nouveauContenu = doc.getElementById("content-to-load");
                        if (nouveauContenu && dynamicContent) {
                            dynamicContent.innerHTML = nouveauContenu.innerHTML;
                            // Ré-exécute le script pour lier les événements aux nouveaux éléments HTML
                            const scriptEnfant = dynamicContent.querySelector('script');
                            if (scriptEnfant) {
                                eval(scriptEnfant.text);
                            }
                        }
                    });
                }
            });

            // 2. Filtrage côté client
            rechercheInput.addEventListener('input', function() {
                const filtre = this.value.toLowerCase().trim();
                const lignes = document.querySelectorAll('.ligne-eleve');

                lignes.forEach(ligne => {
                    const matricule = ligne.querySelector('.cell-matricule').textContent.toLowerCase();
                    const nom = ligne.querySelector('.cell-nom').textContent.toLowerCase();
                    const prenom = ligne.querySelector('.cell-prenom').textContent.toLowerCase();

                    if (matricule.includes(filtre) || nom.includes(filtre) || prenom.includes(filtre)) {
                        ligne.style.display = "";
                    } else {
                        ligne.style.display = "none";
                    }
                });
            });

            // 3. Suppression d'un élève
            document.querySelectorAll('.btn-supprimer').forEach(btn => {
                btn.addEventListener('click', function() {
                    const matricule = this.getAttribute('data-matricule');
                    if (confirm("Êtes-vous sûr de vouloir renvoyer et supprimer définitivement l'élève au matricule " + matricule + " ?")) {
                        fetch('index.php?action=supprimer_eleve&matricule=' + matricule, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            alert(data.message);
                            if (data.succes) {
                                selectClasse.dispatchEvent(new Event('change'));
                            }
                        });
                    }
                });
            });

            // 4. Lancement de la modale de modification
            document.querySelectorAll('.btn-modifier').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('mod-matricule').value = this.getAttribute('data-matricule');
                    document.getElementById('mod-nom').value = this.getAttribute('data-nom');
                    document.getElementById('mod-prenom').value = this.getAttribute('data-prenom');
                    document.getElementById('mod-date').value = this.getAttribute('data-date');
                    document.getElementById('mod-sexe').value = this.getAttribute('data-sexe');
                    document.getElementById('mod-tel').value = this.getAttribute('data-tel');
                    
                    $('#modalModifierEleve').modal('show');
                });
            });

            // 5. Envoi du formulaire de modification
            document.getElementById('form-modifier-eleve').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('index.php?action=modifier_eleve', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    $('#modalModifierEleve').modal('hide');
                    // On attend la fermeture de la modale pour afficher le message et rafraîchir
                    setTimeout(() => {
                        alert(data.message);
                        if (data.succes) {
                            selectClasse.dispatchEvent(new Event('change'));
                        }
                    }, 400);
                });
            });
        })();
    </script>
</div>