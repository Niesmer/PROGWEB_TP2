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

// Récupérer le code client depuis l'URL
$code_client = $_GET['client'] ?? null;
$client_info = null;

if (!$code_client) {
    header('Location: recherche_client.php');
    exit;
}

$stmt = $db_connection->prepare("
    SELECT 
        c.*,
        p.libelle AS pays_libelle,
        f.libelle AS forme_libelle,
        m.libelle AS motif_libelle
    FROM Clients c
    LEFT JOIN Pays p ON c.code_pays = p.code_pays
    LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
    LEFT JOIN Motifs m ON c.code_motif = m.code_motif
    WHERE c.code_client = :code_client
");
$stmt->execute([':code_client' => $code_client]);
$client_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client_info) {
    header('Location: recherche_client.php');
    exit;
}

// Calculer l'âge si date de naissance disponible
$age = null;
if ($client_info['date_naissance']) {
    $birthDate = new DateTime($client_info['date_naissance']);
    $today = new DateTime();
    $age = $birthDate->diff($today)->y;
}

// Calculer l'ancienneté
$anciennete = null;
if ($client_info['date_entree']) {
    $entreeDate = new DateTime($client_info['date_entree']);
    $today = new DateTime();
    $diff = $entreeDate->diff($today);
    $anciennete = $diff->y . ' an' . ($diff->y > 1 ? 's' : '') . ' et ' . $diff->m . ' mois';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Fiche Client - SAV</title>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    <main>
        <section class="bg-gray-50 dark:bg-gray-900 min-h-screen py-8">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Bouton retour -->
                <div class="mb-6">
                    <a href="recherche_client.php" 
                        class="inline-flex items-center text-sm text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour à la recherche
                    </a>
                </div>

                <!-- En-tête client -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-8">
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                                <span class="text-3xl font-bold text-white">
                                    <?= strtoupper(substr($client_info['prenom'], 0, 1) . substr($client_info['nom'], 0, 1)) ?>
                                </span>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">
                                    <?= htmlspecialchars($client_info['prenom'] . ' ' . $client_info['nom']) ?>
                                </h1>
                                <p class="text-white mt-1">
                                    <?= htmlspecialchars($client_info['forme_libelle'] ?? 'Particulier') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Informations détaillées -->
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Informations personnelles
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nom -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nom</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['nom']) ?>
                                </p>
                            </div>

                            <!-- Prénom -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Prénom</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['prenom']) ?>
                                </p>
                            </div>

                            <!-- Date de naissance -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de naissance</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?php if ($client_info['date_naissance']): ?>
                                        <?= date('d/m/Y', strtotime($client_info['date_naissance'])) ?>
                                        <span class="text-sm font-normal text-gray-500">(<?= $age ?> ans)</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Non renseignée</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Numéro de sécurité sociale -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">N° Sécurité Sociale</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1 font-mono">
                                    <?php if ($client_info['num_sec_soc']): ?>
                                        <?= htmlspecialchars($client_info['num_sec_soc']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-sans">Non renseigné</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Pays -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Pays</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['pays_libelle'] ?? '-') ?>
                                    <span class="text-sm font-normal text-gray-500">(<?= htmlspecialchars($client_info['code_pays'] ?? '') ?>)</span>
                                </p>
                            </div>

                            <!-- Forme juridique -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Forme Juridique</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Relation client
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Code client -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Code Client</label>
                                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">
                                    #<?= htmlspecialchars($client_info['code_client']) ?>
                                </p>
                            </div>

                            <!-- Date d'entrée -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Client depuis</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= date('d/m/Y', strtotime($client_info['date_entree'])) ?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= $anciennete ?>
                                </p>
                            </div>

                            <!-- Motif d'entrée -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Motif d'entrée</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <?php if ($client_info['motif_libelle'] === 'Téléphone'): ?>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                            </svg>
                                        <?php elseif ($client_info['motif_libelle'] === 'Mail'): ?>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        <?php elseif ($client_info['motif_libelle'] === 'Web'): ?>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                            </svg>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($client_info['motif_libelle'] ?? '-') ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Actions rapides
                        </h2>

                        <div class="flex flex-wrap gap-4">
                            <!-- Bouton créer devis -->
                            <a href="devis_client.php?client=<?= $client_info['code_client'] ?>" 
                                class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Créer un nouveau devis SAV
                            </a>

                            <!-- Bouton modifier (désactivé pour l'instant) -->
                            <button type="button" disabled
                                class="inline-flex items-center px-6 py-3 text-base font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed dark:bg-gray-700 dark:text-gray-500">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Modifier le client
                            </button>

                            <!-- Bouton historique (désactivé pour l'instant) -->
                            <button type="button" disabled
                                class="inline-flex items-center px-6 py-3 text-base font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed dark:bg-gray-700 dark:text-gray-500">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Historique des devis
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
