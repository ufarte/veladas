<?php
// /eventos/eliminar_evento.php

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';

// --- Validar ID ---
$id_evento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if (!$id_evento) {
    $_SESSION['error_message'] = "ID de evento inválido o no proporcionado.";
    header("Location: listar_eventos.php"); exit;
}

// --- Intentar Eliminar ---
try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");

    // Obtener nombre para mensaje (opcional, antes de borrar)
    $stmt_get_name = $pdo->prepare("SELECT nombre_evento FROM eventos WHERE id_evento = ?");
    $stmt_get_name->execute([$id_evento]);
    $nombre_evento = $stmt_get_name->fetchColumn();

    // Preparar DELETE (ON DELETE CASCADE borrará los combates asociados)
    $sql_delete = "DELETE FROM eventos WHERE id_evento = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$id_evento]);

    if ($stmt_delete->rowCount() > 0) {
        $_SESSION['success_message'] = "Evento '" . htmlspecialchars($nombre_evento ?: "ID: $id_evento") . "' y todos sus combates asociados han sido eliminados.";
    } else {
        $_SESSION['error_message'] = "No se encontró el evento con ID " . htmlspecialchars($id_evento) . " o ya había sido eliminado.";
    }

} catch (PDOException $e) {
    error_log("Error PDO al eliminar evento: " . $e->getMessage());
    $_SESSION['error_message'] = "Error de base de datos al eliminar el evento (Código: " . $e->getCode() . ").";
} catch (Exception $e) {
     error_log("Error general al eliminar evento: " . $e->getMessage());
     $_SESSION['error_message'] = "Error general al eliminar el evento: " . $e->getMessage();
}

// --- Redirigir a la Lista ---
header("Location: listar_eventos.php");
exit;

?>