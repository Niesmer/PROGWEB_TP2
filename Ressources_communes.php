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
InitDB();
$db_connection = connectDB("user1", "hcetylop", "poly_php");
?>