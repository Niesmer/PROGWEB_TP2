<?php
require 'Ressources_communes.php';

// Récupération des paramètres GET
$code_client  = $_GET['client'] ?? '';
$date_debut   = $_GET['date-debut'] ?? '';
$date_fin     = $_GET['date-fin'] ?? '';
$montant_min  = $_GET['montant-min'] ?? '';
$montant_max  = $_GET['montant-max'] ?? '';

// Construction de la requête SQL
$sql_devis = "
    SELECT d.code_devis, d.date_devis, d.montant_ht, d.status_devis, c.nom, c.prenom
    FROM Devis d
    INNER JOIN Clients c ON d.code_client = c.code_client
    WHERE 1=1
";

$params = [];

if (!empty($code_client)) {
    $sql_devis .= " AND d.code_client = :code_client";
    $params[':code_client'] = $code_client;
}

if (!empty($date_debut)) {
    $sql_devis .= " AND d.date_devis >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $sql_devis .= " AND d.date_devis <= :date_fin";
    $params[':date_fin'] = $date_fin;
}

if (!empty($montant_min)) {
    $sql_devis .= " AND d.montant_ht >= :montant_min";
    $params[':montant_min'] = $montant_min;
}

if (!empty($montant_max)) {
    $sql_devis .= " AND d.montant_ht <= :montant_max";
    $params[':montant_max'] = $montant_max;
}

$sql_devis .= " ORDER BY d.date_devis DESC";

$query = $db_connection->prepare($sql_devis);
$query->execute($params);
$devis_list = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Recherche Devis - SAV</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-50 dark:bg-gray-900">
<script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

<main class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4">

        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Recherche de devis</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Recherche de devis par date et/ou montant HT
            </p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <form method="GET" class="space-y-4">
                <input type="hidden" name="client" value="<?= htmlspecialchars($code_client) ?>">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Date début
                        </label>
                        <input type="date" name="date-debut" value="<?= htmlspecialchars($date_debut) ?>"
                            class="mt-1 w-full p-3 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Date fin
                        </label>
                        <input type="date" name="date-fin" value="<?= htmlspecialchars($date_fin) ?>"
                            class="mt-1 w-full p-3 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Montant min (€)
                        </label>
                        <input type="number" step="0.01" name="montant-min"
                            value="<?= htmlspecialchars($montant_min) ?>"
                            class="mt-1 w-full p-3 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Montant max (€)
                        </label>
                        <input type="number" step="0.01" name="montant-max"
                            value="<?= htmlspecialchars($montant_max) ?>"
                            class="mt-1 w-full p-3 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="px-6 py-3 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                        Rechercher
                    </button>
                </div>
            </form>
        </div>

        <!-- Résultats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Résultats
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        (<?= count($devis_list) ?> devis trouvé<?= count($devis_list) > 1 ? 's' : '' ?>)
                    </span>
                </h2>
            </div>

            <?php if (count($devis_list) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3">Code</th>
                                <th class="px-6 py-3">Client</th>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Montant HT</th>
                                <th class="px-6 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($devis_list as $devis): ?>
                                <tr class="border-b dark:border-gray-700 hover:bg-primary-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($devis['code_devis']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?= htmlspecialchars($devis['nom'] . ' ' . $devis['prenom']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($devis['date_devis']))) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?= number_format($devis['montant_ht'], 2, ',', ' ') ?> €
                                    </td>
                                    <td class="px-6 py-4">
                                        <?= htmlspecialchars($devis['status_devis']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="px-6 py-12 text-center">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                        Aucun devis trouvé
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Essayez de modifier vos critères de recherche.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
