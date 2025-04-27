<?php
// /informes/pugiles_por_club.php (v2 - Con Colores por Cat y Botón Eliminar)

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../includes/db_connect.php';
require_once '../includes/functions.php'; // Para calcularCategoriaEdad
require_once '../includes/header.php';

$clubes = [];
$pugiles_por_club = [];
$pugiles_sin_club = [];
$pugiles_profesionales = [];
$errors = [];

// Mapeo de Categorías a Clases de Badge Bootstrap
$badge_classes = [
    'Junior' => 'bg-info text-dark',
    'Joven' => 'bg-success text-white',
    'Elite' => 'bg-warning text-dark',
    'Profesional' => 'bg-dark text-white',
    'Fuera de Rango' => 'bg-secondary text-white',
    '?' => 'bg-light text-dark border'
];

try {
    if (!isset($pdo)) throw new Exception("PDO no disponible.");

    // 1. Cargar todos los clubes ordenados por nombre
    $stmt_clubes = $pdo->query("SELECT id_club, nombre_club FROM clubes ORDER BY nombre_club ASC");
    $clubes = $stmt_clubes->fetchAll(PDO::FETCH_ASSOC);
    $club_lookup = [];
    foreach ($clubes as $club) {
        $club_lookup[$club['id_club']] = $club['nombre_club'];
        $pugiles_por_club[$club['id_club']] = []; // Inicializar array para cada club
    }

    // 2. Cargar todos los púgiles
    $sql_pugiles = "SELECT id_pugil, nombre_pugil, apellido_pugil, fecha_nacimiento, es_profesional, id_club_pertenencia
                    FROM pugiles
                    ORDER BY es_profesional DESC, id_club_pertenencia ASC, apellido_pugil ASC, nombre_pugil ASC";
    $stmt_pugiles = $pdo->query($sql_pugiles);
    $todos_los_pugiles = $stmt_pugiles->fetchAll(PDO::FETCH_ASSOC);

    // 3. Clasificar púgiles
    foreach ($todos_los_pugiles as $pugil) {
        if ($pugil['es_profesional']) {
            $pugil['categoria_display'] = 'Profesional';
            $pugiles_profesionales[] = $pugil;
        } elseif ($pugil['id_club_pertenencia'] !== null && isset($club_lookup[$pugil['id_club_pertenencia']])) {
            $pugil['categoria_display'] = calcularCategoriaEdad($pugil['fecha_nacimiento']) ?: '?';
            $pugiles_por_club[$pugil['id_club_pertenencia']][] = $pugil;
        } else {
            $pugil['categoria_display'] = calcularCategoriaEdad($pugil['fecha_nacimiento']) ?: '?';
            $pugiles_sin_club[] = $pugil;
        }
    }

} catch (Exception $e) { $errors[] = "Error al generar el informe: " . htmlspecialchars($e->getMessage()); error_log("Error informe pugiles_por_club: " . $e->getMessage()); }

?>

<h1><i class="bi bi-file-earmark-text"></i> Informe: Púgiles por Club</h1>

<?php if (!empty($errors)): ?> <div class="alert alert-danger"><strong>Error(es):</strong><ul><?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?></ul></div> <?php endif; ?>

<p class="text-muted">Este informe muestra los púgiles agrupados por su club de pertenencia.</p>

