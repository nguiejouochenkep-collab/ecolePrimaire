<?php
// controleurs/traitement_versement.php

require_once __DIR__ . '/../connexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $matricule_eleve = trim(htmlspecialchars($_POST['matricule_eleve']));
    $montant = floatval($_POST['montant']);
    $motif = trim(htmlspecialchars($_POST['motif']));
    $date_paiement = date('Y-m-d'); 

    // Calcul automatique de l'année scolaire en cours (Ex: En juin 2026 -> "2025-2026")
    $mois_actuel = intval(date('m'));
    $annee_actuelle = intval(date('Y'));
    if ($mois_actuel >= 9) {
        $annee_scolaire = $annee_actuelle . '-' . ($annee_actuelle + 1);
    } else {
        $annee_scolaire = ($annee_actuelle - 1) . '-' . $annee_actuelle;
    }

    if (!empty($matricule_eleve) && $montant > 0 && !empty($motif)) {
        try {
            // 1. SÉCURITÉ : Vérifier si l'élève existe
            $stmt_verif_eleve = $pdo->prepare("SELECT matricule FROM eleve WHERE matricule = ?");
            $stmt_verif_eleve->execute([$matricule_eleve]);
            
            if ($stmt_verif_eleve->rowCount() === 0) {
                header("Location: index.php?action=formulaire_versement&erreur=eleve_inconnu");
                exit();
            }

            // 2. CONTRÔLE ANTI-DOUBLON : L'élève a-t-il déjà payé ce motif pour cette année ?
            $stmt_double = $pdo->prepare("SELECT id_paiement FROM paiement WHERE matricule_eleve = ? AND motif = ? AND annee_scolaire = ?");
            $stmt_double->execute([$matricule_eleve, $motif, $annee_scolaire]);

            if ($stmt_double->rowCount() > 0) {
                // Erreur : paiement déjà effectué !
                header("Location: index.php?action=formulaire_versement&erreur=deja_paye");
                exit();
            }

            // 3. Génération d'un numéro de reçu unique (Ex: REC-20260602-1432)
            $numero_recu = "REC-" . date('YmdHis') . "-" . rand(10, 99);

            // 4. Tout est bon, on insère l'enregistrement
            $stmt_insert = $pdo->prepare("INSERT INTO paiement (matricule_eleve, montant, motif, annee_scolaire, date_paiement, numero_recu) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->execute([$matricule_eleve, $montant, $motif, $annee_scolaire, $date_paiement, $numero_recu]);
            
            $id_paiement = $pdo->lastInsertId();

            // 5. REDIRECTION VERS LA GÉNÉRATION DU REÇU PDF
            header("Location: index.php?action=generer_recu&id=" . $id_paiement);
            exit();
            
        } catch (PDOException $e) {
            die("Erreur lors du traitement : " . $e->getMessage());
        }
    } else {
        header("Location: index.php?action=formulaire_versement&erreur=champs_invalides");
        exit();
    }
} else {
    header("Location: index.php?action=dashboard");
    exit();
}