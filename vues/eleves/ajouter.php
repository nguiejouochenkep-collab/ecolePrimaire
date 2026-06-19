<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscrire un élève</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width: 600px;">
    <h2 class="mb-4">Formulaire d'Inscription</h2>
    
    <form action="index.php?action=sauvegarder_eleve" method="POST">
        <div class="form-group">
            <label>Matricule (Obligatoire)</label>
            <input type="text" name="matricule" class="form-control" required>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>Nom</label>
                <input type="text" name="nom" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" class="form-control" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>Date de Naissance</label>
                <input type="date" name="date_naissance" class="form-control" required>
            </div>
            <div class="col-md-6 form-group">
                <label>Sexe</label>
                <select name="sexe" class="form-control" required>
                    <option value="M">Masculin</option>
                    <option value="F">Féminin</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Téléphone Parent</label>
            <input type="text" name="telephone_parent" class="form-control">
        </div>
        <div class="form-group">
            <label>Adresse</label>
            <textarea name="adresse" class="form-control" rows="2"></textarea>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label>ID de la Classe (Vérifiez dans votre table classe)</label>
                <input type="number" name="id_classe" class="form-control" placeholder="Ex: 1" required>
            </div>
            <div class="col-md-6 form-group">
                <label>ID Année Scolaire (Vérifiez votre table annee_scolaire)</label>
                <input type="number" name="id_annee" class="form-control" placeholder="Ex: 1" required>
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-block">Valider l'Inscription</button>
        <a href="index.php?action=liste_eleves" class="btn btn-secondary btn-block">Annuler</a>
    </form>
</div>
</body>
</html>