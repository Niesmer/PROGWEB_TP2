<?php
$db_connection = new PDO("mysql:host=localhost;dbname=poly_php", "user1", "hcetylop");

echo(json_encode($_POST));

foreach ($_POST['lignes'] as $ligne) {
    $stmt = $db_connection->prepare("INSERT INTO Lignes_Devis (code_devis, description, quantite, prix_unitaire, total_ht) VALUES (:code_devis, :description, :quantite, :prix_unitaire, :total_ht)");
    $stmt->bindParam(':code_devis', $_POST['code_devis']);
    $stmt->bindParam(':description', $ligne['description']);
    $stmt->bindParam(':quantite', $ligne['quantite']);
    $stmt->bindParam(':prix_unitaire', $ligne['prix_unitaire']);
    $stmt->bindParam(':total_ht', $ligne['total_ht']);
    $stmt->execute();
}
?>