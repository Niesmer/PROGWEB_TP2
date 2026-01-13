<?php
$db_connection = new PDO("mysql:host=localhost", "root", "");
$db_connection->query("CREATE USER IF NOT EXISTS 'user1'@'localhost' IDENTIFIED BY 'hcetylop'");
$db_connection->query("GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost' WITH GRANT OPTION");
$db_connection = new PDO("mysql:host=localhost", "user1", "hcetylop");
$db_connection->query("USE poly_php");

// Récupérer les données du formulaire
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

// Calculer les totaux
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

// Générer le numéro de devis
$numDevis = 'DEV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis <?= $numDevis ?></title>
    <style>
        @page {
            margin: 20mm;
        }
        @media print {
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #2563eb;
        }
        .company-info h1 {
            color: #2563eb;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .company-info p {
            margin: 3px 0;
            color: #666;
        }
        .devis-info {
            text-align: right;
        }
        .devis-info h2 {
            color: #2563eb;
            margin: 0 0 10px 0;
        }
        .devis-info p {
            margin: 3px 0;
        }
        .client-section {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .client-section h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
        }
        .client-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .client-grid div {
            display: flex;
        }
        .client-grid label {
            font-weight: 600;
            color: #6b7280;
            min-width: 140px;
        }
        .client-grid span {
            color: #1f2937;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        thead th {
            background: #2563eb;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
        }
        thead th:nth-child(3),
        thead th:nth-child(4),
        thead th:nth-child(5),
        thead th:nth-child(6),
        thead th:nth-child(7) {
            text-align: right;
        }
        tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:hover {
            background: #f9fafb;
        }
        tbody td:nth-child(3),
        tbody td:nth-child(4),
        tbody td:nth-child(5),
        tbody td:nth-child(6),
        tbody td:nth-child(7) {
            text-align: right;
        }
        .totals {
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 350px;
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals-row.tva-detail {
            padding-left: 20px;
            font-size: 11px;
            color: #6b7280;
        }
        .totals-row.total-ttc {
            border-top: 2px solid #2563eb;
            border-bottom: none;
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 10px;
            padding-top: 15px;
        }
        .totals-row label {
            font-weight: 500;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #6b7280;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <!-- Boutons d'action (non imprimés) -->
    <a href="javascript:history.back()" class="btn-back no-print">
        ← Retour
    </a>
    <button onclick="window.print()" class="btn-print no-print">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Imprimer / PDF
    </button>

    <!-- En-tête -->
    <div class="header">
        <div class="company-info">
            <h1>SAV SERVICES</h1>
            <p>123 Rue de la Réparation</p>
            <p>75001 Paris, France</p>
            <p>Tél: 01 23 45 67 89</p>
            <p>Email: contact@sav-services.fr</p>
        </div>
        <div class="devis-info">
            <h2>DEVIS</h2>
            <p><strong>N°:</strong> <?= $numDevis ?></p>
            <p><strong>Date:</strong> <?= date('d/m/Y') ?></p>
            <p><strong>Validité:</strong> 30 jours</p>
        </div>
    </div>

    <!-- Informations client -->
    <div class="client-section">
        <h3>Informations Client</h3>
        <?php if ($client_info): ?>
        <div class="client-grid">
            <div>
                <label>Nom:</label>
                <span><?= htmlspecialchars($client_info['nom'] . ' ' . $client_info['prenom']) ?></span>
            </div>
            <div>
                <label>Forme Juridique:</label>
                <span><?= htmlspecialchars($client_info['forme_libelle'] ?? '-') ?></span>
            </div>
            <div>
                <label>Pays:</label>
                <span><?= htmlspecialchars($client_info['pays_libelle'] ?? '-') ?></span>
            </div>
            <div>
                <label>N° Sécurité Sociale:</label>
                <span><?= htmlspecialchars($client_info['num_sec_soc'] ?? '-') ?></span>
            </div>
            <div>
                <label>Date de naissance:</label>
                <span><?= $client_info['date_naissance'] ? date('d/m/Y', strtotime($client_info['date_naissance'])) : '-' ?></span>
            </div>
            <div>
                <label>Client depuis:</label>
                <span><?= $client_info['date_entree'] ? date('d/m/Y', strtotime($client_info['date_entree'])) : '-' ?></span>
            </div>
        </div>
        <?php else: ?>
        <p>Informations client non disponibles</p>
        <?php endif; ?>
    </div>

    <!-- Détail du devis -->
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Désignation</th>
                <th>Quantité</th>
                <th>Unité</th>
                <th>Prix Unit. HT</th>
                <th>TVA %</th>
                <th>Montant HT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lignes as $ligne): ?>
            <tr>
                <td><?= htmlspecialchars($ligne['code']) ?></td>
                <td><?= htmlspecialchars($ligne['designation']) ?></td>
                <td><?= number_format($ligne['quantite'], 2, ',', ' ') ?></td>
                <td><?= htmlspecialchars($ligne['unite']) ?></td>
                <td><?= number_format($ligne['forfait'], 2, ',', ' ') ?> €</td>
                <td><?= number_format($ligne['tva'], 2, ',', ' ') ?> %</td>
                <td><?= number_format($ligne['montantHT'], 2, ',', ' ') ?> €</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totaux -->
    <div class="totals">
        <div class="totals-box">
            <div class="totals-row">
                <label>Total HT</label>
                <span><?= number_format($totalHT, 2, ',', ' ') ?> €</span>
            </div>
            
            <?php ksort($tvaMap); foreach ($tvaMap as $taux => $montant): ?>
            <div class="totals-row tva-detail">
                <label>TVA <?= number_format($taux, 2, ',', ' ') ?> %</label>
                <span><?= number_format($montant, 2, ',', ' ') ?> €</span>
            </div>
            <?php endforeach; ?>
            
            <div class="totals-row">
                <label>Total TVA</label>
                <span><?= number_format($totalTVA, 2, ',', ' ') ?> €</span>
            </div>
            
            <div class="totals-row total-ttc">
                <label>TOTAL TTC</label>
                <span><?= number_format($totalTTC, 2, ',', ' ') ?> €</span>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>SAV SERVICES - SIRET: 123 456 789 00012 - TVA Intracommunautaire: FR12345678900</p>
        <p>Ce devis est valable 30 jours à compter de sa date d'émission.</p>
    </div>
</body>
</html>
