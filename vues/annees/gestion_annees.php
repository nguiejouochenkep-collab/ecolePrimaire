<?php
// vues/annees/gestion_annees.php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<div class="modal" id="modalAnnees" tabindex="-1" role="dialog" style="display:block; background: rgba(0,0,0,0.5);">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Sélectionner l'année scolaire</h5>
        <button type="button" class="close" onclick="fermerModalAnnees()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="list-group mb-3">
          <?php if (!empty($annees)): ?>
            <?php foreach ($annees as $a): ?>
              <div class="d-flex justify-content-between align-items-center list-group-item">
                <div>
                  <strong><?= htmlspecialchars($a['libelle']) ?></strong>
                  <?php if ($a['statut'] === 'active'): ?>
                    <span class="badge badge-success ml-2">Active</span>
                  <?php elseif ($a['statut'] === 'inactive'): ?>
                    <span class="badge badge-secondary ml-2">Inactive</span>
                  <?php else: ?>
                    <span class="badge badge-light ml-2"><?= htmlspecialchars($a['statut']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="btn-group" role="group">
                  <button class="btn btn-sm btn-outline-primary" onclick="selectAnnee(<?= (int)$a['id'] ?>)">Sélectionner</button>
                  <button class="btn btn-sm btn-outline-warning" onclick="modifierAnnee(<?= (int)$a['id'] ?>, '<?= htmlspecialchars($a['libelle']) ?>', '<?= $a['statut'] ?>')">Modifier</button>
                  <button class="btn btn-sm btn-outline-danger" onclick="supprimerAnnee(<?= (int)$a['id'] ?>)">Supprimer</button>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="alert alert-warning">Aucune année scolaire configurée.</div>
          <?php endif; ?>
        </div>

        <hr />
        <h6>Créer une nouvelle année scolaire</h6>
        <form id="formCreateAnnee" onsubmit="createAnnee(event)">
          <div class="form-group">
            <input type="text" class="form-control" id="libelleAnnee" placeholder="Ex: 2025-2026" required>
          </div>
          <button class="btn btn-success">Créer et sélectionner</button>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="fermerModalAnnees()">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de modification d'année (caché par défaut) -->
<div class="modal" id="modalModifierAnnee" tabindex="-1" role="dialog" style="display:none; background: rgba(0,0,0,0.5);">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Modifier l'année scolaire</h5>
        <button type="button" class="close" onclick="fermerModalModifier()">&times;</button>
      </div>
      <div class="modal-body">
        <form id="formModifierAnnee" onsubmit="submitModifierAnnee(event)">
          <input type="hidden" id="modifyAnneeId">
          <div class="form-group">
            <label>Libellé</label>
            <input type="text" class="form-control" id="modifyLibelle" required>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select class="form-control" id="modifyStatut">
              <option value="inactive">Inactive</option>
              <option value="active">Active</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="fermerModalModifier()">Annuler</button>
        <button type="button" class="btn btn-primary" onclick="submitModifierAnnee(event)">Sauvegarder</button>
      </div>
    </div>
  </div>
</div>

<script>
function fermerModalAnnees() {
  var modal = document.getElementById('modalAnnees');
  if (modal) modal.remove();
}

function selectAnnee(id) {
  fetch('index.php?action=changer_annee', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ id_annee: id, action_type: 'select' })
  }).then(r => r.json()).then(j => {
    if (j.succes) {
      alert(j.message);
      window.location.reload();
    } else alert('Erreur: ' + j.message);
  }).catch(e => alert('Erreur: ' + e.message));
}

function createAnnee(e) {
  e.preventDefault();
  var lib = document.getElementById('libelleAnnee').value.trim();
  if (!lib) return alert('Libellé requis');
  fetch('index.php?action=changer_annee', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ action_type: 'create', libelle: lib })
  }).then(r => r.json()).then(j => {
    if (j.succes) {
      alert('Année créée et sélectionnée: ' + j.libelle);
      window.location.reload();
    } else {
      alert('Erreur: ' + j.message);
    }
  }).catch(e => alert('Erreur: ' + e.message));
}

function modifierAnnee(id, libelle, statut) {
  document.getElementById('modifyAnneeId').value = id;
  document.getElementById('modifyLibelle').value = libelle;
  document.getElementById('modifyStatut').value = statut;
  document.getElementById('modalModifierAnnee').style.display = 'block';
}

function fermerModalModifier() {
  document.getElementById('modalModifierAnnee').style.display = 'none';
}

function submitModifierAnnee(e) {
  e.preventDefault();
  var id = document.getElementById('modifyAnneeId').value;
  var lib = document.getElementById('modifyLibelle').value.trim();
  var statut = document.getElementById('modifyStatut').value;
  
  if (!lib) return alert('Libellé requis');
  
  fetch('index.php?action=changer_annee', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ action_type: 'update', id_annee: id, libelle: lib, statut: statut })
  }).then(r => r.json()).then(j => {
    if (j.succes) {
      alert(j.message);
      window.location.reload();
    } else {
      alert('Erreur: ' + j.message);
    }
  }).catch(e => alert('Erreur: ' + e.message));
}

function supprimerAnnee(id) {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette année scolaire ?')) return;
  fetch('index.php?action=changer_annee', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams({ action_type: 'delete', id_annee: id })
  }).then(r => r.json()).then(j => {
    if (j.succes) {
      alert(j.message);
      window.location.reload();
    } else {
      alert('Erreur: ' + j.message);
    }
  }).catch(e => alert('Erreur: ' + e.message));
}
</script>
