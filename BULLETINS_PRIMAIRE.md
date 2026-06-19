# Documentation : Bulletins Scolaires Camerounais (Primaire)

## 🎯 Vue d'ensemble

Ce système de bulletins est conçu pour les écoles primaires camerounaises, avec support de deux types de bulletins :
1. **Bulletin Trimestriel** : Affiche 2 séquences par trimestre (Séq1 + Séq2)
2. **Bulletin Annuel** : Affiche les 6 séquences de l'année complète + progression

## 📊 Structure de Données

### Tables Principales Créées/Modifiées

#### 1. **`groupe_matiere`** (NEW)
Regroupe les matières en 3 catégories du primaire camerounais:
- I. ENSEIGNEMENTS FONDAMENTAUX (Mathématiques, Français)
- II. ÉVEIL (Histoire, Géographie, Sciences)
- III. VIE PRATIQUE ET SPORT (Dessin, Chant, EPS)

```sql
CREATE TABLE groupe_matiere (
  id INT PRIMARY KEY AUTO_INCREMENT,
  nom_groupe VARCHAR(100) UNIQUE,
  ordre_affichage INT,
  description TEXT
);
```

#### 2. **`sequence`** (NEW)
Représente les 6 séquences de l'année scolaire (2 par trimestre)
```sql
CREATE TABLE sequence (
  id INT PRIMARY KEY AUTO_INCREMENT,
  numero_sequence INT (1-6),
  nom VARCHAR(50),
  id_trimestre INT,
  date_debut DATE,
  date_fin DATE
);
```

#### 3. **`appreciation`** (NEW)
Système d'appréciation automatisé basé sur les notes
```sql
CREATE TABLE appreciation (
  id INT PRIMARY KEY AUTO_INCREMENT,
  note_min DECIMAL(5,2),
  note_max DECIMAL(5,2),
  libelle VARCHAR(50), -- "Très Faible", "Faible", "Passable", "Assez Bien", "Excellent"
  couleur VARCHAR(20)  -- Code couleur hex
);
```

#### 4. **`matiere`** (MODIFIÉ)
Ajout du lien vers les groupes de matières
```sql
ALTER TABLE matiere ADD COLUMN id_groupe_matiere INT;
ALTER TABLE matiere ADD FOREIGN KEY (id_groupe_matiere) REFERENCES groupe_matiere(id);
```

#### 5. **`note`** (MODIFIÉ)
Ajout du lien vers les séquences
```sql
ALTER TABLE note ADD COLUMN id_sequence INT;
ALTER TABLE note ADD FOREIGN KEY (id_sequence) REFERENCES sequence(id);
```

#### 6. **`bulletin`** (RESTRUCTURÉ)
Nouvelle structure avec plus de détails
```sql
CREATE TABLE bulletin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  matricule_eleve VARCHAR(20),
  id_classe INT,
  id_trimestre INT,
  id_sequence INT,
  type_bulletin ENUM('trimestriel', 'annuel'),
  moyenne_general DECIMAL(5,2),
  rang INT,
  absences_justifiees INT,
  absences_injustifiees INT,
  retards INT,
  decision VARCHAR(100), -- Tableau honneur, Encouragements, etc.
  appreciation_globale TEXT,
  date_generation TIMESTAMP,
  date_modification TIMESTAMP
);
```

#### 7. **`bulletin_detail`** (NEW)
Détails des notes par matière
```sql
CREATE TABLE bulletin_detail (
  id INT PRIMARY KEY AUTO_INCREMENT,
  id_bulletin INT,
  id_matiere INT,
  notes_seq JSON, -- {"seq1": 15, "seq2": 12, ...}
  moyenne_matiere DECIMAL(5,2),
  coefficient INT,
  rang_matiere INT,
  note_max_classe DECIMAL(5,2),
  note_min_classe DECIMAL(5,2),
  appreciation VARCHAR(50),
  observation TEXT
);
```

## 🔢 Formules de Calcul

### Bulletin Trimestriel
1. **Moyenne d'une matière** : $M_{mat} = \frac{S_1 + S_2}{2}$
2. **Points pondérés** : $N × C = M_{mat} × Coefficient$
3. **Moyenne générale** : $\frac{\sum(N × C)}{\sum Coef}$

