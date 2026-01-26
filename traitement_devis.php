<?php
require_once 'Ressources_communes.php';

// Check if we have the required data
if (!isset($_POST['code_client']) || empty($_POST['code_client'])) {
    die("Erreur: Aucun client sélectionné.");
}

if (!isset($_POST['lignes']) || empty($_POST['lignes'])) {
    die("Erreur: Aucune ligne de devis.");
}

$code_client = intval($_POST['code_client']);
$code_devis = isset($_POST['code_devis']) ? intval($_POST['code_devis']) : null;
$lignes = $_POST['lignes'];
$is_update = !empty($code_devis);

try {
    // Start transaction
    $db_connection->beginTransaction();

    //start_status cheking
    if ($is_update) {
    // Vérifier le statut du devis - bloqué la modif d'un devis validé
    $stmt = $db_connection->prepare("
        SELECT status_devis FROM Devis WHERE code_devis = :code_devis
    ");
    $stmt->execute([':code_devis' => $code_devis]);
    $devis = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($devis && $devis['status_devis'] == 2) {
        throw new Exception("Ce devis est validé et ne peut plus être modifié.");
    }

    }

    
    // Calculate totals
    $montant_ht_total = 0;
    $montant_tva_total = 0;
    
    // Get article info for each line to calculate TVA
    foreach ($lignes as $ligne) {
        $code_article = $ligne['code'];
        $quantite = floatval($ligne['quantite']);
        
        // Get article price and TVA rate
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
    
    $montant_ttc_total = $montant_ht_total + $montant_tva_total;
    
    if ($is_update) {
        // Update existing devis
        $stmt = $db_connection->prepare("
            UPDATE Devis 
            SET montant_ht = :montant_ht, montant_ttc = :montant_ttc 
            WHERE code_devis = :code_devis
        ");
        $stmt->execute([
            ':montant_ht' => $montant_ht_total,
            ':montant_ttc' => $montant_ttc_total,
            ':code_devis' => $code_devis
        ]);
        
        // Delete existing lines
        $stmt = $db_connection->prepare("DELETE FROM Lignes_Devis WHERE code_devis = :code_devis");
        $stmt->execute([':code_devis' => $code_devis]);
    } else {
        // Insert new devis
        $stmt = $db_connection->prepare("
            INSERT INTO Devis (code_client, date_devis, montant_ht, montant_ttc, status_devis) 
            VALUES (:code_client, CURDATE(), :montant_ht, :montant_ttc, 0)
        ");
        $stmt->execute([
            ':code_client' => $code_client,
            ':montant_ht' => $montant_ht_total,
            ':montant_ttc' => $montant_ttc_total
        ]);
        
        $code_devis = $db_connection->lastInsertId();
    }
    
    // Insert lines (for both new and update)
    foreach ($lignes as $ligne) {
        $code_article = $ligne['code'];
        $quantite = floatval($ligne['quantite']);
        
        // Get article price
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
    
    // Commit transaction
    $db_connection->commit();
    
    // Redirect back to client page with appropriate message
    $param = $is_update ? 'devis_updated' : 'devis_created';
    header("Location: fiche_client.php?client=" . $code_client . "&" . $param . "=" . $code_devis);
    exit();
    
} catch (PDOException $e) {
    // Rollback on error
    if ($db_connection->inTransaction()) {
        $db_connection->rollBack();
    }
    die("Erreur lors de l'enregistrement du devis: " . $e->getMessage());
}
?>