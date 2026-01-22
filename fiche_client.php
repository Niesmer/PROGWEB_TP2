<?php
require_once 'Ressources_communes.php';

$code_client = $_GET['client'] ?? null;
$client_info = null;
$devis_created = $_GET['devis_created'] ?? null;
$devis_updated = $_GET['devis_updated'] ?? null;

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

// Récupérer les devis du client
$stmt = $db_connection->prepare("
    SELECT d.*, 
           (SELECT COUNT(*) FROM Lignes_Devis ld WHERE ld.code_devis = d.code_devis) AS nb_lignes
    FROM Devis d
    WHERE d.code_client = :code_client
    ORDER BY d.date_devis DESC, d.code_devis DESC
");
$stmt->execute([':code_client' => $code_client]);
$devis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                
                <!-- Message de succès création -->
                <?php if ($devis_created): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span><strong>Succès !</strong> Devis #<?= htmlspecialchars($devis_created) ?> créé avec succès!</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Message de succès mise à jour -->
                <?php if ($devis_updated): ?>
                <div class="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span><strong>Mis à jour !</strong> Devis #<?= htmlspecialchars($devis_updated) ?> modifié avec succès!</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-blue-700 hover:text-blue-900">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Bouton retour -->
                <div class="mb-6">
                    <a href="./"
                        class="inline-flex items-center text-sm text-gray-600 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à la recherche
                    </a>
                </div>

                <!-- En-tête client -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="px-6 py-8">
                        <div class="flex justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                                    <span class="text-3xl font-bold dark:text-white">
                                        <?= strtoupper(substr($client_info['prenom'], 0, 1) . substr($client_info['nom'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold dark:text-white">
                                        <?= htmlspecialchars($client_info['prenom'] . ' ' . $client_info['nom']) ?>
                                    </h1>
                                    <p class="dark:text-white mt-1">
                                        <?= htmlspecialchars($client_info['forme_libelle'] ?? 'Particulier') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <a href="devis_client.php?client=<?= $client_info['code_client'] ?>"
                                    class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 focus:ring-4 focus:ring-primary-300 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Créer un nouveau devis
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Informations détaillées -->
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
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
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Date de
                                    naissance</label>
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
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">N° Sécurité
                                    Sociale</label>
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
                                    <span
                                        class="text-sm font-normal text-gray-500">(<?= htmlspecialchars($client_info['code_pays'] ?? '') ?>)</span>
                                </p>
                            </div>

                            <!-- Forme juridique -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Forme
                                    Juridique</label>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                    <?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations client -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
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
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Client
                                depuis</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                <?= date('d/m/Y', strtotime($client_info['date_entree'])) ?>
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= $anciennete ?>
                            </p>
                        </div>

                        <!-- Motif d'entrée -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Motif
                                d'entrée</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <?php if ($client_info['motif_libelle'] === 'Téléphone'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    <?php elseif ($client_info['motif_libelle'] === 'Mail'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    <?php elseif ($client_info['motif_libelle'] === 'Web'): ?>
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($client_info['motif_libelle'] ?? '-') ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Liste des devis -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Devis du client
                        </h2>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <?= count($devis_list) ?> devis
                        </span>
                    </div>

                    <?php if (empty($devis_list)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Aucun devis pour ce client</p>
                        <a href="devis_client.php?client=<?= $client_info['code_client'] ?>"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Créer le premier devis
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">N° Devis</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-center">Lignes</th>
                                    <th class="px-4 py-3 text-right">Montant HT</th>
                                    <th class="px-4 py-3 text-right">Montant TTC</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devis_list as $devis): ?>
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        #<?= htmlspecialchars($devis['code_devis']) ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?= date('d/m/Y', strtotime($devis['date_devis'])) ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold text-primary-800 bg-primary-100 rounded-full dark:bg-primary-900 dark:text-primary-200">
                                            <?= htmlspecialchars($devis['nb_lignes']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <?= number_format($devis['montant_ht'], 2, ',', ' ') ?> €
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                        <?= number_format($devis['montant_ttc'], 2, ',', ' ') ?> €
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex justify-center gap-2">
                                            <!-- Voir/Modifier -->
                                            <a href="devis_client.php?client=<?= $code_client ?>&devis=<?= $devis['code_devis'] ?>"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-700 bg-primary-100 rounded-lg hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300 dark:hover:bg-primary-800"
                                                title="Modifier le devis">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                                Modifier
                                            </a>
                                            <!-- Supprimer -->
                                            <button type="button" 
                                                onclick="confirmerSuppression(<?= $devis['code_devis'] ?>)"
                                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800"
                                                title="Supprimer le devis">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-700">
                                <tr class="font-semibold text-gray-900 dark:text-white">
                                    <td class="px-4 py-3" colspan="3">Total</td>
                                    <td class="px-4 py-3 text-right">
                                        <?= number_format(array_sum(array_column($devis_list, 'montant_ht')), 2, ',', ' ') ?> €
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <?= number_format(array_sum(array_column($devis_list, 'montant_ttc')), 2, ',', ' ') ?> €
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Confirmer la suppression</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        Êtes-vous sûr de vouloir supprimer ce devis ? Cette action est irréversible.
                    </p>
                    <div class="flex justify-center gap-3">
                        <button type="button" onclick="fermerModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Annuler
                        </button>
                        <form id="deleteForm" method="POST" action="supprimer_devis.php" class="inline">
                            <input type="hidden" name="code_devis" id="deleteDevisId" value="">
                            <input type="hidden" name="code_client" value="<?= $code_client ?>">
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmerSuppression(codeDevis) {
        document.getElementById('deleteDevisId').value = codeDevis;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function fermerModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            fermerModal();
        }
    });
    </script>
</body>

</html>