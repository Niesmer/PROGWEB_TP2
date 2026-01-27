<?php
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Source - https://stackoverflow.com/a
// Posted by Kareem, modified by community. See post 'Timeline' for change history
// Retrieved 2026-01-27, License - CC BY-SA 4.0

$env = file_get_contents(__DIR__ . "/.env");
$lines = explode("\n", $env);

foreach ($lines as $line) {
    preg_match("/([^#]+)\=(.*)/", $line, $matches);
    if (isset($matches[2])) {
        putenv(trim($line));
    }
}

enum DEVIS_STATUS : int {
    case ONGOING = 0;
    case PRINTED = 1;
    case ACCEPTED = 2;
    case REJECTED = 3;
}

function connectDB($username, $password, $dbname = ""): PDO
{
    try {
        $DB_CONNECTION = new PDO("mysql:host=localhost;dbname=$dbname", $username, $password);
        return $DB_CONNECTION;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

function InitDB()
{
    if (!file_exists('base_poly.sql')) {
        die("Le fichier base_poly.sql est introuvable.");
    }
    $db_connection = connectDB("root", "");
    $sql = file_get_contents('base_poly.sql');
    try {
        $db_connection->query("CREATE USER IF NOT EXISTS 'user1'@'localhost' IDENTIFIED BY 'hcetylop'");
        $db_connection->query("GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost' WITH GRANT OPTION");
        $db_connection->query("CREATE DATABASE IF NOT EXISTS poly_php;");
        $db_connection = connectDB("user1", "hcetylop", "poly_php");
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $db_connection->exec($statement);
            }
        }
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}

function sendEmail($to, $subject, $body, $attachments = [])
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_SERVER');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->Port = getenv('SMTP_PORT');
        $mail->setFrom('address@mail.com', 'Your Name');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        $mail->send();
    } catch (Exception $e) {
        echo "Le message n'a pas pu être envoyé. Erreur de Mailer: {$mail->ErrorInfo}";
    }
}

function genererPDFDevis($code_devis, $code_client, $db_connection)
{
    // Récupérer les informations du devis et du client
    $stmt = $db_connection->prepare("
        SELECT d.*, c.nom, c.prenom, c.num_sec_soc, c.date_naissance, c.date_entree,
               p.libelle AS pays_libelle, 
               f.libelle AS forme_libelle
        FROM Devis d
        LEFT JOIN Clients c ON d.code_client = c.code_client
        LEFT JOIN Pays p ON c.code_pays = p.code_pays
        LEFT JOIN Formes_Juridiques f ON c.code_forme = f.code_forme
        WHERE d.code_devis = :code_devis AND d.code_client = :code_client
    ");
    $stmt->execute([':code_devis' => $code_devis, ':code_client' => $code_client]);
    $devis_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$devis_info) {
        return false;
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

    // Définir le chemin du PDF
    $pdf_directory = __DIR__ . '/files/devis/client_' . $code_client;
    $pdf_filename = 'devis_' . $code_devis . '_' . $devis_info['nom'] . '_' . $devis_info['prenom'] . '_' . date('Y-m-d') . '.pdf';
    $pdf_path = $pdf_directory . '/' . $pdf_filename;

    // Créer le répertoire s'il n'existe pas
    if (!is_dir($pdf_directory)) {
        mkdir($pdf_directory, 0777, true);
    }

    // Générer le PDF avec TCPDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('POLY Industrie');
    $pdf->SetAuthor('POLY Industrie');
    $pdf->SetTitle('Devis #' . $code_devis);
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
                <h2>DEVIS #' . htmlspecialchars($code_devis) . '</h2>
                <div style="font-size: 9px; margin-top: 5px;"><strong>Date:</strong> ' . date('d/m/Y', strtotime($devis_info['date_devis'])) . '</div>
                <div style="font-size: 9px; margin-top: 3px;"><strong>Validité:</strong> 30 jours</div>
            </div>
        </div>
    </div>

    <div class="client-box">
        <h3>Informations Client</h3>
        <div class="info-row"><span class="info-label">Nom:</span> ' . htmlspecialchars($devis_info['nom'] . ' ' . $devis_info['prenom']) . '</div>
        <div class="info-row"><span class="info-label">Forme Juridique:</span> ' . htmlspecialchars($devis_info['forme_libelle'] ?? '-') . '</div>
        <div class="info-row"><span class="info-label">Pays:</span> ' . htmlspecialchars($devis_info['pays_libelle'] ?? '-') . '</div>
        <div class="info-row"><span class="info-label">N° Sécurité Sociale:</span> ' . htmlspecialchars($devis_info['num_sec_soc'] ?? '-') . '</div>
        <div class="info-row"><span class="info-label">Date de naissance:</span> ' . ($devis_info['date_naissance'] ? date('d/m/Y', strtotime($devis_info['date_naissance'])) : '-') . '</div>
        <div class="info-row"><span class="info-label">Client depuis:</span> ' . ($devis_info['date_entree'] ? date('d/m/Y', strtotime($devis_info['date_entree'])) : '-') . '</div>
    </div>

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

    foreach ($lignes_devis as $ligne) {
        $montantHT = $ligne['quantite'] * $ligne['forfait_ht'];
        $html .= '
            <tr>
                <td>' . htmlspecialchars($ligne['code_article']) . '</td>
                <td>' . htmlspecialchars($ligne['designation']) . '</td>
                <td class="text-right">' . number_format($ligne['quantite'], 2, ',', ' ') . '</td>
                <td class="text-right">' . htmlspecialchars($ligne['unite_libelle']) . '</td>
                <td class="text-right">' . number_format($ligne['forfait_ht'], 2, ',', ' ') . ' €</td>
                <td class="text-right">' . number_format($ligne['tva_taux'], 2, ',', ' ') . ' %</td>
                <td class="text-right">' . number_format($montantHT, 2, ',', ' ') . ' €</td>
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
        <p>POLY Industrie - Service Après-Vente</p>
        <p>Ce devis est valable 30 jours à compter de sa date d\'émission.</p>
    </div>';

    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    // Save PDF to file
    $pdf->Output($pdf_path, 'F');

    return $pdf_path;
}

InitDB();
$db_connection = connectDB("user1", "hcetylop", "poly_php");
?>