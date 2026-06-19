<?php if (!$is_ajax): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrire un élève - EduManage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-image: url("image2.jpeg"); background-size: cover; }
        .form-container { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="mb-4">
            <a href="index.php?action=dashboard" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-2"></i> Retour au tableau de bord</a>
        </div>
<?php endif; ?>

        <div id="content-to-load">
            <div class="form-container">
                <h5 class="mb-4 font-weight-bold text-secondary"><i class="fas fa-user-edit mr-2"></i> Formulaire d'inscription</h5>
                
                <div id="ajax-response-zone">
                    <?php if(!empty($message_succes)): ?> <div class="alert alert-success"><?php echo $message_succes; ?></div> <?php endif; ?>
                    <?php if(!empty($message_erreur)): ?> <div class="alert alert-danger"><?php echo $message_erreur; ?></div> <?php endif; ?>
                </div>

                <form id="form-inscription-ajax" action="index.php?action=sauvegarder_eleve" method="POST">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" placeholder="Ex: KAMGA" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="font-weight-bold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" name="prenom" class="form-control" placeholder="Ex: Jean" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" name="date_naissance" class="form-control" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Sexe <span class="text-danger">*</span></label>
                            <select name="sexe" class="form-control" required>
                                <option value="">Choisir...</option>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">Classe <span class="text-danger">*</span></label>

                               <select name="id_classe" class="form-control" required>
                                <option value="">Choisir la classe...</option>

                                <?php foreach($classes as $classe): ?>
                                    <option value="<?= $classe['id'] ?>">
                                        <?= htmlspecialchars($classe['nom_classe']) ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Téléphone du parent/tuteur</label>
                        <input type="tel" name="telephone_parent" class="form-control" placeholder="Ex: 6xxxxxx">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block font-weight-bold mt-4"><i class="fas fa-save mr-2"></i> Valider l'inscription</button>
                </form>
            </div>

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script>
            $('#form-inscription-ajax').off('submit').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var containerResponse = $('#ajax-response-zone');
                
                $.ajax({
                    type: form.attr('method'),
                    url: form.attr('action'),
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(data) {
                        containerResponse.removeClass('alert alert-success alert-danger');
                        
                        if(data.succes) {
                            containerResponse.addClass('alert alert-success').html(data.message);
                            form[0].reset(); 
                        } else {
                            containerResponse.addClass('alert alert-danger').html(data.message);
                        }
                    },
                    error: function() {
                        containerResponse.removeClass('alert alert-success').addClass('alert alert-danger').html('Une erreur technique est survenue.');
                    }
                });
            });
            </script>
        </div>

<?php if (!$is_ajax): ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php endif; ?>