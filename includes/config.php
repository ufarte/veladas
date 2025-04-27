<?php

//  config.php - Archivo de configuración de la base de datos

// Definir constantes para la conexión a la base de datos
// **¡¡IMPORTANTE!! Reemplaza los valores de ejemplo con tus credenciales reales.**
define('DB_HOST', 'localhost');          // Generalmente 'localhost' si la DB está en el mismo servidor
define('DB_USER', 'boxeo');              // Tu usuario de base de datos
define('DB_PASS', 'An37gelito1!'); // **¡¡PON AQUÍ TU CONTRASEÑA REAL!!** -> An37gelito1!
define('DB_NAME', 'molsotee_boxeo');      // El nombre de tu base de datos

// Opcional: Configuración de Charset para la conexión PDO o MySQLi
define('DB_CHARSET', 'utf8mb4');

// Opcional: Crear la cadena DSN para PDO
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Opcional: Opciones para PDO (ejemplo)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos por defecto
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones nativas de la base de datos
];

/*
// Ejemplo de cómo conectar con PDO (lo haremos en otro archivo, ej: db_connect.php)
try {
     $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
     // En producción, deberías loggear el error y mostrar un mensaje genérico
     // die('Error de conexión a la base de datos.');
}
*/

/*
// Ejemplo de cómo conectar con MySQLi (lo haremos en otro archivo, ej: db_connect.php)
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Establecer charset después de conectar
if (!$mysqli->set_charset(DB_CHARSET)) {
    // printf("Error loading character set utf8mb4: %s\n", $mysqli->error);
}

// Chequear conexión
if ($mysqli->connect_error) {
  die("Connection failed: " . $mysqli->connect_error);
  // En producción, loggear y mensaje genérico.
}
*/

?>