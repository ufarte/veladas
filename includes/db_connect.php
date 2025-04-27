<?php
// includes/db_connect.php

// Incluir el archivo de configuración una sola vez
require_once 'config.php';

// Variable global para la conexión PDO
$pdo = null;

try {
    // Usar las constantes definidas en config.php para crear el DSN y conectar
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Crear la instancia PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (\PDOException $e) {
    // En un entorno real, loggear el error en lugar de mostrarlo directamente
    // error_log("Error de conexión a BD: " . $e->getMessage()); // Ejemplo de log
    // Mostrar un mensaje genérico al usuario
    die("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
    // O podrías redirigir a una página de error personalizada
    // header('Location: /error_db.php');
    // exit;
}

// Ahora la variable $pdo está disponible para ser usada en otros scripts que incluyan este archivo.
?>