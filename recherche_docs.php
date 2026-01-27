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

<main class="min-h-screen py-8">
<div class="max-w-7xl px-4 mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6">Recherche de documents</h1>

    <!-- Formulaire de recherche -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <form method="GET" class="space-y-4">
            <div class="flex flex-col md:flex-row gap-4">
                <input type="text" id="search_client" name="search_client" placeholder="Nom ou prénom client" 
                       value="<?= htmlspecialchars($search_client) ?>"
                       class="flex-1 p-3 rounded-lg border border-gray-300" list="client_suggestions" />

                <datalist id="client_suggestions"></datalist>

                <input type="text" name="search_file" placeholder="Nom du document" 
                       value="<?= htmlspecialchars($search_file) ?>"
                       class="flex-1 p-3 rounded-lg border border-gray-300" />

                <input type="text" name="type" placeholder="Type de fichier" 
                       value="<?= htmlspecialchars($filter_type) ?>"
                       class="p-3 rounded-lg border border-gray-300" />

                <select name="nature" class="p-3 rounded-lg border border-gray-300">
                    <option value="">Nature       </option>
                    <?php foreach ($natures_docs as $nature): ?>
                        <option value="<?= $nature ?>" <?= $filter_nature == $nature ? 'selected' : '' ?>><?= htmlspecialchars($nature) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="upload_date" value="<?= htmlspecialchars($filter_upload_date) ?>" class="p-3 rounded-lg border border-gray-300" />

                <button type="submit" class="px-5 py-3 bg-primary-600 text-white rounded-lg">Rechercher</button>
            </div>
        </form>
    </div>

    <!-- Résultats -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Résultats (<?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?>)
            </h2>
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
                    <tr class="hover:bg-gray-100 dark:hover:bg-gray-700">
                        <td class="px-6 py-4"><?= htmlspecialchars($doc['file_name']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($doc['nom'] . ' ' . $doc['prenom']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($doc['file_nature']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($doc['file_type']) ?></td>
                        <td class="px-6 py-4"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($doc['upload_date']))) ?></td>
                        <td class="px-6 py-4"><?= round($doc['file_size']/1024, 2) ?></td>
                        <td class="px-6 py-4"><?= $doc['code_devis'] ?? '-' ?></td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="text-blue-600">Ouvrir</a>
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="text-green-600">Télécharger</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="px-6 py-12 text-center">
                <p class="text-gray-500">Aucun document trouvé. Essayez de modifier vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</main>

<script>
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
