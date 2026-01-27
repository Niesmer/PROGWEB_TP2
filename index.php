<?php
require 'Ressources_communes.php';

$search = $_GET['search'] ?? '';
$filter_forme = $_GET['forme'] ?? '';
$filter_motif = $_GET['motif'] ?? '';
$filter_pays = $_GET['pays'] ?? '';
$filter_start_membership = $_GET['start-membership'] ?? '';

// Récupérer les options pour les filtres
$formes_juridiques = $db_connection->query("SELECT code_forme, libelle FROM Formes_Juridiques ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
$motifs = $db_connection->query("SELECT code_motif, libelle FROM Motifs ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
$pays_list = $db_connection->query("SELECT code_pays, libelle FROM Pays ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);


// Fonction pour générer l'URL sans un paramètre spécifique
function buildUrlWithoutParam($paramToRemove)
{
    $params = $_GET;
    unset($params[$paramToRemove]);
    return '?' . http_build_query($params);
}

// Récupérer les libellés des filtres actifs
$active_forme_label = '';
if (!empty($filter_forme)) {
    foreach ($formes_juridiques as $forme) {
        if ($forme['code_forme'] == $filter_forme) {
            $active_forme_label = $forme['libelle'];
            break;
        }
    }
}

$active_motif_label = '';
if (!empty($filter_motif)) {
    foreach ($motifs as $motif) {
        if ($motif['code_motif'] == $filter_motif) {
            $active_motif_label = $motif['libelle'];
            break;
        }
    }
}

$active_pays_label = '';
if (!empty($filter_pays)) {
    foreach ($pays_list as $pays) {
        if ($pays['code_pays'] === $filter_pays) {
            $active_pays_label = $pays['libelle'];
            break;
        }
    }
}

$active_date_label = '';
if (!empty($filter_start_membership)) {
    $active_date_label = date('d/m/Y', strtotime($filter_start_membership));
}

$has_active_filters = !empty($search) || !empty($filter_forme) || !empty($filter_motif) || !empty($filter_pays) || !empty($filter_start_membership);

// Construction de la requête avec filtres
$sql_clients = "
    SELECT 
        c.code_client,
        c.nom,
        c.prenom,
        c.date_naissance,
        c.num_sec_soc,
        c.date_entree,
        p.libelle AS pays,
        m.libelle AS motif,
        f.libelle AS forme_juridique
    FROM Clients c
    LEFT JOIN Pays p ON c.code_pays = p.code_pays
    LEFT JOIN Motifs m ON c.code_motif = m.code_motif
    LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
    WHERE deleted_at IS NULL
";

$params = [];

if (!empty($search)) {
    $sql_clients .= " AND (c.nom LIKE :search OR c.prenom LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filter_forme)) {
    $sql_clients .= " AND c.code_forme = :forme";
    $params[':forme'] = $filter_forme;
}

if (!empty($filter_motif)) {
    $sql_clients .= " AND c.code_motif = :motif";
    $params[':motif'] = $filter_motif;
}

if (!empty($filter_pays)) {
    $sql_clients .= " AND c.code_pays = :pays";
    $params[':pays'] = $filter_pays;
}

if (!empty($filter_start_membership)) {
    $sql_clients .= " AND c.date_entree >= :start_membership";
    $params[':start_membership'] = $filter_start_membership;
}

$sql_clients .= " ORDER BY c.nom, c.prenom";

$query = $db_connection->prepare($sql_clients);
$query->execute($params);
$clients = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Recherche Client - SAV</title>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

    <!-- Navbar Flowbite -->
    <?php include("./components/navbar.php") ?>

    <main class="min-h-screen py-8">
        <div class="max-w-7xl px-4 mx-auto sm:px-6 lg:px-8">
            <!-- En-tête avec breadcrumb Flowbite -->
            <nav class="flex mb-5" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                    <li class="inline-flex items-center">
                        <a href="./"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                            <svg class="w-3 h-3 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z" />
                            </svg>
                            Accueil
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Recherche
                                Client</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- En-tête avec Badge -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-2">
                            Recherche Client
                        </h1>
                        <p class="text-base text-gray-600 dark:text-gray-400">
                            Sélectionnez un client pour créer un nouveau devis SAV
                        </p>
                    </div>
                    <span
                        class="inline-flex items-center bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                        <svg class="w-3 h-3 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z" />
                        </svg>
                        <?= count($clients) ?> Clients
                    </span>
                </div>
            </div>

            <!-- Bouton GED avec style Flowbite amélioré -->
            <div class="flex justify-end mb-6 gap-4">
                <a href="upload_docs.php"
                    class="inline-flex items-center text-white bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 hover:bg-gradient-to-br focus:ring-4 focus:outline-none focus:ring-blue-300 dark:focus:ring-blue-800 shadow-lg shadow-blue-500/50 dark:shadow-lg dark:shadow-blue-800/80 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                    </svg>
                    Gestion Électronique de Documents
                </a>
                <a href="recherche_docs.php"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800 inline-flex items-center">
                    <svg class="w-4 h-4 me-2" aria-hidden="true" fill="none" viewBox="0 0 20 20">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                    </svg>
                    Rechercher document
                </a>
            </div>

            <!-- Card de recherche et filtres avec style Flowbite -->
            <div
                class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 mb-6">
                <form method="GET" class="space-y-6">
                    <!-- Barre de recherche Flowbite -->
                    <div>
                        <label for="default-search"
                            class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Rechercher</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                            </div>
                            <input type="search" id="default-search" name="search"
                                value="<?= htmlspecialchars($search) ?>"
                                class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Rechercher par nom ou prénom..." />
                            <button type="submit"
                                class="text-white absolute end-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                                </svg>
                                <span class="sr-only">Rechercher</span>
                            </button>
                        </div>
                    </div>

                    <!-- Filtres avec Select Flowbite -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Filtre Forme Juridique -->
                        <div>
                            <label for="forme" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                <svg class="w-4 h-4 inline me-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                    <path fill-rule="evenodd"
                                        d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                                        clip-rule="evenodd" />
                                </svg>
                                Forme Juridique
                            </label>
                            <select id="forme" name="forme"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Toutes les formes</option>
                                <?php foreach ($formes_juridiques as $forme): ?>
                                    <option value="<?= $forme['code_forme'] ?>" <?= $filter_forme == $forme['code_forme'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($forme['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtre Motif -->
                        <div>
                            <label for="motif" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                <svg class="w-4 h-4 inline me-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                                Motif
                            </label>
                            <select id="motif" name="motif"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Tous les motifs</option>
                                <?php foreach ($motifs as $motif): ?>
                                    <option value="<?= $motif['code_motif'] ?>" <?= $filter_motif == $motif['code_motif'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($motif['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtre Pays -->
                        <div>
                            <label for="pays" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                <svg class="w-4 h-4 inline me-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Pays
                            </label>
                            <select id="pays" name="pays"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Tous les pays</option>
                                <?php foreach ($pays_list as $pays): ?>
                                    <option value="<?= $pays['code_pays'] ?>" <?= $filter_pays === $pays['code_pays'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pays['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtre Date -->
                        <div>
                            <label for="start-membership"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                <svg class="w-4 h-4 inline me-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd" />
                                </svg>
                                Date d'entrée depuis
                            </label>
                            <input type="date" id="start-membership" name="start-membership"
                                value="<?= htmlspecialchars($filter_start_membership ?? '1950-01-01') ?>"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                        </div>
                    </div>

                    <!-- Badges des filtres actifs avec style Flowbite -->
                    <?php if ($has_active_filters): ?>
                        <div class="flex flex-wrap items-center gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Filtres actifs:</span>

                            <?php if (!empty($search)): ?>
                                <a href="<?= buildUrlWithoutParam('search') ?>"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 transition-colors">
                                    <svg class="w-3 h-3 me-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    "<?= htmlspecialchars($search) ?>"
                                    <button type="button"
                                        class="inline-flex items-center p-0.5 ms-1 text-blue-400 bg-transparent rounded-sm hover:bg-blue-200 hover:text-blue-900 dark:hover:bg-blue-800 dark:hover:text-blue-300">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_forme_label)): ?>
                                <a href="<?= buildUrlWithoutParam('forme') ?>"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-900 dark:text-purple-300 dark:hover:bg-purple-800 transition-colors">
                                    Forme: <?= htmlspecialchars($active_forme_label) ?>
                                    <button type="button"
                                        class="inline-flex items-center p-0.5 ms-1 text-purple-400 bg-transparent rounded-sm hover:bg-purple-200 hover:text-purple-900 dark:hover:bg-purple-800 dark:hover:text-purple-300">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_motif_label)): ?>
                                <a href="<?= buildUrlWithoutParam('motif') ?>"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 transition-colors">
                                    Motif: <?= htmlspecialchars($active_motif_label) ?>
                                    <button type="button"
                                        class="inline-flex items-center p-0.5 ms-1 text-green-400 bg-transparent rounded-sm hover:bg-green-200 hover:text-green-900 dark:hover:bg-green-800 dark:hover:text-green-300">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_pays_label)): ?>
                                <a href="<?= buildUrlWithoutParam('pays') ?>"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900 dark:text-yellow-300 dark:hover:bg-yellow-800 transition-colors">
                                    Pays: <?= htmlspecialchars($active_pays_label) ?>
                                    <button type="button"
                                        class="inline-flex items-center p-0.5 ms-1 text-yellow-400 bg-transparent rounded-sm hover:bg-yellow-200 hover:text-yellow-900 dark:hover:bg-yellow-800 dark:hover:text-yellow-300">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_date_label)): ?>
                                <a href="<?= buildUrlWithoutParam('start-membership') ?>"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800 hover:bg-pink-200 dark:bg-pink-900 dark:text-pink-300 dark:hover:bg-pink-800 transition-colors">
                                    Depuis: <?= htmlspecialchars($active_date_label) ?>
                                    <button type="button"
                                        class="inline-flex items-center p-0.5 ms-1 text-pink-400 bg-transparent rounded-sm hover:bg-pink-200 hover:text-pink-900 dark:hover:bg-pink-800 dark:hover:text-pink-300">
                                        <svg class="w-2 h-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </a>
                            <?php endif; ?>

                            <a href="./"
                                class="inline-flex items-center text-sm font-medium text-red-600 hover:underline dark:text-red-500">
                                <svg class="w-3.5 h-3.5 me-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                Effacer tous
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table des résultats avec style Flowbite -->
            <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
                <div
                    class="flex flex-col px-4 py-3 space-y-3 lg:flex-row lg:items-center lg:justify-between lg:space-y-0 lg:space-x-4">
                    <div class="flex items-center flex-1 space-x-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Résultats de la recherche
                        </h2>
                        <span
                            class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                            <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z" />
                            </svg>
                            <?= count($clients) ?> trouvé<?= count($clients) > 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>

                <?php if (count($clients) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Nom</th>
                                    <th scope="col" class="px-4 py-3">Prénom</th>
                                    <th scope="col" class="px-4 py-3">Forme Juridique</th>
                                    <th scope="col" class="px-4 py-3">Pays</th>
                                    <th scope="col" class="px-4 py-3">Date d'entrée</th>
                                    <th scope="col" class="px-4 py-3">Motif</th>
                                    <th scope="col" class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr onclick="window.location='fiche_client.php?client=<?= $client['code_client'] ?>'"
                                        class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer transition-all duration-200">
                                        <th scope="row"
                                            class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            <?= htmlspecialchars($client['nom']) ?>
                                        </th>
                                        <td class="px-4 py-3">
                                            <?= htmlspecialchars($client['prenom']) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($client['forme_juridique']): ?>
                                                <span
                                                    class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                                    <?= htmlspecialchars($client['forme_juridique']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($client['pays']): ?>
                                                <span
                                                    class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                                                    <?= htmlspecialchars($client['pays']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 font-medium">
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($client['date_entree']))) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php if ($client['motif']): ?>
                                                <span
                                                    class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">
                                                    <?= htmlspecialchars($client['motif']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                                            <button onclick="ouvrirModalSuppressionClient(<?= $client['code_client'] ?>, '<?= htmlspecialchars($client['nom']) ?>', '<?= htmlspecialchars($client['prenom']) ?>')" 
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                        <svg class="w-20 h-20 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Aucun client trouvé</h3>
                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                            Aucun résultat ne correspond à vos critères de recherche.
                        </p>
                        <a href="./"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-center text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-3.5 h-3.5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 18 18">
                                <path
                                    d="M3 7H1a1 1 0 0 0-1 1v8a2 2 0 0 0 4 0V8a1 1 0 0 0-1-1Zm12.954 0H12l1.558-4.5a1.778 1.778 0 0 0-3.331-1.06A24.859 24.859 0 0 1 6 6.8v9.586h.114C8.223 16.969 11.015 18 13.6 18c1.4 0 1.592-.526 1.88-1.317l2.354-7A2 2 0 0 0 15.954 7Z" />
                            </svg>
                            Réinitialiser les filtres
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal de confirmation de suppression client -->
    <?php
    $modal_id = 'modalSuppressionClient';
    include './components/modal_client_deletion.php';
    ?>

    <script>
        function ouvrirModalSuppressionClient(codeClient, nom, prenom) {
            document.getElementById('codeClientASupprimer').value = codeClient;
            document.getElementById('nomClientASupprimer').textContent = nom + ' ' + prenom;
            const modal = document.getElementById('modalSuppressionClient');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    </script>
</body>

</html>