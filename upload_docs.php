<?php
// upload_document.php

require_once 'Ressources_communes.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {

    $client_id = intval($_POST['code_client'] ?? 0);
    $nature_doc = $_POST['nature_doc'] ?? '';

    $file = $_FILES['document'];

    if ($client_id <= 0 || empty($nature_doc)) {
        $message = ['type' => 'error', 'text' => 'Veuillez sélectionner un client et une nature de document.'];
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $message = ['type' => 'error', 'text' => 'Erreur lors de l\'upload du fichier.'];
    } else {

        // Création du répertoire client si nécessaire
        $upload_dir = __DIR__ . '/files/uploads/client_' . $client_id;
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

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
                ':file_name' => $file['name'],
                ':file_path' => $target_path,
                ':file_type' => $mime_type,
                ':file_nature' => $nature_doc,
                ':file_size' => $file_size,
                ':code_client' => $client_id,
                ':code_devis' => $code_devis
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
        .alert-close {
            float: right;
            cursor: pointer;
            font-weight: bold;
            font-size: 1.2rem;
            line-height: 1;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

    <!-- Navbar Flowbite -->
    <?php include("./components/navbar.php") ?>

    <main class="min-h-screen py-8">
        <div class="max-w-7xl px-4 mx-auto sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-5" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a href="./"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                            </svg>
                            Accueil
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" fill="none"
                                viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Upload
                                Document</span>
                        </div>
                    </li>
                </ol>
            </nav>
            
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Téléverser un document client</h1>

            <?php if (!empty($message)): ?>
                <?php if ($message['type'] === 'success'): ?>
                    <div id="alert-success"
                        class="flex items-center p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400"
                        role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium"><?= htmlspecialchars($message['text']) ?></div>
                        <button type="button"
                            class="ms-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-green-400 dark:hover:bg-gray-700"
                            data-dismiss-target="#alert-success" aria-label="Close"
                            onclick="this.closest('[role=alert]').remove()">
                            <span class="sr-only">Fermer</span>
                            <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                        </button>
                    </div>
                <?php else: ?>
                    <div id="alert-error"
                        class="flex items-center p-4 mb-4 text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
                        role="alert">
                        <svg class="flex-shrink-0 w-4 h-4" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                        </svg>
                        <span class="sr-only">Info</span>
                        <div class="ms-3 text-sm font-medium"><?= htmlspecialchars($message['text']) ?></div>
                        <button type="button"
                            class="ms-auto -mx-1.5 -my-1.5 bg-red-50 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"
                            data-dismiss-target="#alert-error" aria-label="Close"
                            onclick="this.closest('[role=alert]').remove()">
                            <span class="sr-only">Fermer</span>
                            <svg class="w-3 h-3" aria-hidden="true" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data"
                class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 space-y-6">
                <!-- Client -->
                <div>
                    <label for="code_client" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Client
                        *</label>
                    <select id="code_client" name="code_client" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="">-- Sélectionnez un client --</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['code_client'] ?>"><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Nature -->
                <div>
                    <label for="nature_doc" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nature
                        du document *</label>
                    <select id="nature_doc" name="nature_doc" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        <option value="">-- Sélectionnez --</option>
                        <option value="CNI">CNI</option>
                        <option value="Devis">Devis</option>
                        <option value="Facture">Facture</option>
                        <option value="Mail">Mail</option>
                        <option value="Contrat">Contrat</option>
                    </select>
                </div>

                <!-- Fichier -->
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="file_input">Fichier
                        *</label>
                    <input
                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                        id="file_input" name="document" type="file" required>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">Formats acceptés : PDF, images, documents
                    </p>
                </div>

                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 inline me-2" aria-hidden="true" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                    </svg>
                    Uploader le document
                </button>
            </form>
        </div>
    </main>
</body>

</html>