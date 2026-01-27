<?php
require_once 'Ressources_communes.php';

$code_devis = intval($_POST['code_devis']);

$stmt = $db_connection->prepare("
    UPDATE Devis 
    SET status_devis = 2 
    WHERE code_devis = :code_devis
");
$stmt->execute([':code_devis' => $code_devis]);

header('Location: fiche_client.php?client='.$client_id.'&devis_validated=1');

exit();

