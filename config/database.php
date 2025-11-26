<?php
function db() {
    $host = '127.0.0.1';
    $db   = 'sisperlenteng';
    $user = 'indrawansep';
    $pass = 'indrawansep';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (Exception $e) {
        exit('Database connection error: ' . $e->getMessage());
    }
}


