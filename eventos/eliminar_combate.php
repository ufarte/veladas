<?php
// /eventos/eliminar_combate.php

// Iniciar sesión para poder usar mensajes flash
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db_connect.php'; // Necesitamos $pdo

// --- 1. Validar IDs ---
// Necesitamos tanto el ID del combate a eliminar como el ID del evento para redirigir correctamente
$id_combate = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$id_evento = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

// Si alguno de los IDs falta o no es válido, redirigir a la lista de eventos (o al dashboard)
if (!$id_combate || !$id_evento) {
    $_SESSION['error_message'] = "ID de combate o de evento inválido/faltante para la eliminación.";
    header("Location: listar_eventos.php"); // O redirigir a /index.php
    exit;
}

// --- 2. Intentar Eliminar ---
try {
    if (!isset($pdo)) {
        throw new \Exception("La conexión a la base de datos (\$pdo) no está disponible.");
    }

    // Preparar la consulta DELETE
    // Añadimos id_evento en el WHERE por seguridad, para asegurar que solo borramos del evento correcto.
    $sql_delete = "DELETE FROM combates WHERE id_combate = ? AND id_evento = ?";
    $stmt_delete = $pdo->prepare($sql_delete);

    // Ejecutar la eliminación
    $stmt_delete->execute([$id_combate, $id_evento]);

    // Verificar si se eliminó alguna fila
    if ($stmt_delete->rowCount() > 0) {
        // Éxito: se eliminó el combate
        $_SESSION['success_message'] = "Combate (ID: " . htmlspecialchars($id_combate) . ") eliminado correctamente del evento.";
    } else {
        // No se encontró el combate (quizás ya fue eliminado o el ID era incorrecto para ese evento)
        $_SESSION['error_message'] = "No se encontró el combate con ID " . htmlspecialchars($id_combate) . " en este evento (ID: " . htmlspecialchars($id_evento) . ").";
    }

} catch (\PDOException $e) {
    error_log("Error PDO al eliminar combate: " . $e->getMessage());
    $_SESSION['error_message'] = "Error de base de datos al intentar eliminar el combate (Código: " . $e->getCode() . ").";
} catch (\Exception $e) {
     error_log("Error general al eliminar combate: " . $e->getMessage());
     $_SESSION['error_message'] = "Error general al intentar eliminar el combate: " . $e->getMessage();
}

// --- 3. Redirigir de vuelta a la vista del Evento ---
// Usamos el id_evento que validamos al principio
header("Location: ver_evento.php?id=" . $id_evento);
exit; // Detener ejecución

?>