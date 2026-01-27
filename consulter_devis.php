<?php
require_once 'Ressources_communes.php';

$code_devis = $_GET['devis'] ?? null;
$devis_info = null;
$client_info = null;
$lignes_devis = [];

if (!$code_devis) {
    header('Location: ./');
    exit;
}

// Récupérer les informations du devis
$stmt = $db_connection->prepare("
    SELECT d.*, c.nom, c.prenom, c.num_sec_soc,
           p.libelle AS pays_libelle, 
           f.libelle AS forme_libelle
    FROM Devis d
    LEFT JOIN Clients c ON d.code_client = c.code_client
    LEFT JOIN Pays p ON c.code_pays = p.code_pays
    LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
    WHERE d.code_devis = :code_devis
");
$stmt->execute([':code_devis' => $code_devis]);
$devis_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$devis_info) {
    header('Location: ./');
    exit;
}

// Récupérer les lignes du devis
$stmt = $db_connection->prepare("
    SELECT ld.*, a.designation, a.forfait_ht, t.taux AS tva_taux, u.libelle AS unite_libelle
    FROM Lignes_Devis ld
    LEFT JOIN Articles a ON ld.code_article = a.code_article
    LEFT JOIN TVA t ON a.code_tva = t.code_tva
    LEFT JOIN Unites u ON a.code_unite = u.code_unite
    WHERE ld.code_devis = :code_devis
    ORDER BY ld.code_article
");
$stmt->execute([':code_devis' => $code_devis]);
$lignes_devis = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux
$totalHT = 0;
$tvaMap = [];

foreach ($lignes_devis as $ligne) {
    $montantHT = $ligne['quantite'] * $ligne['forfait_ht'];
    $totalHT += $montantHT;
    
    $tauxTVA = $ligne['tva_taux'];
    $montantTVA = $montantHT * ($tauxTVA / 100);
    
    if (!isset($tvaMap[$tauxTVA])) {
        $tvaMap[$tauxTVA] = 0;
    }
    $tvaMap[$tauxTVA] += $montantTVA;
}

$totalTVA = array_sum($tvaMap);
$totalTTC = $totalHT + $totalTVA;

// Traduire le statut en texte
$statusLabels = [
    DEVIS_STATUS::ONGOING->value => 'En cours',
    DEVIS_STATUS::PRINTED->value => 'Imprimé',
    DEVIS_STATUS::ACCEPTED->value => 'Validé',
    DEVIS_STATUS::REJECTED->value => 'Refusé'
];
$statut_libelle = $statusLabels[$devis_info['status_devis']] ?? 'Inconnu';

// Couleurs des badges de statut
$badgeColors = [
    'En cours' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    'Imprimé' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    'Validé' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    'Refusé' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
];
$statusColor = $badgeColors[$statut_libelle] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Consultation Devis #<?= htmlspecialchars($code_devis) ?> - SAV</title>
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>

    <!-- Navbar Flowbite -->
    <nav class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a href="./" class="flex items-center space-x-3 rtl:space-x-reverse">
                <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">SAV Manager</span>
            </a>
        </div>
    </nav>

    <main class="min-h-screen py-8">
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    <li>
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <a href="fiche_client.php?client=<?= urlencode($devis_info['code_client']) ?>" class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">Fiche Client</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">Consultation Devis #<?= htmlspecialchars($code_devis) ?></span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- En-tête du devis -->
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-2">
                            Devis SAV
                            <span class="text-2xl font-normal text-gray-500 dark:text-gray-400">#<?= htmlspecialchars($code_devis) ?></span>
                        </h1>
                        <div class="flex items-center gap-3 mt-2">
                            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                Date: <?= date('d/m/Y', strtotime($devis_info['date_devis'])) ?>
                            </p>
                            <span class="<?= $statusColor ?> text-xs font-medium px-2.5 py-0.5 rounded">
                                <?= htmlspecialchars($statut_libelle) ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">POLY Industrie</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Service Après-Vente</p>
                    </div>
                </div>
            </div>

            <!-- Informations client -->
            <div class="p-6 bg-blue-50 border border-blue-200 rounded-lg dark:bg-gray-800 dark:border-blue-800 mb-6">
                <div class="flex items-center mb-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 me-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Informations Client</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center">
                        <span class="text-gray-600 dark:text-gray-400 w-32">Client:</span>
                        <span class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($devis_info['nom'] . ' ' . $devis_info['prenom']) ?></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600 dark:text-gray-400 w-32">Code Client:</span>
                        <span class="font-medium text-gray-900 dark:text-white">#<?= htmlspecialchars($devis_info['code_client']) ?></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600 dark:text-gray-400 w-32">Forme Juridique:</span>
                        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300">
                            <?= htmlspecialchars($devis_info['forme_libelle'] ?? '-') ?>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600 dark:text-gray-400 w-32">Pays:</span>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-green-900 dark:text-green-300">
                            <?= htmlspecialchars($devis_info['pays_libelle'] ?? '-') ?>
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600 dark:text-gray-400 w-32">N° Sécurité Sociale:</span>
                        <span class="font-mono text-gray-900 dark:text-white"><?= htmlspecialchars($devis_info['num_sec_soc'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <!-- Articles du devis -->
            <div class="relative overflow-hidden bg-white shadow-md dark:bg-gray-800 sm:rounded-lg mb-6">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400 me-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                        </svg>
                        Articles du devis
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Code</th>
                                <th class="px-4 py-3">Désignation</th>
                                <th class="px-4 py-3 text-center">Quantité</th>
                                <th class="px-4 py-3 text-center">Unité</th>
                                <th class="px-4 py-3 text-right">Prix Unit. HT</th>
                                <th class="px-4 py-3 text-right">TVA %</th>
                                <th class="px-4 py-3 text-right">Montant HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lignes_devis)): ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">Aucun article dans ce devis</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lignes_devis as $ligne): ?>
                                    <?php $montantHT = $ligne['quantite'] * $ligne['forfait_ht']; ?>
                                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($ligne['code_article']) ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?= htmlspecialchars($ligne['designation']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-center font-medium">
                                            <?= number_format($ligne['quantite'], 2, ',', ' ') ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <?= htmlspecialchars($ligne['unite_libelle']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <?= number_format($ligne['forfait_ht'], 2, ',', ' ') ?> €
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <?= number_format($ligne['tva_taux'], 2, ',', ' ') ?> %
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                            <?= number_format($montantHT, 2, ',', ' ') ?> €
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Totaux -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 mb-6">
                <div class="flex justify-end">
                    <div class="w-full max-w-md">
                        <!-- Total HT -->
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= number_format($totalHT, 2, ',', ' ') ?> €</span>
                        </div>

                        <!-- Détail TVA -->
                        <div class="border-b border-gray-200 dark:border-gray-700">
                            <?php ksort($tvaMap); ?>
                            <?php foreach ($tvaMap as $taux => $montant): ?>
                                <div class="flex justify-between py-2 text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">TVA <?= number_format($taux, 2, ',', ' ') ?>%</span>
                                    <span class="text-gray-700 dark:text-gray-300"><?= number_format($montant, 2, ',', ' ') ?> €</span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Total TVA -->
                        <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Total TVA</span>
                            <span class="font-semibold text-gray-900 dark:text-white"><?= number_format($totalTVA, 2, ',', ' ') ?> €</span>
                        </div>

                        <!-- Total TTC -->
                        <div class="flex justify-between py-3 text-lg">
                            <span class="font-bold text-gray-900 dark:text-white">Total TTC</span>
                            <span class="font-bold text-primary-600 dark:text-primary-400"><?= number_format($totalTTC, 2, ',', ' ') ?> €</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex flex-col sm:flex-row justify-between gap-3">
                <a href="fiche_client.php?client=<?= urlencode($devis_info['code_client']) ?>"
                    class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 inline-flex items-center justify-center">
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Retour à la fiche client
                </a>
                <div class="flex flex-col sm:flex-row gap-3">
                    <?php if ($devis_info['status_devis'] != DEVIS_STATUS::ACCEPTED->value): ?>
                        <a href="devis_client.php?client=<?= urlencode($devis_info['code_client']) ?>&devis=<?= urlencode($code_devis) ?>"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center justify-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modifier le devis
                        </a>
                    <?php endif; ?>
                    <form method="POST" action="generer_pdf.php" target="_blank" class="inline">
                        <input type="hidden" name="code_devis" value="<?= htmlspecialchars($code_devis) ?>">
                        <input type="hidden" name="code_client" value="<?= htmlspecialchars($devis_info['code_client']) ?>">
                        <button type="submit"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 inline-flex items-center justify-center w-full sm:w-auto">
                            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimer PDF
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>

</html>
