<?php
    $host = "localhost";
    $user = "u347279731_baco_robles";
    $password = "Baco_Robles_2025";
    $database = "u347279731_baco_robles_db";
    
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $message = "";
?>
