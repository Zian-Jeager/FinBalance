<?php
// =============================================
// CONEXIÓN A LA BASE DE DATOS :)
// =============================================

$host = 'shinkansen.proxy.rlwy.net';
$port = '55403';
$dbname = 'railway';
$username = 'root';
$password = 'LswOpRtnOUlOTmEaFviGmmycfUaNqMHF';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

} catch (PDOException $e) {

    die("Error al conectar con la BD.");
}
