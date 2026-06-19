<?php
// modeles/Classe.php

require_once __DIR__ . '/bdd.php';

class Classe {
    private $db;

    public function __construct() {
        $this->db = Bdd::connexion();
    }

    // Récupérer toutes les classes simples (Utile pour la gestion)
    public function getAllClasses() {
        $query = "SELECT * FROM classe ORDER BY nom_classe ASC";
        $stmt = $this->db->query($query);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    // Ton ancienne méthode existante pour les effectifs
    public function getEffectifsParClasse() {
        $query = "SELECT c.id, c.nom_classe, COUNT(e.matricule) AS effectif 
                  FROM classe c 
                  LEFT JOIN eleve e ON c.id = e.id_classe 
                  GROUP BY c.id, c.nom_classe 
                  ORDER BY c.id ASC";
        $stmt = $this->db->query($query);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    // [NOUVEAU] Ajouter une classe
    public function ajouter($nom_classe) {
        $query = "INSERT INTO classe (nom_classe) VALUES (:nom_classe)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':nom_classe' => $nom_classe]);
    }

    // [NOUVEAU] Modifier une classe
    public function modifier($id, $nom_classe) {
        $query = "UPDATE classe SET nom_classe = :nom_classe WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':nom_classe' => $nom_classe,
            ':id' => $id
        ]);
    }

    // [NOUVEAU] Supprimer une classe
    public function supprimer($id) {
        $query = "DELETE FROM classe WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }
}
?>