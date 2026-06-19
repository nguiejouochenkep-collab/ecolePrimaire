# Guide d'Installation des Corrections

## ✅ Étape 1: Vérifier les fichiers modifiés

Les fichiers suivants ont déjà été modifiés automatiquement :
- ✅ `controleurs/EleveControleur.php` 
- ✅ `controleurs/UtilisateurControleur.php`

**Ces modifications sont déjà en place - aucune action requise de votre part.**

## 📊 Étape 2: Exécuter la migration SQL pour CP B

Le fichier `migration_cp_b.sql` crée la classe CP B et ajoute les données de test.

### Option A: Utiliser phpMyAdmin (Interface Web)

1. Ouvrez phpMyAdmin dans votre navigateur
   - URL: `http://localhost/phpmyadmin/`
   - Utilisateur: `root` (pas de mot de passe)

2. Sélectionnez la base de données `gestion_ecole`

3. Cliquez sur l'onglet **SQL** en haut

4. Ouvrez le fichier `migration_cp_b.sql` :
   - Cliquez sur "Charger un fichier SQL"
   - Naviguez vers `c:\wamp64\www\Projet_de_stage\migration_cp_b.sql`
   - OU copiez le contenu du fichier et collez-le

5. Cliquez sur **Exécuter**

6. Vous devriez voir un message de succès

### Option B: Utiliser la ligne de commande

1. Ouvrez PowerShell ou Invite de Commande

2. Naviguez vers le dossier du projet :
   ```bash
   cd c:\wamp64\www\Projet_de_stage\
   ```

3. Exécutez la migration :
   ```bash
   mysql -u root gestion_ecole < migration_cp_b.sql
   ```

4. Attendez que la commande se termine

### Option C: Exécuter directement via MySQL

1. Ouvrez MySQL Command Line Client

2. Connectez-vous :
   ```sql
   USE gestion_ecole;
   ```

3. Copie-collez le contenu de `migration_cp_b.sql` ligne par ligne

## 🧪 Étape 3: Vérifier que tout fonctionne

### Test 1: Vérifier CP B dans PhpMyAdmin

1. Dans phpMyAdmin, sélectionnez l'onglet "Données" de la table `classe`
2. Vous devriez voir "CP B" dans la liste
3. Regardez les élèves associés - vous devriez voir TMP011 à TMP020 dans CP B

### Test 2: Tester l'ajout de classe dans l'interface

1. Connectez-vous avec un compte administrateur
2. Allez dans **Gestion** → **Configuration des Classes**
3. Cliquez sur **Nouvelle Classe**
4. Entrez "CE3" comme nom et cliquez "Créer la classe"
5. **Résultat attendu:** Message de succès et nouvelle classe visible
6. Essayez d'ajouter "CE3" à nouveau
7. **Résultat attendu:** Message d'erreur "Une classe avec ce nom existe déjà"

### Test 3: Tester la modification de classe

1. Sur la même page, cliquez **Renommer** sur une classe
2. Changez le nom
3. **Résultat attendu:** Message de succès
4. Essayez de le changer en un nom existant (ex: "CP B")
5. **Résultat attendu:** Message d'erreur

### Test 4: Tester la suppression de classe

1. Cliquez **Supprimer** sur "CE3" (la classe que vous venez de créer)
2. Confirmez dans la boîte de dialogue
3. **Résultat attendu:** Message de succès et classe disparue
4. Cliquez **Supprimer** sur "CP B" (qui contient des élèves)
5. **Résultat attendu:** Message d'erreur "Impossible de supprimer cette classe car elle contient X élève(s)"

### Test 5: Vérifier les bulletins de CP B

1. Allez dans **Notes** → **Bulletins**
2. Sélectionnez **CP B** dans la liste déroulante "Classe"
3. Sélectionnez un trimestre (ex: "Trimestre 1")
4. Cliquez **Charger**
5. **Résultat attendu:** Vous voyez les bulletins des élèves de CP B

## 🐛 Dépannage

### Problème: "Erreur lors de l'exécution du script SQL"

**Solution:**
- Vérifiez que vous avez sélectionné la bonne base de données (`gestion_ecole`)
- Vérifiez que MySQL est bien lancé
- Essayez d'exécuter les commandes une par une

### Problème: "Aucun bulletin disponible pour CP B"

**Solutions:**
1. Vérifiez dans phpMyAdmin que CP B existe réellement
2. Vérifiez que des élèves sont affectés à CP B
3. Vérifiez que CP B a des matières assignées

### Problème: "Impossible d'ajouter/modifier des classes"

**Solution:**
- Vérifiez que vous êtes connecté avec un compte administrateur
- Vérifiez les droits de votre compte (role = "admin" ou "directeur")
- Vérifiez les logs PHP pour plus de détails

## 📞 Support

Si vous avez besoin d'aide :

1. Consulter le fichier `RAPPORT_CORRECTIONS.md` pour plus de détails
2. Consulter le fichier `CHANGELOG.md` pour l'historique des changements
3. Vérifier les logs PHP dans `C:\wamp64\logs\php_error.log`
4. Vérifier la console du navigateur (F12 → Console) pour les erreurs JavaScript

## ✅ Résumé des Corrections

| Problème | Correction | Status |
|----------|-----------|--------|
| Variable `$trimestreStmt` non définie | Corrigée en `$stmt` | ✅ |
| Pas de vérification avant ajout de classe | Ajout de vérification | ✅ |
| Pas de vérification avant modification | Ajout de vérification | ✅ |
| Pas de validation avant suppression | Ajout de validation | ✅ |
| CP B n'existe pas | Migration SQL créée | ⏳ *En attente d'exécution* |
| Pas de matières pour CP B | Migration SQL les ajoute | ⏳ *En attente d'exécution* |

## 🎉 Vous êtes prêt!

Tous les correctifs sont en place. Il ne reste plus qu'à :
1. Exécuter la migration SQL (option A, B ou C ci-dessus)
2. Tester selon l'Étape 3
3. Profiter de l'application corrigée!
