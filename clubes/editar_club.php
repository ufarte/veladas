<?php
// /clubes/editar_club.php (Con Validación Cliente)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

$id_club = null; $nombre_club_actual = ''; $nombre_club_nuevo = '';
$errors = []; $is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$form_values = [];

// --- Obtener ID y Datos (igual que antes) ---
$id_club = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($is_post_request) { $id_club_post = filter_input(INPUT_POST, 'id_club', FILTER_VALIDATE_INT); if ($id_club_post) $id_club = $id_club_post; $form_values = $_POST; }
if (!$id_club) { $_SESSION['error_message'] = "ID club inválido."; header("Location: listar_clubes.php"); exit; }
if (!$is_post_request) { try { if (!isset($pdo)) throw new Exception("PDO no disp."); $sql_fetch = "SELECT nombre_club FROM clubes WHERE id_club = ?"; $stmt_fetch = $pdo->prepare($sql_fetch); $stmt_fetch->execute([$id_club]); $club = $stmt_fetch->fetch(); if (!$club) { $_SESSION['error_message'] = "Club no encontrado."; header("Location: listar_clubes.php"); exit; } $nombre_club_actual = $club['nombre_club']; $form_values['nombre_club'] = $club['nombre_club']; } catch (Exception $e) { $errors[] = "Error cargando datos: " . $e->getMessage(); } }

// --- Procesar POST (Update - igual que antes) ---
if ($is_post_request && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico')))) {
    $nombre_club_nuevo = trim($form_values['nombre_club'] ?? '');
    if (empty($nombre_club_nuevo)) { $errors[] = "Nombre obligatorio."; }
    else { /* Check duplicados (excluyendo self) */ try { if (!isset($pdo)) throw new Exception("PDO no disp."); $sql_check = "SELECT COUNT(*) FROM clubes WHERE nombre_club = ? AND id_club != ?"; $stmt_check = $pdo->prepare($sql_check); $stmt_check->execute([$nombre_club_nuevo, $id_club]); if ($stmt_check->fetchColumn() > 0) { $errors[] = "Ya existe OTRO club con nombre '".htmlspecialchars($nombre_club_nuevo)."'."; } } catch (Exception $e) { $errors[]="Error DB check duplicado."; error_log("Err check club edit: ".$e->getMessage()); } }
    if (empty($errors)) { /* UPDATE */ try { $sql_update = "UPDATE clubes SET nombre_club = ? WHERE id_club = ?"; $stmt_update = $pdo->prepare($sql_update); if ($stmt_update->execute([$nombre_club_nuevo, $id_club])) { $_SESSION['success_message'] = "Club '".htmlspecialchars($nombre_club_nuevo)."' actualizado."; header("Location: listar_clubes.php"); exit; } else { $errors[] = "Error desconocido al actualizar."; } } catch (Exception $e) { $errors[] = "Error BD al actualizar."; error_log("Err update club: ".$e->getMessage()); } }
}

// --- Mostrar Página ---
?>
<h1><i class="bi bi-pencil-square"></i> Editar Club</h1>
<a href="listar_clubes.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>¡Error Servidor!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<?php if ($evento_actual || $is_post_request || isset($form_values['nombre_club'])): // Mostrar form si hay datos ?>
<form action="editar_club.php?id=<?php echo htmlspecialchars($id_club); ?>" method="POST" class="needs-validation" novalidate> <?php // <-- Clases Añadidas ?>
    <input type="hidden" name="id_club" value="<?php echo htmlspecialchars($id_club); ?>">
    <div class="mb-3">
        <label for="nombre_club" class="form-label">Nombre del Club:</label>
        <input type="text"
               class="form-control"
               id="nombre_club"
               name="nombre_club"
               value="<?php echo htmlspecialchars($form_values['nombre_club'] ?? ''); ?>"
               required> <?php // <-- Required Añadido ?>
        <div class="invalid-feedback"> <?php // <-- Feedback Añadido ?>
            Por favor, introduce el nombre del club.
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
</form>
<?php elseif (empty($errors)): ?>
    <div class="alert alert-warning">No se pudieron cargar los datos del club.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>