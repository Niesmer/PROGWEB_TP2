<?php
require_once 'Ressources_communes.php';

$code_devis  = $_POST['code_devis'] ?? null;
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

// Create PDF with TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('POLY Industrie');
$pdf->SetAuthor('POLY Industrie');
$pdf->SetTitle('Devis');
$pdf->SetSubject('Devis Client');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Build HTML content
$html = '
<style>
    h1 { color: #2563eb; font-size: 20px; margin: 0; }
    h2 { color: #2563eb; font-size: 16px; margin: 0; }
    h3 { color: #111827; font-size: 12px; margin: 10px 0 5px 0; border-bottom: 1px solid #d1d5db; padding-bottom: 5px; }
    .header { margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
    .client-box { background-color: #f3f4f6; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
    .info-row { margin: 5px 0; font-size: 9px; }
    .info-label { font-weight: bold; color: #4b5563; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th { background-color: #2563eb; color: white; padding: 8px; text-align: left; font-weight: bold; font-size: 9px; }
    td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
    .text-right { text-align: right; }
    .totals-box { background-color: #f9fafb; padding: 10px; margin: 20px 0; border-radius: 5px; }
    .total-row { margin: 5px 0; font-size: 9px; border-bottom: 1px solid #e5e7eb; padding: 5px 0; }
    .total-final { font-weight: bold; color: #2563eb; font-size: 11px; border-top: 2px solid #2563eb; padding-top: 8px; margin-top: 5px; }
    .footer { text-align: center; color: #6b7280; font-size: 8px; border-top: 1px solid #e5e7eb; padding-top: 10px; margin-top: 20px; }
</style>

<div class="header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <h1>POLY Industrie</h1>
        <div style="text-align: right;">
            <h2>DEVIS</h2>
            <div style="font-size: 9px; margin-top: 5px;"><strong>Date:</strong> ' . date('d/m/Y') . '</div>
            <div style="font-size: 9px; margin-top: 3px;"><strong>Validité:</strong> 30 jours</div>
        </div>
    </div>
</div>

<div class="client-box">
    <h3>Informations Client</h3>';

if ($client_info) {
    $html .= '
    <div class="info-row"><span class="info-label">Nom:</span> ' . htmlspecialchars($client_info['nom'] . ' ' . $client_info['prenom']) . '</div>
    <div class="info-row"><span class="info-label">Forme Juridique:</span> ' . htmlspecialchars($client_info['forme_libelle'] ?? '-') . '</div>
    <div class="info-row"><span class="info-label">Pays:</span> ' . htmlspecialchars($client_info['pays_libelle'] ?? '-') . '</div>
    <div class="info-row"><span class="info-label">N° Sécurité Sociale:</span> ' . htmlspecialchars($client_info['num_sec_soc'] ?? '-') . '</div>
    <div class="info-row"><span class="info-label">Date de naissance:</span> ' . ($client_info['date_naissance'] ? date('d/m/Y', strtotime($client_info['date_naissance'])) : '-') . '</div>
    <div class="info-row"><span class="info-label">Client depuis:</span> ' . ($client_info['date_entree'] ? date('d/m/Y', strtotime($client_info['date_entree'])) : '-') . '</div>';
} else {
    $html .= '<div class="info-row">Informations client non disponibles</div>';
}

$html .= '</div>

<table>
    <thead>
        <tr>
            <th style="width: 10%;">Code</th>
            <th style="width: 30%;">Désignation</th>
            <th style="width: 10%;" class="text-right">Quantité</th>
            <th style="width: 8%;" class="text-right">Unité</th>
            <th style="width: 13%;" class="text-right">Prix Unit. HT</th>
            <th style="width: 9%;" class="text-right">TVA %</th>
            <th style="width: 13%;" class="text-right">Montant HT</th>
        </tr>
    </thead>
    <tbody>';

foreach ($lignes as $ligne) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($ligne['code']) . '</td>
            <td>' . htmlspecialchars($ligne['designation']) . '</td>
            <td class="text-right">' . number_format($ligne['quantite'], 2, ',', ' ') . '</td>
            <td class="text-right">' . htmlspecialchars($ligne['unite']) . '</td>
            <td class="text-right">' . number_format($ligne['forfait'], 2, ',', ' ') . ' €</td>
            <td class="text-right">' . number_format($ligne['tva'], 2, ',', ' ') . ' %</td>
            <td class="text-right">' . number_format($ligne['montantHT'], 2, ',', ' ') . ' €</td>
        </tr>';
}

$html .= '
    </tbody>
</table>

<div style="text-align: right;">
    <div class="totals-box" style="width: 250px; display: inline-block;">
        <div class="total-row">
            <strong>Total HT:</strong> <span style="float: right;">' . number_format($totalHT, 2, ',', ' ') . ' €</span>
        </div>';

ksort($tvaMap);
foreach ($tvaMap as $taux => $montant) {
    $html .= '
        <div style="font-size: 8px; color: #6b7280; padding: 3px 0; padding-left: 15px;">
            TVA ' . number_format($taux, 2, ',', ' ') . ' %: <span style="float: right;">' . number_format($montant, 2, ',', ' ') . ' €</span>
        </div>';
}

$html .= '
        <div class="total-row">
            <strong>Total TVA:</strong> <span style="float: right;">' . number_format($totalTVA, 2, ',', ' ') . ' €</span>
        </div>
        <div class="total-final">
            <strong>TOTAL TTC:</strong> <span style="float: right;">' . number_format($totalTTC, 2, ',', ' ') . ' €</span>
        </div>
    </div>
</div>

<div class="footer">
    <p>POLY Industrie</p>
    <p>Ce devis est valable 30 jours à compter de sa date d\'émission.</p>
</div>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

//if the directory does not exist, create it
if (!is_dir(__DIR__ . '/files/devis')) {
    mkdir(__DIR__ . '/files/devis', 0777, true);
}

// Close and output PDF document
$pdf->Output(__DIR__ . '/files/devis/devis_' . $client_info['nom'] . '_' . $client_info['prenom'] . date('Y-m-d') . '.pdf', 'F');
$pdf->Output('/devis_' . date('Y-m-d') . '.pdf', 'I');
?>