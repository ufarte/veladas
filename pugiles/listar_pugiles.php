<?php
// /pugiles/listar_pugiles.php (v9 - Debug SQL Filtro Categoría)

// --- ERROR REPORTING TEMPORAL ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- FIN ERROR REPORTING ---

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// --- Variables de Filtro ---
$filtro_sexo = $_GET['filtro_sexo'] ?? 'todos';
$filtro_club = $_GET['filtro_club'] ?? 'todos';
$filtro_categoria = $_GET['filtro_categoria'] ?? 'todos'; // <-- Nuevo Filtro

// --- Variables de Paginación ---
define('ITEMS_PER_PAGE_PUGILES', 20);
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$total_items = 0; $total_pages = 0; $offset = 0; $limit = (int) ITEMS_PER_PAGE_PUGILES;

// --- Variables de Datos y Errores ---
$errors = []; $pugiles_pagina = []; $clubes_filtro = [];

// --- Mensajes Flash (Toasts) ---
$flash_message_type = null; $flash_message_text = null; if (isset($_SESSION['success_message'])) { $flash_message_type = 'success'; $flash_message_text = $_SESSION['success_message']; unset($_SESSION['success_message']); } elseif (isset($_SESSION['error_message'])) { $flash_message_type = 'danger'; $flash_message_text = $_SESSION['error_message']; unset($_SESSION['error_message']); }

$badge_classes = [ 'Junior'=>'bg-info text-dark', 'Joven'=>'bg-success text-white', 'Elite'=>'bg-warning text-dark', 'Profesional'=>'bg-dark text-white', 'Fuera de Rango'=>'bg-secondary text-white', '?'=>'bg-light text-dark border' ];

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");
    $clubes_filtro = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC")->fetchAll(PDO::FETCH_ASSOC);

    // --- Construir Cláusula WHERE y Parámetros (Named Placeholders) ---
    $baseSqlFrom = "FROM pugiles p LEFT JOIN clubes c ON p.id_club_pertenencia = c.id_club";
    $whereClausesNamed = [];
    $paramsNamed = []; // Array ASOCIATIVO
    $filter_error = false;

    // Sexo
    if ($filtro_sexo === 'Masculino' || $filtro_sexo === 'Femenino') { $whereClausesNamed[] = "p.sexo = :sexo_filter"; $paramsNamed[':sexo_filter'] = $filtro_sexo; }
    // Club
    if ($filtro_club !== 'todos') { if ($filtro_club === 'sin_club') { $whereClausesNamed[] = "p.id_club_pertenencia IS NULL AND p.es_profesional = 0"; } else { $clubId = filter_var($filtro_club, FILTER_VALIDATE_INT); if ($clubId) { $whereClausesNamed[] = "p.id_club_pertenencia = :club_id_filter"; $paramsNamed[':club_id_filter'] = $clubId; } else { $errors[] = "ID Club filtro inválido."; $filter_error = true; } } }
    // Categoría
    if ($filtro_categoria !== 'todos' && !$filter_error) {
        $current_year = (int)date('Y');
        if ($filtro_categoria === 'Profesional') { $whereClausesNamed[] = "p.es_profesional = 1"; }
        else { $whereClausesNamed[] = "p.es_profesional = 0"; $year_ranges = [ 'Elite' => [$current_year - 40, $current_year - 19], 'Joven' => [$current_year - 18, $current_year - 17], 'Junior' => [$current_year - 16, $current_year - 15] ];
            if (array_key_exists($filtro_categoria, $year_ranges)) { $range = $year_ranges[$filtro_categoria]; $whereClausesNamed[] = "YEAR(p.fecha_nacimiento) BETWEEN :year_start AND :year_end"; $paramsNamed[':year_start'] = $range[0]; $paramsNamed[':year_end'] = $range[1]; }
            elseif ($filtro_categoria === 'Fuera de Rango') { $not_in = []; $idx=0; foreach($year_ranges as $r){$s=':fr_s'.$idx;$e=':fr_e'.$idx;$not_in[]="YEAR(p.fecha_nacimiento) BETWEEN $s AND $e";$paramsNamed[$s]=$r[0];$paramsNamed[$e]=$r[1];$idx++;} if(!empty($not_in)) $whereClausesNamed[] = "p.fecha_nacimiento IS NOT NULL AND NOT (" . implode(" OR ", $not_in) . ")";}
            else { $errors[] = "Categoría filtro inválida."; $filter_error = true; }
        }
    }

    $sqlWhereNamed = "";
    if (!empty($whereClausesNamed)) { $sqlWhereNamed = " WHERE " . implode(" AND ", $whereClausesNamed); }

    // 1. Contar TOTAL con FILTROS
    if (!$filter_error){ $sql_count_named = "SELECT COUNT(p.id_pugil) " . $baseSqlFrom . $sqlWhereNamed; $stmt_count_named = $pdo->prepare($sql_count_named); $stmt_count_named->execute($paramsNamed); $total_items = $stmt_count_named->fetchColumn(); $total_pages = ($total_items > 0) ? ceil($total_items / ITEMS_PER_PAGE_PUGILES) : 1; if ($current_page > $total_pages) { $current_page = $total_pages; } $offset = max(0, ($current_page - 1) * ITEMS_PER_PAGE_PUGILES); } else { $total_items = 0; $total_pages = 1; $current_page = 1; $offset = 0; }

    // 2. Obtener Púgiles de la Página
    if (!$filter_error && $total_items > 0){
        $sql_list_named = "SELECT p.id_pugil, p.nombre_pugil, p.apellido_pugil, p.fecha_nacimiento, p.sexo, p.es_profesional, c.nombre_club " . $baseSqlFrom . $sqlWhereNamed . " ORDER BY p.nombre_pugil ASC, p.apellido_pugil ASC LIMIT :limit OFFSET :offset";

      

        $stmt_list_named = $pdo->prepare($sql_list_named);
        foreach ($paramsNamed as $key => &$value) { $stmt_list_named->bindValue($key, $value); } unset($value);
        $stmt_list_named->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt_list_named->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_list_named->execute();
        $pugiles_pagina = $stmt_list_named->fetchAll(PDO::FETCH_ASSOC);
    } else { $pugiles_pagina = []; }

} catch (Exception $e) { $errors[] = "Error al cargar púgiles: " . $e->getMessage(); error_log("Error listar pugiles v9: ".$e->getMessage()); }

