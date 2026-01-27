<?php
require 'Ressources_communes.php';

// Récupération des filtres
$search_client = $_GET['search_client'] ?? '';
$search_file = $_GET['search_file'] ?? '';
$filter_type = $_GET['type'] ?? ''; 
$filter_nature = $_GET['nature'] ?? '';
$filter_upload_date = $_GET['upload_date'] ?? '';

// Récupérer les options pour le filtre nature (les types sont maintenant libres)
$natures_docs = $db_connection->query("SELECT DISTINCT file_nature FROM Files ORDER BY file_nature")->fetchAll(PDO::FETCH_COLUMN);

// Fonction pour générer l'URL sans un paramètre spécifique
function buildUrlWithoutParam($paramToRemove)
{
    $params = $_GET;
    unset($params[$paramToRemove]);
    return '?' . http_build_query($params);
}

// Construction de la requête avec filtres
$sql_docs = "
    SELECT 
        f.id_file,
        f.file_name,
        f.file_path,
        f.file_type,
        f.file_nature,
        f.upload_date,
        f.file_size,
        c.nom,
        c.prenom,
        d.code_devis,
        d.montant_ht
    FROM Files f
    LEFT JOIN Clients c ON f.code_client = c.code_client
    LEFT JOIN Devis d ON f.code_devis = d.code_devis
    WHERE 1=1
";

$params = [];

if (!empty($search_client)) {
    $sql_docs .= " AND (c.nom LIKE :search_client OR c.prenom LIKE :search_client)";
    $params[':search_client'] = '%' . $search_client . '%';
}

if (!empty($search_file)) {
    $sql_docs .= " AND f.file_name LIKE :search_file";
    $params[':search_file'] = '%' . $search_file . '%';
}

if (!empty($filter_type)) {
    $sql_docs .= " AND f.file_type LIKE :type";
    $params[':type'] = '%' . $filter_type . '%';
}

if (!empty($filter_nature)) {
    $sql_docs .= " AND f.file_nature = :nature";
    $params[':nature'] = $filter_nature;
}

if (!empty($filter_upload_date)) {
    $sql_docs .= " AND DATE(f.upload_date) = :upload_date";
    $params[':upload_date'] = $filter_upload_date;
}

$sql_docs .= " ORDER BY f.upload_date DESC";

$query = $db_connection->prepare($sql_docs);
$query->execute($params);
$documents = $query->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les clients pour autocomplétion
$all_clients = $db_connection->query("SELECT nom, prenom FROM Clients ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);
$client_list_js = json_encode(array_map(function($c) {
    return $c['nom'] . ' ' . $c['prenom'];
}, $all_clients));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Recherche Documents</title>
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
                <a href="./" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                    <svg class="w-3 h-3 me-2.5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                        <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                    </svg>
                    Accueil
                </a>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Recherche Documents</span>
                </div>
            </li>
        </ol>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Recherche de documents</h1>

    <!-- Formulaire de recherche Flowbite -->
    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 mb-6">
        <form method="GET" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label for="search_client" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Client</label>
                    <input type="text" id="search_client" name="search_client" placeholder="Nom ou prénom" 
                           value="<?= htmlspecialchars($search_client) ?>"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" list="client_suggestions" />
                    <datalist id="client_suggestions"></datalist>
                </div>

                <div>
                    <label for="search_file" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Document</label>
                    <input type="text" id="search_file" name="search_file" placeholder="Nom du fichier" 
                           value="<?= htmlspecialchars($search_file) ?>"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
                </div>

                <div>
                    <label for="type" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Type</label>
                    <input type="text" id="type" name="type" placeholder="Type de fichier" 
                           value="<?= htmlspecialchars($filter_type) ?>"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
                </div>

                <div>
                    <label for="nature" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nature</label>
                    <select id="nature" name="nature" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                        <option value="">Toutes</option>
                        <?php foreach ($natures_docs as $nature): ?>
                            <option value="<?= $nature ?>" <?= $filter_nature == $nature ? 'selected' : '' ?>><?= htmlspecialchars($nature) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="upload_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date d'upload</label>
                    <input type="date" id="upload_date" name="upload_date" value="<?= htmlspecialchars($filter_upload_date) ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4 me-2" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                    </svg>
                    Rechercher
                </button>
            </div>
        </form>
    </div>

    <!-- Résultats Table Flowbite -->
    <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col px-4 py-3 space-y-3 lg:flex-row lg:items-center lg:justify-between lg:space-y-0 lg:space-x-4">
            <div class="flex items-center flex-1 space-x-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Résultats</h2>
                <span class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                    <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm3.982 13.982a1 1 0 0 1-1.414 0l-3.274-3.274A1.012 1.012 0 0 1 9 10V6a1 1 0 0 1 2 0v3.586l2.982 2.982a1 1 0 0 1 0 1.414Z"/>
                    </svg>
                    <?= count($documents) ?> trouvé<?= count($documents) > 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <?php if (count($documents) > 0): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Nom du fichier</th>
                        <th class="px-6 py-3">Client</th>
                        <th class="px-6 py-3">Nature</th>
                        <th class="px-6 py-3">Type</th>
                        <th class="px-6 py-3">Date d'upload</th>
                        <th class="px-6 py-3">Taille (Ko)</th>
                        <th class="px-6 py-3">Devis associé</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"><?= htmlspecialchars($doc['file_name']) ?></th>
                        <td class="px-4 py-3"><?= htmlspecialchars($doc['nom'] . ' ' . $doc['prenom']) ?></td>
                        <td class="px-4 py-3">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                <?= htmlspecialchars($doc['file_nature']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">
                                <?= htmlspecialchars($doc['file_type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($doc['upload_date']))) ?></td>
                        <td class="px-4 py-3"><?= round($doc['file_size']/1024, 2) ?> Ko</td>
                        <td class="px-4 py-3"><?= $doc['code_devis'] ? '<span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-green-900 dark:text-green-300">#' . $doc['code_devis'] . '</span>' : '-' ?></td>
                        <td class="px-4 py-3 flex items-center gap-2">
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Ouvrir</a>
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="font-medium text-green-600 dark:text-green-500 hover:underline">Télécharger</a>
                            <button onclick="confirmerSuppressionFichier(<?= $doc['id_file'] ?>)" class="font-medium text-red-600 dark:text-red-500 hover:underline">Supprimer</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="flex flex-col items-center justify-center p-12">
                <svg class="w-20 h-20 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Aucun document trouvé</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Essayez de modifier vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</main>

<!-- Modal de suppression de fichier -->
<?php
$modal_id = 'modalSuppressionFichier';
$title = 'Êtes-vous sûr de vouloir supprimer ce fichier ?';
$message = '';
$form_action = 'supprimer_fichier.php';
$hidden_inputs = ['id_file' => 'idFichierASupprimer'];
$submit_label = 'Supprimer définitivement';
$submit_color = 'red';
include './components/modal_confirmation.php';
?>

<script>
    function confirmerSuppressionFichier(idFile) {
        document.getElementById('idFichierASupprimer').value = idFile;
        const modal = document.getElementById('modalSuppressionFichier');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Remplissage de la datalist pour autocomplétion
    const clients = <?= $client_list_js ?>;
    const dataList = document.getElementById('client_suggestions');

    clients.forEach(name => {
        const option = document.createElement('option');
        option.value = name;
        dataList.appendChild(option);
    });
</script>
</body>
</html>
