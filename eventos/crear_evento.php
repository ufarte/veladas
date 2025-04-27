<?php
// /eventos/crear_evento.php (v2 - Con Selects Municipio/Recinto)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

$form_values = $_POST; // Usar POST para pre-rellenar si hay error
$errors = [];
$municipios_lista = []; // Para el desplegable

// Cargar lista de municipios
try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");
    $stmt_mun = $pdo->query("SELECT id_municipio, nombre_municipio FROM municipios ORDER BY nombre_municipio ASC");
    $municipios_lista = $stmt_mun->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = "Error al cargar la lista de municipios: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos (ahora IDs para municipio/recinto)
    $nombre_evento_form = trim($form_values['nombre_evento'] ?? '');
    $fecha_evento_form = trim($form_values['fecha_evento'] ?? '');
    $id_municipio_form = filter_input(INPUT_POST, 'id_municipio', FILTER_VALIDATE_INT); // <-- NUEVO
    $id_recinto_form = filter_input(INPUT_POST, 'id_recinto', FILTER_VALIDATE_INT);     // <-- NUEVO
    $max_combates_form = trim($form_values['max_combates'] ?? '');

    // Validar datos
    if (empty($nombre_evento_form)) $errors[] = "Nombre obligatorio.";
    if (empty($fecha_evento_form)) { $errors[] = "Fecha obligatoria."; }
    // ... (resto de validaciones de fecha) ...
     elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$fecha_evento_form)){$errors[]="Formato fecha inválido.";} else {try{$d=new DateTime($fecha_evento_form);$y=(int)$d->format('Y');$cy=(int)date('Y');if($d->format('Y-m-d')!==$fecha_evento_form||$y<($cy-1)||$y>($cy+5))$errors[]="Fecha fuera de rango razonable.";}catch(Exception $e){$errors[]="Fecha inválida.";}}

    // Validar IDs seleccionados
    if (empty($id_municipio_form)) { $errors[] = "Debe seleccionar un municipio."; }
    if (empty($id_recinto_form)) { $errors[] = "Debe seleccionar un recinto."; }
    // Podríamos añadir una verificación extra para asegurar que el recinto pertenece al municipio, pero JS debería prevenir selecciones inválidas.

    if (empty($max_combates_form)) { $errors[] = "Max. combates obligatorio."; }
    elseif (!filter_var($max_combates_form, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) { $errors[] = "Max. combates debe ser número positivo."; }

    // Insertar si no hay errores
    if (empty($errors)) {
        try {
            // OJO: Usamos las NUEVAS columnas id_municipio, id_recinto
            // Las antiguas lugar_celebracion, recinto_deportivo ya no se usan desde el form
            $sql_insert = "INSERT INTO eventos (nombre_evento, fecha_evento, id_municipio, id_recinto, max_combates)
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            if ($stmt_insert->execute([
                $nombre_evento_form,
                $fecha_evento_form,
                $id_municipio_form,
                $id_recinto_form,
                (int)$max_combates_form
            ])) {
                $nuevo_evento_id = $pdo->lastInsertId();
                $_SESSION['success_message'] = "Evento '".htmlspecialchars($nombre_evento_form)."' creado con ID: $nuevo_evento_id.";
                // Redirigir a añadir combates para este nuevo evento
                header("Location: añadir_combate.php?evento_id=" . $nuevo_evento_id);
                exit;
            } else {
                $errors[] = "Error desconocido al guardar el evento.";
            }
        } catch (PDOException $e) {
             error_log("Error PDO crear evento: ".$e->getMessage());
             $errors[] = "Error de base de datos al guardar (PDO). Código: ".$e->getCode();
        } catch (Exception $e) {
            error_log("Error general crear evento: ".$e->getMessage());
            $errors[] = "Error general al guardar: " . $e->getMessage();
        }
    }
}
?>

<h1><i class="bi bi-calendar-plus"></i> Crear Nuevo Evento (Velada)</h1>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>¡Error!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<form action="crear_evento.php" method="POST" class="needs-validation" novalidate>
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
            <label for="id_municipio" class="form-label">Municipio:</label> <?php // <-- CAMBIADO ?>
            <select class="form-select" id="id_municipio" name="id_municipio" required> <?php // <-- CAMBIADO a Select ?>
                <option value="" selected disabled>--- Seleccionar Municipio ---</option>
                <?php foreach ($municipios_lista as $municipio): ?>
                    <option value="<?php echo $municipio['id_municipio']; ?>" <?php if (($form_values['id_municipio'] ?? '') == $municipio['id_municipio']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($municipio['nombre_municipio']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
             <div class="invalid-feedback">Selecciona un municipio.</div>
        </div>
        <div class="col-md-6 mb-3">
             <label for="id_recinto" class="form-label">Recinto Deportivo:</label> <?php // <-- CAMBIADO ?>
             <select class="form-select" id="id_recinto" name="id_recinto" required disabled> <?php // <-- CAMBIADO a Select, empieza disabled ?>
                 <option value="" selected>--- Selecciona Municipio Primero ---</option>
                 <?php // Las opciones se cargarán con JavaScript ?>
                 <?php // Si hubo error POST, intentamos mostrar el recinto que se había seleccionado?>
                 <?php if (!empty($form_values['id_recinto']) && !empty($form_values['id_municipio'])): ?>
                     <option value="<?php echo htmlspecialchars($form_values['id_recinto']); ?>" selected>(Recargar Recintos...)</option>
                 <?php endif; ?>
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
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i> Crear Evento y Añadir Combates</button>
</form>

<?php require_once '../includes/footer.php'; ?>

<?php // El JavaScript para los desplegables dependientes se incluirá en el footer ?>