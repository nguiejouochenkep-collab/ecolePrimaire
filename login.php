<?php
// 1. Démarrage de la session pour pouvoir enregistrer les informations de l'utilisateur
session_start();

// 2. Inclusion du fichier de connexion à la base de données (qui contient ta variable $pdo)
require_once 'connexion.php';

// 3. Vérification que le formulaire a bien été soumis en méthode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération et nettoyage des données saisies (enlève les espaces vides inutiles)
    $login_saisi = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password_saisi = isset($_POST['password']) ? trim($_POST['password']) : '';

    // Vérification que les champs ne sont pas vides
    if (!empty($login_saisi) && !empty($password_saisi)) {
        try {
            // 4. Préparation de la requête SQL pour chercher l'utilisateur par son LOGIN
            // (Sécurisé contre les injections SQL grâce aux marqueurs nominatifs)
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = :login");
            $stmt->execute(['login' => $login_saisi]);
            
            // Récupération de la ligne correspondante
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // 5. Vérification : Est-ce que l'utilisateur existe et est-ce que le mot de passe correspond ?
            $isPasswordValid = false;
            if ($user) {
                if (password_verify($password_saisi, $user['mot_de_passe'])) {
                    $isPasswordValid = true;
                } elseif (!empty($user['salt']) && password_verify($password_saisi . $user['salt'], $user['mot_de_passe'])) {
                    $isPasswordValid = true;
                } elseif ($user['mot_de_passe'] === $password_saisi) {
                    $isPasswordValid = true;
                }

                // Si l'ancien mot de passe était stocké en clair ou avec un salt personnalisé, migrer vers password_hash
                if ($isPasswordValid && !password_verify($password_saisi, $user['mot_de_passe'])) {
                    $hash = password_hash($password_saisi, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE utilisateur SET mot_de_passe = :hash WHERE login = :login");
                    $update->execute(['hash' => $hash, 'login' => $login_saisi]);
                }
            }

            if ($user && $isPasswordValid) {
                // 6. Régénération de l'ID de session par sécurité
                session_regenerate_id(true);

                // 7. Enregistrement des variables de session indispensables pour ton dashboard
                $_SESSION['login'] = $user['login'];   // Ex: 'admin' ou 'enseignant'
                $_SESSION['role']  = $user['role'];    // Ex: 'admin' ou 'enseignant'
                
                // Initialisation du timestamp d'activité pour la sécurité des 5 minutes
                $_SESSION['derniere_activite'] = time();

                // 8. Redirection vers le tableau de bord
                header("Location: dashboard.php");
                exit();

            } else {
                // Identifiants incorrects (soit le login n'existe pas, soit le mot de passe est faux)
                header("Location: index.php?erreur=identifiants_incorrects");
                exit();
            }

        } catch (PDOException $e) {
            // En cas d'erreur avec la base de données
            die("Erreur lors de la tentative de connexion : " . $e->getMessage());
        }
    } else {
        // Si l'utilisateur a soumis le formulaire avec des champs vides
        header("Location: index.php?erreur=champs_vides");
        exit();
    }
} else {
    // Si quelqu'un tente d'accéder à login.php directement sans passer par le formulaire
    header("Location: index.php");
    exit();
}
?>