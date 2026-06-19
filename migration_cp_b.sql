-- Script de migration pour ajouter la classe CP B et les données associées
-- Date: 2026-06-06

-- 1. Supprimer la contrainte UNIQUE sur nom_classe pour permettre plusieurs sections
-- (CP, CP A, CP B, etc. avec des noms différents mais dans le même niveau)
-- Note: Si vous avez besoin de garder l'unicité, utilisez plutôt des noms différents

-- 2. Ajouter la classe CP B (si elle n'existe pas)
INSERT INTO `classe` (`nom_classe`, `niveau`, `capacite`, `created_at`) 
SELECT 'CP B', 'Cours Préparatoire - Groupe B', 50, NOW()
WHERE NOT EXISTS (SELECT 1 FROM classe WHERE nom_classe = 'CP B');

-- 3. Ajouter les matières pour CP B (copier de CP A)
INSERT INTO `matiere` (`nom_matiere`, `coefficient`, `id_classe`, `domaine`)
SELECT CONCAT(nom_matiere, ''), coefficient, 
       (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1), 
       domaine
FROM matiere 
WHERE id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP' LIMIT 1)
  AND NOT EXISTS (
    SELECT 1 FROM matiere m2 
    WHERE m2.nom_matiere = matiere.nom_matiere 
      AND m2.id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1)
  );

-- 4. Associer des élèves à CP B (les derniers élèves de CP)
-- Vérifiez et ajustez les matricules selon vos données
UPDATE eleve 
SET id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1)
WHERE id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP' LIMIT 1)
  AND matricule IN ('TMP011', 'TMP012', 'TMP013', 'TMP014', 'TMP015', 'TMP016', 'TMP017', 'TMP018', 'TMP019', 'TMP020');

-- 5. Ajouter quelques notes de test pour les élèves de CP B
-- Vérifiez les ID des matières avant d'exécuter cette partie
INSERT INTO `note` (`matricule_eleve`, `id_examen`, `id_matiere`, `id_trimestre`, `valeur`, `observation`)
SELECT 
  e.matricule,
  0,
  m.id,
  1,
  ROUND(RAND() * 20, 2),
  'Note de test'
FROM eleve e
CROSS JOIN matiere m
WHERE e.id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1)
  AND m.id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1)
  AND NOT EXISTS (
    SELECT 1 FROM note n 
    WHERE n.matricule_eleve = e.matricule 
      AND n.id_matiere = m.id
      AND n.id_trimestre = 1
  )
LIMIT 50;

-- 6. Afficher le résumé des modifications
SELECT 'Classes créées:' as 'Type';
SELECT CONCAT('- ', nom_classe) FROM classe WHERE nom_classe IN ('CP', 'CP B') ORDER BY nom_classe;

SELECT '' as '';
SELECT 'Élèves par classe:' as 'Type';
SELECT CONCAT(c.nom_classe, ': ', COUNT(e.matricule), ' élève(s)') as 'Répartition'
FROM classe c
LEFT JOIN eleve e ON c.id = e.id_classe
WHERE c.nom_classe IN ('CP', 'CP B')
GROUP BY c.id, c.nom_classe;

SELECT '' as '';
SELECT 'Matières disponibles pour CP B:' as 'Type';
SELECT CONCAT('- ', nom_matiere, ' (coef: ', coefficient, ')') FROM matiere 
WHERE id_classe = (SELECT id FROM classe WHERE nom_classe = 'CP B' LIMIT 1)
ORDER BY domaine, nom_matiere;
