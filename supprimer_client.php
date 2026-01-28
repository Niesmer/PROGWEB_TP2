<?php
require_once 'Ressources_communes.php';

$code_client = $_POST['code_client'] ?? null;
$delete_type = $_POST['delete_type'] ?? null; // 'soft' ou 'hard'

if (!$code_client || !$delete_type) {
    header('Location: index.php?error=missing_params');
    exit;
}

if (!in_array($delete_type, ['soft', 'hard'])) {
    header('Location: index.php?error=invalid_delete_type');
    exit;
}

try {
    if ($delete_type === 'soft') {
        // Soft delete: Marquer le client comme archivé
        // Vérifier si la colonne deleted_at existe, sinon la créer
        try {
            $stmt = $db_connection->query("SHOW COLUMNS FROM Clients LIKE 'deleted_at'");
            if ($stmt->rowCount() == 0) {
                $db_connection->exec("ALTER TABLE Clients ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
            }
        } catch (Exception $e) {
            // La colonne existe déjà ou erreur, on continue
        }
        
        $stmt = $db_connection->prepare("UPDATE Clients SET deleted_at = NOW() WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        
        header('Location: index.php?client_archived=' . $code_client);
        exit;
        
    } else {
        // Hard delete: Supprimer définitivement
        $db_connection->beginTransaction();
        
        // Récupérer tous les devis du client
        $stmt = $db_connection->prepare("SELECT code_devis FROM Devis WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        $devis_list = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Supprimer les lignes de devis
        foreach ($devis_list as $code_devis) {
            $stmt = $db_connection->prepare("DELETE FROM Lignes_Devis WHERE code_devis = :code_devis");
            $stmt->execute([':code_devis' => $code_devis]);
        }
        
        // Récupérer et supprimer tous les fichiers du client
        $stmt = $db_connection->prepare("SELECT file_path FROM Files WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($files as $file) {
            $file_path = __DIR__ . '/' . $file['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Supprimer les entrées de fichiers
        $stmt = $db_connection->prepare("DELETE FROM Files WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        
        // Supprimer le répertoire du client
        $client_directory = __DIR__ . '/files/uploads/client_' . $code_client;
        if (is_dir($client_directory)) {
            $files = glob($client_directory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($client_directory);
        }
        
        // Supprimer le répertoire devis du client
        $devis_directory = __DIR__ . '/files/devis/client_' . $code_client;
        if (is_dir($devis_directory)) {
            $files = glob($devis_directory . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($devis_directory);
        }
        
        // Supprimer les devis
        $stmt = $db_connection->prepare("DELETE FROM Devis WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        
        // Supprimer le client
        $stmt = $db_connection->prepare("DELETE FROM Clients WHERE code_client = :code_client");
        $stmt->execute([':code_client' => $code_client]);
        
        $db_connection->commit();
        
        header('Location: index.php?client_deleted=' . $code_client);
        exit;
    }
    
} catch (Exception $e) {
    if ($db_connection->inTransaction()) {
        $db_connection->rollBack();
    }
    header('Location: index.php?error=delete_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>
