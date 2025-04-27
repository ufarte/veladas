<?php
// /clubes/añadir_club.php (Con Validación Cliente)

require_once '../includes/db_connect.php';

$nombre_club = '';
$errors = []; // Errores del servidor
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_club = trim($_POST['nombre_club'] ?? '');
    // === INICIO MODIFICACIÓN: Convertir a MAYÚSCULAS ===
    $nombre_club = strtoupper($nombre_club);
    // === FIN MODIFICACIÓN ===

    if (empty($nombre_club)) { $errors[] = "Nombre club vacío."; }
    else { // Verificar duplicados solo si no está vacío
        // ... resto del código de validación y inserción
        try {
            if (!isset($pdo)) throw new Exception("PDO no disponible.");
            $sql_check = "SELECT COUNT(*) FROM clubes WHERE nombre_club = ?";
            $stmt_check = $pdo->prepare($sql_check); $stmt_check->execute([$nombre_club]);
            if ($stmt_check->fetchColumn() > 0) { $errors[] = "Club '".htmlspecialchars($nombre_club)."' ya existe."; }
        } catch (Exception $e) { $errors[] = "Error DB al verificar club."; error_log("Error check club: ".$e->getMessage()); }
    }
    if (empty($errors)) { // Insertar si no hay errores
        try {
            $sql_insert = "INSERT INTO clubes (nombre_club) VALUES (?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            if ($stmt_insert->execute([$nombre_club])) {
                if (session_status() == PHP_SESSION_NONE) session_start();
                $_SESSION['success_message'] = "Club '".htmlspecialchars($nombre_club)."' añadido.";
                header("Location: listar_clubes.php"); exit;
            } else { $errors[] = "Error al guardar."; }
        } catch (Exception $e) { $errors[] = "Error BD al guardar."; error_log("Error insert club: ".$e->getMessage()); }
    }
}

require_once '../includes/header.php'; // Incluye session_start()
?>

<h1><i class="bi bi-plus-circle"></i> Añadir Nuevo Club</h1>
<p>Introduce el nombre del nuevo club.</p>

<a href="listar_clubes.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><strong>¡Error del Servidor!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php /* La validación de cliente se activa con JS, no necesita $success_message aquí si siempre rediriges */ ?>

<form action="añadir_club.php" method="POST" class="needs-validation" novalidate>
    <div class="mb-3">
        <label for="nombre_club" class="form-label">Nombre del Club:</label>
        <input type="text"
               class="form-control" <?php // Bootstrap añade 'is-invalid' vía JS ?>
               id="nombre_club"
               name="nombre_club"
               value="<?php echo htmlspecialchars($nombre_club); ?>"
               required> <?php // <-- Añadido required ?>
        <div class="invalid-feedback"> <?php // <-- Añadido div para mensaje BS ?>
            Por favor, introduce el nombre del club.
        </div>
         <div class="valid-feedback"> <?php // <-- Opcional: mensaje si es válido ?>
            ¡Correcto!
        </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar Club</button>
</form>

<?php require_once '../includes/footer.php'; ?>