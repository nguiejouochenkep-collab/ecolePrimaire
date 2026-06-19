-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 04 juin 2026 à 13:16
-- Version du serveur : 8.0.31
-- Version de PHP : 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_ecole`
--

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

DROP TABLE IF EXISTS `administrateur`;
CREATE TABLE IF NOT EXISTS `administrateur` (
  `matricule` varchar(50) NOT NULL,
  `id_utilisateur` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`matricule`),
  UNIQUE KEY `unique_id_util_admin` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `administrateur`
--

INSERT INTO `administrateur` (`matricule`, `id_utilisateur`, `nom`, `prenom`, `telephone`) VALUES
('DIR-2026-A', 'ADMIN-01', 'Chenkep', 'Lynsha', '677000000');

-- --------------------------------------------------------

--
-- Structure de la table `affectation_enseignant`
--

DROP TABLE IF EXISTS `affectation_enseignant`;
CREATE TABLE IF NOT EXISTS `affectation_enseignant` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` varchar(50) NOT NULL,
  `id_classe` int NOT NULL,
  `nom_matiere` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `annee_scolaire`
--

DROP TABLE IF EXISTS `annee_scolaire`;
CREATE TABLE IF NOT EXISTS `annee_scolaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(20) NOT NULL,
  `statut` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `libelle` (`libelle`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `annee_scolaire`
--

INSERT INTO `annee_scolaire` (`id`, `libelle`, `statut`) VALUES
(1, '2025-2026', 'active'),
(2, '2024-2025', 'inactive');

-- --------------------------------------------------------

--
-- Structure de la table `archive`
--

DROP TABLE IF EXISTS `archive`;
CREATE TABLE IF NOT EXISTS `archive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_archive` varchar(100) DEFAULT NULL,
  `description` text,
  `date_archive` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `bulletin`
--

DROP TABLE IF EXISTS `bulletin`;
CREATE TABLE IF NOT EXISTS `bulletin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule_eleve` varchar(20) NOT NULL,
  `id_trimestre` int NOT NULL,
  `moyenne` decimal(5,2) DEFAULT NULL,
  `rang` int DEFAULT NULL,
  `appreciation` text,
  PRIMARY KEY (`id`),
  KEY `matricule_eleve` (`matricule_eleve`),
  KEY `id_trimestre` (`id_trimestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

DROP TABLE IF EXISTS `classe`;
CREATE TABLE IF NOT EXISTS `classe` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_classe` varchar(50) NOT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `capacite` int DEFAULT '50',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`id`, `nom_classe`, `niveau`, `capacite`, `created_at`) VALUES
(1, 'SIL', 'Section d Initiation à la Lecture', 50, '2026-05-27 10:53:07'),
(2, 'CP', 'Cours Préparatoire', 50, '2026-05-27 10:53:07'),
(3, 'CE1', 'Cours Élémentaire 1ère année', 50, '2026-05-27 10:53:07'),
(4, 'CE2', 'Cours Élémentaire 2ème année', 50, '2026-05-27 10:53:07'),
(5, 'CM1', 'Cours Moyen 1ère année', 50, '2026-05-27 10:53:07'),
(6, 'CM2', 'Cours Moyen 2ème année', 50, '2026-05-27 10:53:07');

-- --------------------------------------------------------

--
-- Structure de la table `eleve`
--

DROP TABLE IF EXISTS `eleve`;
CREATE TABLE IF NOT EXISTS `eleve` (
  `matricule` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `telephone_parent` varchar(20) DEFAULT NULL,
  `adresse` text,
  `id_classe` int NOT NULL,
  `statut_activite` varchar(20) NOT NULL DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`matricule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `eleve`
--

