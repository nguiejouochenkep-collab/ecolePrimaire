<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Changer mon mot de passe</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($message_erreur)): ?>
                        <div class="alert alert-danger"><?= $message_erreur ?></div>
                    <?php endif; ?>
                    <?php if (isset($message_succes)): ?>
                        <div class="alert alert-success"><?= $message_succes ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="index.php?action=changer_mot_de_passe">
                        <div class="form-group">
                            <label>Ancien mot de passe</label>
                            <input type="password" name="ancien_mot_de_passe" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="nouveau_mot_de_passe" id="nouveau_mdp" class="form-control" required>
                            <small class="text-muted">
                                Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.
                            </small>
                        </div>
                        <div class="form-group">
                            <label>Confirmer le mot de passe</label>
                            <input type="password" name="confirmer_mot_de_passe" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Changer le mot de passe</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>