<?php

$driver = 'mysql';
$host = 'localhost';
$username = 'http_user';
$password = 'bf6912';
$port = 3306;
$database = 'cmcintosh';
$charset = 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

return [
    'driver' => $driver,
    'host' => $host,
    'port' => $port,
    'database' => $database,
    'username' => $username,
    'password' => $password,
    'charset' => $charset,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
    'dsn' => $dsn,
];
