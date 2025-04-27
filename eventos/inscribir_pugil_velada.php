<?php
// /eventos/inscribir_pugil_velada.php - Formulario de Inscripción de Púgil a Velada (v4 - Fix Club en POST)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // Para calcularCategoriaEdad y estandarización de nombre/apellido
require_once '../includes/header.php'; // Asume que incluye inicio de sesión y Bootstrap

// --- Variables e Inicialización ---
$evento_id = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT);
$evento = null;
$clubes = [];
$form_values = $_POST; // Usar POST para pre-rellenar si hay error de validación
$errors = [];
$success_message = ''; // Se usará para mostrar un mensaje de éxito en la misma página si no redirigimos inmediatamente

// --- 1. Validar ID de Evento y cargar sus datos ---
if (!$evento_id) {
    $_SESSION['error_message'] = "ID de evento inválido o no proporcionado para la inscripción.";
    header("Location: listar_eventos.php"); // Redirigir a la lista de eventos si falta el ID
    exit;
}

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");

    // Cargar datos del evento
    $sql_evento = "SELECT id_evento, nombre_evento, fecha_evento
                   FROM eventos
                   WHERE id_evento = ?";
    $stmt_evento = $pdo->prepare($sql_evento);
    $stmt_evento->execute([$evento_id]);
    $evento = $stmt_evento->fetch(PDO::FETCH_ASSOC);

    if (!$evento) {
        $_SESSION['error_message'] = "Evento no encontrado.";
        header("Location: listar_eventos.php"); // Redirigir si el evento no existe en BD
        exit;
    }

    // Cargar lista de clubes para el desplegable del formulario
    $stmt_clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC");
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errors[] = "Error crítico cargando datos iniciales: " . $e->getMessage();
    error_log("Error loading data for inscribir_pugil_velada.php: " . $e->getMessage());
}

