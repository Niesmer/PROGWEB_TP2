<?php
// Récupérer les filtres depuis l'URL
$search = $_GET['search'] ?? '';
$filter_forme = $_GET['forme'] ?? '';
$filter_motif = $_GET['motif'] ?? '';
$filter_pays = $_GET['pays'] ?? '';

// Récupérer les options pour les filtres
$formes_juridiques = $db_connection->query("SELECT code_forme, libelle FROM Formes_Juridiques ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
$motifs = $db_connection->query("SELECT code_motif, libelle FROM Motifs ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);
$pays_list = $db_connection->query("SELECT code_pays, libelle FROM Pays ORDER BY libelle")->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour générer l'URL sans un paramètre spécifique
function buildUrlWithoutParam($paramToRemove) {
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

$has_active_filters = !empty($search) || !empty($filter_forme) || !empty($filter_motif) || !empty($filter_pays);
?>

<section class="bg-gray-50 dark:bg-gray-900 h-screen flex items-center">
    <div class="max-w-7xl px-4 mx-auto lg:px-12 w-full">
        <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
            <div
                class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-10">
                <div class="w-full">
                    <form method="GET" class="flex gap-2 items-center">
                        <label for="simple-search" class="sr-only">Search</label>
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400"
                                    fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" id="simple-search" name="search" value="<?= htmlspecialchars($search) ?>"
                                class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                placeholder="Rechercher par nom ou prénom">
                        </div>
                        <button type="submit"
                            class="bg-primary-500 hover:bg-primary-800 text-white font-medium rounded-lg text-sm px-4 py-2 focus:ring-4 focus:outline-none focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">
                            Search
                        </button>
                        <button id="filterDropdownButton" data-dropdown-toggle="filterDropdown"
                            class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                            type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                class="h-4 w-4 mr-2 text-gray-400" viewbox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                    clip-rule="evenodd" />
                            </svg>
                            Motif
                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path clip-rule="evenodd" fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </button>
                        <div id="filterDropdown"
                            class="z-10 hidden w-64 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                            <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Motif</h6>
                            <ul class="space-y-2 text-sm" aria-labelledby="filterDropdownButton">
                                <li class="flex items-center">
                                    <input id="motif-all" type="radio" name="motif" value="" <?= $filter_motif === '' ? 'checked' : '' ?>
                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label for="motif-all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">Toutes</label>
                                </li>
                                <?php foreach ($motifs as $motif): ?>
                                <li class="flex items-center">
                                    <input id="motif-<?= $motif['code_motif'] ?>" type="radio" name="motif" value="<?= $motif['code_motif'] ?>" <?= $filter_motif == $motif['code_motif'] ? 'checked' : '' ?>
                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label for="motif-<?= $motif['code_motif'] ?>" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= htmlspecialchars($motif['libelle']) ?>
                                    </label>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <button id="filterCountryDropdownButton" data-dropdown-toggle="filterCountryDropdown"
                            class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                            type="button">
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true"
                                class="h-4 w-4 mr-2 text-gray-400" viewbox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                    clip-rule="evenodd" />
                            </svg>
                            Pays
                            <svg class="-mr-1 ml-1.5 w-5 h-5" fill="currentColor" viewbox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path clip-rule="evenodd" fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" />
                            </svg>
                        </button>
                        <div id="filterCountryDropdown"
                            class="z-10 hidden w-48 p-3 bg-white rounded-lg shadow dark:bg-gray-700">
                            <h6 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Choisissez le pays</h6>
                            <ul class="space-y-2 text-sm" aria-labelledby="filterCountryDropdownButton">
                                <li class="flex items-center">
                                    <input id="pays-all" type="radio" name="pays" value="" <?= $filter_pays === '' ? 'checked' : '' ?>
                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label for="pays-all" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">Tous</label>
                                </li>
                                <?php foreach ($pays_list as $pays): ?>
                                <li class="flex items-center">
                                    <input id="pays-<?= $pays['code_pays'] ?>" type="radio" name="pays" value="<?= $pays['code_pays'] ?>" <?= $filter_pays === $pays['code_pays'] ? 'checked' : '' ?>
                                        class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500">
                                    <label for="pays-<?= $pays['code_pays'] ?>" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= htmlspecialchars($pays['libelle']) ?>
                                    </label>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Filter Tags -->
            <?php if ($has_active_filters): ?>
            <div class="flex flex-wrap gap-2 px-4 pb-4">
                <?php if (!empty($search)): ?>
                <a href="<?= htmlspecialchars(buildUrlWithoutParam('search')) ?>" 
                   class="inline-flex items-center px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                    Recherche: <?= htmlspecialchars($search) ?>
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($filter_motif)): ?>
                <a href="<?= htmlspecialchars(buildUrlWithoutParam('motif')) ?>" 
                   class="inline-flex items-center px-3 py-1 text-sm font-medium text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-800 transition-colors">
                    Motif: <?= htmlspecialchars($active_motif_label) ?>
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($filter_pays)): ?>
                <a href="<?= htmlspecialchars(buildUrlWithoutParam('pays')) ?>" 
                   class="inline-flex items-center px-3 py-1 text-sm font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800 transition-colors">
                    Pays: <?= htmlspecialchars($active_pays_label) ?>
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
                <?php endif; ?>
                
                <!-- Clear all filters -->
                <a href="?" class="inline-flex items-center px-3 py-1 text-sm font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    Effacer tout
                    <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
            
            <?php include("./components/clients.php") ?>
        </div>
    </div>
</section>