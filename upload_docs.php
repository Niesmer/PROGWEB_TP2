<?php
// upload_document.php

require_once 'Ressources_communes.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {

    $client_id  = intval($_POST['code_client'] ?? 0);
    $nature_doc = $_POST['nature_doc'] ?? '';

    $file = $_FILES['document'];

    if ($client_id <= 0 || empty($nature_doc)) {
        $message = ['type' => 'error', 'text' => 'Veuillez sélectionner un client et une nature de document.'];
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => 'Erreur lors de l\'upload du fichier.'];
    } else {

        // Création du répertoire client si nécessaire
        $upload_dir = __DIR__ . '/files/uploads/client_' . $client_id;
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Génération d'un nom unique
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_name = uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . '/' . $unique_name;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            $message = ['type' => 'error', 'text' => 'Impossible de déplacer le fichier sur le serveur.'];
        } else {

            $mime_type = mime_content_type($target_path);
            $file_size = filesize($target_path);

            // Gestion automatique du code_devis
            $code_devis = null;
            if ($nature_doc === 'Devis') {
                // On crée un nouveau devis pour ce client
                $stmt = $db_connection->prepare("
                    INSERT INTO Devis (code_client, date_devis, montant_ht, montant_ttc)
                    VALUES (:code_client, NOW(), 0, 0)
                ");
                $stmt->execute([':code_client' => $client_id]);
                $code_devis = $db_connection->lastInsertId(); // récupère le code_devis auto-incrémenté
            }

            // Insertion dans Files
            $stmt = $db_connection->prepare("
                INSERT INTO Files (file_name, file_path, file_type, file_nature, file_size, code_client, code_devis)
                VALUES (:file_name, :file_path, :file_type, :file_nature, :file_size, :code_client, :code_devis)
            ");

            $stmt->execute([
                ':file_name'   => $file['name'],
                ':file_path'   => $target_path,
                ':file_type'   => $mime_type,
                ':file_nature' => $nature_doc,
                ':file_size'   => $file_size,
                ':code_client' => $client_id,
                ':code_devis'  => $code_devis
            ]);

            $message = ['type' => 'success', 'text' => 'Fichier uploadé avec succès !'];
        }
    }
}

// Récupérer les clients pour le select
$clients = $db_connection->query("SELECT code_client, nom, prenom FROM Clients ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Upload Document - GED</title>
    <style>
        .alert-close { float: right; cursor: pointer; font-weight: bold; font-size: 1.2rem; line-height: 1; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
<script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
<main class="min-h-screen py-8">
    <div class="max-w-3xl px-4 mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Télécharger un document client</h1>
        <div class="flex justify-end mb-4">
               <a href="recherche_docs.php"
                class="inline-flex items-center px-5 py-3 text-sm font-medium text-white 
                        bg-primary-600 rounded-lg hover:bg-primary-700 
                        focus:ring-4 focus:ring-primary-300">
                    
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Rechercher document
                </a>
            </div>
        <?php if (!empty($message)): ?>
            <div id="messageBox"
                 class="mb-6 p-4 rounded-lg <?= $message['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <span class="alert-close" onclick="document.getElementById('messageBox').style.display='none';">&times;</span>
                <?= htmlspecialchars($message['text']) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 space-y-4">
            <!-- Client -->
            <label class="block">
                <span class="text-gray-700 dark:text-gray-300">Client</span>
                <select name="code_client" required class="mt-1 block w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">-- Sélectionnez un client --</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['code_client'] ?>"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <!-- Nature -->
            <label class="block">
                <span class="text-gray-700 dark:text-gray-300">Nature du document</span>
                <select name="nature_doc" required class="mt-1 block w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">-- Sélectionnez --</option>
                    <option value="CNI">CNI</option>
                    <option value="Devis">Devis</option>
                    <option value="Facture">Facture</option>
                    <option value="Mail">Mail</option>
                    <option value="Contrat">Contrat</option>
                </select>
            </label>

            <!-- Fichier -->
            <label class="block">
                <span class="text-gray-700 dark:text-gray-300">Fichier</span>
                <input type="file" name="document" required class="mt-1 block w-full p-3 rounded-lg border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </label>

            <button type="submit"
                class="w-full px-5 py-3 text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300">
                Uploader le document
            </button>
        </form>
    </div>
</main>
</body>
</html>