// --- 2. Procesar POST (Lógica de Guardado IMPLEMENTADA) ---
// El filtro ignora el error placeholder si existe para permitir que la lógica real se ejecute.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty(array_filter($errors, fn($e) => strpos($e, 'La lógica de guardado') !== false))) {
    // === INICIO: Lógica de procesamiento POST ===

    // Recibir datos del formulario (POST)
    $id_evento_post = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT); // Should match $evento_id
    $selected_pugil_value = trim($_POST['id_pugil_inscripcion'] ?? ''); // Value from the pugil select (ID, '', or '--ADD_NEW--')

    // Data from manual fields (will be used if selected_pugil_value is '--ADD_NEW--')
    $nombre_pugil_manual = trim($form_values['nombre_pugil'] ?? '');
    $apellido_original_manual = trim($form_values['apellido_pugil'] ?? '');
    $fecha_nacimiento_manual = trim($form_values['fecha_nacimiento'] ?? '');
    $sexo_manual = trim($form_values['sexo'] ?? ''); // Use $form_values as it's populated earlier
    $id_club_pertenencia_form = trim($form_values['id_club_pertenencia_inscripcion'] ?? ''); // Club selected at the top
    $notas_form = trim($form_values['notas'] ?? '');

    // Re-validate event ID from POST
    if ($id_evento_post !== $evento_id) { // Check if the hidden ID matches the GET ID
        $errors[] = "Inconsistencia en el ID del evento al enviar el formulario.";
        error_log("Inconsistency in event ID during POST: GET={$evento_id}, POST={$id_evento_post}");
    }

    // --- Determine the Club ID for the pugiles table (CORREGIDO) ---
    $id_club_para_db = null; // Default value if the pugil doesn't belong to a club
    $club_selected_value = trim($form_values['id_club_pertenencia_inscripcion'] ?? ''); // Get value from the main club select

    // Validate and determine the final $id_club_para_db
    if (empty($club_selected_value)) {
        // "--- Sin Club ---" seleccionado -> ID será NULL en pugiles
        $id_club_para_db = null;
    } elseif ($club_selected_value === 'PROFESIONAL') {
        // "--- PROFESIONAL (Sin Club) ---" seleccionado -> ID será NULL en pugiles
        $id_club_para_db = null; // Los profesionales generalmente no tienen id_club_pertenencia
    } else {
        // Se seleccionó un ID que debería ser de un Club real
        $club_id_int = filter_var($club_selected_value, FILTER_VALIDATE_INT);
        if ($club_id_int === false || $club_id_int === null) {
             // No es un ID entero válido (Debería ser atrapado por validación client-side required)
             $errors[] = "Club seleccionado inválido."; // Añadir error si no es un int válido
             $id_club_para_db = null; // Asegurar que sea null si es inválido
        } else {
             // Verificar si el club ID entero realmente existe en la lista cargada
             $club_exists = false;
             foreach($clubes as $club_option) {
                 if ($club_option['id_club'] == $club_id_int) {$club_exists=true; break;}
             }
             if (!$club_exists) {
                 $errors[] = "El club seleccionado no existe."; // Añadir error si no está en la lista
                 $id_club_para_db = null; // Asegurar que sea null si no existe
             } else {
                 $id_club_para_db = $club_id_int; // Usar el ID entero válido
             }
        }
    }
    // --- FIN Determine the Club ID ---


    // --- Determinar el ID del Púgil para la Inscripción ---
    $id_pugil_para_inscripcion = null; // This will hold the final pugil ID

    if (empty($errors)) { // Proceed only if initial event ID validation and Club validation is OK

        if (filter_var($selected_pugil_value, FILTER_VALIDATE_INT) !== false && $selected_pugil_value > 0) {
            // === Caso 1: Se seleccionó un Púgil EXISTENTE (por su ID) ===
            $id_pugil_existente_seleccionado = filter_var($selected_pugil_value, FILTER_VALIDATE_INT);

            if ($id_pugil_existente_seleccionado === false || $id_pugil_existente_seleccionado === null) {
                $errors[] = "ID de púgil seleccionado no válido.";
                error_log("Invalid pugil ID selected from dropdown: " . $selected_pugil_value);
            } else {
                // Optional: Verify if this ID actually exists in the pugiles table
                // For simplicity, we trust the dropdown was populated correctly by JS
                $id_pugil_para_inscripcion = $id_pugil_existente_seleccionado;
                error_log("DEBUG: Púgil existente seleccionado con ID: " . $id_pugil_para_inscripcion);
            }

        } elseif ($selected_pugil_value === '--ADD_NEW--') {
            // === Caso 2: Se eligió AÑADIR NUEVO PÚGIL ===

            // Validate manual fields (required if ADD_NEW)
            if (empty($nombre_pugil_manual)) $errors[] = "Nombre del nuevo púgil es obligatorio.";
            if (empty($apellido_original_manual)) $errors[] = "Apellido del nuevo púgil es obligatorio.";
            if (empty($fecha_nacimiento_manual)) { $errors[] = "Fecha de nacimiento del nuevo púgil es obligatoria."; }
             elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento_manual)){ $errors[] = "Formato de fecha de nacimiento del nuevo púgil inválido."; } else { try { $d=new DateTime($fecha_nacimiento_manual); $y=(int)$d->format('Y'); $cy=(int)date('Y'); if($d->format('Y-m-d')!==$fecha_nacimiento_manual || $y>$cy || $y<($cy-100)) $errors[] = "Fecha de nacimiento del nuevo púgil inválida."; } catch (Exception $e) { $errors[] = "Fecha de nacimiento del nuevo púgil inválida."; } }
            if (empty($sexo_manual) || !in_array($sexo_manual, ['Masculino', 'Femenino'])) $errors[] = "Sexo del nuevo púgil inválido.";

             // Si hay errores de validación de los campos manuales, detenemos aquí.
             if (!empty($errors)) {
                  // Los errores se mostrarán al recargar la página con $form_values
             } else {
                 // Apply standardization to manual inputs
                 $apellido_procesado = ''; // Variable para el apellido después de procesar
                 if (!empty($apellido_original_manual)) {
                     $apellido_parts = explode(' ', $apellido_original_manual);
                     $processed_parts = [];
                     if (!empty($apellido_parts[0])) {
                         $processed_parts[] = $apellido_parts[0];
                         $first_word_upper = strtoupper($apellido_parts[0]);
                         $compound_prefixes = ['DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS'];
                         if (count($apellido_parts) > 1 && in_array($first_word_upper, $compound_prefixes)) { // Corregida condición para count
                             $second_word_upper = strtoupper($apellido_parts[1]);
                             if ($first_word_upper === 'DE' && in_array($second_word_upper, ['LA', 'LOS', 'LAS']) && count($apellido_parts) > 2) {
                                  $processed_parts[] = $apellido_parts[1];
                                  $processed_parts[] = $apellido_parts[2];
                             } else {
                                  $processed_parts[] = $apellido_parts[1];
                             }
                         }
                     }
                     $apellido_procesado = implode(' ', $processed_parts);
                 }

                 $nombre_procesado = strtoupper($nombre_pugil_manual);
                 $apellido_final_db = strtoupper($apellido_procesado);

                 // Validar que el apellido procesado no quede vacío si era obligatorio
                  if (empty($apellido_final_db)) {
                       $errors[] = "El apellido del nuevo púgil no puede quedar vacío después de procesar.";
                  }

                  if (empty($errors)) { // Proceed if manual field validation and standardization is okay
                       // Search for existing pugil with these standardized manual details
                       $id_pugil_buscado = null;
                       try {
                           $sql_buscar_pugil = "SELECT id_pugil FROM pugiles
                                                WHERE nombre_pugil = ?
                                                  AND apellido_pugil = ?
                                                  AND fecha_nacimiento = ?
                                                  AND sexo = ?";
                           $params_buscar = [$nombre_procesado, $apellido_final_db, $fecha_nacimiento_manual, $sexo_manual];

                           // Use the derived $id_club_para_db for the search query
                           if ($id_club_para_db === null) {
                               $sql_buscar_pugil .= " AND id_club_pertenencia IS NULL";
                           } else {
                               $sql_buscar_pugil .= " AND id_club_pertenencia = ?";
                               $params_buscar[] = $id_club_para_db;
                           }

                           $stmt_buscar_pugil = $pdo->prepare($sql_buscar_pugil);
                           $stmt_buscar_pugil->execute($params_buscar);
                           $resultado_busqueda = $stmt_buscar_pugil->fetch(PDO::FETCH_ASSOC);

                           if ($resultado_busqueda) {
                               $id_pugil_buscado = $resultado_busqueda['id_pugil'];
                               error_log("DEBUG: Púgil existente (buscado por datos manuales) encontrado con ID: " . $id_pugil_buscado);
                               $id_pugil_para_inscripcion = $id_pugil_buscado; // Usar el existente
                               $_SESSION['success_message'] = "El púgil introducido ya existe. Se ha usado el registro existente para la inscripción."; // Mensaje informativo

                           } else {
                                // If not found, create the new pugil
                                error_log("DEBUG: No se encontró púgil existente por datos manuales, se creará uno nuevo.");
                                $es_profesional = 0; // Asumimos amateur por defecto (o read from form if added)

                               $sql_insert_pugil = "INSERT INTO pugiles (nombre_pugil, apellido_pugil, fecha_nacimiento, sexo, es_profesional, id_club_pertenencia)
                                                    VALUES (?, ?, ?, ?, ?, ?)";
                               $stmt_insert_pugil = $pdo->prepare($sql_insert_pugil);

                               if ($stmt_insert_pugil->execute([
                                   $nombre_procesado,
                                   $apellido_final_db,
                                   $fecha_nacimiento_manual,
                                   $sexo_manual,
                                   $es_profesional,
                                   $id_club_para_db // Use the derived $id_club_para_db
                               ])) {
                                   $id_pugil_para_inscripcion = $pdo->lastInsertId();
                                   error_log("DEBUG: Nuevo púgil creado con ID: " . $id_pugil_para_inscripcion);
                               } else {
                                   $errors[] = "Error al crear el nuevo púgil.";
                                    error_log("Error inserting new pugil (manual entry): " . implode(", ", $stmt_insert_pugil->errorInfo()));
                               }
                           }

                       } catch (Exception $e) {
                           $errors[] = "Error BD al buscar/crear púgil: " . $e->getMessage();
                           error_log("Error DB searching/inserting pugil (manual entry): " . $e->getMessage());
                       }
                  } // End if empty($errors) after standardization validation
             } // End if empty($errors) after basic manual field validation

        } else {
            // === Caso 3: No se seleccionó un púgil existente válido ni se eligió Añadir Nuevo ===
            // This happens if selected_pugil_value is '', 0, false, or some unexpected non-numeric non-ADD_NEW value.
            // If it's '', it means the required select wasn't chosen or was reset.
            $errors[] = "Debes seleccionar un púgil existente o introducir los datos para añadir uno nuevo.";
            error_log("DEBUG: Selección de púgil no reconocida. selected_pugil_value: " . $selected_pugil_value);
        }
    } // End if empty($errors) after initial event ID validation and Club validation


    // --- Insertar la inscripción si se pudo determinar el ID del púgil ---
    // Procede SOLO si no hay errores Y se pudo determinar el ID del púgil
    if (empty($errors) && $id_pugil_para_inscripcion !== null) {
        try {
            // Verificar si ya existe una inscripción para este evento y púgil
            $sql_check_inscripcion = "SELECT COUNT(*) FROM inscripciones_veladas WHERE id_evento = ? AND id_pugil = ?";
            $stmt_check_inscripcion = $pdo->prepare($sql_check_inscripcion);
            $stmt_check_inscripcion->execute([$evento_id, $id_pugil_para_inscripcion]);

            if ($stmt_check_inscripcion->fetchColumn() > 0) {
                // Ya existe una inscripción
                // Podrías querer obtener el nombre del púgil para el mensaje aquí
                $errors[] = "Este púgil ya está inscrito en esta velada.";
                error_log("DEBUG: Púgil ID " . $id_pugil_para_inscripcion . " ya inscrito en evento ID " . $evento_id);
            } else {
                 // Insertar la nueva inscripción
                $sql_insert_inscripcion = "INSERT INTO inscripciones_veladas (id_evento, id_pugil, notas) VALUES (?, ?, ?)";
                $stmt_insert_inscripcion = $pdo->prepare($sql_insert_inscripcion);
                if ($stmt_insert_inscripcion->execute([$evento_id, $id_pugil_para_inscripcion, $notas_form])) {
                    // Éxito: Redirigir con mensaje de éxito
                    $_SESSION['success_message'] = "Púgil inscrito correctamente en la velada.";
                    error_log("DEBUG: Inscripción exitosa para púgil ID " . $id_pugil_para_inscripcion . " en evento ID " . $evento_id);
                    header("Location: ver_evento.php?id=" . htmlspecialchars($evento_id)); // Redirigir a la vista del evento
                    exit; // Importante para detener la ejecución después de la redirección
                } else {
                    // Error al insertar la inscripción
                    $errors[] = "Error al guardar la inscripción.";
                     error_log("Error inserting inscription: " . implode(", ", $stmt_insert_inscripcion->errorInfo()));
                }
            }

        } catch (Exception $e) {
            $errors[] = "Error BD al guardar inscripción: " . $e->getMessage();
             error_log("Error BD al guardar inscripción: " . $e->getMessage());
        }
    } elseif (empty($errors) && $id_pugil_para_inscripcion === null) {
         // Si llegamos aquí sin errores PERO id_pugil_para_inscripcion es null,
         // significa que falló la determinación del púgil ID en los pasos anteriores.
         $errors[] = "No se pudo determinar el púgil para la inscripción.";
         error_log("DEBUG: No se pudo determinar ID de púgil para inscripción después de buscar/crear (Errores anteriores?).");
    }


    // Si llegamos aquí sin redireccionar, significa que hubo errores.
    // Los errores ya están en el array $errors y se mostrarán arriba del formulario.
    // La variable $form_values ya contiene los datos del POST para pre-rellenar.
    // La página se recargará mostrando el formulario con los datos enviados y los mensajes de error.

    // === FIN: Lógica de procesamiento POST ===
}


