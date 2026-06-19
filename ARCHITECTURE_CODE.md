# 📖 ARCHITECTURE DU CODE - Bulletins Primaire Camerounais

## 🏗️ Vue d'Ensemble

Le système de bulletins suit une architecture **MVC (Model-View-Controller)** classique avec une séparation claire des responsabilités.

```
USER REQUEST
    ↓
index.php (Router)
    ↓
BulletinControleur (HTTP Logic)
    ↓
Bulletin (Business Logic)
    ↓
Database
    ↓
View (HTML/CSS)
```

---

## 📚 MODÈLE : `modeles/Bulletin.php`

### Responsabilités
- Calculs mathématiques (moyennes, rangs)
- Récupération données BD
- Formatage données pour vues
- Appréciation automatique

### Méthodes Clés

#### 1. `calculerMoyenneMatiereTrimestre($matricule, $id_matiere, $id_trimestre)`
```php
// Calcule : (Seq1 + Seq2) / 2
$moyenne = ($notes[0] + $notes[1]) / 2;
```
- **Entrée** : élève + matière + trimestre
- **Sortie** : moyenne trimestrielle
- **Utilité** : base pour bulletins 1 & 2

#### 2. `calculerMoyenneMatierAnnuelle($matricule, $id_matiere)`
```php
// Calcule : (Seq1 + Seq2 + ... + Seq6) / 6
$moyenne = AVG(valeur) sur toutes séquences
```
- **Entrée** : élève + matière
- **Sortie** : moyenne annuelle
- **Utilité** : base pour bulletin 3

#### 3. `calculerMoyenneTrimestre($matricule, $id_trimestre, $id_classe)`
```php
// Calcule : Σ(Moy_matière × Coef) / Σ(Coef)
$total_points += moyenne_mat * coefficient;
$moyenne = total_points / total_coefs;
```
- **Entrée** : élève + trimestre + classe
- **Sortie** : moyenne générale du trimestre
- **Utilité** : affichage dans bulletin

#### 4. `getBulletinTrimestriel($matricule, $id_trimestre, $id_classe)`
```php
// Structure complète pour affichage bulletin T1/T2
$bulletin = [
    'type' => 'trimestriel',
    'groupes' => [
        [
            'nom' => 'I. ENSEIGNEMENTS FONDAMENTAUX',
            'matieres' => [
                ['nom' => 'Mathématiques', 'seq1' => 15, 'seq2' => 14, 'moyenne' => 14.5, ...],
                ...
            ],
            'total_coef' => 4,
            'total_points' => 58
        ],
        ...
    ],
    'moyenne_general' => 13.5,
    'rang' => 5
]
```
- **Entrée** : élève + trimestre + classe
- **Sortie** : structure complète pour rendu
- **Utilité** : base de l'affichage trimestriel

#### 5. `getBulletinAnnuel($matricule, $id_classe)`
```php
// Structure complète pour affichage bulletin annuel + progression
$bulletin = [
    'type' => 'annuel',
    'groupes' => [...],
    'progression' => ['seq1' => 13.2, 'seq2' => 13.5, ...],
    'moyenne_general' => 13.5
]
```
- **Entrée** : élève + classe
- **Sortie** : structure avec progression
- **Utilité** : base de l'affichage annuel

#### 6. `getAppreciation($note)`
```php
// Retourne appréciation basée sur note
if ($note >= 16.01) return 'Excellent';
elseif ($note >= 13.01) return 'Assez Bien';
// ...
```
- **Entrée** : note (0-20)
- **Sortie** : libellé appréciation
- **Utilité** : classification automatique

### Classe Structure
```php
class Bulletin {
    private $pdo;  // Connexion BD
    
    public function __construct($pdo) { ... }
    
    // Calculs individuels
    public function calculerMoyenneMatiereTrimestre(...) { ... }
    public function calculerMoyenneMatierAnnuelle(...) { ... }
    public function calculerMoyenneTrimestre(...) { ... }
    
    // Récupération données complètes
    public function getBulletinTrimestriel(...) { ... }
    public function getBulletinAnnuel(...) { ... }
    
    // Utilitaires
    public function getAppreciation($note) { ... }
    private function calculerRangTrimestre(...) { ... }
}
```

