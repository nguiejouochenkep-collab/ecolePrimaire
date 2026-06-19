<?php
require_once __DIR__ . '/modeles/bdd.php';
require_once __DIR__ . '/modeles/Eleve.php';

$pdo = Bdd::connexion();
$eleveModel = new Eleve();

$libelle = isset($_GET['libelle']) ? $_GET['libelle'] : '2029-2030-TEST';

// Get new year id
$stmt = $pdo->prepare('SELECT id FROM annee_scolaire WHERE libelle = :libelle LIMIT 1');
$stmt->execute(['libelle' => $libelle]);
$new = $stmt->fetch();
if (!$new) {
    echo json_encode(['error' => 'Année non trouvée: ' . $libelle]);
    exit;
}
$new_id = $new['id'];

// Find previous year (id < new_id, latest)
$stmt = $pdo->prepare('SELECT id, libelle FROM annee_scolaire WHERE id < :id ORDER BY id DESC LIMIT 1');
$stmt->execute(['id' => $new_id]);
$prev = $stmt->fetch();
$prev_id = $prev ? $prev['id'] : null;

// Inscriptions in new year
$stmt = $pdo->prepare('SELECT matricule_eleve, id_classe FROM inscription WHERE id_annee = :id_annee');
$stmt->execute(['id_annee' => $new_id]);
$ins_new = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inscriptions in prev year
$ins_prev = [];
if ($prev_id) {
    $stmt = $pdo->prepare('SELECT matricule_eleve, id_classe FROM inscription WHERE id_annee = :id_annee');
    $stmt->execute(['id_annee' => $prev_id]);
    $ins_prev = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Compute previous-year students with moyenne < 9.5
$prev_moins_95 = [];
foreach ($ins_prev as $row) {
    $mat = $row['matricule_eleve'];
    $moy = $eleveModel->calculerMoyenneAnnuellePourAnnee($mat, $prev_id);
    if ($moy !== null && $moy < 9.5) {
        $prev_moins_95[$mat] = ['matricule' => $mat, 'moyenne' => $moy, 'id_classe' => $row['id_classe']];
    }
}

// Build sets
$set_new = array_column($ins_new, 'matricule_eleve');
$set_prev95 = array_keys($prev_moins_95);

$missing_in_new = array_values(array_diff($set_prev95, $set_new));
$extra_in_new = array_values(array_diff($set_new, $set_prev95));

// Teacher counts
$stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM enseignant WHERE statut_activite = 'actif'");
$enseignants = $stmt->fetch();
$count_enseignants = $enseignants['cnt'];

// Payments total for new year
$stmt = $pdo->prepare("SELECT IFNULL(SUM(montant),0) AS total FROM paiement WHERE annee_scolaire = :libelle");
$stmt->execute(['libelle' => $libelle]);
$total_paiements = $stmt->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'libelle' => $libelle,
    'new_id' => $new_id,
    'prev' => $prev,
    'inscriptions_new_count' => count($ins_new),
    'inscriptions_prev_count' => count($ins_prev),
    'prev_moyenne_lt_9_5_count' => count($prev_moins_95),
    'prev_moyenne_lt_9_5_samples' => array_values(array_slice($prev_moins_95,0,10)),
    'missing_in_new' => $missing_in_new,
    'extra_in_new' => $extra_in_new,
    'enseignants_actifs' => $count_enseignants,
    'paiements_total_new' => (float)$total_paiements,
], JSON_PRETTY_PRINT);

?>