// --- Mostrar la Página con el Formulario ---
// Asegurarse de que $form_values contenga los valores del POST si hubo errores para pre-rellenar
$nombre_evento = htmlspecialchars($evento['nombre_evento'] ?? 'Cargando...');
$fecha_evento = isset($evento['fecha_evento']) ? htmlspecialchars(date('d/m/Y', strtotime($evento['fecha_evento']))) : 'N/A';

?>

<h1><i class="bi bi-person-plus"></i> Inscribir Púgil en Velada</h1>
<p>Inscribir un púgil en la velada: <strong><?php echo $nombre_evento; ?></strong> (<?php echo $fecha_evento; ?>)</p>

<?php if ($evento): // Mostrar enlace de volver solo si el evento cargó correctamente ?>
<a href="ver_evento.php?id=<?php echo htmlspecialchars($evento_id); ?>" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver al Evento</a>
<?php endif; ?>


<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><strong>¡Error!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!empty($success_message)): // Este solo se mostraría si NO se redirige en caso de éxito ?>
    <div class="alert alert-success"><strong>¡Éxito!</strong> <?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<?php // Mostrar el formulario solo si el evento cargó bien y no hay errores críticos iniciales ?>
<?php // Filtramos el error placeholder si todavía está presente en $errors array (no debería estar si el POST se procesó) ?>
<?php if ($evento && empty(array_filter($errors, fn($e) => strpos($e, 'La lógica de guardado') !== false))): ?>
<form action="inscribir_pugil_velada.php?evento_id=<?php echo htmlspecialchars($evento_id); ?>" method="POST" class="needs-validation" novalidate id="inscripcion-form"> <?php // Added ID for easier JS selection ?>
    <input type="hidden" name="id_evento" value="<?php echo htmlspecialchars($evento_id); ?>">

    <h4>Datos del Púgil para Inscribir</h4>
    <p class="text-muted">Selecciona un club para buscar púgiles existentes o añadir uno nuevo.</p>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_club_pertenencia_inscripcion" class="form-label">Club de Pertenencia:</label>
             <select class="form-select club-select-inscripcion" id="id_club_pertenencia_inscripcion" name="id_club_pertenencia_inscripcion" required> <?php // Added required, specific ID/Name ?>
                 <option value="">--- Seleccionar Club ---</option>
                 <?php foreach ($clubes as $club): ?> <option value="<?php echo htmlspecialchars($club['id_club']); ?>" <?php if(($form_values['id_club_pertenencia_inscripcion'] ?? '') == $club['id_club']) echo 'selected'; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?>
                 <?php /* Si quieres permitir seleccionar "Sin Club" para Profesionales */ ?>
                  <option value="PROFESIONAL" <?php if(($form_values['id_club_pertenencia_inscripcion'] ?? '') == 'PROFESIONAL') echo 'selected'; ?>>--- PROFESIONAL (Sin Club) ---</option> <?php // Opción para profesionales sin club asignado ?>

            </select>
             <div class="invalid-feedback">Selecciona el club o la opción Profesional.</div>
        </div>
        <div class="col-md-6 mb-3">
             <label for="id_pugil_inscripcion" class="form-label">Seleccionar Púgil:</label>
             <select class="form-select pugil-select-inscripcion" id="id_pugil_inscripcion" name="id_pugil_inscripcion" required disabled> <?php // Select para púgiles existentes + opción "Añadir Nuevo" ?>
                 <option value="">--- Selecciona un Club primero ---</option>
                 <?php // Las opciones se cargarán con JavaScript ?>
                 <?php
                    // Si hubo un error POST y se había seleccionado un púgil existente,
                    // el valor de $form_values['id_pugil_inscripcion'] ya contendrá el ID o '--ADD_NEW--'
                    // para que el JS lo use en la carga inicial.
                 ?>
             </select>
              <div class="invalid-feedback">Selecciona un púgil de la lista.</div>
        </div>
    </div>

    <?php // Sección que se mostrará si se elige "Añadir Nuevo Púgil" en el selector de arriba ?>
    <div id="pugil-details-section" style="display: none;">
        <hr><h4>Datos del Nuevo Púgil</h4>
        <p class="text-muted">Completa los datos para añadir este púgil e inscribirlo.</p>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre_pugil" class="form-label">Nombre:</label>
                <input type="text" class="form-control" id="nombre_pugil" name="nombre_pugil" value="<?php echo htmlspecialchars($form_values['nombre_pugil'] ?? ''); ?>" > <?php // JS manejará required ?>
                <div class="invalid-feedback">Introduce el nombre del púgil.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="apellido_pugil" class="form-label">Apellidos (Primer apellido):</label>
                <input type="text" class="form-control" id="apellido_pugil" name="apellido_pugil" value="<?php echo htmlspecialchars($form_values['apellido_pugil'] ?? ''); ?>" > <?php // JS manejará required ?>
                 <div class="invalid-feedback">Introduce el apellido del púgil.</div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($form_values['fecha_nacimiento'] ?? ''); ?>" > <?php // JS manejará required ?>
                 <div class="invalid-feedback">Introduce la fecha de nacimiento.</div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label d-block">Sexo:</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sexo" id="sexo_masculino" value="Masculino" <?php if(($form_values['sexo'] ?? '') === 'Masculino') echo 'checked'; ?> > <?php // JS manejará required ?>
                        <label class="form-check-label" for="sexo_masculino">Masculino</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sexo" id="sexo_femenino" value="Femenino" <?php if(($form_values['sexo'] ?? '') === 'Femenino') echo 'checked'; ?> > <?php // JS manejará required ?>
                        <label class="form-check-label" for="sexo_femenino">Femenino</label>
                    </div>
                </div>
                 <div class="invalid-feedback">Selecciona el sexo.</div>
            </div>
        </div>

        <?php /* Si añades el checkbox de profesional aquí, debes leerlo en el POST */ ?>
         <?php /*
          <div class="row">
               <div class="col-md-6 mb-3 align-self-center">
                  <label class="form-label">¿Es Profesional?</label>
                  <div class="form-check form-switch">
                     <input class="form-check-input" type="checkbox" role="switch" id="es_profesional" name="es_profesional" value="1" <?php if(!empty($form_values['es_profesional'])) echo 'checked'; ?>>
                     <label class="form-check-label" for="es_profesional">Marcar si es profesional</label>
                 </div>
             </div>
          </div>
         */ ?>

    </div> <?php // End pugil-details-section ?>


     <?php /* Optional notes field */ ?>
     <div class="mb-3">
         <label for="notas" class="form-label">Notas de inscripción:</label>
         <textarea class="form-control" id="notas" name="notas" rows="3"><?php echo htmlspecialchars($form_values['notas'] ?? ''); ?></textarea>
     </div>


    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-plus-circle"></i> Inscribir Púgil</button>

</form>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>