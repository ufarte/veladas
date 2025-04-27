<?php
// /eventos/ver_evento.php (v2.1 - Con Toasts)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

$evento_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT); $evento = null; $combates = []; $clubes_assoc = []; $pugiles_assoc = []; $pesos_assoc = []; $errors = [];

// --- Revisar Mensajes de Sesión para Toasts ---
$flash_message_type = null; $flash_message_text = null; if (isset($_SESSION['success_message'])) { $flash_message_type = 'success'; $flash_message_text = $_SESSION['success_message']; unset($_SESSION['success_message']); } elseif (isset($_SESSION['error_message'])) { $flash_message_type = 'danger'; $flash_message_text = $_SESSION['error_message']; unset($_SESSION['error_message']); }
// --- Fin Revisar Mensajes ---

if (!$evento_id) { $errors[] = "ID evento inválido."; } else { try { if (!isset($pdo)) throw new Exception("PDO no disp."); /* 1. Cargar Evento */ $sql_evento = "SELECT e.*, m.nombre_municipio, r.nombre_recinto FROM eventos e LEFT JOIN municipios m ON e.id_municipio = m.id_municipio LEFT JOIN recintos r ON e.id_recinto = r.id_recinto WHERE e.id_evento = ?"; $stmt_evento = $pdo->prepare($sql_evento); $stmt_evento->execute([$evento_id]); $evento = $stmt_evento->fetch(PDO::FETCH_ASSOC); if (!$evento) { $errors[] = "Evento no encontrado."; } else { /* 2. Cargar Clubes */ $stmt_clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes"); while ($row = $stmt_clubes->fetch(PDO::FETCH_ASSOC)){$clubes_assoc[$row['id_club']]=$row['nombre_club'];} /* 3. Cargar Púgiles */ $stmt_pugiles = $pdo->query("SELECT id_pugil, nombre_pugil, apellido_pugil, fecha_nacimiento, es_profesional FROM pugiles"); while ($row = $stmt_pugiles->fetch(PDO::FETCH_ASSOC)){$pugiles_assoc[$row['id_pugil']]=$row;} /* 4. Cargar Pesos */ $stmt_pesos = $pdo->query("SELECT id_categoria_peso, descripcion_peso FROM categorias_peso"); while ($row = $stmt_pesos->fetch(PDO::FETCH_ASSOC)){$pesos_assoc[$row['id_categoria_peso']]=$row['descripcion_peso'];} /* 5. Cargar Combates */ $sql_combates = "SELECT * FROM combates WHERE id_evento = ? ORDER BY numero_combate ASC"; $stmt_combates = $pdo->prepare($sql_combates); $stmt_combates->execute([$evento_id]); $combates = $stmt_combates->fetchAll(PDO::FETCH_ASSOC); } } catch (Exception $e) { $errors[] = "Error cargando datos: ".$e->getMessage(); } }

?>

<?php // --- Pasar mensaje flash a JavaScript --- ?>
<?php if ($flash_message_type && $flash_message_text): ?>
<script id="flash-message-data" type="application/json">
    <?php echo json_encode(['type' => $flash_message_type, 'message' => $flash_message_text]); ?>
</script>
<?php endif; ?>
<?php // --- FIN Mensaje Flash --- ?>

<?php if ($evento): ?>
    <h1><i class="bi bi-calendar-event"></i> Evento: <?php echo htmlspecialchars($evento['nombre_evento']); ?></h1>
    <div class="card mb-4 shadow-sm"> <div class="card-body"> <h5 class="card-title">Detalles</h5> <p class="card-text"> <strong>Fecha:</strong> <?php echo htmlspecialchars(date('d/m/Y',strtotime($evento['fecha_evento']))); ?><br> <strong>Municipio:</strong> <?php echo htmlspecialchars($evento['nombre_municipio']??'N/D'); ?> | <strong>Recinto:</strong> <?php echo htmlspecialchars($evento['nombre_recinto']??'N/D'); ?><br> <strong>Máx. Combates:</strong> <?php echo htmlspecialchars($evento['max_combates']); ?> | <strong>Registrados:</strong> <?php echo count($combates); ?> </p> <a href="listar_eventos.php" class="btn btn-secondary btn-sm"><i class="bi bi-list-ul"></i> Volver</a> <a href="editar_evento.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Editar</a> <?php if(isset($combates) && count($combates) < $evento['max_combates']): ?> <a href="añadir_combate.php?evento_id=<?php echo $evento['id_evento']; ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Añadir Combate</a> <?php else: ?> <span class="badge bg-light text-dark p-2">Cartelera Completa</span> <?php endif; ?> <a href="eliminar_evento.php?id=<?php echo $evento['id_evento']; ?>" class="btn btn-danger btn-sm float-end" onclick="return confirm('Seguro eliminar evento y TODOS sus combates?');"><i class="bi bi-trash3"></i> Eliminar Evento</a> </div> </div>
<?php else: ?> <h1>Evento no encontrado</h1> <a href="listar_eventos.php" class="btn btn-secondary"><i class="bi bi-list-ul"></i> Volver</a> <?php endif; ?>

<?php // Mostrar errores de carga ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger mt-3"><strong>Error(es):</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<?php if ($evento && !empty($combates)): ?>
    <h2 class="mt-4 mb-3">Listado de combates (<?php echo count($combates); ?>)</h2>
    <?php foreach ($combates as $combate): ?>
        <?php // Preparar datos (igual que antes)...
            $peso_desc=$pesos_assoc[$combate['id_categoria_peso_combate']]??'?';$nombre_completo_rojo='?';$info_extra_rojo='';$categoria_edad_rojo='';if(isset($pugiles_assoc[$combate['id_pugil_rojo']])){$pgr=$pugiles_assoc[$combate['id_pugil_rojo']];$nombre_completo_rojo=htmlspecialchars($pgr['nombre_pugil'].' '.$pgr['apellido_pugil']);if($combate['es_profesional_rojo']){$info_extra_rojo='<span class="badge bg-dark me-1">PRO</span>'.($combate['procedencia_rojo']?' '.htmlspecialchars($combate['procedencia_rojo']):'');$categoria_edad_rojo='Pro';}else{$info_extra_rojo=htmlspecialchars($clubes_assoc[$combate['id_club_rojo']]??'Club?');$categoria_edad_rojo=calcularCategoriaEdad($pgr['fecha_nacimiento'])?:'?';}}else{if($combate['es_profesional_rojo']){$nombre_completo_rojo='Pro (Error Datos)';$info_extra_rojo=htmlspecialchars($combate['procedencia_rojo']??'?');}else{$nombre_completo_rojo='Amateur (Error Datos)';$info_extra_rojo=htmlspecialchars($clubes_assoc[$combate['id_club_rojo']]??'Club?');}}
            $nombre_completo_azul='?';$info_extra_azul='';$categoria_edad_azul='';if(isset($pugiles_assoc[$combate['id_pugil_azul']])){$pga=$pugiles_assoc[$combate['id_pugil_azul']];$nombre_completo_azul=htmlspecialchars($pga['nombre_pugil'].' '.$pga['apellido_pugil']);if($combate['es_profesional_azul']){$info_extra_azul='<span class="badge bg-dark me-1">PRO</span>'.($combate['procedencia_azul']?' '.htmlspecialchars($combate['procedencia_azul']):'');$categoria_edad_azul='Pro';}else{$info_extra_azul=htmlspecialchars($clubes_assoc[$combate['id_club_azul']]??'Club?');$categoria_edad_azul=calcularCategoriaEdad($pga['fecha_nacimiento'])?:'?';}}else{if($combate['es_profesional_azul']){$nombre_completo_azul='Pro (Error Datos)';$info_extra_azul=htmlspecialchars($combate['procedencia_azul']??'?');}else{$nombre_completo_azul='Amateur (Error Datos)';$info_extra_azul=htmlspecialchars($clubes_assoc[$combate['id_club_azul']]??'Club?');}}
            $icono_sexo = ($combate['sexo']==='Femenino')?'<i class="bi bi-gender-female text-danger"></i>':'<i class="bi bi-gender-male text-primary"></i>'; $categoria_combate=htmlspecialchars($combate['categoria']); $peso_display=htmlspecialchars($peso_desc);
            $badge_classes = ['Junior'=>'bg-info text-dark','Joven'=>'bg-success text-white','Elite'=>'bg-warning text-dark','Profesional'=>'bg-dark text-white','?'=>'bg-secondary']; $badge_class_rojo=$badge_classes[$categoria_edad_rojo]??'bg-light text-dark border'; $badge_class_azul=$badge_classes[$categoria_edad_azul]??'bg-light text-dark border';
        ?>
        <div class="card mb-3 shadow-sm"> <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap"> <div class="fw-bold me-3"> Combate Nº <?php echo htmlspecialchars($combate['numero_combate']); ?>: <span class="badge text-bg-secondary ms-1"><?php echo $categoria_combate; ?></span> <?php echo $icono_sexo; ?> <span class="badge text-bg-light border text-dark ms-1"><?php echo $peso_display; ?></span> </div> <div class="mt-1 mt-md-0"> <a href="editar_combate.php?id=<?php echo $combate['id_combate']; ?>" class="btn btn-sm btn-warning me-1" title="Editar"><i class="bi bi-pencil-fill"></i> Editar</a> <a href="eliminar_combate.php?id=<?php echo $combate['id_combate']; ?>&evento_id=<?php echo htmlspecialchars($evento_id);?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('Seguro eliminar combate Nº <?php echo $combate['numero_combate']; ?>?');"><i class="bi bi-trash3-fill"></i> Eliminar</a> </div> </div> <div class="card-body"> <div class="row align-items-center">
            <div class="col-md-5 text-center text-md-end border-end-md border-danger border-3 pe-md-3 mb-2 mb-md-0"> <h5 class="text-danger fw-bold mb-1"><?php echo $nombre_completo_rojo; ?> <span class="badge <?php echo $badge_class_rojo; ?>"><?php echo $categoria_edad_rojo; ?></span></h5> <div class="text-muted"><small><?php echo $info_extra_rojo; ?></small></div> </div>
            <div class="col-md-2 text-center fw-bold display-6 my-2 my-md-0"> <span class="badge bg-secondary">VS</span> </div>
            <div class="col-md-5 text-center text-md-start border-start-md border-primary border-3 ps-md-3"> <h5 class="text-primary fw-bold mb-1"><?php echo $nombre_completo_azul; ?> <span class="badge <?php echo $badge_class_azul; ?>"><?php echo $categoria_edad_azul; ?></span></h5> <div class="text-muted"><small><?php echo $info_extra_azul; ?></small></div> </div>
        </div> </div> </div>
    <?php endforeach; ?>
<?php elseif ($evento && count($combates)==0 && empty($errors)): ?>
    <div class="alert alert-info mt-4">No hay combates registrados para este evento. <?php if(isset($evento) && count($combates) < $evento['max_combates']):?><a href="añadir_combate.php?evento_id=<?php echo htmlspecialchars($evento_id);?>" class="alert-link">Añadir</a><?php endif;?>.</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; // Este footer incluye toasts.js ?>