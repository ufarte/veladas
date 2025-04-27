<?php
// /pugiles/añadir_pugil.php (Con Validación Cliente, Primer Apellido Compuesto + Mayúsculas + Sexo en línea)

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$form_values = $_POST; // Usar POST para pre-rellenar en caso de error
$errors = [];
$clubes = [];

try { if(isset($pdo)) $clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC")->fetchAll(); }
catch (Exception $e) { $errors[] = "Error cargando clubes."; error_log("Err load clubes add pugil: ".$e->getMessage()); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y Limpiar
     $nombre_pugil = trim($form_values['nombre_pugil'] ?? '');
     $apellido_original = trim($form_values['apellido_pugil'] ?? ''); // Guardamos el original para procesar
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


     // Validar datos (las validaciones de vacío ahora aplican *después* del trim y procesamiento)
     if (empty($nombre_pugil)) $errors[] = "Nombre obligatorio.";
     if (empty($apellido_pugil)) $errors[] = "Apellido obligatorio."; // Ahora valida si el primer apellido (procesado) está vacío
     if (empty($fecha_nacimiento)) { $errors[] = "Fecha nacimiento obligatoria."; }
     // ... (resto de validaciones de fecha, sexo y club) ...
     elseif (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha_nacimiento)) { $errors[] = "Formato fecha inválido."; } else { try { $d=new DateTime($fecha_nacimiento); $y=(int)$d->format('Y'); $cy=(int)date('Y'); if($d->format('Y-m-d')!==$fecha_nacimiento || $y>$cy || $y<($cy-100)) $errors[] = "Fecha nacimiento inválida."; } catch (Exception $e) { $errors[] = "Fecha nacimiento inválida."; } }
     if (empty($sexo) || !in_array($sexo, ['Masculino', 'Femenino'])) $errors[] = "Sexo inválido.";
     $club_id_para_db = null;
     if (!empty($id_club_pertenencia)) {
         if (!filter_var($id_club_pertenencia, FILTER_VALIDATE_INT)) {
             $errors[] = "Club inválido.";
         } else {
             $club_existe = false;
             foreach($clubes as $club) {
                 if ($club['id_club'] == $id_club_pertenencia) {$club_existe=true; break;}
             }
             if (!$club_existe) $errors[] = "Club no existe.";
             else $club_id_para_db = (int)$id_club_pertenencia;
         }
     }
    // Insertar si no hay errores
    if (empty($errors)) { try { if (!isset($pdo)) throw new Exception("PDO no disponible."); $sql_insert = "INSERT INTO pugiles (nombre_pugil, apellido_pugil, fecha_nacimiento, sexo, es_profesional, id_club_pertenencia) VALUES (?, ?, ?, ?, ?, ?)"; $stmt_insert = $pdo->prepare($sql_insert); if ($stmt_insert->execute([ $nombre_pugil, $apellido_pugil, $fecha_nacimiento, $sexo, $es_profesional, $club_id_para_db ])) { if(session_status()==PHP_SESSION_NONE) session_start(); $_SESSION['success_message'] = "Púgil añadido."; header("Location: listar_pugiles.php"); exit; } else { $errors[] = "Error al guardar."; } } catch (Exception $e) { $errors[] = "Error BD al guardar."; error_log("Err insert pugil: ".$e->getMessage()); } }
}

require_once '../includes/header.php';
?>

<h1><i class="bi bi-person-plus"></i> Añadir Nuevo Púgil</h1>
<a href="listar_pugiles.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>¡Error Servidor!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<form action="añadir_pugil.php" method="POST" class="needs-validation" novalidate> <?php // <-- Clases Añadidas ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nombre_pugil" class="form-label">Nombre:</label>
            <input type="text" class="form-control" id="nombre_pugil" name="nombre_pugil" value="<?php echo htmlspecialchars($form_values['nombre_pugil'] ?? ''); ?>" required> <?php // <-- Required ?>
            <div class="invalid-feedback">El nombre es obligatorio.</div> <?php // <-- Feedback ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="apellido_pugil" class="form-label">Primer apellido (indicar unicamente <b>primer apellido</b>):</label> <?php // <-- Texto actualizado ?>
            <input type="text" class="form-control" id="apellido_pugil" name="apellido_pugil" value="<?php echo htmlspecialchars($form_values['apellido_pugil'] ?? ''); ?>" required> <?php // <-- Required ?>
             <div class="invalid-feedback">El apellido es obligatorio.</div> <?php // <-- Feedback actualizado ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($form_values['fecha_nacimiento'] ?? ''); ?>" required> <?php // <-- Required ?>
             <div class="invalid-feedback">La fecha de nacimiento es obligatoria y debe ser válida.</div> <?php // <-- Feedback ?>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label d-block">Sexo:</label> <?php // Añadido d-block para que la etiqueta principal vaya encima ?>
            <div class="d-flex gap-3"> <?php // Contenedor flexbox para poner los radios en línea ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sexo" id="sexo_masculino" value="Masculino" <?php if(($form_values['sexo'] ?? '') === 'Masculino') echo 'checked'; ?> required>
                    <label class="form-check-label" for="sexo_masculino">Masculino</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="sexo" id="sexo_femenino" value="Femenino" <?php if(($form_values['sexo'] ?? '') === 'Femenino') echo 'checked'; ?> required>
                    <label class="form-check-label" for="sexo_femenino">Femenino</label>
                </div>
            </div>
            <div class="invalid-feedback">Debe seleccionar el sexo.</div> <?php // Feedback para el grupo ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_club_pertenencia" class="form-label">Club de Pertenencia:</label>
             <select class="form-select" id="id_club_pertenencia" name="id_club_pertenencia"> <?php // No es required ?>
                 <option value="">--- Sin Club ---</option>
                 <?php foreach ($clubes as $club): ?> <option value="<?php echo htmlspecialchars($club['id_club']); ?>" <?php if(($form_values['id_club_pertenencia'] ?? '') == $club['id_club']) echo 'selected'; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?>
            </select>
        </div>
         <div class="col-md-6 mb-3 align-self-center">
             <label class="form-label">¿Es Profesional?</label>
             <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="es_profesional" name="es_profesional" value="1" <?php if(!empty($form_values['es_profesional'])) echo 'checked'; ?>>
                <label class="form-check-label" for="es_profesional">Marcar si es profesional</label>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i> Guardar Púgil</button>
</form>

<?php require_once '../includes/footer.php'; ?>