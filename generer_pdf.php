<?php
require_once 'Ressources_communes.php';

$code_devis = $_POST['code_devis'] ?? null;
$code_client = $_POST['code_client'] ?? null;

if (!$code_devis || !$code_client) {
    die('Paramètres manquants');
}

// Générer le PDF en utilisant la fonction partagée
$pdf_path = genererPDFDevis($code_devis, $code_client, $db_connection);

if (!$pdf_path) {
    die('Erreur lors de la génération du PDF');
}

// Afficher le PDF dans le navigateur
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="devis_' . $code_devis . '.pdf"');
header('Content-Length: ' . filesize($pdf_path));
readfile($pdf_path);
?>