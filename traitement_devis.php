<?php
require_once 'Ressources_communes.php';

function validateInput() {
    if (!isset($_POST['code_client']) || empty($_POST['code_client'])) {
        die("Erreur: Aucun client sélectionné.");
    }
    if (!isset($_POST['lignes']) || empty($_POST['lignes'])) {
        die("Erreur: Aucune ligne de devis.");
    }
}

function calculateTotals($db_connection, $lignes) {
    $montant_ht_total = 0;
    $montant_tva_total = 0;
    
    foreach ($lignes as $ligne) {
        $code_article = $ligne['code'];
        $quantite = floatval($ligne['quantite']);
        
        $stmt = $db_connection->prepare("
            SELECT a.forfait_ht, t.taux 
            FROM Articles a 
            LEFT JOIN TVA t ON a.code_tva = t.code_tva 
            WHERE a.code_article = :code_article
        ");
        $stmt->execute([':code_article' => $code_article]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($article) {
            $prix_unitaire_ht = floatval($article['forfait_ht']);
            $taux_tva = floatval($article['taux']);
            $montant_ht_ligne = $quantite * $prix_unitaire_ht;
            $montant_tva_ligne = $montant_ht_ligne * ($taux_tva / 100);
            
            $montant_ht_total += $montant_ht_ligne;
            $montant_tva_total += $montant_tva_ligne;
        }
    }
    
    return [
        'montant_ht' => $montant_ht_total,
        'montant_tva' => $montant_tva_total,
        'montant_ttc' => $montant_ht_total + $montant_tva_total
    ];
}

function saveDevis($db_connection, $code_client, $code_devis, $totals, $status_devis, $is_update) {
    if ($is_update) {
        $stmt = $db_connection->prepare("
            UPDATE Devis 
            SET montant_ht = :montant_ht, montant_ttc = :montant_ttc, status_devis = :status_devis
            WHERE code_devis = :code_devis
        ");
        $stmt->execute([
            ':montant_ht' => $totals['montant_ht'],
            ':montant_ttc' => $totals['montant_ttc'],
            ':code_devis' => $code_devis,
            ':status_devis' => $status_devis
        ]);
    } else {
        $stmt = $db_connection->prepare("
            INSERT INTO Devis (code_client, date_devis, montant_ht, montant_ttc, status_devis) 
            VALUES (:code_client, CURDATE(), :montant_ht, :montant_ttc, :status_devis)
        ");
        $stmt->execute([
            ':code_client' => $code_client,
            ':montant_ht' => $totals['montant_ht'],
            ':montant_ttc' => $totals['montant_ttc'],
            ':status_devis' => $status_devis
        ]);
        
        $code_devis = $db_connection->lastInsertId();
    }
    
    return $code_devis;
}

function saveLines($db_connection, $code_devis, $lignes) {
    foreach ($lignes as $ligne) {
        $code_article = $ligne['code'];
        $quantite = floatval($ligne['quantite']);
        
        $stmt = $db_connection->prepare("
            SELECT forfait_ht FROM Articles WHERE code_article = :code_article
        ");
        $stmt->execute([':code_article' => $code_article]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($article) {
            $prix_unitaire_ht = floatval($article['forfait_ht']);
            $montant_ht_ligne = $quantite * $prix_unitaire_ht;
            
            $stmt = $db_connection->prepare("
                INSERT INTO Lignes_Devis (code_devis, code_article, quantite, prix_unitaire_ht, montant_ht) 
                VALUES (:code_devis, :code_article, :quantite, :prix_unitaire_ht, :montant_ht)
            ");
            $stmt->execute([
                ':code_devis' => $code_devis,
                ':code_article' => $code_article,
                ':quantite' => $quantite,
                ':prix_unitaire_ht' => $prix_unitaire_ht,
                ':montant_ht' => $montant_ht_ligne
            ]);
        }
    }
}

try {
    validateInput();
    
    $code_client = intval($_POST['code_client']);
    $code_devis = isset($_POST['code_devis']) ? intval($_POST['code_devis']) : null;
    $lignes = $_POST['lignes'];
    $is_update = !empty($code_devis);
    $status_devis = intval($_POST['status_devis'] ?? DEVIS_STATUS::ONGOING->value);
    
    $db_connection->beginTransaction();
    
    $totals = calculateTotals($db_connection, $lignes);
    $code_devis = saveDevis($db_connection, $code_client, $code_devis, $totals, $status_devis, $is_update);
    
    if ($is_update) {
        $stmt = $db_connection->prepare("DELETE FROM Lignes_Devis WHERE code_devis = :code_devis");
        $stmt->execute([':code_devis' => $code_devis]);
    }
    
    saveLines($db_connection, $code_devis, $lignes);
    
    $db_connection->commit();
    
    $param = $is_update ? 'devis_updated' : 'devis_created';
    header("Location: fiche_client.php?client=$code_client&$param=$code_devis");
    exit();
    
} catch (PDOException $e) {
    if ($db_connection->inTransaction()) {
        $db_connection->rollBack();
    }
    die("Erreur lors de l'enregistrement du devis: " . $e->getMessage());
}