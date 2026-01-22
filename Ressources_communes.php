<?php
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