---

## 🎮 CONTRÔLEUR : `controleurs/BulletinControleur.php`

### Responsabilités
- Routage des requêtes HTTP
- Authentification & autorisation
- Orchestration Modèle ↔ Vue
- Réponses JSON pour AJAX

### Méthodes

#### 1. `afficherBulletins()`
```php
// GET: index.php?action=bulletins
GET params:
  - id_classe (optionnel)
  - id_trimestre (optionnel)
  - matricule (optionnel)

Flow:
1. Vérifier authentification
2. Récupérer classes/trimestres disponibles
3. SI params complets:
   - Récupérer élève
   - Récupérer bulletin (trimestriel ou annuel)
4. Require vues/bulletins/index.php
```

#### 2. `genererPDF()`
```php
// GET: index.php?action=generer_bulletin_pdf
GET params:
  - matricule
  - id_trimestre
  - id_classe

Flow:
1. Vérifier authentification & autorisations
2. Récupérer données bulletin
3. Générer PDF (interface pour FPDF/TCPDF)
4. Retour JSON avec URL download
```

#### 3. `getStatistiquesBulletin()`
```php
// GET: index.php?action=eleves_classe
// Utilise aussi pour: action=stats_bulletin
GET params:
  - id_classe

Response JSON:
{
  "status": "success",
  "eleves": [...],
  "count": N
}

// Utilisé par interface AJAX pour remplir liste élèves
```

### Classe Structure
```php
class BulletinControleur {
    private $bulletinModele;  // Instance du modèle
    private $pdo;              // Connexion BD
    
    public function __construct() {
        $this->initialiserConnexion();
        $this->bulletinModele = new Bulletin($this->pdo);
    }
    
    private function initialiserConnexion() { ... }
    private function verifierAuthentification() { ... }
    private function verifierClasseAutorisee($id_classe) { ... }
    
    public function afficherBulletins() { ... }
    public function genererPDF() { ... }
    public function getStatistiquesBulletin() { ... }
}
```

---

## 👁️ VUE : `vues/bulletins/index.php`

### Structure Générale

```html
<!DOCTYPE html>
  <head>
    <meta charset="UTF-8">
    <title>Bulletins</title>
    <link rel="stylesheet" href="bootstrap.min.css">
    <style>...</style>
  </head>
  
  <body>
    <div class="container-fluid">
      
      <!-- 1. PANNEAU DE CONTRÔLE (Sélection) -->
      <div class="control-panel">
        <form method="GET">
          <select name="id_classe">...</select>
          <select name="id_trimestre">...</select>
          <select name="matricule" id="eleve">...</select>
        </form>
      </div>
      
      <!-- 2. AFFICHAGE BULLETIN (si données disponibles) -->
      <?php if ($bulletin_data && $eleve_data): ?>
        
        <!-- En-tête institutionnel -->
        <div class="bulletin-header">...</div>
        
        <!-- Infos élève -->
        <div class="bulletin-meta">...</div>
        
        <!-- Bulletins par groupe -->
        <?php foreach ($bulletin_data['groupes'] as $groupe): ?>
          <div class="groupe-matiere">
            <div class="groupe-titre">I. ENSEIGNEMENTS FONDAMENTAUX</div>
            <table>
              <!-- Si TRIMESTRIEL: Seq1 | Seq2 | Moy -->
              <!-- Si ANNUEL: Seq1 | ... | Seq6 | Ann -->
            </table>
          </div>
        <?php endforeach; ?>
        
        <!-- Résumé général -->
        <div class="summary-box">
          Moyenne générale | Rang | Appréciation
        </div>
        
        <!-- Progression (annuel) -->
        <?php if ($bulletin_data['type'] === 'annuel'): ?>
          <div class="progression-chart">
            Seq1: 13.2, Seq2: 13.5, ...
          </div>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="signature-box">
          Enseignant | Conseil | Directeur
        </div>
        
        <!-- Actions -->
        <div class="actions">
          <button onclick="window.print()">Imprimer</button>
          <a href="...">Retour</a>
        </div>
        
      <?php endif; ?>
    </div>
    
    <script>
      // AJAX pour charger élèves quand trimestre sélectionné
      trimestreSelect.addEventListener('change', function() {
        fetch('?action=eleves_classe&id_classe=' + classeSelect.value)
          .then(r => r.json())
          .then(data => {
            eleveSelect.innerHTML = '<option>-- Choisir --</option>';
            data.eleves.forEach(e => {
              option.value = e.matricule;
              option.textContent = e.nom + ' ' + e.prenom;
            });
          });
      });
    </script>
  </body>
</html>
```

