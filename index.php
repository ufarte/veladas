<?php
// index.php - Dashboard Principal (Actualizado)

require_once 'includes/db_connect.php'; // Para los contadores
require_once 'includes/header.php';

// Obtener contadores simples
$count_eventos = 0;
$count_clubes = 0;
$count_pugiles = 0;

try {
    if(isset($pdo)){
        $count_eventos = $pdo->query("SELECT COUNT(*) FROM eventos")->fetchColumn();
        $count_clubes = $pdo->query("SELECT COUNT(*) FROM clubes")->fetchColumn();
        $count_pugiles = $pdo->query("SELECT COUNT(*) FROM pugiles")->fetchColumn();
    }
} catch (Exception $e) {
    // No hacer nada grave si fallan los contadores, quizás loggear el error
    error_log("Error al obtener contadores para dashboard: " . $e->getMessage());
}

?>

<div class="p-5 mb-4 bg-light rounded-3">
  <div class="container-fluid py-5">
    <h1 class="display-5 fw-bold">Dashboard de Gestión</h1>
    <p class="col-md-8 fs-4">Bienvenido al sistema de gestión de veladas de boxeo.</p>
    <p>
        <span class="badge text-bg-primary position-relative me-3">
            Eventos Registrados <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $count_eventos; ?></span>
        </span>
         <span class="badge text-bg-info position-relative me-3">
            Clubes Registrados <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $count_clubes; ?></span>
        </span>
         <span class="badge text-bg-success position-relative me-3">
            Púgiles Registrados <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"><?php echo $count_pugiles; ?></span>
        </span>
    </p>
  </div>
</div>

<div class="row align-items-md-stretch">
      <div class="col-md-6 mb-3">
        <div class="h-100 p-5 text-bg-dark rounded-3">
          <h2><i class="bi bi-calendar-event"></i> Eventos</h2>
          <p>Crear nuevas veladas o ver/editar las existentes y sus combates.</p>
          <a class="btn btn-outline-light me-2" href="/eventos/crear_evento.php" role="button"><i class="bi bi-plus-circle"></i> Crear Nuevo</a>
          <a class="btn btn-outline-light" href="/eventos/listar_eventos.php" role="button"><i class="bi bi-list-ul"></i> Ver Lista</a>
        </div>
      </div>
       <div class="col-md-6 mb-3">
        <div class="h-100 p-5 bg-secondary-subtle border rounded-3">
          <h2><i class="bi bi-shield-shaded"></i> Clubes</h2>
          <p>Añade, edita o elimina clubes de la base de datos.</p>
           <a class="btn btn-outline-primary me-2" href="/clubes/añadir_club.php" role="button"><i class="bi bi-plus-circle"></i> Añadir Nuevo</a>
          <a class="btn btn-outline-primary" href="/clubes/listar_clubes.php" role="button"><i class="bi bi-list-ul"></i> Ver Lista</a>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="h-100 p-5 bg-secondary-subtle border rounded-3">
          <h2><i class="bi bi-person-standing"></i> Púgiles</h2>
          <p>Administra la información de los boxeadores federados.</p>
           <a class="btn btn-outline-primary me-2" href="/pugiles/añadir_pugil.php" role="button"><i class="bi bi-person-plus"></i> Añadir Nuevo</a>
          <a class="btn btn-outline-primary" href="/pugiles/listar_pugiles.php" role="button"><i class="bi bi-list-ul"></i> Ver Lista</a>
        </div>
      </div>
       <div class="col-md-6 mb-3">
        <div class="h-100 p-5 bg-light border rounded-3">
          <h2><i class="bi bi-gear"></i> Próximos Pasos</h2>
          <p>Podríamos añadir más funcionalidades como informes, gestión de usuarios, validación avanzada, etc.</p>
          <button class="btn btn-outline-secondary" type="button" disabled>Próximamente »</button>
        </div>
      </div>
</div>

<?php
// Incluir pie de página
require_once 'includes/footer.php';
?>