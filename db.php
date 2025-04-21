<?php 

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nom_de_votre_base";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}