### Variables Disponibles (du contrôleur)
```php
$classes                    // Array de toutes les classes
$trimestres                 // Array de tous les trimestres
$id_classe_selectionnee     // Classe choisie (ou null)
$id_trimestre_selectionne   // Trimestre choisi (ou null)
$bulletin_data              // [type, groupes, moyenne_general, rang, progression]
$eleve_data                 // [matricule, nom, prenom, ...]
$classe                     // [id, nom_classe, niveau]
$trimestre                  // Nom du trimestre
```

### Logique Conditionnelle
```php
// Affichage du type de tableau selon trimestre

IF trimestre = 3 (Annuel):
  Affiche 6 séquences (Seq1-Seq6)
  Affiche progression
  Titre: "BULLETIN ANNUEL"
ELSE:
  Affiche 2 séquences (Seq1, Seq2)
  Titre: "BULLETIN TRIMESTRIEL"

// Appréciation automatique
IF note >= 16.01:
  "Excellent" (bleu)
ELSEIF note >= 13.01:
  "Assez Bien" (vert)
ELSEIF note >= 10.01:
  "Passable" (jaune)
ELSEIF note >= 5.01:
  "Faible" (orange)
ELSE:
  "Très Faible" (rouge)
```

---

## 🔄 FLUX DE DONNÉES (Complet)

### Cas 1 : Affichage Bulletin Trimestriel

```
User Click: index.php?action=bulletins&id_classe=1&id_trimestre=1&matricule=TMP001
       ↓
index.php Router
       ↓
$bulletinCtrl->afficherBulletins()
       ↓
BulletinControleur:
  1. Récupérer données GET
  2. Vérifier authentification
  3. Récupérer classes/trimestres
  4. IF params complets:
     - $bulletinModele->getBulletinTrimestriel(TMP001, 1, 1)
       ↓
       Bulletin::getBulletinTrimestriel()
       ├─ Pour chaque matière de classe 1:
       │  ├─ SELECT notes avec id_sequence=1 ET 2
       │  ├─ calculerMoyenneMatiereTrimestre()
       │  └─ getAppreciation()
       ├─ Grouper par groupe_matiere
       ├─ calculerMoyenneTrimestre()
       └─ calculerRangTrimestre()
       ↓
       Return: [type:'trimestriel', groupes:[...], moyenne_general:13.5, rang:5]
       
  5. Require vues/bulletins/index.php
       ↓
Vue affiche:
  - Groupes avec matières/moyennes
  - Tableau Seq1|Seq2|Moy|Appréciation
  - Résumé général
  - Signatures
```

### Cas 2 : Affichage Bulletin Annuel

```
Similar, mais:
- bulletinModele->getBulletinAnnuel() au lieu de getTrimestrel()
- SELECT notes sans filtrage par id_sequence
- Calcul: moyennes sur 6 séquences
- Affichage: Seq1|Seq2|Seq3|Seq4|Seq5|Seq6|Ann
- Plus: affichage progression par séquence
```

### Cas 3 : AJAX - Charger Élèves

