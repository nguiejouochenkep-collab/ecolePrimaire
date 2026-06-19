<?php
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    
    // Sélectionner l'année 2025_2026 (id=1)
    $stmt = $pdo->prepare("SELECT libelle FROM annee_scolaire WHERE id = :id");
    $stmt->execute(['id' => 1]);
    $libelle = $stmt->fetchColumn();
    
    $_SESSION['annee_scolaire_id'] = 1;
    $_SESSION['annee_scolaire_libelle'] = $libelle;
    
    // Marquer en base comme active
    $pdo->prepare("UPDATE annee_scolaire SET statut = 'inactive'")->execute();
    $pdo->prepare("UPDATE annee_scolaire SET statut = 'active' WHERE id = :id")->execute(['id' => 1]);
    
    echo "Année sélectionnée: " . htmlspecialchars($libelle) . "\n";
    echo "ID annee: 1\n";
    echo "Session annee_scolaire_id: " . $_SESSION['annee_scolaire_id'] . "\n";
    echo "Session annee_scolaire_libelle: " . $_SESSION['annee_scolaire_libelle'] . "\n";
    
    // Redirection vers promotion
    header('Location: index.php?action=promotion');
    exit();
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}
?>
