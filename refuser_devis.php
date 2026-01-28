<?php
require_once 'Ressources_communes.php';

$code_devis = intval($_POST['code_devis']);
$code_client = intval($_POST['code_client']);

$stmt = $db_connection->prepare("
    UPDATE Devis 
    SET status_devis = :status 
    WHERE code_devis = :code_devis
");
$stmt->execute([
    ':status' => DEVIS_STATUS::REJECTED->value,
    ':code_devis' => $code_devis
]);

header('Location: fiche_client.php?client='.$code_client.'&devis_refused=1');

exit();

