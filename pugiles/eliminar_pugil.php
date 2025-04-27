<?php
// /pugiles/eliminar_pugil.php (Redirección Mejorada)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';

$id_pugil = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$ref = $_GET['ref'] ?? 'list'; // 'report' o 'list' (por defecto)
$redirect_url = ($ref === 'report') ? '/informes/pugiles_por_club.php' : '/pugiles/listar_pugiles.php'; // Ajusta rutas base si es necesario

if (!$id_pugil) { $_SESSION['error_message'] = "ID púgil inválido."; header("Location: " . $redirect_url); exit; }

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");
    $stmt_get_name = $pdo->prepare("SELECT nombre_pugil, apellido_pugil FROM pugiles WHERE id_pugil = ?");
    $stmt_get_name->execute([$id_pugil]); $pugil_data = $stmt_get_name->fetch();
    $nombre_completo = $pugil_data ? $pugil_data['nombre_pugil'].' '.$pugil_data['apellido_pugil'] : "ID: $id_pugil";
    $sql_delete = "DELETE FROM pugiles WHERE id_pugil = ?"; $stmt_delete = $pdo->prepare($sql_delete); $stmt_delete->execute([$id_pugil]);
    if ($stmt_delete->rowCount() > 0) { $_SESSION['success_message'] = "Púgil '".htmlspecialchars($nombre_completo)."' eliminado."; }
    else { $_SESSION['error_message'] = "No se encontró púgil ID ".htmlspecialchars($id_pugil)."."; }
} catch (Exception $e) { error_log("Error eliminar púgil ID $id_pugil: ".$e->getMessage()); $_SESSION['error_message'] = "Error al eliminar púgil: " . $e->getMessage(); }

header("Location: " . $redirect_url);
exit;
?>