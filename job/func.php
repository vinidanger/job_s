<?php

function connect($host,$user,$password,$database) {
    try {
        $pcon = "pgsql:host=$host;port=5432;dbname=$database;";
        $pdo = new PDO($pcon, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (PDOException $e) {
        //die($e->getMessage());
        return false;
    }
    return $pdo;
}

function executeData($pdo,$str) {
    if (!$pdo) {
        return false;
    }

    $res = $pdo->query($str);
    if (!$res) {
        return false;
    }

    return $res;
}

?>