<?php
// /clubes/eliminar_club.php

// Iniciar sesión para poder usar mensajes flash
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db_connect.php'; // Necesitamos $pdo

// --- 1. Validar ID ---
$id_club = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if (!$id_club) {
    // Si el ID no es válido o no se proporciona
    $_SESSION['error_message'] = "ID de club inválido o no proporcionado para eliminar.";
    header("Location: listar_clubes.php");
    exit;
}

// --- 2. Intentar Eliminar ---
// Nota: La FK en pugiles está ON DELETE SET NULL, así que no necesitamos comprobar púgiles aquí.
try {
    if (!isset($pdo)) {
        throw new \Exception("La conexión a la base de datos (\$pdo) no está disponible.");
    }

    // Obtener nombre para mensaje (opcional, antes de borrar)
    $stmt_get_name = $pdo->prepare("SELECT nombre_club FROM clubes WHERE id_club = ?");
    $stmt_get_name->execute([$id_club]);
    $nombre_club = $stmt_get_name->fetchColumn();

    // Preparar la consulta DELETE
    $sql_delete = "DELETE FROM clubes WHERE id_club = ?";
    $stmt_delete = $pdo->prepare($sql_delete);

    // Ejecutar la eliminación
    $stmt_delete->execute([$id_club]);

    // Verificar si se eliminó alguna fila
    if ($stmt_delete->rowCount() > 0) {
        // Éxito: se eliminó el club
        $_SESSION['success_message'] = "Club '" . htmlspecialchars($nombre_club ?: "ID: $id_club") . "' eliminado correctamente.";
    } else {
        // No se encontró el club (quizás ya fue eliminado)
        $_SESSION['error_message'] = "No se encontró ningún club con el ID " . htmlspecialchars($id_club) . " para eliminar.";
    }

} catch (\PDOException $e) {
    error_log("Error PDO al eliminar club: " . $e->getMessage());
    // Podría haber un error si OTRA tabla tuviera una restricción FK sin ON DELETE SET NULL/CASCADE
    $_SESSION['error_message'] = "Error de base de datos al intentar eliminar el club (Código: " . $e->getCode() . "). Verifique si está siendo usado en otra parte.";
} catch (\Exception $e) {
     error_log("Error general al eliminar club: " . $e->getMessage());
     $_SESSION['error_message'] = "Error general al intentar eliminar el club: " . $e->getMessage();
}

// --- 3. Redirigir a la Lista ---
header("Location: listar_clubes.php");
exit; // Detener ejecución

?>