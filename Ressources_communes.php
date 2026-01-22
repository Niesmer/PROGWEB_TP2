<?php
function connectDB($username, $password, $dbname = ""): PDO
{
    try {
        $db_connection = new PDO("mysql:host=localhost:8889;dbname=$dbname", $username, $password);
        return $db_connection;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}

function InitDB()
{
    if (!file_exists('base_poly.sql')) {
        die("Le fichier base_poly.sql est introuvable.");
    }
    $db_connection = connectDB("root", "root");
    $sql = file_get_contents('base_poly.sql');
    try {
        $db_connection->exec("CREATE USER IF NOT EXISTS 'user1'@'localhost' IDENTIFIED BY 'hcetylop'");
        $db_connection->exec("GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost' WITH GRANT OPTION");
        $db_connection->exec("CREATE DATABASE IF NOT EXISTS poly_php;");
        $db_connection = connectDB("user1", "hcetylop", "poly_php");
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db_connection->exec($statement);
            }
        }
    } catch (PDOException $e) {
        die($e->getMessage());
    }
}
InitDB();
$db_connection = connectDB("user1", "hcetylop", "poly_php");
?>