<div class="accordion" id="accordionInformePugiles">

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingProfesionales">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProfesionales" aria-expanded="false" aria-controls="collapseProfesionales">
                <i class="bi bi-person-badge me-2"></i> Púgiles Profesionales (<?php echo count($pugiles_profesionales); ?>)
            </button>
        </h2>
        <div id="collapseProfesionales" class="accordion-collapse collapse" aria-labelledby="headingProfesionales" data-bs-parent="#accordionInformePugiles">
            <div class="accordion-body">
                <?php if (empty($pugiles_profesionales)): ?>
                    <p>No hay púgiles profesionales registrados.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pugiles_profesionales as $pro): ?>
                             <?php $badge_class = $badge_classes['Profesional'] ?? 'bg-secondary'; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span>
                                    <?php echo htmlspecialchars($pro['nombre_pugil'] . ' ' . $pro['apellido_pugil']); ?>
                                    <span class="badge <?php echo $badge_class; ?> ms-1"><?php echo $pro['categoria_display']; ?></span>
                                     <?php // Mostrar club si excepcionalmente un pro tiene club ?>
                                     <?php if ($pro['id_club_pertenencia'] && isset($club_lookup[$pro['id_club_pertenencia']])): ?>
                                        <span class="text-muted ms-2">(Club: <?php echo htmlspecialchars($club_lookup[$pro['id_club_pertenencia']]); ?>)</span>
                                    <?php endif; ?>
                                </span>
                                <span class="text-nowrap">
                                    <a href="/pugiles/editar_pugil.php?id=<?php echo $pro['id_pugil']; ?>" class="btn btn-outline-warning btn-sm me-1" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="/pugiles/eliminar_pugil.php?id=<?php echo $pro['id_pugil']; ?>&ref=report" class="btn btn-outline-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro que quieres eliminar a <?php echo htmlspecialchars(addslashes($pro['nombre_pugil'].' '.$pro['apellido_pugil'])); ?>?');"><i class="bi bi-trash3"></i></a>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php foreach ($clubes as $club): ?>
        <?php $id_club_actual = $club['id_club']; ?>
        <?php $pugiles_en_club = $pugiles_por_club[$id_club_actual] ?? []; ?>
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingClub<?php echo $id_club_actual; ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseClub<?php echo $id_club_actual; ?>" aria-expanded="false" aria-controls="collapseClub<?php echo $id_club_actual; ?>">
                   <i class="bi bi-shield-shaded me-2"></i> <?php echo htmlspecialchars($club['nombre_club']); ?> (<?php echo count($pugiles_en_club); ?> Amateur<?php echo (count($pugiles_en_club) != 1 ? 's' : ''); ?>)
                </button>
            </h2>
            <div id="collapseClub<?php echo $id_club_actual; ?>" class="accordion-collapse collapse" aria-labelledby="headingClub<?php echo $id_club_actual; ?>" data-bs-parent="#accordionInformePugiles">
                <div class="accordion-body">
                    <?php if (empty($pugiles_en_club)): ?>
                        <p>No hay púgiles amateur registrados en este club.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pugiles_en_club as $pugil): ?>
                                <?php $cat_display = $pugil['categoria_display']; ?>
                                <?php $badge_class = $badge_classes[$cat_display] ?? 'bg-light text-dark border'; // Clase por defecto si no está en mapeo ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <span>
                                        <?php echo htmlspecialchars($pugil['nombre_pugil'] . ' ' . $pugil['apellido_pugil']); ?>
                                        <span class="badge <?php echo $badge_class; ?> ms-1"><?php echo $cat_display; ?></span>
                                    </span>
                                     <span class="text-nowrap">
                                        <a href="/pugiles/editar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>" class="btn btn-outline-warning btn-sm me-1" title="Editar"><i class="bi bi-pencil"></i></a>
                                        <a href="/pugiles/eliminar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>&ref=report" class="btn btn-outline-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro que quieres eliminar a <?php echo htmlspecialchars(addslashes($pugil['nombre_pugil'].' '.$pugil['apellido_pugil'])); ?>?');"><i class="bi bi-trash3"></i></a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="accordion-item">
        <h2 class="accordion-header" id="headingSinClub">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSinClub" aria-expanded="false" aria-controls="collapseSinClub">
                <i class="bi bi-person-slash me-2"></i> Púgiles Amateur Sin Club Asignado (<?php echo count($pugiles_sin_club); ?>)
            </button>
        </h2>
        <div id="collapseSinClub" class="accordion-collapse collapse" aria-labelledby="headingSinClub" data-bs-parent="#accordionInformePugiles">
            <div class="accordion-body">
                 <?php if (empty($pugiles_sin_club)): ?>
                    <p>Todos los púgiles amateur tienen club asignado.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pugiles_sin_club as $pugil): ?>
                             <?php $cat_display = $pugil['categoria_display']; ?>
                             <?php $badge_class = $badge_classes[$cat_display] ?? 'bg-light text-dark border'; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span>
                                    <?php echo htmlspecialchars($pugil['nombre_pugil'] . ' ' . $pugil['apellido_pugil']); ?>
                                    <span class="badge <?php echo $badge_class; ?> ms-1"><?php echo $cat_display; ?></span>
                                </span>
                                 <span class="text-nowrap">
                                    <a href="/pugiles/editar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>" class="btn btn-outline-warning btn-sm me-1" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <a href="/pugiles/eliminar_pugil.php?id=<?php echo $pugil['id_pugil']; ?>&ref=report" class="btn btn-outline-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro que quieres eliminar a <?php echo htmlspecialchars(addslashes($pugil['nombre_pugil'].' '.$pugil['apellido_pugil'])); ?>?');"><i class="bi bi-trash3"></i></a>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div> <?php require_once '../includes/footer.php'; ?>