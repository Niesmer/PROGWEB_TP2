<?php
require_once 'Ressources_communes.php';

$code_devis = $_POST['code_devis'] ?? null;
$code_client = $_POST['code_client'] ?? null;
$email_to = $_POST['email_to'] ?? null;
$email_subject = $_POST['email_subject'] ?? 'Votre devis POLY Industrie';
$email_body = $_POST['email_body'] ?? '';

if (!$code_devis || !$code_client || !$email_to) {
    header('Location: fiche_client.php?client=' . urlencode($code_client ?? '') . '&error=missing_params');
    exit;
}

// Validate email
if (!filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
    header('Location: fiche_client.php?client=' . urlencode($code_client) . '&error=invalid_email');
    exit;
}

// Définir le chemin du PDF
$pdf_directory = __DIR__ . '/files/devis/client_' . $code_client;
$pdf_filename = '';

// Récupérer le nom du client pour le nom de fichier
$stmt = $db_connection->prepare("SELECT c.nom, c.prenom FROM Clients c WHERE c.code_client = :code_client");
$stmt->execute([':code_client' => $code_client]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if ($client) {
    $pdf_filename = 'devis_' . $code_devis . '_' . $client['nom'] . '_' . $client['prenom'] . '_' . date('Y-m-d') . '.pdf';
}

$pdf_path = $pdf_directory . '/' . $pdf_filename;

// Vérifier si le PDF existe déjà, sinon le générer
if (!file_exists($pdf_path)) {
    $pdf_path = genererPDFDevis($code_devis, $code_client, $db_connection);
    
    if (!$pdf_path) {
        header('Location: fiche_client.php?client=' . urlencode($code_client) . '&error=pdf_generation_failed');
        exit;
    }
}

// Envoyer l'email avec le PDF en pièce jointe
try {
    sendEmail($email_to, $email_subject, nl2br(htmlspecialchars($email_body)), [$pdf_path]);
    
    // Mettre à jour le statut du devis en "Imprimé" si c'est encore "En cours"
    $stmt = $db_connection->prepare("SELECT status_devis FROM Devis WHERE code_devis = :code_devis");
    $stmt->execute([':code_devis' => $code_devis]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($devis && $devis['status_devis'] == DEVIS_STATUS::ONGOING->value) {
        $stmt = $db_connection->prepare("UPDATE Devis SET status_devis = :status WHERE code_devis = :code_devis");
        $stmt->execute([
            ':status' => DEVIS_STATUS::PRINTED->value,
            ':code_devis' => $code_devis
        ]);
    }
    
    header('Location: fiche_client.php?client=' . urlencode($code_client) . '&email_sent=' . $code_devis);
    exit;
} catch (Exception $e) {
    header('Location: fiche_client.php?client=' . urlencode($code_client) . '&error=email_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>