INSERT INTO `eleve` (`matricule`, `nom`, `prenom`, `date_naissance`, `sexe`, `telephone_parent`, `adresse`, `id_classe`, `created_at`) VALUES
('26EL001', 'Djoukeng', 'Raoul', '2016-08-01', 'M', '677667788', NULL, 0, '2026-06-01 09:30:56'),
('26EL196', 'magne', 'corine', '2015-10-10', 'F', '6 52 45 15 62', NULL, 0, '2026-06-01 10:03:11'),
('26EL282', 'Kamgo', 'jean', '2019-10-25', 'M', '655263546', NULL, 6, '2026-06-01 13:24:36'),
('26EL371', 'Noumssi', 'dariol', '2021-05-04', 'M', '6 89 45 12 45', NULL, 1, '2026-06-02 09:49:19'),
('26EL789', 'tanga', 'roger', '2019-05-05', 'M', '6 58 95 58 55', NULL, 4, '2026-06-03 12:33:26'),
('26EL839', 'Tafomo', 'Chantal', '2019-05-09', 'F', '677667788', NULL, 4, '2026-06-02 13:10:35'),
('26EL849', 'Tchomo', 'bertol', '2017-05-05', 'M', '6 95 62 45 23', NULL, 5, '2026-06-02 13:07:08'),
('26EL963', 'Tagni', 'Victor', '2016-06-24', 'M', '688223344', NULL, 0, '2026-06-01 09:41:35'),
('26EL981', 'Matoum', 'raissa', '2022-06-03', 'F', '6 85 46 48 62', NULL, 1, '2026-06-02 09:52:26'),
('TMP001', 'Tchoumi', 'Paul', '2019-01-15', 'M', '677111001', NULL, 1, '2026-06-01 10:51:40'),
('TMP002', 'Fouda', 'Marie', '2019-03-12', 'F', '677111002', NULL, 1, '2026-06-01 10:51:40'),
('TMP003', 'Nana', 'Jean', '2019-05-20', 'M', '677111003', NULL, 1, '2026-06-01 10:51:40'),
('TMP004', 'Kamga', 'Alice', '2019-06-18', 'F', '677111004', NULL, 1, '2026-06-01 10:51:40'),
('TMP005', 'Tamo', 'Brice', '2019-02-10', 'M', '677111005', NULL, 1, '2026-06-01 10:51:40'),
('TMP006', 'Mbia', 'Sandra cyndy', '2019-07-22', 'F', '677111006', NULL, 1, '2026-06-01 10:51:40'),
('TMP007', 'Ngue', 'Patrick', '2019-08-14', 'M', '677111007', NULL, 1, '2026-06-01 10:51:40'),
('TMP008', 'Tagne', 'Julie', '2019-09-09', 'F', '677111008', NULL, 1, '2026-06-01 10:51:40'),
('TMP009', 'Simo', 'Roger', '2019-10-11', 'M', '677111009', NULL, 1, '2026-06-01 10:51:40'),
('TMP010', 'Momo', 'Linda', '2019-11-25', 'F', '677111010', NULL, 1, '2026-06-01 10:51:40'),
('TMP011', 'Fotso', 'Kevin', '2018-01-15', 'M', '677111011', NULL, 2, '2026-06-01 10:51:40'),
('TMP012', 'Kemajou', 'Diane', '2018-02-10', 'F', '677111012', NULL, 2, '2026-06-01 10:51:40'),
('TMP013', 'Noumi', 'Eric', '2018-03-15', 'M', '677111013', NULL, 2, '2026-06-01 10:51:40'),
('TMP014', 'Mouafo', 'Chantal', '2018-04-20', 'F', '677111014', NULL, 2, '2026-06-01 10:51:40'),
('TMP015', 'Tchinda', 'David', '2018-05-12', 'M', '677111015', NULL, 2, '2026-06-01 10:51:40'),
('TMP016', 'Kenfack', 'Sonia', '2018-06-18', 'F', '677111016', NULL, 2, '2026-06-01 10:51:40'),
('TMP017', 'Fongang', 'Arnaud', '2018-07-05', 'M', '677111017', NULL, 2, '2026-06-01 10:51:40'),
('TMP018', 'Tsafack', 'Esther', '2018-08-21', 'F', '677111018', NULL, 2, '2026-06-01 10:51:40'),
('TMP019', 'Tchouangue', 'Joel', '2018-09-09', 'M', '677111019', NULL, 2, '2026-06-01 10:51:40'),
('TMP020', 'Nono', 'Grace', '2018-10-25', 'F', '677111020', NULL, 2, '2026-06-01 10:51:40');

-- --------------------------------------------------------

--
-- Structure de la table `enseignant`
--