### Bulletin Annuel
1. **Moyenne annuelle d'une matière** : $M_{ann} = \frac{S_1 + S_2 + S_3 + S_4 + S_5 + S_6}{6}$
2. **Moyenne générale annuelle** : $\frac{\sum(M_{ann} × Coef)}{\sum Coef}$

## 📁 Structure des Fichiers

```
modeles/
  ├── Bulletin.php          -- Logique de calcul des bulletins
  └── ...

controleurs/
  ├── BulletinControleur.php -- Routes et logique métier
  └── ...

vues/
  └── bulletins/
      └── index.php         -- Vue d'affichage

migration_primaire_bulletin.sql  -- Scripts de migration
```

## 🚀 Mise en Place

### Étape 1 : Exécuter la Migration SQL
```bash
mysql -u root gestion_ecole < migration_primaire_bulletin.sql
```

### Étape 2 : Adapter les Matières
Mettre à jour les matières existantes vers les groupes du primaire :
```sql
UPDATE matiere SET id_groupe_matiere = 1 
WHERE nom_matiere IN ('Mathématiques', 'Français', 'Calcul');

UPDATE matiere SET id_groupe_matiere = 2 
WHERE nom_matiere IN ('Histoire', 'Géographie', 'Sciences', 'Civisme');

UPDATE matiere SET id_groupe_matiere = 3 
WHERE nom_matiere IN ('Dessin', 'Chant', 'EPS');
```

### Étape 3 : Ajouter les Notes avec Séquences
Lors de la saisie de notes, spécifier `id_sequence` :
```sql
INSERT INTO note (matricule_eleve, id_matiere, id_trimestre, id_sequence, valeur) 
VALUES ('MAT001', 5, 1, 1, 15.5);
```

## 📝 Utilisation

### Accès aux Bulletins
Route : `index.php?action=bulletins`

#### Interface de Sélection
1. Choisir **Classe** (ex: CP, CE1, CM2)
2. Choisir **Trimestre** 
3. Automatiquement, **Élèves** de cette classe s'affichent
4. Sélectionner un **Élève** → Affichage du bulletin

### Types d'Affichage

#### Bulletin Trimestriel (Trimestre 1 ou 2)
Affiche :
- 2 séquences (Seq1, Seq2)
- Moyennes par matière
- Groupes de matières

#### Bulletin Annuel (Trimestre 3)
Affiche :
- 6 séquences (Seq1 à Seq6)
- Moyennes annuelles
- **Courbe de progression** par séquence

## 💡 Points Clés

✅ **Groupes de matières adaptés au primaire camerounais**
✅ **Appréciation automatisée basée sur les notes**
✅ **Formules de calcul conformes aux normes**
✅ **Distinction claire : trimestriel vs annuel**
✅ **Préformaté pour impression (A4)**
✅ **Conforme au modèle camerounais officiel**

## 🔧 Maintenance

### Ajouter une Matière
```sql
INSERT INTO matiere (nom_matiere, coefficient, id_classe, id_groupe_matiere)
VALUES ('Informatique', 2, 5, 2);
```

### Modifier les Appréciations
Éditer la table `appreciation` selon le barème souhaité.

### Générer un PDF
Route : `index.php?action=generer_bulletin_pdf&matricule=...&id_trimestre=...&id_classe=...`

## ⚠️ Conditions Préalables

- PHP ≥ 7.4
- MySQL ≥ 8.0
- PDO activé
- Tables primaires (`classe`, `eleve`, `matiere`, `trimestre`) existantes
- Au moins une note enregistrée par élève/matière/séquence

## 🎓 Exemple de Données

### Séquences
```
Trimestre 1 : Séquence 1, Séquence 2
Trimestre 2 : Séquence 3, Séquence 4
Trimestre 3 : Séquence 5, Séquence 6
```

### Groupes de Matières
```
I. ENSEIGNEMENTS FONDAMENTAUX
   - Mathématiques (Coef: 2)
   - Français (Coef: 2)

II. ÉVEIL
   - Histoire (Coef: 1)
   - Géographie (Coef: 1)
   - Sciences (Coef: 1)

III. VIE PRATIQUE ET SPORT
   - Dessin (Coef: 1)
   - Chant (Coef: 1)
   - EPS (Coef: 1)
```

## 📞 Support

En cas de problème :
1. Vérifier que les tables ont été créées
2. Vérifier que les notes incluent `id_sequence`
3. Consulter les logs PHP pour les erreurs de requête
4. Vérifier les droits d'accès de l'utilisateur BD
