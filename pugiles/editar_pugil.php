<?php
// /pugiles/editar_pugil.php (Con Validación Cliente, Primer Apellido Compuesto + Mayúsculas)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$id_pugil = null; $pugil_actual = null; $errors = [];
$clubes = []; $is_post_request = ($_SERVER['REQUEST_METHOD'] === 'POST');
$form_values = [];

// --- Obtener ID Pugil ---
$id_pugil = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($is_post_request) { $id_pugil_post = filter_input(INPUT_POST, 'id_pugil', FILTER_VALIDATE_INT); if ($id_pugil_post) $id_pugil = $id_pugil_post; $form_values = $_POST; }
if (!$id_pugil) { $_SESSION['error_message'] = "ID púgil inválido."; header("Location: listar_pugiles.php"); exit; }

// --- Cargar Clubes ---
try { if(isset($pdo)) $clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC")->fetchAll(); }
catch (Exception $e) { $errors[] = "Error cargando clubes."; }

// --- Cargar Datos Pugil Actual (si GET) o usar POST ---
if (!$is_post_request) {
    try { if (!isset($pdo)) throw new Exception("PDO no disp."); $sql_fetch = "SELECT * FROM pugiles WHERE id_pugil = ?"; $stmt_fetch = $pdo->prepare($sql_fetch); $stmt_fetch->execute([$id_pugil]); $pugil_actual = $stmt_fetch->fetch(PDO::FETCH_ASSOC); // Usar FETCH_ASSOC
        if (!$pugil_actual) { $_SESSION['error_message'] = "Púgil no encontrado."; header("Location: listar_pugiles.php"); exit; }
        // Pre-rellenar form_values para GET
        $form_values['nombre_pugil'] = $pugil_actual['nombre_pugil']; $form_values['apellido_pugil'] = $pugil_actual['apellido_pugil']; $form_values['fecha_nacimiento'] = $pugil_actual['fecha_nacimiento']; $form_values['sexo'] = $pugil_actual['sexo']; $form_values['es_profesional'] = $pugil_actual['es_profesional']; $form_values['id_club_pertenencia'] = $pugil_actual['id_club_pertenencia'] ?? '';
    } catch (Exception $e) { $errors[] = "Error cargando datos púgil: " . $e->getMessage(); }
}

// --- Procesar POST (Update) ---
if ($is_post_request && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico') || str_contains($err, 'Error cargando')))) {
    // Recoger y Limpiar
    $nombre_pugil = trim($form_values['nombre_pugil'] ?? '');
    $apellido_original = trim($form_values['apellido_pugil'] ?? ''); // Usar el apellido trimado como original
    $fecha_nacimiento = trim($form_values['fecha_nacimiento'] ?? '');
    $sexo = trim($form_values['sexo'] ?? '');
    $es_profesional = isset($form_values['es_profesional']) ? 1 : 0;
    $id_club_pertenencia = trim($form_values['id_club_pertenencia'] ?? '');

    // === INICIO MODIFICACIÓN: Procesar Apellido Compuesto y Convertir a MAYÚSCULAS ===
    $apellido_pugil = ''; // Variable donde guardaremos el apellido procesado

    if (!empty($apellido_original)) {
        $apellido_parts = explode(' ', $apellido_original);
        $processed_parts = []; // Partes que formarán el apellido final

        if (!empty($apellido_parts[0])) {
            $processed_parts[] = $apellido_parts[0]; // Siempre incluimos la primera parte
            $first_word_upper = strtoupper($apellido_parts[0]);

            // Prefijos comunes en apellidos compuestos
            $compound_prefixes = ['DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS'];

            // Verificar si la primera parte es un prefijo y hay al menos otra parte
            if (in_array($first_word_upper, $compound_prefixes) && count($apellido_parts) > 1) {
                $second_word_upper = strtoupper($apellido_parts[1]);

                // Manejar casos como "DE LA", "DE LOS", "DE LAS"
                if ($first_word_upper === 'DE' && in_array($second_word_upper, ['LA', 'LOS', 'LAS']) && count($apellido_parts) > 2) {
                     $processed_parts[] = $apellido_parts[1]; // Añadir "LA", "LOS", "LAS"
                     $processed_parts[] = $apellido_parts[2]; // Añadir la siguiente palabra (ej: "MANCHA")
                } else {
                     // Manejar "DE XXX", "DEL XXX", "LA XXX", "EL XXX", etc.
                     $processed_parts[] = $apellido_parts[1]; // Añadir la siguiente palabra (ej: "CASTILLO", "MOSSUI")
                }
            }
        }
        // Unir las partes procesadas para formar el apellido
        $apellido_pugil = implode(' ', $processed_parts);
    }

    // Convertir Nombre y el Apellido procesado a MAYÚSCULAS
    $nombre_pugil = strtoupper($nombre_pugil);
    $apellido_pugil = strtoupper($apellido_pugil); // Convertir el apellido ya procesado a MAYÚSCULAS
    // === FIN MODIFICACIÓN ===

    // Validar datos (igual que en añadir)
    if (empty($nombre_pugil)) $errors[] = "Nombre obligatorio."; if (empty($apellido_pugil)) $errors[] = "Apellido obligatorio."; if (empty($fecha_nacimiento)) { $errors[] = "Fecha obligatoria."; } elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento)) { $errors[] = "Formato fecha inválido."; } else { try { $d=new DateTime($fecha_nacimiento); $y=(int)$d->format('Y'); $cy=(int)date('Y'); if($d->format('Y-m-d')!==$fecha_nacimiento || $y>$cy || $y<($cy-100)) $errors[] = "Fecha inválida."; } catch (Exception $e) { $errors[] = "Fecha inválida."; } }
    if (empty($sexo) || !in_array($sexo, ['Masculino', 'Femenino'])) $errors[] = "Sexo inválido.";
    $club_id_para_db = null; if (!empty($id_club_pertenencia)) { if (!filter_var($id_club_pertenencia, FILTER_VALIDATE_INT)) { $errors[] = "Club inválido."; } else { $club_existe = false; foreach($clubes as $club) { if ($club['id_club'] == $id_club_pertenencia) {$club_existe=true; break;} } if (!$club_existe) $errors[] = "Club no existe."; else $club_id_para_db = (int)$id_club_pertenencia; } }
    // Update si no hay errores
    if (empty($errors)) { try { if (!isset($pdo)) throw new Exception("PDO no disp."); $sql_update = "UPDATE pugiles SET nombre_pugil = ?, apellido_pugil = ?, fecha_nacimiento = ?, sexo = ?, es_profesional = ?, id_club_pertenencia = ? WHERE id_pugil = ?"; $stmt_update = $pdo->prepare($sql_update); if ($stmt_update->execute([ $nombre_pugil, $apellido_pugil, $fecha_nacimiento, $sexo, $es_profesional, $club_id_para_db, $id_pugil ])) { $_SESSION['success_message'] = "Púgil actualizado."; header("Location: listar_pugiles.php"); exit; } else { $errors[] = "Error al actualizar."; } } catch (Exception $e) { $errors[] = "Error BD al actualizar."; error_log("Err update pugil: ".$e->getMessage()); } }
}

