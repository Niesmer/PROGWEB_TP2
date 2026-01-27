<?php
require_once 'Ressources_communes.php';

$id_file = $_POST['id_file'] ?? null;

if (!$id_file) {
    header('Location: recherche_docs.php?error=missing_id');
    exit;
}

try {
    // Récupérer les informations du fichier
    $stmt = $db_connection->prepare("SELECT file_path FROM Files WHERE id_file = :id_file");
    $stmt->execute([':id_file' => $id_file]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        header('Location: recherche_docs.php?error=file_not_found');
        exit;
    }
    
    // Supprimer le fichier physique
    $file_path = __DIR__ . '/' . $file['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Supprimer l'entrée de la base de données
    $stmt = $db_connection->prepare("DELETE FROM Files WHERE id_file = :id_file");
    $stmt->execute([':id_file' => $id_file]);
    
    header('Location: recherche_docs.php?file_deleted=1');
    exit;
    
} catch (Exception $e) {
    header('Location: recherche_docs.php?error=delete_failed&message=' . urlencode($e->getMessage()));
    exit;
}
?>
