<?php
// /eventos/añadir_combate.php (VERSIÓN FINAL COMPLETA - Usa JS Externo + Select Procedencia + POST Corregido)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// --- Variables e Inicialización ---
$evento_id = null; $evento = null; $numero_combate_actual = 1; $max_combates_alcanzado = false; $errors = []; $clubes = []; $categorias_peso = []; $form_values = $_POST; // Usamos POST para pre-rellenar en caso de error

// --- Revisar Mensajes de Sesión para Toasts ---
$flash_message_type = null;
$flash_message_text = null;
if (isset($_SESSION['success_message'])) {
    $flash_message_type = 'success';
    $flash_message_text = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $flash_message_type = 'danger'; // 'danger' se mapea a bg-danger en JS
    $flash_message_text = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
// --- Fin Revisar Mensajes ---


$evento_id = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT); if (!$evento_id) { $evento_id = filter_input(INPUT_POST, 'id_evento', FILTER_VALIDATE_INT); if (!$evento_id) { $_SESSION['error_message'] = "ID evento inválido."; header("Location: /index.php"); exit; } }
try { if (!isset($pdo)) throw new Exception("PDO no disponible."); $sql_evento = "SELECT e.*, m.nombre_municipio, r.nombre_recinto
               FROM eventos e
               LEFT JOIN municipios m ON e.id_municipio = m.id_municipio
               LEFT JOIN recintos r ON e.id_recinto = r.id_recinto
               WHERE e.id_evento = ?"; $stmt_evento = $pdo->prepare($sql_evento); $stmt_evento->execute([$evento_id]); $evento = $stmt_evento->fetch(); if (!$evento) { $_SESSION['error_message'] = "Evento no encontrado."; header("Location: /index.php"); exit; } $sql_max_num = "SELECT MAX(numero_combate) as max_num FROM combates WHERE id_evento = ?"; $stmt_max_num = $pdo->prepare($sql_max_num); $stmt_max_num->execute([$evento_id]); $max_num_actual = $stmt_max_num->fetchColumn(); $numero_combate_actual = ($max_num_actual === null || $max_num_actual === false) ? 1 : (int)$max_num_actual + 1; if ($numero_combate_actual > $evento['max_combates']) { $max_combates_alcanzado = true; } } catch (Exception $e) { $errors[] = "Error crítico cargando evento: " . $e->getMessage(); }
if (empty(array_filter($errors, fn($err) => str_contains($err, 'crítico')))) { try { $clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC")->fetchAll(); $categorias_peso = $pdo->query("SELECT id_categoria_peso, descripcion_peso, categoria, sexo FROM categorias_peso ORDER BY categoria, sexo, id_categoria_peso")->fetchAll(); } catch (Exception $e) { $errors[] = "Error cargando desplegables: " . $e->getMessage(); } }
// --- Procesar POST (Validación y Guardado CORREGIDO para Procedencia ID) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$max_combates_alcanzado && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico') || str_contains($err, 'desplegables')))) {
    $id_evento_post=filter_input(INPUT_POST,'id_evento',FILTER_VALIDATE_INT);
    $numero_combate_post=filter_input(INPUT_POST,'numero_combate',FILTER_VALIDATE_INT);

    $id_club_rojo=$_POST['id_club_rojo']??'';
    $id_pugil_rojo=$_POST['id_pugil_rojo']??'';
    // Ahora leer el ID de procedencia como un entero validado
    $id_procedencia_rojo_post = filter_input(INPUT_POST, 'procedencia_rojo', FILTER_VALIDATE_INT); // <-- LEER COMO INT

    $id_club_azul=$_POST['id_club_azul']??'';
    $id_pugil_azul=$_POST['id_pugil_azul']??'';
    // Ahora leer el ID de procedencia como un entero validado
    $id_procedencia_azul_post = filter_input(INPUT_POST, 'procedencia_azul', FILTER_VALIDATE_INT); // <-- LEER COMO INT

    $categoria=$_POST['categoria']??'';
    $sexo=$_POST['sexo']??'';
    $id_categoria_peso_combate=$_POST['id_categoria_peso_combate']??'';

    // Validaciones generales (sin cambios)
    if ($id_evento_post != $evento_id) $errors[] = "Inconsistencia ID evento.";
    if ($numero_combate_post != $numero_combate_actual) $errors[] = "Inconsistencia nº combate.";
    if (empty($categoria) || !in_array($categoria, ['Profesional', 'Elite', 'Joven', 'Junior'])) $errors[] = "Categoría inválida.";
    if (empty($sexo) || !in_array($sexo, ['Masculino', 'Femenino'])) $errors[] = "Sexo inválido.";
    if (empty($id_categoria_peso_combate) || !filter_var($id_categoria_peso_combate, FILTER_VALIDATE_INT)) $errors[] = "Peso inválido.";

    // Validar Rincón Rojo
    $es_profesional_rojo=0;
    $db_id_club_rojo=null;
    $db_id_pugil_rojo=null;
    $db_id_procedencia_rojo=null; // Variable para el ID de procedencia final en BD

    if($id_club_rojo==='PROFESIONAL'){
        $es_profesional_rojo=1;
        $db_id_club_rojo=null; // Profesionales no tienen id_club_pertenencia

        if(empty($id_pugil_rojo)||$id_pugil_rojo=='--ADD_PRO--' || !filter_var($id_pugil_rojo, FILTER_VALIDATE_INT)){
            $errors[]="Púgil Profesional Rojo requerido.";
        }else{
            $db_id_pugil_rojo=(int)$id_pugil_rojo;
        }

        // === INICIO CORRECCIÓN: Validar y asignar ID de Procedencia para Profesional ===
        // filter_input devuelve null si no está seteado, false si falla la validación.
        if ($id_procedencia_rojo_post === null || $id_procedencia_rojo_post === false) {
             $errors[]="Procedencia Rojo requerida."; // Mensaje más simple
        } else {
             $db_id_procedencia_rojo = $id_procedencia_rojo_post; // Usar el ID validado
        }
        // === FIN CORRECCIÓN ===

        if($categoria!=='Profesional')$errors[]="Combate debe ser Profesional (rojo).";

    }elseif(!empty($id_club_rojo) && $id_club_rojo !== '--ADD_NEW--'){ // Rincón Amateur con Club
        $db_id_club_rojo=filter_var($id_club_rojo,FILTER_VALIDATE_INT);
        if(!$db_id_club_rojo)$errors[]="Club rojo inválido.";

        if(empty($id_pugil_rojo)||$id_pugil_rojo=='--ADD_PRO--' || !filter_var($id_pugil_rojo, FILTER_VALIDATE_INT)){
            $errors[]="Púgil Amateur Rojo requerido.";
        }else{
            $db_id_pugil_rojo=(int)$id_pugil_rojo;
        }
        $db_id_procedencia_rojo=null; // Amateur no tiene id_procedencia
        if($categoria==='Profesional')$errors[]="Combate Amateur no puede ser Profesional (rojo).";

    } else { // Selección inválida de Club (o "--ADD_NEW--", manejado por JS pero validamos aquí por seguridad)
         $errors[]="Selección inválida club rojo.";
    }


    // Validar Rincón Azul (Lógica simétrica al Rojo)
    $es_profesional_azul=0;
    $db_id_club_azul=null;
    $db_id_pugil_azul=null;
    $db_id_procedencia_azul=null; // Variable para el ID de procedencia final en BD

    if($id_club_azul==='PROFESIONAL'){
        $es_profesional_azul=1;
        $db_id_club_azul=null; // Profesionales no tienen id_club_pertenencia

        if(empty($id_pugil_azul)||$id_pugil_azul=='--ADD_PRO--' || !filter_var($id_pugil_azul, FILTER_VALIDATE_INT)){
            $errors[]="Púgil Profesional Azul requerido.";
        }else{
            $db_id_pugil_azul=(int)$id_pugil_azul;
        }

        // === INICIO CORRECCIÓN: Validar y asignar ID de Procedencia para Profesional ===
        if ($id_procedencia_azul_post === null || $id_procedencia_azul_post === false) {
             $errors[]="Procedencia Azul requerida.";
        } else {
             $db_id_procedencia_azul = $id_procedencia_azul_post;
        }
        // === FIN CORRECCIÓN ===

        if($categoria!=='Profesional')$errors[]="Combate debe ser Profesional (azul).";

    }elseif(!empty($id_club_azul) && $id_club_azul !== '--ADD_NEW--'){ // Rincón Amateur con Club
        $db_id_club_azul=filter_var($id_club_azul,FILTER_VALIDATE_INT);
        if(!$db_id_club_azul)$errors[]="Club azul inválido.";

        if(empty($id_pugil_azul)||$id_pugil_azul=='--ADD_PRO--' || !filter_var($id_pugil_azul, FILTER_VALIDATE_INT)){
            $errors[]="Púgil Amateur Azul requerido.";
        }else{
            $db_id_pugil_azul=(int)$id_pugil_azul;
        }
        $db_id_procedencia_azul=null; // Amateur no tiene id_procedencia
        if($categoria==='Profesional')$errors[]="Combate Amateur no puede ser Profesional (azul).";

    } else { // Selección inválida de Club
         $errors[]="Selección inválida club azul.";
    }

    // Validar que no sea el mismo púgil en ambos rincones
    if ($db_id_pugil_rojo !== null && $db_id_pugil_rojo === $db_id_pugil_azul) { $errors[] = "Mismo púgil en ambos rincones."; }

    // Validar categorías y sexos de púgiles si son amateurs (sin cambios, usa db_id_pugil)
    if(empty($errors)){
        if(!function_exists('validarCategoriaPugil')){function validarCategoriaPugil($pdo,$id_p,$cat_c,$r){ if(!$id_p)return null;try{$s=$pdo->prepare("SELECT fecha_nacimiento,es_profesional FROM pugiles WHERE id_pugil=?");$s->execute([$id_p]);$pd=$s->fetch();if(!$pd)return "Púgil $r no encontrado.";if($pd['es_profesional'])return null; $cc=calcularCategoriaEdad($pd['fecha_nacimiento']);if(!$cc||$cc=='Fuera de Rango'||$cc=='Fecha Futura'||$cc=='Error Fecha')return "Cat. edad inválida púgil $r ($cc)";if($cc!==$cat_c)return "Cat. púgil $r ($cc) != combate ($cat_c).";return null;}catch(Exception $e){return "Error DB Púgil $r.";}}}
        // Llamar a la validación SOLO si el púgil NO es profesional y se ha seleccionado un púgil
        if(!$es_profesional_rojo && $db_id_pugil_rojo !== null){
            $eR=validarCategoriaPugil($pdo,$db_id_pugil_rojo,$categoria,'rojo');
            if($eR)$errors[]=$eR;
        }
        if(!$es_profesional_azul && $db_id_pugil_azul !== null){
            $eA=validarCategoriaPugil($pdo,$db_id_pugil_azul,$categoria,'azul');
            if($eA)$errors[]=$eA;
        }
    }

    // Insertar si no hay errores
    if(empty($errors)){
        try{
            // === INICIO CORRECCIÓN: USAR los IDs de Procedencia en el INSERT ===
            $sql_insert="INSERT INTO combates (id_evento,numero_combate,id_club_rojo,id_pugil_rojo,es_profesional_rojo,id_procedencia_rojo,id_club_azul,id_pugil_azul,es_profesional_azul,id_procedencia_azul,categoria,sexo,id_categoria_peso_combate)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt_insert=$pdo->prepare($sql_insert);
            if($stmt_insert->execute([
                $evento_id,
                $numero_combate_actual,
                $db_id_club_rojo,
                $db_id_pugil_rojo,
                $es_profesional_rojo,
                $db_id_procedencia_rojo, // <-- Usar la variable INT validada
                $db_id_club_azul,
                $db_id_pugil_azul,
                $es_profesional_azul,
                $db_id_procedencia_azul, // <-- Usar la variable INT validada
                $categoria,
                $sexo,
                $id_categoria_peso_combate
            ])){$_SESSION['success_message']="Combate Nº ".$numero_combate_actual." añadido.";header("Location: añadir_combate.php?evento_id=".$evento_id);exit;}else{$errors[]="Error guardando combate."; $errors[]=implode(", ", $stmt_insert->errorInfo());}
        }catch(PDOException $e){$errors[]="Error BD: ".$e->getCode();}catch(Exception $e){$errors[]="Error general: ".$e->getMessage();}}
} // Fin POST

// --- Mostrar Página ---
$nombre_evento = $evento ? htmlspecialchars($evento['nombre_evento']) : 'Evento no encontrado'; $fecha_evento = $evento ? htmlspecialchars(date('d/m/Y', strtotime($evento['fecha_evento']))) : 'N/A'; $max_combates_evento = $evento ? htmlspecialchars($evento['max_combates']) : 'N/A';
?>

<h2><i class="bi bi-pencil-square"></i> Añadir Combates para: <?php echo $nombre_evento; ?></h2>
<p><strong>Fecha:</strong> <?php echo $fecha_evento; ?> | <strong>Municipio:</strong> <?php echo htmlspecialchars($evento['nombre_municipio'] ?? 'N/D'); ?> | <strong>Recinto:</strong> <?php echo htmlspecialchars($evento['nombre_recinto'] ?? 'N/D'); ?></p>
<?php if (!$max_combates_alcanzado): ?><h3>Combate Nº <?php echo $numero_combate_actual; ?> de <?php echo $max_combates_evento; ?></h3><?php endif; ?>

<?php // Mostrar mensajes ... ?>



<?php // --- Pasar mensaje flash a JavaScript --- ?>
<?php if ($flash_message_type && $flash_message_text): ?>
<script id="flash-message-data" type="application/json">
    <?php echo json_encode(['type' => $flash_message_type, 'message' => $flash_message_text]); ?>
</script>
<?php endif; ?>
<?php // --- FIN Mensaje Flash --- ?>


<?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?><div class="alert alert-danger"><strong>¡Error Servidor!</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($max_combates_alcanzado): ?>
     <div class="alert alert-warning" role="alert">
       <i class="bi bi-exclamation-triangle-fill"></i> Se ha alcanzado el número máximo de combates (<?php echo $max_combates_evento; ?>) para este evento. No se pueden añadir más.
       <hr>
       <a href="/eventos/ver_evento.php?id=<?php echo htmlspecialchars($evento_id); ?>" class="btn btn-primary btn-sm"><i class="bi bi-card-list"></i> Ver Cartelera Completa</a>
       <a href="/eventos/listar_eventos.php" class="btn btn-secondary btn-sm"><i class="bi bi-list-ul"></i> Ver lista de Eventos</a>
       <a href="/index.php" class="btn btn-info btn-sm"><i class="bi bi-speedometer2"></i> Ir al Dashboard</a>
     </div>
<?php endif; ?>

<?php // Mostrar formulario si OK ?>
<?php if (!$max_combates_alcanzado && empty(array_filter($errors, fn($err) => str_contains($err, 'crítico') || str_contains($err, 'desplegables')))): ?>
<script id="initial-form-data" type="application/json"><?php echo json_encode($form_values ?: new stdClass()); ?></script>

<form action="añadir_combate.php?evento_id=<?php echo $evento_id; ?>" method="POST" id="form-combate" class="needs-validation" novalidate>
    <input type="hidden" name="id_evento" value="<?php echo $evento_id; ?>">
    <input type="hidden" name="numero_combate" value="<?php echo $numero_combate_actual; ?>">
    <div class="row">
        <div class="col-md-6 border p-3 mb-3 bg-danger-subtle">
            <h4><i class="bi bi-person-square" style="color: red;"></i> Rincón Rojo</h4>
            <div class="mb-2">
                <label for="id_club_rojo" class="form-label">Club Rojo:</label>
                <select class="form-select club-select" id="id_club_rojo" name="id_club_rojo" data-target-pugil="#id_pugil_rojo" data-target-procedencia="#procedencia_rojo_div" required>
                    <option value="">--- Seleccionar Club ---</option>
                    <option value="PROFESIONAL" <?php if(($form_values['id_club_rojo'] ?? '') == 'PROFESIONAL') echo 'selected'; ?>>--- PROFESIONAL ---</option>
                    <?php foreach ($clubes as $club): ?> <option value="<?php echo htmlspecialchars($club['id_club']); ?>" <?php if(($form_values['id_club_rojo'] ?? '') == $club['id_club']) echo 'selected'; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?>
                    <option value="--ADD_NEW--">** Añadir Nuevo Club... **</option>
                </select>
                <div class="invalid-feedback">Selecciona un club o PROFESIONAL.</div>
            </div>
            <div class="mb-2">
                 <label for="id_pugil_rojo" class="form-label">Púgil Rojo:</label>
                 <?php // Required se añade/quita con JS ?>
                 <select class="form-select pugil-select" id="id_pugil_rojo" name="id_pugil_rojo" <?php if(empty($form_values['id_club_rojo'] ?? '') || ($form_values['id_club_rojo'] ?? '') == '--ADD_NEW--') echo 'disabled'; ?>>
                     <option value="">--- Seleccionar Púgil ---</option>
                     <?php /* JS Carga esto */ ?>
                 </select>
                 <div class="invalid-feedback">Selecciona un púgil (o registra uno nuevo si es Pro).</div>
             </div>
             <?php // === Reemplazar Input Texto por Select por Select de Procedencia === ?>
             <div class="mb-2" id="procedencia_rojo_div" style="<?php echo (($form_values['id_club_rojo'] ?? '') == 'PROFESIONAL') ? '' : 'display: none;'; ?>">
                 <label for="procedencia_rojo" class="form-label">Procedencia:</label>
                 <?php // Required será manejado por JS basado en si es profesional ?>
                 <select class="form-select procedencia-select" id="procedencia_rojo" name="procedencia_rojo" required> <?php // Añadido required, nueva clase procedencia-select ?>
                     <option value="">--- Cargando Procedencias... ---</option> <?php // Placeholder inicial ?>
                 </select>
                 <div class="invalid-feedback">Selecciona la procedencia.</div> <?php // Mensaje de feedback actualizado ?>
            </div>
            <?php // === FIN MODIFICACIÓN === ?>
        </div>
        <div class="col-md-6 border p-3 mb-3 bg-primary-subtle">
            <h4><i class="bi bi-person-square" style="color: blue;"></i> Rincón Azul</h4>
             <div class="mb-2">
                <label for="id_club_azul" class="form-label">Club Azul:</label>
                 <select class="form-select club-select" id="id_club_azul" name="id_club_azul" data-target-pugil="#id_pugil_azul" data-target-procedencia="#procedencia_azul_div" required>
                     <option value="">--- Seleccionar Club ---</option>
                     <option value="PROFESIONAL" <?php if(($form_values['id_club_azul'] ?? '') == 'PROFESIONAL') echo 'selected'; ?>>--- PROFESIONAL ---</option>
                     <?php foreach ($clubes as $club): ?> <option value="<?php echo htmlspecialchars($club['id_club']); ?>" <?php if(($form_values['id_club_azul'] ?? '') == $club['id_club']) echo 'selected'; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?>
                     <option value="--ADD_NEW--">** Añadir Nuevo Club... **</option>
                 </select>
                 <div class="invalid-feedback">Selecciona un club o PROFESIONAL.</div>
            </div>
            <div class="mb-2">
                   <label for="id_pugil_azul" class="form-label">Púgil Azul:</label>
                    <?php // Required se añade/quita con JS ?>
                   <select class="form-select pugil-select" id="id_pugil_azul" name="id_pugil_azul" <?php if(empty($form_values['id_club_azul'] ?? '') || ($form_values['id_club_azul'] ?? '') == '--ADD_NEW--') echo 'disabled'; ?>>
                       <option value="">--- Seleccionar Púgil ---</option>
                        <?php /* JS Carga esto */ ?>
                   </select>
                   <div class="invalid-feedback">Selecciona un púgil (o registra uno nuevo si es Pro).</div>
               </div>
               <?php // === Reemplazar Input Texto por Select por Select de Procedencia === ?>
               <div class="mb-2" id="procedencia_azul_div" style="<?php echo (($form_values['id_club_azul'] ?? '') == 'PROFESIONAL') ? '' : 'display: none;'; ?>">
                   <label for="procedencia_azul" class="form-label">Procedencia:</label>
                   <?php // Required será manejado por JS basado en si es profesional ?>
                   <select class="form-select procedencia-select" id="procedencia_azul" name="procedencia_azul" required> <?php // Añadido required, nueva clase procedencia-select ?>
                       <option value="">--- Cargando Procedencias... ---</option> <?php // Placeholder inicial ?>
                   </select>
                   <div class="invalid-feedback">Selecciona la procedencia.</div> <?php // Mensaje de feedback actualizado ?>
              </div>
               <?php // === FIN MODIFICACIÓN === ?>
        </div>
    </div>
    <hr><h4>Detalles del Combate</h4>
    <div class="row">
        <div class="col-md-4 mb-3">
             <label for="categoria" class="form-label">Categoría:</label>
             <select class="form-select" id="categoria" name="categoria" required>
                 <option value="" disabled <?php if(empty($form_values['categoria'] ?? '')) echo 'selected'; ?>>--- Seleccionar ---</option>
                 <option value="Profesional" <?php if(($form_values['categoria'] ?? '') == 'Profesional') echo 'selected'; ?>>Profesional</option>
                 <option value="Elite" <?php if(($form_values['categoria'] ?? '') == 'Elite') echo 'selected'; ?>>Elite</option>
                 <option value="Joven" <?php if(($form_values['categoria'] ?? '') == 'Joven') echo 'selected'; ?>>Joven</option>
                 <option value="Junior" <?php if(($form_values['categoria'] ?? '') == 'Junior') echo 'selected'; ?>>Junior</option>
             </select>
             <div class="invalid-feedback">Selecciona la categoría.</div>
         </div>
         <div class="col-md-4 mb-3">
              <label for="sexo" class="form-label">Sexo:</label>
              <select class="form-select" id="sexo" name="sexo" required>
                  <option value="" disabled <?php if(empty($form_values['sexo'] ?? '')) echo 'selected'; ?>>--- Seleccionar ---</option>
                  <option value="Masculino" <?php if(($form_values['sexo'] ?? '') == 'Masculino') echo 'selected'; ?>>Masculino</option>
                  <option value="Femenino" <?php if(($form_values['sexo'] ?? '') == 'Femenino') echo 'selected'; ?>>Femenino</option>
              </select>
               <div class="invalid-feedback">Selecciona el sexo.</div>
          </div>
          <div class="col-md-4 mb-3">
              <label for="id_categoria_peso_combate" class="form-label">Categoría de Peso:</label>
              <select class="form-select" id="id_categoria_peso_combate" name="id_categoria_peso_combate" required <?php if(empty($form_values['categoria'] ?? '') || empty($form_values['sexo'] ?? '')) echo 'disabled'; ?>>
                  <option value="">--- Seleccione Cat/Sexo ---</option>
                  <?php /* JS Carga esto */ ?>
              </select>
              <div class="invalid-feedback">Selecciona la categoría de peso.</div>
           </div>
    </div>
    <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-check-circle"></i> Guardar Combate Nº <?php echo $numero_combate_actual; ?></button>
</form>
<?php endif; ?>

<div class="modal fade" id="addNewClubModal" tabindex="-1" aria-labelledby="addNewClubModalLabel" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h1 class="modal-title fs-5" id="addNewClubModalLabel">Añadir Nuevo Club</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div id="modalAlertPlaceholder"></div><div class="mb-3"><label for="newClubNameInput" class="form-label">Nombre:</label><input type="text" class="form-control" id="newClubNameInput" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="saveNewClubBtn">Guardar Club</button></div></div></div>
</div>

<div class="modal fade" id="addNewProModal" tabindex="-1" aria-labelledby="addNewProModalLabel" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h1 class="modal-title fs-5" id="addNewProModalLabel">Registrar Nuevo Profesional</h1><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div id="modalProAlertPlaceholder"></div> <div class="mb-3"><label for="newProNombreInput" class="form-label">Nombre:</label><input type="text" class="form-control" id="newProNombreInput" required></div> <div class="mb-3"><label for="newProApellidoInput" class="form-label">Apellidos:</label><input type="text" class="form-control" id="newProApellidoInput" required></div> <div class="mb-3"><label for="newProSexoSelect" class="form-label">Sexo:</label><select class="form-select" id="newProSexoSelect" required><option value="" selected disabled>Seleccionar...</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div> </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="saveNewProBtn">Guardar Profesional</button></div></div></div>
</div>

<div class="modal fade" id="addNewPugilModal" tabindex="-1" aria-labelledby="addNewPugilModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="addNewPugilModalLabel">Añadir Nuevo Púgil Amateur a Club</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modalPugilAlertPlaceholder"></div> <p>Se añadirá al club: <strong id="modalPugilClubName"></strong></p> <?php // JS pondrá aquí el nombre del club ?>
        <input type="hidden" id="modalPugilClubId"> <?php // JS pondrá aquí el ID del club ?>

        <div class="mb-3">
          <label for="newPugilNombreInput" class="form-label">Nombre:</label>
          <input type="text" class="form-control" id="newPugilNombreInput" required>
        </div>
         <div class="mb-3">
          <label for="newPugilApellidoInput" class="form-label">Apellidos:</label>
          <input type="text" class="form-control" id="newPugilApellidoInput" required>
        </div>
         <div class="mb-3">
          <label for="newPugilDobInput" class="form-label">Fecha Nacimiento:</label>
          <input type="date" class="form-control" id="newPugilDobInput" required>
           <div class="form-text">Esto determinará su categoría (Junior, Joven, Elite).</div>
        </div>
         <div class="mb-3">
          <label for="newPugilSexoSelect" class="form-label">Sexo:</label>
          <select class="form-select" id="newPugilSexoSelect" required>
              <option value="" selected disabled>Seleccionar...</option>
              <option value="Masculino">Masculino</option>
              <option value="Femenino">Femenino</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="saveNewPugilBtn">Guardar Púgil</button> <?php // Nuevo ID ?>
      </div>
    </div>
  </div>
</div>


<?php require_once '../includes/footer.php'; // Este incluye los JS: Bootstrap, combate_form.js, bs-validation.js ?>