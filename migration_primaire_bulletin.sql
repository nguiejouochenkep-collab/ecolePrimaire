-- ============================================================
-- MIGRATION : Structure de bulletins camerounais au PRIMAIRE
-- Adaptation pour trimestriel (2 séquences) + annuel (6 séquences)
-- ============================================================

-- 1. TABLE DES SÉQUENCES (remplace l'usage direct d'id_examen)
CREATE TABLE IF NOT EXISTS `sequence` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_sequence` int NOT NULL COMMENT '1-6 pour l\'année',
  `nom` varchar(50) NOT NULL COMMENT 'Séquence 1, 2, ..., 6',
  `id_trimestre` int NOT NULL COMMENT 'Trimestre auquel appartient cette séquence',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_trimestre` (`id_trimestre`),
  UNIQUE KEY `uniq_seq_trimestre` (`numero_sequence`, `id_trimestre`),
  FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertion des 6 séquences
INSERT INTO `sequence` (`numero_sequence`, `nom`, `id_trimestre`) VALUES
(1, 'Séquence 1', 1),
(2, 'Séquence 2', 1),
(3, 'Séquence 3', 2),
(4, 'Séquence 4', 2),
(5, 'Séquence 5', 3),
(6, 'Séquence 6', 3);

-- 2. TABLE DES GROUPES/DOMAINES PRIMAIRE
CREATE TABLE IF NOT EXISTS `groupe_matiere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_groupe` varchar(100) NOT NULL COMMENT 'Ex: Enseignements Fondamentaux',
  `ordre_affichage` int DEFAULT '1',
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_nom_groupe` (`nom_groupe`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertion des 3 groupes du primaire camerounais
INSERT INTO `groupe_matiere` (`nom_groupe`, `ordre_affichage`, `description`) VALUES
('I. ENSEIGNEMENTS FONDAMENTAUX', 1, 'Mathématiques, Français, Langue'),
('II. ÉVEIL', 2, 'Histoire, Géographie, Sciences, Civisme'),
('III. VIE PRATIQUE ET SPORT', 3, 'Dessin, Chant, Éducation Physique');

-- 3. TABLE D'APPRÉCIATION (système automatisé)
CREATE TABLE IF NOT EXISTS `appreciation` (
  `id` int NOT NULL AUTO_INCREMENT,
  `note_min` decimal(5,2) NOT NULL,
  `note_max` decimal(5,2) NOT NULL,
  `libelle` varchar(50) NOT NULL COMMENT 'Ex: Très Faible, Faible, Passable',
  `couleur` varchar(20) DEFAULT '#000000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_range` (`note_min`, `note_max`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insertion des niveaux d'appréciation (pour notes sur 20)
INSERT INTO `appreciation` (`note_min`, `note_max`, `libelle`, `couleur`) VALUES
(0, 5, 'Très Faible', '#FF0000'),
(5.01, 10, 'Faible', '#FF6600'),
(10.01, 13, 'Passable', '#FFAA00'),
(13.01, 16, 'Assez Bien', '#00BB00'),
(16.01, 20, 'Excellent', '#0066FF');

-- 4. MODIFICATION de la table MATIERE pour lier aux groupes primaire
ALTER TABLE `matiere` 
ADD COLUMN `id_groupe_matiere` int DEFAULT 1 AFTER `domaine`,
ADD FOREIGN KEY (`id_groupe_matiere`) REFERENCES `groupe_matiere` (`id`) ON DELETE SET NULL;

-- Mise à jour des matières existantes vers les groupes du primaire
UPDATE `matiere` SET `id_groupe_matiere` = 1 WHERE `domaine` LIKE '%LANGUES%' OR `domaine` LIKE '%FONDAMENTAL%';
UPDATE `matiere` SET `id_groupe_matiere` = 2 WHERE `domaine` LIKE '%HUMAINES%' OR `domaine` LIKE '%SCIENCE%';
UPDATE `matiere` SET `id_groupe_matiere` = 3 WHERE `domaine` LIKE '%AVENTURE%' OR `domaine` LIKE '%SPORT%';

-- 5. MODIFICATION de la table NOTE pour ajouter la séquence
ALTER TABLE `note` 
ADD COLUMN `id_sequence` int DEFAULT NULL AFTER `id_trimestre`,
ADD FOREIGN KEY (`id_sequence`) REFERENCES `sequence` (`id`) ON DELETE SET NULL;

-- 6. REMPLACEMENT de la table BULLETIN pour une meilleure structure
DROP TABLE IF EXISTS `bulletin_ancien`;
RENAME TABLE `bulletin` TO `bulletin_ancien`;

CREATE TABLE IF NOT EXISTS `bulletin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule_eleve` varchar(20) NOT NULL,
  `id_classe` int NOT NULL,
  `id_trimestre` int NOT NULL,
  `id_sequence` int DEFAULT NULL COMMENT 'NULL si annuel',
  `type_bulletin` enum('trimestriel','annuel') DEFAULT 'trimestriel',
  `moyenne_general` decimal(5,2) DEFAULT NULL,
  `rang` int DEFAULT NULL,
  `absences_justifiees` int DEFAULT 0,
  `absences_injustifiees` int DEFAULT 0,
  `retards` int DEFAULT 0,
  `decision` varchar(100) DEFAULT NULL COMMENT 'Tableau honneur, Encouragements, Promu, etc.',
  `appreciation_globale` text,
  `date_generation` timestamp DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `matricule_eleve` (`matricule_eleve`),
  KEY `id_classe` (`id_classe`),
  KEY `id_trimestre` (`id_trimestre`),
  KEY `id_sequence` (`id_sequence`),
  UNIQUE KEY `uniq_bulletin` (`matricule_eleve`, `id_trimestre`, `id_sequence`),
  FOREIGN KEY (`matricule_eleve`) REFERENCES `eleve` (`matricule`) ON DELETE CASCADE,
  FOREIGN KEY (`id_classe`) REFERENCES `classe` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sequence`) REFERENCES `sequence` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 7. TABLE DÉTAILS BULLETIN (notes par matière)
CREATE TABLE IF NOT EXISTS `bulletin_detail` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_bulletin` int NOT NULL,
  `id_matiere` int NOT NULL,
  `notes_seq` json DEFAULT NULL COMMENT '{"seq1": 15, "seq2": 12, ...}',
  `moyenne_matiere` decimal(5,2) DEFAULT NULL,
  `coefficient` int NOT NULL DEFAULT 1,
  `rang_matiere` int DEFAULT NULL COMMENT 'Rang dans la matière pour la classe',
  `note_max_classe` decimal(5,2) DEFAULT NULL,
  `note_min_classe` decimal(5,2) DEFAULT NULL,
  `appreciation` varchar(50) DEFAULT NULL,
  `observation` text,
  PRIMARY KEY (`id`),
  KEY `id_bulletin` (`id_bulletin`),
  KEY `id_matiere` (`id_matiere`),
  FOREIGN KEY (`id_bulletin`) REFERENCES `bulletin` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================
-- FIN DE MIGRATION
-- ============================================================