// --- Mostrar Página ---
?>
<h1><i class="bi bi-person-gear"></i> Editar Púgil</h1>
<a href="listar_pugiles.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>¡Error Servidor!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<?php if ($pugil_actual || $is_post_request): ?>
<form action="editar_pugil.php?id=<?php echo htmlspecialchars($id_pugil); ?>" method="POST" class="needs-validation" novalidate> <?php // <-- Clases Añadidas ?>
     <input type="hidden" name="id_pugil" value="<?php echo htmlspecialchars($id_pugil); ?>">
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre_pugil" class="form-label">Nombre:</label>
            <input type="text" class="form-control" id="nombre_pugil" name="nombre_pugil" value="<?php echo htmlspecialchars($form_values['nombre_pugil'] ?? ''); ?>" required> <?php // <-- Required ?>
            <div class="invalid-feedback">El nombre es obligatorio.</div> <?php // <-- Feedback ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="apellido_pugil" class="form-label">Apellidos (Primer apellido, incluyendo prefijos comunes):</label> <?php // <-- Texto actualizado ?>
            <input type="text" class="form-control" id="apellido_pugil" name="apellido_pugil" value="<?php echo htmlspecialchars($form_values['apellido_pugil'] ?? ''); ?>" required> <?php // <-- Required ?>
             <div class="invalid-feedback">El apellido es obligatorio.</div> <?php // <-- Feedback ?>
        </div>
    </div>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($form_values['fecha_nacimiento'] ?? ''); ?>" required> <?php // <-- Required ?>
             <div class="invalid-feedback">La fecha de nacimiento es obligatoria y válida.</div> <?php // <-- Feedback ?>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Sexo:</label> <?php // Required en los inputs ?>
            <div class="d-flex gap-3"> <?php // Contenedor flexbox ?>
                 <div class="form-check"> <input class="form-check-input" type="radio" name="sexo" id="sexo_masculino" value="Masculino" <?php if(($form_values['sexo'] ?? '') === 'Masculino') echo 'checked'; ?> required> <label class="form-check-label" for="sexo_masculino">Masculino</label> </div>
                 <div class="form-check"> <input class="form-check-input" type="radio" name="sexo" id="sexo_femenino" value="Femenino" <?php if(($form_values['sexo'] ?? '') === 'Femenino') echo 'checked'; ?> required> <label class="form-check-label" for="sexo_femenino">Femenino</label> <div class="invalid-feedback">Debe seleccionar el sexo.</div> </div>
            </div>
        </div>
    </div>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_club_pertenencia" class="form-label">Club:</label>
             <select class="form-select" id="id_club_pertenencia" name="id_club_pertenencia"> <?php // No required ?>
                 <option value="">--- Sin Club ---</option>
                 <?php foreach ($clubes as $club): ?> <option value="<?php echo htmlspecialchars($club['id_club']); ?>" <?php if(($form_values['id_club_pertenencia'] ?? '') == $club['id_club']) echo 'selected'; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?>
            </select>
        </div>
         <div class="col-md-6 mb-3 align-self-center">
             <label class="form-label">¿Es Profesional?</label>
             <div class="form-check form-switch"> <input class="form-check-input" type="checkbox" role="switch" id="es_profesional" name="es_profesional" value="1" <?php if(!empty($form_values['es_profesional'])) echo 'checked'; ?>> <label class="form-check-label" for="es_profesional">Marcar si es profesional</label> </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save"></i> Guardar Cambios</button>
</form>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>