// Preparar query string para paginación
$pagination_params = ['filtro_sexo' => $filtro_sexo, 'filtro_club' => $filtro_club, 'filtro_categoria' => $filtro_categoria];
$active_filters = array_filter($pagination_params, fn($val) => $val !== 'todos' && $val !== '');
$pagination_query_string = http_build_query($active_filters);
if (!empty($pagination_query_string)) $pagination_query_string .= '&';

?>

<h1><i class="bi bi-person-standing"></i> Gestión de Púgiles</h1>

<?php // --- Pasar mensaje flash a JavaScript --- ?>
<?php if ($flash_message_type && $flash_message_text): ?><script id="flash-message-data" type="application/json"><?php echo json_encode(['type' => $flash_message_type, 'message' => $flash_message_text]); ?></script><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2"> <a href="añadir_pugil.php" class="btn btn-success"><i class="bi bi-person-plus"></i> Añadir</a> <span class="text-muted">Total: <?php echo $total_items; ?> púgiles</span> </div>

<?php // --- Formulario de Filtros --- ?>
<form action="listar_pugiles.php" method="GET" class="row g-2 align-items-end bg-light p-3 rounded mb-4 border">
    <div class="col-md-3"> <label for="filtro_sexo" class="form-label">Sexo:</label> <select name="filtro_sexo" id="filtro_sexo" class="form-select form-select-sm"> <option value="todos" <?php echo ($filtro_sexo === 'todos') ? 'selected' : ''; ?>>Todos</option> <option value="Masculino" <?php echo ($filtro_sexo === 'Masculino') ? 'selected' : ''; ?>>Masculino</option> <option value="Femenino" <?php echo ($filtro_sexo === 'Femenino') ? 'selected' : ''; ?>>Femenino</option> </select> </div>
    <div class="col-md-4"> <label for="filtro_club" class="form-label">Club:</label> <select name="filtro_club" id="filtro_club" class="form-select form-select-sm"> <option value="todos" <?php echo ($filtro_club === 'todos') ? 'selected' : ''; ?>>Todos</option> <option value="sin_club" <?php echo ($filtro_club === 'sin_club') ? 'selected' : ''; ?>>Amateur Sin Club</option> <?php foreach ($clubes_filtro as $club): ?> <option value="<?php echo $club['id_club']; ?>" <?php echo ($filtro_club == $club['id_club']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($club['nombre_club']); ?></option> <?php endforeach; ?> </select> </div>
     <div class="col-md-3"> <label for="filtro_categoria" class="form-label">Categoría:</label> <select name="filtro_categoria" id="filtro_categoria" class="form-select form-select-sm"> <option value="todos" <?php echo ($filtro_categoria === 'todos') ? 'selected' : ''; ?>>Todas</option> <option value="Profesional" <?php echo ($filtro_categoria === 'Profesional') ? 'selected' : ''; ?>>Profesional</option> <option value="Elite" <?php echo ($filtro_categoria === 'Elite') ? 'selected' : ''; ?>>Elite</option> <option value="Joven" <?php echo ($filtro_categoria === 'Joven') ? 'selected' : ''; ?>>Joven</option> <option value="Junior" <?php echo ($filtro_categoria === 'Junior') ? 'selected' : ''; ?>>Junior</option> <option value="Fuera de Rango" <?php echo ($filtro_categoria === 'Fuera de Rango') ? 'selected' : ''; ?>>Fuera de Rango</option> </select> </div>
    <div class="col-md-2"> <label>&nbsp;</label><button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button> </div>
</form>

<?php if (!empty($errors)): ?><div class="alert alert-danger"><strong>Error(es):</strong><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="table-responsive"> <table class="table table-striped table-hover caption-top">
    <caption>Púgiles (Pág. <?php echo $current_page; ?> de <?php echo $total_pages; ?>)</caption>
    <thead class="table-dark"> <tr> <th>ID</th><th>Nombre</th><th>Apellidos</th><th>F. Nac.</th><th>Sexo</th><th>Categoría</th><th>Club</th><th>Acciones</th> </tr> </thead>
    <tbody>
         <?php if (empty($pugiles_pagina) && empty($errors)): ?> <tr><td colspan="8" class="text-center">No se encontraron púgiles<?php echo (!empty($active_filters)) ? ' con los filtros seleccionados' : ''; ?>.</td></tr>
         <?php else: ?>
            <?php foreach ($pugiles_pagina as $pugil): ?>
                <?php $catEdad = $pugil['es_profesional'] ? 'Profesional' : (calcularCategoriaEdad($pugil['fecha_nacimiento']) ?: '?'); $badge_class = $badge_classes[$catEdad] ?? 'bg-light text-dark border'; ?>
                <tr> <td><?php echo htmlspecialchars($pugil['id_pugil']); ?></td><td><?php echo htmlspecialchars($pugil['nombre_pugil']); ?></td><td><?php echo htmlspecialchars($pugil['apellido_pugil']); ?></td><td><?php echo htmlspecialchars($pugil['fecha_nacimiento'] ? date('d/m/Y', strtotime($pugil['fecha_nacimiento'])) : '-'); ?></td><td><?php echo htmlspecialchars($pugil['sexo']); ?></td><td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($catEdad); ?></span></td><td><?php echo htmlspecialchars($pugil['nombre_club'] ?: '--- Sin Club ---'); ?></td> <td class="text-nowrap"> <a href="editar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>" class="btn btn-sm btn-warning me-1" title="Editar"><i class="bi bi-pencil-square"></i></a> <a href="eliminar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('Seguro eliminar a \'<?php echo htmlspecialchars(addslashes($pugil['nombre_pugil'].' '.$pugil['apellido_pugil'])); ?>\'?');"><i class="bi bi-trash3"></i></a> </td> </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table> </div>

<?php // --- Controles de Paginación --- ?>
<?php if ($total_pages > 1): ?>
<nav aria-label="Navegación de páginas"> <ul class="pagination justify-content-center">
    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo $pagination_query_string; ?>page=<?php echo $current_page - 1; ?>">&laquo;</a></li>
    <?php for ($i = 1; $i <= $total_pages; $i++): ?> <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo $pagination_query_string; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a></li> <?php endfor; ?>
    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>"><a class="page-link" href="?<?php echo $pagination_query_string; ?>page=<?php echo $current_page + 1; ?>">&raquo;</a></li>
</ul> </nav>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>