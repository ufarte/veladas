<?php
// /clubes/listar_clubes.php (v3 - Con Paginación y Toasts)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/header.php'; // Asume que header incluye functions.php si es necesario

// --- Variables de Paginación ---
define('ITEMS_PER_PAGE', 15); // ¿Cuántos clubes por página?
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$total_items = 0;
$total_pages = 0;
$offset = ($current_page - 1) * ITEMS_PER_PAGE;
// --- Fin Variables Paginación ---

$errors = [];
$clubes_pagina = []; // Solo los clubes de esta página

// --- Revisar Mensajes de Sesión para Toasts ---
$flash_message_type = null; $flash_message_text = null;
if (isset($_SESSION['success_message'])) { $flash_message_type = 'success'; $flash_message_text = $_SESSION['success_message']; unset($_SESSION['success_message']); }
elseif (isset($_SESSION['error_message'])) { $flash_message_type = 'danger'; $flash_message_text = $_SESSION['error_message']; unset($_SESSION['error_message']); }
// --- Fin Revisar Mensajes ---

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");

    // 1. Contar el TOTAL de clubes
    $sql_count = "SELECT COUNT(*) FROM clubes";
    $total_items = $pdo->query($sql_count)->fetchColumn();
    $total_pages = ceil($total_items / ITEMS_PER_PAGE);

    // Asegurarse que la página actual no sea mayor que el total de páginas
    if ($current_page > $total_pages && $total_items > 0) {
        // Opcional: redirigir a la última página válida
        header("Location: listar_clubes.php?page=" . $total_pages);
        exit;
    }

    // 2. Obtener SOLO los clubes para la página actual
    $sql_list = "SELECT id_club, nombre_club
                 FROM clubes
                 ORDER BY nombre_club ASC
                 LIMIT :limit OFFSET :offset";
    $stmt_list = $pdo->prepare($sql_list);
    // Necesitamos bindValue para LIMIT/OFFSET con PDO
    $stmt_list->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt_list->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_list->execute();
    $clubes_pagina = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { $errors[] = "Error al cargar clubes: " . $e->getMessage(); error_log("Error listar clubes paginacion: ".$e->getMessage()); }

// --- Mostrar la Página ---
?>

<h1><i class="bi bi-shield-shaded"></i> Gestión de Clubes</h1>

<?php // --- Pasar mensaje flash a JavaScript --- ?>
<?php if ($flash_message_type && $flash_message_text): ?>
<script id="flash-message-data" type="application/json">
    <?php echo json_encode(['type' => $flash_message_type, 'message' => $flash_message_text]); ?>
</script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="añadir_club.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Añadir Nuevo Club</a>
    <span class="text-muted">Total: <?php echo $total_items; ?> clubes</span>
</div>

<?php if (!empty($errors)): ?><div class="alert alert-danger"><strong>Error(es):</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead class="table-dark">
            <tr> <th>ID</th> <th>Nombre del Club</th> <th class="text-center">Acciones</th> </tr>
        </thead>
        <tbody>
            <?php if (empty($clubes_pagina) && $total_items > 0 && empty($errors)): ?>
                 <tr><td colspan="3" class="text-center">No hay clubes en esta página (Página <?php echo $current_page; ?> de <?php echo $total_pages; ?>).</td></tr>
            <?php elseif (empty($clubes_pagina) && $total_items == 0 && empty($errors)): ?>
                 <tr><td colspan="3" class="text-center">No hay clubes registrados todavía.</td></tr>
            <?php else: ?>
                <?php foreach ($clubes_pagina as $club): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($club['id_club']); ?></td>
                        <td><?php echo htmlspecialchars($club['nombre_club']); ?></td>
                        <td class="text-center text-nowrap">
                            <a href="editar_club.php?id=<?php echo $club['id_club']; ?>" class="btn btn-sm btn-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a>
                            <a href="eliminar_club.php?id=<?php echo $club['id_club']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('Seguro eliminar \'<?php echo htmlspecialchars(addslashes($club['nombre_club'])); ?>\'?');"><i class="bi bi-trash3"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php // --- Controles de Paginación --- ?>
<?php if ($total_pages > 1): ?>
<nav aria-label="Navegación de páginas">
  <ul class="pagination justify-content-center">
    <?php // Botón Anterior ?>
    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Anterior">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>

    <?php // Números de Página (Lógica simple para mostrar todos) ?>
    <?php // Para muchas páginas, aquí iría lógica para mostrar solo algunos números (ej. 1 ... 5 6 7 ... 20) ?>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
      </li>
    <?php endfor; ?>

    <?php // Botón Siguiente ?>
    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
      <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Siguiente">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  </ul>
</nav>
<?php endif; ?>
<?php // --- Fin Controles de Paginación --- ?>

<?php require_once '../includes/footer.php'; ?>