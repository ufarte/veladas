<?php
// /eventos/editar_evento.php (v2 - Con Selects Municipio/Recinto)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

$id_evento = null; $evento_actual = null; $errors = [];
$is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$form_values = []; // Para guardar valores POST o los cargados de BD
$municipios_lista = []; // Para el desplegable

// --- Obtener ID Evento ---
$id_evento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($is_post_request) { $id_evento_post = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT); if ($id_evento_post) $id_evento = $id_evento_post; $form_values = $_POST; }
if (!$id_evento) { $_SESSION['error_message'] = "ID evento inválido."; header("Location: listar_eventos.php"); exit; }

// --- Cargar Lista de Municipios (siempre necesario) ---
try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");
    $stmt_mun = $pdo->query("SELECT id_municipio, nombre_municipio FROM municipios ORDER BY nombre_municipio ASC");
    $municipios_lista = $stmt_mun->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $errors[] = "Error al cargar la lista de municipios: " . $e->getMessage(); }

// --- Cargar Datos Evento Actual (si es GET inicial) ---
if (!$is_post_request && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico')))) {
    try {
        $sql_fetch = "SELECT * FROM eventos WHERE id_evento = ?"; $stmt_fetch = $pdo->prepare($sql_fetch); $stmt_fetch->execute([$id_evento]); $evento_actual = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
        if (!$evento_actual) { $_SESSION['error_message'] = "Evento no encontrado."; header("Location: listar_eventos.php"); exit; }
        $form_values = $evento_actual; // Cargar datos actuales en form_values
        // Los campos lugar_celebracion y recinto_deportivo (VARCHAR) ya no se usan directamente
    } catch (Exception $e) { $errors[] = "Error crítico cargando datos: " . $e->getMessage(); }
}
// $form_values ahora tiene los datos de POST o los datos originales de la BD

// --- Procesar POST para Actualizar ---
if ($is_post_request && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico')))) {
    // Recoger datos (IDs de municipio/recinto)
    $nombre_evento_form = trim($form_values['nombre_evento'] ?? ''); $fecha_evento_form = trim($form_values['fecha_evento'] ?? ''); $id_municipio_form = filter_input(INPUT_POST, 'id_municipio', FILTER_VALIDATE_INT); $id_recinto_form = filter_input(INPUT_POST, 'id_recinto', FILTER_VALIDATE_INT); $max_combates_form = trim($form_values['max_combates'] ?? '');

    // Validar datos (igual que en crear)
    if(empty($nombre_evento_form))$errors[]="Nombre.";if(empty($fecha_evento_form))$errors[]="Fecha.";elseif(!preg_match("/^\d{4}-\d{2}-\d{2}$/",$fecha_evento_form))$errors[]="Formato fecha.";else{/*validar rango fecha*/} if(empty($id_municipio_form))$errors[]="Municipio.";if(empty($id_recinto_form))$errors[]="Recinto.";if(empty($max_combates_form))$errors[]="Max combates.";elseif(!filter_var($max_combates_form,FILTER_VALIDATE_INT,["options"=>["min_range"=>1]])) $errors[]="Max combates inválido.";

    // Update si no hay errores
    if (empty($errors)) {
        try {
            $sql_update = "UPDATE eventos SET
                                nombre_evento = ?, fecha_evento = ?, id_municipio = ?,
                                id_recinto = ?, max_combates = ?
                           WHERE id_evento = ?";
            $stmt_update = $pdo->prepare($sql_update);
            if ($stmt_update->execute([ $nombre_evento_form, $fecha_evento_form, $id_municipio_form, $id_recinto_form, (int)$max_combates_form, $id_evento ])) {
                $_SESSION['success_message'] = "Evento '".htmlspecialchars($nombre_evento_form)."' actualizado."; header("Location: listar_eventos.php"); exit;
            } else { $errors[] = "Error desconocido al actualizar."; }
        } catch (Exception $e) { $errors[] = "Error BD al actualizar: " . $e->getMessage(); }
    }
}

// --- Pasar datos iniciales (o de POST) a JavaScript ---
// Necesitamos saber el id_municipio y id_recinto iniciales para que JS los preseleccione
$initial_js_data = json_encode([
    'id_municipio' => $form_values['id_municipio'] ?? null,
    'id_recinto'   => $form_values['id_recinto'] ?? null
]);

// --- Mostrar Página ---
?>
<h1><i class="bi bi-pencil-square"></i> Editar Evento</h1>
<a href="listar_eventos.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>¡Error!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<?php if (!empty($form_values)): // Mostrar form si tenemos datos (de GET o POST fallido) ?>
<script id="evento-initial-data" type="application/json"><?php echo $initial_js_data; ?></script>

<form action="editar_evento.php?id=<?php echo htmlspecialchars($id_evento); ?>" method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="id_evento" value="<?php echo htmlspecialchars($id_evento); ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre_evento" class="form-label">Nombre del Evento:</label>
            <input type="text" class="form-control" id="nombre_evento" name="nombre_evento" value="<?php echo htmlspecialchars($form_values['nombre_evento'] ?? ''); ?>" required>
            <div class="invalid-feedback">Introduce el nombre del evento.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="fecha_evento" class="form-label">Fecha del Evento:</label>
            <input type="date" class="form-control" id="fecha_evento" name="fecha_evento" value="<?php echo htmlspecialchars($form_values['fecha_evento'] ?? ''); ?>" required>
            <div class="invalid-feedback">Introduce una fecha válida.</div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_municipio" class="form-label">Municipio:</label>
            <select class="form-select" id="id_municipio" name="id_municipio" required>
                <option value="" disabled>--- Seleccionar Municipio ---</option>
                 <?php foreach ($municipios_lista as $municipio): ?>
                    <option value="<?php echo $municipio['id_municipio']; ?>" <?php if (($form_values['id_municipio'] ?? '') == $municipio['id_municipio']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($municipio['nombre_municipio']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona un municipio.</div>
        </div>
        <div class="col-md-6 mb-3">
            <label for="id_recinto" class="form-label">Recinto Deportivo:</label>
            <select class="form-select" id="id_recinto" name="id_recinto" required <?php if(empty($form_values['id_municipio'] ?? '')) echo 'disabled'; // Deshabilitado si no hay municipio inicial?>>
                 <option value="">--- Selecciona Municipio Primero ---</option>
                 <?php // JS cargará las opciones y preseleccionará basado en initial_js_data ?>
            </select>
            <div class="invalid-feedback">Selecciona un recinto deportivo.</div>
        </div>
    </div>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="max_combates" class="form-label">Nº Máximo de Combates:</label>
            <input type="number" class="form-control" id="max_combates" name="max_combates" value="<?php echo htmlspecialchars($form_values['max_combates'] ?? ''); ?>" min="1" required>
             <div class="invalid-feedback">Introduce un número válido (mínimo 1).</div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save"></i> Guardar Cambios</button>
</form>
<?php elseif(empty($errors)): ?>
    <div class="alert alert-warning">No se pudieron cargar los datos del evento para editar.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
<?php // El JavaScript para los desplegables dependientes se incluirá en el footer ?>