<?php
require_once 'Ressources_communes.php';

$code_devis = $_POST['code_devis'] ?? null;
$code_client = $_POST['code_client'] ?? null;

if (!$code_devis || !$code_client) {
    header('Location: fiche_client.php?client=' . urlencode($code_client ?? '') . '&error=missing_params');
    exit;
}

try {
    $db_connection->beginTransaction();
    
    // Supprimer les lignes du devis
    $stmt = $db_connection->prepare("DELETE FROM Lignes_Devis WHERE code_devis = :code_devis");
    $stmt->execute([':code_devis' => $code_devis]);
    
    // Supprimer les fichiers associés au devis
    $stmt = $db_connection->prepare("SELECT file_path FROM Files WHERE code_devis = :code_devis");
    $stmt->execute([':code_devis' => $code_devis]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($files as $file) {
        $file_path = __DIR__ . '/' . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Supprimer les entrées de fichiers de la base de données
    $stmt = $db_connection->prepare("DELETE FROM Files WHERE code_devis = :code_devis");
    $stmt->execute([':code_devis' => $code_devis]);
    
    // Supprimer le PDF si existant
    $pdf_directory = __DIR__ . '/files/devis/client_' . $code_client;
    if (is_dir($pdf_directory)) {
        $files = glob($pdf_directory . '/devis_' . $code_devis . '_*.pdf');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    // Supprimer le devis
    $stmt = $db_connection->prepare("DELETE FROM Devis WHERE code_devis = :code_devis");
    $stmt->execute([':code_devis' => $code_devis]);
    
    $db_connection->commit();
    
    header('Location: fiche_client.php?client=' . urlencode($code_client) . '&devis_deleted=' . $code_devis);
    exit;
    
} catch (Exception $e) {
    if ($db_connection->inTransaction()) {
        $db_connection->rollBack();
    }
    header('Location: fiche_client.php?client=' . urlencode($code_client) . '&error=delete_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>