```
User selects Trimestre
       ↓
JavaScript Event: trimestreSelect.onChange
       ↓
fetch('?action=eleves_classe&id_classe=1')
       ↓
index.php routes to: $bulletinCtrl->getStatistiquesBulletin()
       ↓
BulletinControleur:
  1. GET id_classe
  2. SELECT * FROM eleve WHERE id_classe = 1
  3. header('Content-Type: application/json')
  4. echo json_encode(['eleves' => $eleves, 'count' => count($eleves)])
       ↓
Browser receives JSON
       ↓
JavaScript:
  forEach(eleve) {
    create <option> with matricule & nom+prenom
  }
       ↓
Dropdown rempli ! 
User peut maintenant sélectionner un élève
```

---

## 🗄️ STRUCTURES DE DONNÉES CLÉS

### Structure du Bulletin Trimestriel
```php
[
  'type' => 'trimestriel',
  'trimestre' => 1,
  'groupes' => [
    [
      'nom' => 'I. ENSEIGNEMENTS FONDAMENTAUX',
      'matieres' => [
        [
          'nom' => 'Mathématiques',
          'coefficient' => 2,
          'seq1' => 15,
          'seq2' => 14,
          'moyenne' => 14.5,
          'appreciation' => 'Assez Bien'
        ],
        ...
      ],
      'total_coef' => 4,
      'total_points' => 58
    ],
    ...
  ],
  'moyenne_general' => 13.5,
  'rang' => 5
]
```

### Structure du Bulletin Annuel
```php
[
  'type' => 'annuel',
  'groupes' => [
    [
      'nom' => 'I. ENSEIGNEMENTS FONDAMENTAUX',
      'matieres' => [
        [
          'nom' => 'Mathématiques',
          'coefficient' => 2,
          'notes' => ['seq1' => 15, 'seq2' => 14, ..., 'seq6' => 16],
          'moyenne_annuelle' => 14.5,
          'appreciation' => 'Assez Bien'
        ],
        ...
      ],
      'total_coef' => 4,
      'total_points' => 58
    ],
    ...
  ],
  'progression' => [
    'seq1' => 13.2,
    'seq2' => 13.5,
    'seq3' => 12.8,
    'seq4' => 13.0,
    'seq5' => 14.0,
    'seq6' => 15.0
  ],
  'moyenne_general' => 13.5
]
```

---

## 🔐 SÉCURITÉ & AUTORISATIONS

### Authentification
```php
// Toutes les méthodes vérifient:
private function verifierAuthentification() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php?action=connexion');
        exit();
    }
}
```

### Autorisation (Classes)
```php
// Admin peut accéder à toutes les classes
// Enseignant ne peut accéder qu'à ses classes

private function verifierClasseAutorisee($id_classe) {
    if ($_SESSION['role'] === 'admin') {
        return true;
    }
    // Vérifier si enseignant enseigne cette classe
    $stmt = $this->pdo->prepare("SELECT id FROM classe WHERE id = :id");
    $stmt->execute(['id' => $id_classe]);
    return $stmt->fetchColumn() !== false;
}
```

---

## ✨ POINTS CLÉS D'IMPLÉMENTATION

### 1. Pas de Doublons
Les bulletins utilisent `UNIQUE KEY (matricule, id_trimestre, id_sequence)` pour éviter les doublons

### 2. Formules Normalisées
Toutes les moyennes suivent les formules camerounaises officielles

### 3. Appréciations Automatiques
Pas de saisie manuelle, générées dynamiquement par la BD (`table appreciation`)

### 4. Responsive Design
Interface utilise Bootstrap 4 pour fonctionner sur tous les appareils

### 5. Impression Optimisée
CSS spécifique pour impression via `window.print()`

---

## 🚀 OPTIMISATIONS POSSIBLES

1. **Mise en Cache** : Cacher les bulletins générés
2. **Lazy Loading** : Charger les données à la demande
3. **Indexation** : Ajouter indexes BD sur colonnes fréquemment recherchées
4. **API REST** : Exposer via endpoints JSON pour mobiles
5. **Export PDF** : Intégrer FPDF pour génération automatique

---

Ce document technique explique l'architecture complète du système de bulletins camerounais primaire. Pour les détails spécifiques, consultez le code source ou la documentation `BULLETINS_PRIMAIRE.md`.
