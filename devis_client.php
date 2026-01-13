<?php
$db_connection = new PDO("mysql:host=localhost", "root", "");

# Permet de créer un nouvel utilisateur user1 avec tout les privilèges
$db_connection->query("CREATE USER IF NOT EXISTS 'user1'@'localhost' IDENTIFIED BY 'hcetylop'");
$db_connection->query("GRANT ALL PRIVILEGES ON *.* TO 'user1'@'localhost' WITH GRANT OPTION");

# On recrée une connection au serveur Mysql en se connectant avec le nouvel utilisateur
$db_connection = new PDO("mysql:host=localhost", "user1", "hcetylop");

$db_connection->query("CREATE DATABASE IF NOT EXISTS poly_php;");
$db_connection->query("USE poly_php");

$sql = false;
if (file_exists('data.sql')) {
    $sql = file_get_contents('data.sql');
}
if ($sql !== false) {
    try {
        $statements = explode(';', $sql);
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $db_connection->exec($statement);
            }
        }
    } catch (PDOException $e) {
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./global.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.css" rel="stylesheet" />
    <title>Nouveau Devis SAV</title>
</head>

<body class="bg-gray-100 dark:bg-gray-900">
    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
    <main class="min-h-screen">
        <?php include("./components/request_form.php") ?> 
    </main>
</body>

</html>