DROP TABLE IF EXISTS `enseignant`;
CREATE TABLE IF NOT EXISTS `enseignant` (
  `id_utilisateur` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `statut` varchar(50) NOT NULL,
  `statut_activite` varchar(20) NOT NULL DEFAULT 'actif',
  PRIMARY KEY (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enseigner`
--

DROP TABLE IF EXISTS `enseigner`;
CREATE TABLE IF NOT EXISTS `enseigner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule_enseignant` varchar(50) NOT NULL,
  `id_matiere` int NOT NULL,
  `id_classe` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `matricule_enseignant` (`matricule_enseignant`),
  KEY `id_matiere` (`id_matiere`),
  KEY `id_classe` (`id_classe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `examen`
--

DROP TABLE IF EXISTS `examen`;
CREATE TABLE IF NOT EXISTS `examen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_examen` varchar(50) NOT NULL,
  `date_examen` date DEFAULT NULL,
  `id_trimestre` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_trimestre` (`id_trimestre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `frais_scolarite`
--

DROP TABLE IF EXISTS `frais_scolarite`;
CREATE TABLE IF NOT EXISTS `frais_scolarite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_classe` int NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_classe` (`id_classe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inscription`
--

DROP TABLE IF EXISTS `inscription`;
CREATE TABLE IF NOT EXISTS `inscription` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule_eleve` varchar(20) NOT NULL,
  `id_classe` int NOT NULL,
  `id_annee` int NOT NULL,
  `date_inscription` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `matricule_eleve` (`matricule_eleve`),
  KEY `id_classe` (`id_classe`),
  KEY `id_annee` (`id_annee`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `inscription`
--

INSERT INTO `inscription` (`id`, `matricule_eleve`, `id_classe`, `id_annee`, `date_inscription`) VALUES
(1, 'MAT-26059353', 2, 2, '2026-05-27'),
(2, 'MAT-26051474', 3, 2, '2026-05-27'),
(3, 'MAT-26057892', 4, 2, '2026-05-28'),
(4, 'MAT-26051375', 1, 2, '2026-05-28'),
(5, 'MAT-26052565', 5, 2, '2026-05-29');

--
-- Déclencheurs `inscription`
--
DROP TRIGGER IF EXISTS `apres_inscription_paiement_auto`;
DELIMITER $$
CREATE TRIGGER `apres_inscription_paiement_auto` AFTER INSERT ON `inscription` FOR EACH ROW BEGIN
    INSERT INTO paiement (matricule_eleve, montant, motif, annee_scolaire, date_paiement, numero_recu)
    VALUES (NEW.matricule_eleve, 7500, 'Frais d'inscription', '2025-2026', NOW(), CONCAT('REC-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '-AUTO'));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `matiere`
--

DROP TABLE IF EXISTS `matiere`;
CREATE TABLE IF NOT EXISTS `matiere` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_matiere` varchar(100) NOT NULL,
  `coefficient` int NOT NULL DEFAULT '1',
  `id_classe` int NOT NULL,
  `domaine` varchar(150) DEFAULT 'I. ENSEIGNEMENTS GENERAUX',
  PRIMARY KEY (`id`),
  -- Remplacement de l'unicité globale par unicité par (nom_matiere, id_classe)
  UNIQUE KEY `uniq_matiere_classe` (`nom_matiere`,`id_classe`),
  KEY `fk_matiere_classe` (`id_classe`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `matiere`
--

INSERT INTO `matiere` (`id`, `nom_matiere`, `coefficient`, `id_classe`, `domaine`) VALUES
(1, 'Mathematique', 2, 4, 'II. SCIENCE ET TECHNOLOGIE'),
(2, 'Anglais', 3, 4, 'I. LANGUES ET COMMUNICATION'),
(3, 'Copie', 2, 1, 'I. LANGUES ET COMMUNICATION'),
(4, 'M', 1, 1, 'I. LANGUES ET COMMUNICATION'),
(5, 'Francais', 2, 2, 'I. LANGUES ET COMMUNICATION'),
(6, 'calcul rapide', 1, 1, 'II. SCIENCE ET TECHNOLOGIE'),
(7, 'TIC', 2, 1, 'II. SCIENCE ET TECHNOLOGIE'),
(8, 'Entretient', 1, 1, 'III. SCIENCES HUMAINES'),
(9, 'TM', 1, 1, 'IV. L\'AVENTURE HUMAINE');

-- --------------------------------------------------------

--
-- Structure de la table `note`
--

DROP TABLE IF EXISTS `note`;
CREATE TABLE IF NOT EXISTS `note` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule_eleve` varchar(20) NOT NULL,
  `id_examen` int NOT NULL,
  `id_matiere` int NOT NULL,
  `id_trimestre` int NOT NULL,
  `valeur` decimal(5,2) NOT NULL,
  `observation` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `matricule_eleve` (`matricule_eleve`),
  KEY `id_examen` (`id_examen`),
  KEY `id_matiere` (`id_matiere`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `note`
--

INSERT INTO `note` (`id`, `matricule_eleve`, `id_examen`, `id_matiere`, `id_trimestre`, `valeur`, `observation`) VALUES
(1, 'TMP002', 0, 3, 1, '10.00', ''),
(2, 'TMP002', 0, 4, 1, '12.00', ''),
(3, 'TMP004', 0, 3, 1, '13.00', ''),
(4, 'TMP004', 0, 4, 1, '15.00', ''),
(5, 'TMP006', 0, 3, 1, '13.00', ''),
(6, 'TMP006', 0, 4, 1, '6.00', ''),
(7, 'TMP010', 0, 3, 1, '19.00', ''),
(8, 'TMP010', 0, 4, 1, '11.00', ''),
(9, 'TMP003', 0, 3, 1, '15.00', ''),
(10, 'TMP003', 0, 4, 1, '16.00', ''),
(11, 'TMP007', 0, 3, 1, '11.00', ''),
(12, 'TMP007', 0, 4, 1, '16.00', ''),
(13, 'TMP009', 0, 3, 1, '18.00', ''),
(14, 'TMP009', 0, 4, 1, '14.00', ''),
(15, 'TMP008', 0, 3, 1, '13.00', ''),
(16, 'TMP008', 0, 4, 1, '18.00', ''),
(17, 'TMP005', 0, 3, 1, '8.00', ''),
(18, 'TMP005', 0, 4, 1, '16.00', ''),
(19, 'TMP001', 0, 3, 1, '15.00', ''),
(20, 'TMP001', 0, 4, 1, '13.00', '');

-- --------------------------------------------------------

--
-- Structure de la table `paiement`
--

DROP TABLE IF EXISTS `paiement`;
CREATE TABLE IF NOT EXISTS `paiement` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `matricule_eleve` varchar(20) NOT NULL,
  `montant` int NOT NULL,
  `motif` varchar(100) NOT NULL,
  `annee_scolaire` varchar(20) NOT NULL,
  `date_paiement` date NOT NULL,
  `numero_recu` varchar(50) NOT NULL,
  PRIMARY KEY (`id_paiement`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `paiement`
--

INSERT INTO `paiement` (`id_paiement`, `matricule_eleve`, `montant`, `motif`, `annee_scolaire`, `date_paiement`, `numero_recu`) VALUES
(1, '26EL282', 25000, 'Scolarité - Tranche 1', '2025-2026', '2026-06-02', 'REC-20260602151303-70'),
(2, '26EL282', 3000, 'Frais d\'inscription', '2025-2026', '2026-06-02', 'REC-20260602155748-15'),
(3, '26EL849', 3000, 'Frais d\'inscription', '2025-2026', '2026-06-03', 'REC-20260603085045-10'),
(4, '26EL849', 25000, 'Scolarité - Tranche 1', '2025-2026', '2026-06-03', 'REC-20260603090629-51');

-- --------------------------------------------------------

--
-- Structure de la table `trimestre`
--

DROP TABLE IF EXISTS `trimestre`;
CREATE TABLE IF NOT EXISTS `trimestre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Données par défaut pour les trimestres
--
INSERT INTO `trimestre` (`id`, `nom`) VALUES
(1, '1er Trimestre'),
(2, '2e Trimestre'),
(3, '3e Trimestre');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

DROP TABLE IF EXISTS `utilisateur`;
CREATE TABLE IF NOT EXISTS `utilisateur` (
  `id_utilisateur` varchar(50) NOT NULL,
  `login` varchar(50) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('admin','enseignant') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `login`, `mot_de_passe`, `role`, `created_at`) VALUES
('ADMIN-01', 'admin', 'admin123', 'admin', '2026-06-04 12:28:12');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `administrateur`
--
ALTER TABLE `administrateur`
  ADD CONSTRAINT `fk_admin_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `affectation_enseignant`
--
ALTER TABLE `affectation_enseignant`
  ADD CONSTRAINT `fk_affectation_enseignant_prof` FOREIGN KEY (`id_utilisateur`) REFERENCES `enseignant` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `enseignant`
--
ALTER TABLE `enseignant`
  ADD CONSTRAINT `fk_enseignant_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
