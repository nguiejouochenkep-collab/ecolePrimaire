<?php
// migrate_passwords.php - À SUPPRIMER APRÈS UTILISATION

$host = "localhost";
$dbname = "gestion_ecole";
$user = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// 1. Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT id_utilisateur, login, mot_de_passe FROM utilisateur");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$compteur = 0;

foreach ($utilisateurs as $user) {
    $ancien_mdp = $user['mot_de_passe'];
    $login = $user['login'];
    $id = $user['id_utilisateur'];

    // Si le mot de passe est déjà un hash valide, on ne fait rien
    if (strpos($ancien_mdp, '$2y$') === 0 || strpos($ancien_mdp, '$2a$') === 0 || strpos($ancien_mdp, '$2b$') === 0) {
        echo "⏭️ Utilisateur '{$login}' : mot de passe déjà haché, ignoré<br>";
        continue;
    }

    // Hashage standard du mot de passe en clair
    $hash = password_hash($ancien_mdp, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = :hash WHERE id_utilisateur = :id");
    $update->execute(['hash' => $hash, 'id' => $id]);

    echo "✅ Utilisateur '{$login}' : mot de passe mis à jour<br>";
    $compteur++;
}

echo "<br><hr>";
echo "📊 Résumé : {$compteur} mot(s) de passe mis à jour<br>";

// 2. S'assurer que l'utilisateur admin a un mot de passe fort
$adminHash = password_hash('Admin123!', PASSWORD_DEFAULT);
$checkAdmin = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE login = 'admin'");
$checkAdmin->execute();

if ($checkAdmin->rowCount() == 0) {
    $insert = $pdo->prepare("INSERT INTO utilisateur (login, mot_de_passe, role) VALUES ('admin', :hash, 'admin')");
    $insert->execute(['hash' => $adminHash]);
    echo "✅ Utilisateur 'admin' créé avec le mot de passe 'Admin123!'<br>";
} else {
    $updateAdmin = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = :hash WHERE login = 'admin'");
    $updateAdmin->execute(['hash' => $adminHash]);
    echo "✅ Utilisateur 'admin' : mot de passe mis à jour avec 'Admin123!'<br>";
}

echo "<br><hr>";
echo "🔐 Informations de connexion :<br>";
echo "- Identifiant : admin<br>";
echo "- Mot de passe : Admin123!<br>";
echo "<br>";
echo "<a href='index.php?action=connexion'>➡️ Aller à la page de connexion</a>";
?>