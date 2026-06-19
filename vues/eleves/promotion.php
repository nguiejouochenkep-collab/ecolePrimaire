<?php
// vues/eleves/promotion.php

// Inclure interface.js pour avoir accès à chargerPromotion()
// (script déjà chargé dans le dashboard mais ré-inclus ici par sécurité)
?>
<script src="interface.js"></script>
<div id="content-to-load">
    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-arrow-up mr-2"></i> Promotion des élèves</h4>
                <small>Les élèves avec une moyenne annuelle ≥ 10/20 peuvent passer en classe supérieure</small>
            </div>
            <div class="card-body">
                
                <?php if (isset($_SESSION['message_promotion'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i> <?= $_SESSION['message_promotion'] ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['message_promotion']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_promotion'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?= $_SESSION['error_promotion'] ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                    <?php unset($_SESSION['error_promotion']); ?>
                <?php endif; ?>
                
                <form method="GET" action="index.php" class="mb-4" onsubmit="return false;">
                    <input type="hidden" name="action" value="promotion">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="font-weight-bold">Classe actuelle</label>
                            <select name="id_classe" class="form-control" onchange="chargerPromotion(this.value)">
                                <option value="">-- Toutes les classes --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($id_classe == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nom_classe']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($eleves)): ?>
                    <form method="POST" action="index.php?action=executer_promotion" id="formPromotion">
                        <input type="hidden" name="id_classe_source" value="<?= $id_classe ?>">
                        <input type="hidden" name="id_classe_destination" value="<?= $classe_destination_id ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th width="50"><input type="checkbox" id="checkAll"></th>
                                        <th>Matricule</th>
                                        <th>Nom & Prénom</th>
                                        <th>Classe actuelle</th>
                                        <th>Moyenne annuelle</th>
                                        <th>Statut</th>
                                        <th>Classe destination</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eleves as $eleve): ?>
                                        <tr class="<?= ($eleve['moyenne_annuelle'] >= 10) ? 'table-success' : 'table-danger' ?>">
                                            <td class="text-center">
                                                <?php if ($eleve['moyenne_annuelle'] >= 10): ?>
                                                    <input type="checkbox" name="eleves[]" value="<?= $eleve['matricule'] ?>" class="checkbox-eleve">
                                                <?php else: ?>
                                                    <input type="checkbox" disabled>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($eleve['matricule']) ?></td>
                                            <td><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                                            <td><?= htmlspecialchars($eleve['classe_actuelle'] ?? '-') ?></td>
                                            <td class="text-center font-weight-bold">
                                                <?= number_format($eleve['moyenne_annuelle'], 2) ?> / 20
                                            </td>
                                            <td class="text-center">
                                                <?php if ($eleve['moyenne_annuelle'] >= 10): ?>
                                                    <span class="badge badge-success">Admis</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Non admis</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($eleve['moyenne_annuelle'] >= 10 && $classe_destination): ?>
                                                    <span class="text-primary">
                                                        <i class="fas fa-arrow-right mr-1"></i> 
                                                        <?= htmlspecialchars($classe_destination['nom_classe']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Reste ici</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($classe_destination): ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                Les élèves sélectionnés seront promus en <strong><?= htmlspecialchars($classe_destination['nom_classe']) ?></strong>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg" onclick="return confirm('Êtes-vous sûr de vouloir promouvoir les élèves sélectionnés ?')">
                                <i class="fas fa-arrow-up mr-2"></i> Promouvoir les élèves sélectionnés
                            </button>
                            <a href="index.php?action=dashboard" class="btn btn-secondary btn-lg ml-2">
                                <i class="fas fa-home mr-2"></i> Retour
                            </a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>Aucun élève trouvé</h5>
                        <p>Sélectionnez une classe pour voir les élèves</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="interface.js"></script>

<script>
    // Définir la fonction chargerPromotion localement si elle n'existe pas déjà
    // (au cas où interface.js n'aurait pas encore chargé)
    if (typeof chargerPromotion === 'undefined') {
        window.chargerPromotion = function(id_classe) {
            var url = 'index.php?action=promotion';
            if (id_classe && id_classe !== '') url += '&id_classe=' + encodeURIComponent(id_classe);

            var dynamic = document.getElementById('dynamic-content');
            if (!dynamic) {
                window.location.href = url;
                return;
            }

            dynamic.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3 text-secondary font-weight-bold">Chargement...</p>
                </div>`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (resp) {
                    if (!resp.ok) throw new Error('Erreur ' + resp.status);
                    return resp.text();
                })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var nouveau = doc.getElementById('dynamic-content') || doc.getElementById('content-to-load') || doc.body;
                    if (nouveau) {
                        dynamic.innerHTML = nouveau.innerHTML;
                        Array.from(nouveau.querySelectorAll('script')).forEach(function(s) {
                            var ns = document.createElement('script');
                            if (s.src) ns.src = s.src; else ns.text = s.textContent || s.innerText || '';
                            document.body.appendChild(ns);
                        });
                    } else {
                        dynamic.innerHTML = html;
                    }
                    try { history.pushState({}, '', url); } catch (e) {}
                })
                .catch(function (err) {
                    dynamic.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement : ${err.message}</div>`;
                });
        };
    }

    // Initialiser la gestion des cases à cocher - utiliser un délai pour s'assurer que jQuery soit disponible
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            $('#checkAll').on('click', function() {
                $('.checkbox-eleve').prop('checked', this.checked);
            });
            
            $('.checkbox-eleve').on('change', function() {
                if ($('.checkbox-eleve:checked').length === $('.checkbox-eleve').length) {
                    $('#checkAll').prop('checked', true);
                } else {
                    $('#checkAll').prop('checked', false);
                }
            });
        });
    } else {
        // Fallback si jQuery n'est pas disponible
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                $(document).ready(function() {
                    $('#checkAll').on('click', function() {
                        $('.checkbox-eleve').prop('checked', this.checked);
                    });
                    
                    $('.checkbox-eleve').on('change', function() {
                        if ($('.checkbox-eleve:checked').length === $('.checkbox-eleve').length) {
                            $('#checkAll').prop('checked', true);
                        } else {
                            $('#checkAll').prop('checked', false);
                        }
                    });
                });
            }
        }, 500);
    }
</script>