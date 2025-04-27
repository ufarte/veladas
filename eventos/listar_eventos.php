<?php
// /eventos/listar_eventos.php (v4 - Con Paginación y Toasts)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

// --- Variables de Paginación ---
define('ITEMS_PER_PAGE_EVENTOS', 10); // Items por página para eventos
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$total_items = 0;
$total_pages = 0;
$offset = ($current_page - 1) * ITEMS_PER_PAGE_EVENTOS;
// --- Fin Variables Paginación ---

$eventos_pagina = [];
$errors = [];

// --- Revisar Mensajes de Sesión para Toasts ---
$flash_message_type = null; $flash_message_text = null; if (isset($_SESSION['success_message'])) { $flash_message_type = 'success'; $flash_message_text = $_SESSION['success_message']; unset($_SESSION['success_message']); } elseif (isset($_SESSION['error_message'])) { $flash_message_type = 'danger'; $flash_message_text = $_SESSION['error_message']; unset($_SESSION['error_message']); }
// --- Fin Revisar Mensajes ---

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");

    // 1. Contar el TOTAL de eventos (WHERE iría aquí si hubiera filtros)
    $sql_count = "SELECT COUNT(*) FROM eventos";
    $total_items = $pdo->query($sql_count)->fetchColumn();
    $total_pages = ceil($total_items / ITEMS_PER_PAGE_EVENTOS);

    if ($current_page > $total_pages && $total_items > 0) { header("Location: listar_eventos.php?page=" . $total_pages); exit; }

    // 2. Obtener SOLO los eventos para la página actual (con JOINs y COUNT combates)
    $sql_list = "SELECT
                    e.id_evento, e.nombre_evento, e.fecha_evento, e.max_combates,
                    m.nombre_municipio, r.nombre_recinto,
                    COUNT(c.id_combate) AS num_combates_actual
                 FROM eventos e
                 LEFT JOIN municipios m ON e.id_municipio = m.id_municipio
                 LEFT JOIN recintos r ON e.id_recinto = r.id_recinto
                 LEFT JOIN combates c ON e.id_evento = c.id_evento
                 -- WHERE iría aquí si hubiera filtros
                 GROUP BY e.id_evento, e.nombre_evento, e.fecha_evento, e.max_combates, m.nombre_municipio, r.nombre_recinto
                 ORDER BY e.fecha_evento DESC, e.id_evento DESC
                 LIMIT :limit OFFSET :offset";
    $stmt_list = $pdo->prepare($sql_list);
    $stmt_list->bindValue(':limit', ITEMS_PER_PAGE_EVENTOS, PDO::PARAM_INT);
    $stmt_list->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_list->execute();
    $eventos_pagina = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { $errors[] = "Error al cargar eventos: " . $e->getMessage(); error_log("Error listar eventos paginacion: ".$e->getMessage()); }

?>

<h1><i class="bi bi-calendar-event-fill"></i> Lista de Eventos (Veladas)</h1>

<?php // --- Pasar mensaje flash a JavaScript --- ?>
<?php if ($flash_message_type && $flash_message_text): ?><script id="flash-message-data" type="application/json"><?php echo json_encode(['type' => $flash_message_type, 'message' => $flash_message_text]); ?></script><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="crear_evento.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Crear Nuevo Evento</a>
     <span class="text-muted">Total: <?php echo $total_items; ?> eventos</span>
</div>

<?php if (!empty($errors)): ?><div class="alert alert-danger"><strong>Error(es):</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered align-middle">
        <thead class="table-dark">
             <tr><th>Nombre</th><th>Fecha</th><th>Municipio</th><th>Recinto</th><th class="text-center">Combates<br><small>(Reg/Max)</small></th><th class="text-center">Acciones</th></tr>
        </thead>
        <tbody>
             <?php if (empty($eventos_pagina) && $total_items > 0 && empty($errors)): ?>
                 <tr><td colspan="6" class="text-center">No hay eventos en esta página (Pág. <?php echo $current_page; ?> de <?php echo $total_pages; ?>).</td></tr>
             <?php elseif (empty($eventos_pagina) && $total_items == 0 && empty($errors)): ?>
                 <tr><td colspan="6" class="text-center">No hay eventos registrados todavía.</td></tr>
             <?php else: ?>
                <?php foreach ($eventos_pagina as $evento): ?>
                    <?php $evento_lleno = ($evento['num_combates_actual'] >= $evento['max_combates']); ?>
                    <tr>
                        <td><?php echo htmlspecialchars($evento['nombre_evento']); ?></td><td><?php echo htmlspecialchars(date('d/m/Y', strtotime($evento['fecha_evento']))); ?></td><td><?php echo htmlspecialchars($evento['nombre_municipio'] ?? 'N/D'); ?></td><td><?php echo htmlspecialchars($evento['nombre_recinto'] ?? 'N/D'); ?></td>
                        <td class="text-center"><?php echo $evento['num_combates_actual']; ?> / <?php echo htmlspecialchars($evento['max_combates']); ?><?php if($evento_lleno):?><i class="bi bi-check-circle-fill text-success ms-1" title="Completo"></i><?php endif;?></td>
                        <td class="text-center text-nowrap">
                            <a href="ver_evento.php?id=<?php echo $evento['id_evento'];?>" class="btn btn-sm btn-info" title="Ver"><i class="bi bi-eye-fill"></i></a>
                            <?php if($evento_lleno):?> <button type="button" class="btn btn-sm btn-secondary disabled" title="Lleno"><i class="bi bi-ban"></i></button>
                            <?php else:?> <a href="añadir_combate.php?evento_id=<?php echo $evento['id_evento'];?>" class="btn btn-sm btn-success" title="Añadir Combate"><i class="bi bi-plus-lg"></i></a>
                            <?php endif;?>
                            <a href="editar_evento.php?id=<?php echo $evento['id_evento'];?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil-square"></i></a>
                            <a href="eliminar_evento.php?id=<?php echo $evento['id_evento'];?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Seguro eliminar evento y TODOS sus combates?');"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php // --- Controles de Paginación --- ?>
<?php if ($total_pages > 1): ?>
<nav aria-label="Navegación de páginas"> <ul class="pagination justify-content-center">
    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo $current_page - 1; ?>">&laquo;</a></li>
    <?php // Lógica para mostrar números (podría mejorarse para muchas páginas) ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?> <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li> <?php endfor; ?>
    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo $current_page + 1; ?>">&raquo;</a></li>
</ul> </nav>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>