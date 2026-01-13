<?php
$db_connection = new PDO("mysql:host=localhost", "root", "");

# Permet de créer un nouvel utilisateur user1 avec tout les privilèges
$db_connection->query("CREATE USER IF NOT EXISTS 'user1'@'localhost' IDENTIFIED BY 'hcetylop'");
$db_connection->query("GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost' WITH GRANT OPTION");

# On recrée une connection au serveur Mysql en se connectant avec le nouvel utilisateur
$db_connection = new PDO("mysql:host=localhost", "user1", "hcetylop");

$db_connection->query("CREATE DATABASE IF NOT EXISTS poly_php;");
$db_connection->query("USE poly_php");

$sql = false;
if (file_exists('data.sql')) {
    $sql = file_get_contents('data.sql');
}
if ($sql !== false) {
    try {
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db_connection->exec($statement);
            }
        }
    } catch (PDOException $e) {
    }
}

// Récupérer les filtres depuis l'URL
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

$has_active_filters = !empty($search) || !empty($filter_forme) || !empty($filter_motif) || !empty($filter_pays);

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
    WHERE 1=1
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

    <main class="min-h-screen py-8">
        <div class="max-w-7xl px-4 mx-auto">
            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Recherche Client</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Sélectionnez un client pour créer un nouveau devis SAV
                </p>
            </div>

            <!-- Zone de recherche et filtres -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="space-y-4">
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Barre de recherche -->
                        <div class="flex-1">
                            <label for="search" class="sr-only">Rechercher</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>"
                                    class="block w-full p-3 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                                    placeholder="Rechercher par nom ou prénom...">
                            </div>
                        </div>

                        <!-- Filtres déroulants -->
                        <div class="flex flex-wrap gap-3">
                            <!-- Filtre Forme Juridique -->
                            <select name="forme"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Indifférent</option>
                                <?php foreach ($formes_juridiques as $forme): ?>
                                    <option value="<?= $forme['code_forme'] ?>" <?= $filter_forme == $forme['code_forme'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($forme['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Filtre Motif -->
                            <select name="motif"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Indifférent</option>
                                <?php foreach ($motifs as $motif): ?>
                                    <option value="<?= $motif['code_motif'] ?>" <?= $filter_motif == $motif['code_motif'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($motif['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Filtre Pays -->
                            <select name="pays"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Indifférent</option>
                                <?php foreach ($pays_list as $pays): ?>
                                    <option value="<?= $pays['code_pays'] ?>" <?= $filter_pays === $pays['code_pays'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pays['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input value="<?= htmlspecialchars($filter_start_membership ?? '1950-01-01') ?>" type="date" name="start-membership" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 p-3 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />

                            <!-- Bouton Rechercher -->
                            <button type="submit"
                                class="px-5 py-3 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300">
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Rechercher
                            </button>
                        </div>
                    </div>

                    <!-- Tags des filtres actifs -->
                    <?php if ($has_active_filters): ?>
                        <div class="flex flex-wrap gap-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Filtres actifs:</span>

                            <?php if (!empty($search)): ?>
                                <a href="<?= buildUrlWithoutParam('search') ?>"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200 hover:bg-primary-200">
                                    Recherche: "<?= htmlspecialchars($search) ?>"
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_forme_label)): ?>
                                <a href="<?= buildUrlWithoutParam('forme') ?>"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200">
                                    Forme: <?= htmlspecialchars($active_forme_label) ?>
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_motif_label)): ?>
                                <a href="<?= buildUrlWithoutParam('motif') ?>"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200">
                                    Motif: <?= htmlspecialchars($active_motif_label) ?>
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <?php if (!empty($active_pays_label)): ?>
                                <a href="<?= buildUrlWithoutParam('pays') ?>"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 hover:bg-yellow-200">
                                    Pays: <?= htmlspecialchars($active_pays_label) ?>
                                    <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <a href="./" class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 underline">
                                Effacer tous les filtres
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Résultats -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Résultats de la recherche
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                            (<?= count($clients) ?> client<?= count($clients) > 1 ? 's' : '' ?>
                            trouvé<?= count($clients) > 1 ? 's' : '' ?>)
                        </span>
                    </h2>
                </div>

                <?php if (count($clients) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Nom</th>
                                    <th scope="col" class="px-6 py-3">Prénom</th>
                                    <th scope="col" class="px-6 py-3">Forme Juridique</th>
                                    <th scope="col" class="px-6 py-3">Pays</th>
                                    <th scope="col" class="px-6 py-3">Date d'entrée</th>
                                    <th scope="col" class="px-6 py-3">Motif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                    <tr onclick="window.location='fiche_client.php?client=<?= $client['code_client'] ?>'"
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-primary-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                            <?= htmlspecialchars($client['nom']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($client['prenom']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($client['forme_juridique'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($client['pays'] ?? '-') ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($client['date_entree']))) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?= htmlspecialchars($client['motif'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun client trouvé</h3>
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