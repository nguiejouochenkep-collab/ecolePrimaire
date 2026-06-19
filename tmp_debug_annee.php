<?php
try {
     = new PDO('mysql:host=localhost;dbname=gestion_ecole;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
     = ->query('SELECT id, libelle, statut FROM annee_scolaire ORDER BY id DESC');
    echo "=== ANNEES ===\n";
    foreach (->fetchAll(PDO::FETCH_ASSOC) as ) {
        echo json_encode() . "\n";
    }
     = ->query('SELECT id, matricule_eleve, id_classe, id_annee FROM inscription ORDER BY id');
    echo "=== INSCRIPTIONS ===\n";
    foreach (->fetchAll(PDO::FETCH_ASSOC) as ) {
        echo json_encode() . "\n";
    }
} catch (Exception ) {
    echo 'ERROR: ' . ->getMessage() . "\n";
}
?>
