<?php
require_once 'Ressources_communes.php';

$code_client = $_POST['code_client'] ?? null;
$lignes_json = $_POST['lignes_json'] ?? '[]';
$lignes = json_decode($lignes_json, true);

// Récupérer les infos du client
$client_info = null;
if ($code_client) {
    $stmt = $db_connection->prepare("
        SELECT c.*, p.libelle AS pays_libelle, f.libelle AS forme_libelle
        FROM Clients c
        LEFT JOIN Pays p ON c.code_pays = p.code_pays
        LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
        WHERE c.code_client = :code_client
    ");
    $stmt->execute([':code_client' => $code_client]);
    $client_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

$totalHT = 0;
$tvaMap = [];

foreach ($lignes as $ligne) {
    $montantHT = $ligne['montantHT'];
    $totalHT += $montantHT;
    $taux = $ligne['tva'];
    $montantTVA = $montantHT * ($taux / 100);
    
    if (!isset($tvaMap[$taux])) {
        $tvaMap[$taux] = 0;
    }
    $tvaMap[$taux] += $montantTVA;
}

$totalTVA = array_sum($tvaMap);
$totalTTC = $totalHT + $totalTVA;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="global.css">
    <style>
        @page {
            margin: 20mm;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-white text-gray-800 text-sm leading-relaxed">
    <a href="./" class="no-print fixed top-5 left-5 bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 shadow-md">
        ← Retour
    </a>
    
    <button onclick="window.print()" class="no-print fixed top-5 right-5 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center gap-2 shadow-md">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Imprimer / PDF
    </button>

    <div class="p-5 max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between mb-8 pb-5 border-b-2 border-blue-600">
            <div>
                <h1 class="text-2xl font-bold text-blue-600 mb-2">POLY Industrie</h1>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-blue-600 mb-2">DEVIS</h2>
                <p><strong>Date:</strong> <?= date('d/m/Y') ?></p>
                <p><strong>Validité:</strong> 30 jours</p>
            </div>
        </div>

        <!-- Client Section -->
        <div class="bg-gray-100 p-4 rounded-lg mb-8">
            <h3 class="font-bold text-gray-900 mb-3 pb-2 border-b border-gray-300">Informations Client</h3>
            <?php if ($client_info): ?>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">Nom:</label>
                    <span class="text-gray-900"><?= htmlspecialchars($client_info['nom'] . ' ' . $client_info['prenom']) ?></span>
                </div>
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">Forme Juridique:</label>
                    <span class="text-gray-900"><?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?></span>
                </div>
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">Pays:</label>
                    <span class="text-gray-900"><?= htmlspecialchars($client_info['pays_libelle'] ?? '-') ?></span>
                </div>
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">N° Sécurité Sociale:</label>
                    <span class="text-gray-900"><?= htmlspecialchars($client_info['num_sec_soc'] ?? '-') ?></span>
                </div>
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">Date de naissance:</label>
                    <span class="text-gray-900"><?= $client_info['date_naissance'] ? date('d/m/Y', strtotime($client_info['date_naissance'])) : '-' ?></span>
                </div>
                <div class="flex">
                    <label class="font-semibold text-gray-600 min-w-fit mr-4">Client depuis:</label>
                    <span class="text-gray-900"><?= $client_info['date_entree'] ? date('d/m/Y', strtotime($client_info['date_entree'])) : '-' ?></span>
                </div>
            </div>
            <?php else: ?>
            <p class="text-gray-600">Informations client non disponibles</p>
            <?php endif; ?>
        </div>

        <!-- Table -->
        <table class="w-full mb-8 border-collapse">
            <thead>
                <tr class="bg-blue-600 text-white">
                    <th class="px-2 py-3 text-left font-semibold">Code</th>
                    <th class="px-2 py-3 text-left font-semibold">Désignation</th>
                    <th class="px-2 py-3 text-right font-semibold">Quantité</th>
                    <th class="px-2 py-3 text-right font-semibold">Unité</th>
                    <th class="px-2 py-3 text-right font-semibold">Prix Unit. HT</th>
                    <th class="px-2 py-3 text-right font-semibold">TVA %</th>
                    <th class="px-2 py-3 text-right font-semibold">Montant HT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-50">
                    <td class="px-2 py-3"><?= htmlspecialchars($ligne['code']) ?></td>
                    <td class="px-2 py-3"><?= htmlspecialchars($ligne['designation']) ?></td>
                    <td class="px-2 py-3 text-right"><?= number_format($ligne['quantite'], 2, ',', ' ') ?></td>
                    <td class="px-2 py-3 text-right"><?= htmlspecialchars($ligne['unite']) ?></td>
                    <td class="px-2 py-3 text-right"><?= number_format($ligne['forfait'], 2, ',', ' ') ?> €</td>
                    <td class="px-2 py-3 text-right"><?= number_format($ligne['tva'], 2, ',', ' ') ?> %</td>
                    <td class="px-2 py-3 text-right"><?= number_format($ligne['montantHT'], 2, ',', ' ') ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
            <div class="w-80 bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <label class="font-medium">Total HT</label>
                    <span><?= number_format($totalHT, 2, ',', ' ') ?> €</span>
                </div>
                
                <?php ksort($tvaMap); foreach ($tvaMap as $taux => $montant): ?>
                <div class="flex justify-between py-2 pl-5 text-xs text-gray-600">
                    <label>TVA <?= number_format($taux, 2, ',', ' ') ?> %</label>
                    <span><?= number_format($montant, 2, ',', ' ') ?> €</span>
                </div>
                <?php endforeach; ?>
                
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <label class="font-medium">Total TVA</label>
                    <span><?= number_format($totalTVA, 2, ',', ' ') ?> €</span>
                </div>
                
                <div class="flex justify-between py-3 border-t-2 border-blue-600 text-base font-bold text-blue-600 mt-2">
                    <label>TOTAL TTC</label>
                    <span><?= number_format($totalTTC, 2, ',', ' ') ?> €</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 pt-5 text-center text-xs text-gray-600">
            <p>POLY Industrie</p>
            <p>Ce devis est valable 30 jours à compter de sa date d'émission.</p>
        </div>
    </div>
</body>
</html>
