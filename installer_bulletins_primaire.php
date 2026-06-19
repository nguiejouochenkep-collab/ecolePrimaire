#!/usr/bin/env php
<?php
/**
 * installer_bulletins_primaire.php
 * Script d'installation de la structure de bulletins camerounais primaire
 * 
 * Usage: php installer_bulletins_primaire.php
 */

echo "=== Installation Bulletins Primaire Camerounais ===\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "[✓] Connexion à la base de données réussie\n";

    // 1. CRÉER LA TABLE GROUPE_MATIERE
    echo "\n[1/7] Création de la table groupe_matiere...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `groupe_matiere` (
          `id` int NOT NULL AUTO_INCREMENT,
          `nom_groupe` varchar(100) NOT NULL,
          `ordre_affichage` int DEFAULT '1',
          `description` text,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_nom_groupe` (`nom_groupe`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    echo " [✓]\n";

    // Insérer les groupes de matières
    $pdo->exec("
        INSERT IGNORE INTO `groupe_matiere` (`nom_groupe`, `ordre_affichage`, `description`) VALUES
        ('I. ENSEIGNEMENTS FONDAMENTAUX', 1, 'Mathématiques, Français, Langue'),
        ('II. ÉVEIL', 2, 'Histoire, Géographie, Sciences, Civisme'),
        ('III. VIE PRATIQUE ET SPORT', 3, 'Dessin, Chant, Éducation Physique')
    ");
    echo "   Groupes insérés [✓]\n";

    // 2. CRÉER LA TABLE SEQUENCE
    echo "\n[2/7] Création de la table sequence...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sequence` (
          `id` int NOT NULL AUTO_INCREMENT,
          `numero_sequence` int NOT NULL,
          `nom` varchar(50) NOT NULL,
          `id_trimestre` int NOT NULL,
          `date_debut` date DEFAULT NULL,
          `date_fin` date DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `id_trimestre` (`id_trimestre`),
          UNIQUE KEY `uniq_seq_trimestre` (`numero_sequence`, `id_trimestre`),
          FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    echo " [✓]\n";

    // Insérer les séquences
    $pdo->exec("
        INSERT IGNORE INTO `sequence` (`numero_sequence`, `nom`, `id_trimestre`) VALUES
        (1, 'Séquence 1', 1),
        (2, 'Séquence 2', 1),
        (3, 'Séquence 3', 2),
        (4, 'Séquence 4', 2),
        (5, 'Séquence 5', 3),
        (6, 'Séquence 6', 3)
    ");
    echo "   Séquences insérées [✓]\n";

    // 3. CRÉER LA TABLE APPRECIATION
    echo "\n[3/7] Création de la table appreciation...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `appreciation` (
          `id` int NOT NULL AUTO_INCREMENT,
          `note_min` decimal(5,2) NOT NULL,
          `note_max` decimal(5,2) NOT NULL,
          `libelle` varchar(50) NOT NULL,
          `couleur` varchar(20) DEFAULT '#000000',
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_range` (`note_min`, `note_max`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    echo " [✓]\n";

    // Insérer les appréciations
    $pdo->exec("
        INSERT IGNORE INTO `appreciation` (`note_min`, `note_max`, `libelle`, `couleur`) VALUES
        (0, 5, 'Très Faible', '#FF0000'),
        (5.01, 10, 'Faible', '#FF6600'),
        (10.01, 13, 'Passable', '#FFAA00'),
        (13.01, 16, 'Assez Bien', '#00BB00'),
        (16.01, 20, 'Excellent', '#0066FF')
    ");
    echo "   Appréciations insérées [✓]\n";

    // 4. MODIFIER LA TABLE MATIERE
    echo "\n[4/7] Modification de la table matiere...";
    
    // Vérifier si la colonne existe déjà
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                         WHERE TABLE_SCHEMA = 'gestion_ecole' AND TABLE_NAME = 'matiere' AND COLUMN_NAME = 'id_groupe_matiere'");
    
    if (!$stmt->fetchColumn()) {
        $pdo->exec("
            ALTER TABLE `matiere` 
            ADD COLUMN `id_groupe_matiere` int DEFAULT 1,
            ADD FOREIGN KEY (`id_groupe_matiere`) REFERENCES `groupe_matiere` (`id`) ON DELETE SET NULL
        ");
        echo " [✓]\n";
    } else {
        echo " [SKIP] Colonne existante\n";
    }

    // 5. MODIFIER LA TABLE NOTE
    echo "\n[5/7] Modification de la table note...";
    
    // Vérifier si la colonne existe
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                         WHERE TABLE_SCHEMA = 'gestion_ecole' AND TABLE_NAME = 'note' AND COLUMN_NAME = 'id_sequence'");
    
    if (!$stmt->fetchColumn()) {
        $pdo->exec("
            ALTER TABLE `note` 
            ADD COLUMN `id_sequence` int DEFAULT NULL,
            ADD FOREIGN KEY (`id_sequence`) REFERENCES `sequence` (`id`) ON DELETE SET NULL
        ");
        echo " [✓]\n";
    } else {
        echo " [SKIP] Colonne existante\n";
    }

    // 6. CRÉER LA TABLE BULLETIN (réstructurée)
    echo "\n[6/7] Création de la table bulletin restructurée...";
    
    // Vérifier si une ancienne table existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'bulletin'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("DROP TABLE IF EXISTS `bulletin`");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bulletin` (
          `id` int NOT NULL AUTO_INCREMENT,
          `matricule_eleve` varchar(20) NOT NULL,
          `id_classe` int NOT NULL,
          `id_trimestre` int NOT NULL,
          `id_sequence` int DEFAULT NULL,
          `type_bulletin` enum('trimestriel','annuel') DEFAULT 'trimestriel',
          `moyenne_general` decimal(5,2) DEFAULT NULL,
          `rang` int DEFAULT NULL,
          `absences_justifiees` int DEFAULT 0,
          `absences_injustifiees` int DEFAULT 0,
          `retards` int DEFAULT 0,
          `decision` varchar(100) DEFAULT NULL,
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    echo " [✓]\n";

    // 7. CRÉER LA TABLE BULLETIN_DETAIL
    echo "\n[7/7] Création de la table bulletin_detail...";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bulletin_detail` (
          `id` int NOT NULL AUTO_INCREMENT,
          `id_bulletin` int NOT NULL,
          `id_matiere` int NOT NULL,
          `notes_seq` json DEFAULT NULL,
          `moyenne_matiere` decimal(5,2) DEFAULT NULL,
          `coefficient` int NOT NULL DEFAULT 1,
          `rang_matiere` int DEFAULT NULL,
          `note_max_classe` decimal(5,2) DEFAULT NULL,
          `note_min_classe` decimal(5,2) DEFAULT NULL,
          `appreciation` varchar(50) DEFAULT NULL,
          `observation` text,
          PRIMARY KEY (`id`),
          KEY `id_bulletin` (`id_bulletin`),
          KEY `id_matiere` (`id_matiere`),
          FOREIGN KEY (`id_bulletin`) REFERENCES `bulletin` (`id`) ON DELETE CASCADE,
          FOREIGN KEY (`id_matiere`) REFERENCES `matiere` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
    ");
    echo " [✓]\n";

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ Installation terminée avec succès!\n";
    echo str_repeat("=", 50) . "\n";

    echo "\n📋 Prochaines étapes:\n";
    echo "1. Mettez à jour vos matières existantes vers les groupes primaire\n";
    echo "2. Commencez à saisir les notes avec id_sequence\n";
    echo "3. Accédez aux bulletins via : index.php?action=bulletins\n";
    echo "\n📖 Documentation: Consultez BULLETINS_PRIMAIRE.md\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
?>
