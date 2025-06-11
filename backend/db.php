<?php
// =============================================
// CONEXIÓN SEGURA A LA BASE DE DATOS
// =============================================

// Reemplaza con tus credenciales reales o usa variables de entorno
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

    // Conexión exitosa, no hacer nada
} catch (PDOException $e) {
    // Puedes loguear el error aquí si lo deseas
    die("Error al conectar con la BD.");
}
