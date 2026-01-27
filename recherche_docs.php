<?php
// recherche_document.php
require_once 'Ressources_communes.php';

$type_recherche = $_POST['file_type'] ?? '';
$nature_recherche = $_POST['file_nature_search'] ?? '';
$client_recherche = $_POST['client_search'] ?? '';

$sql = "SELECT f.*, c.nom, c.prenom 
        FROM Files f
        JOIN Clients c ON f.code_client = c.code_client
        WHERE 1=1";
$params = [];

if (!empty($type_recherche)) {
    $sql .= " AND f.file_type LIKE :file_type";
    $params['file_type'] = "%$type_recherche%";
}
if (!empty($nature_recherche)) {
    $sql .= " AND f.file_nature LIKE :file_nature";
    $params['file_nature'] = "%$nature_recherche%";
}
if (!empty($client_recherche)) {
    $sql .= " AND (c.nom LIKE :client OR c.prenom LIKE :client)";
    $params['client'] = "%$client_recherche%";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recherche Documents</title>
</head>
<body>
<h2>Recherche de documents</h2>
<form method="post">
    Nom ou prénom du client: <input type="text" name="client_search" value="<?= htmlspecialchars($client_recherche) ?>"><br><br>
    Type du document (pdf, image, etc): <input type="text" name="file_type" value="<?= htmlspecialchars($type_recherche) ?>"><br><br>
    Nature du document:
    <select name="file_nature_search">
        <option value="">-- Tous --</option>
        <option value="CNI" <?= ($nature_recherche=='CNI')?'selected':'' ?>>CNI</option>
        <option value="Devis" <?= ($nature_recherche=='Devis')?'selected':'' ?>>Devis</option>
        <option value="Contrat" <?= ($nature_recherche=='Contrat')?'selected':'' ?>>Contrat</option>
        <option value="Autre" <?= ($nature_recherche=='Autre')?'selected':'' ?>>Autre</option>
    </select><br><br>
    <input type="submit" value="Rechercher">
</form>

<?php if(!empty($documents)): ?>
    <h3>Résultats:</h3>
    <ul>
        <?php foreach($documents as $doc): ?>
            <li>
                <?= htmlspecialchars($doc['file_name']) ?> 
                (<?= htmlspecialchars($doc['file_type']) ?> / <?= htmlspecialchars($doc['file_nature']) ?>) 
                - Client: <?= htmlspecialchars($doc['nom'].' '.$doc['prenom']) ?>
                - <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank">Ouvrir</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Aucun document trouvé.</p>
<?php endif; ?>

<p><a href="upload_document.php">Uploader un nouveau document</a></p>
</body>
</html>
