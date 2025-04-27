<?php
// includes/header.php (Actualizado con Navegación y Active State)
if (session_status() == PHP_SESSION_NONE) { // Asegurar inicio de sesión
    session_start();
}
// Determinar la página actual para el estado 'active'
$current_page = basename($_SERVER['PHP_SELF']); // Nombre del archivo actual
$current_dir = basename(dirname($_SERVER['PHP_SELF'])); // Nombre del directorio actual
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Veladas de Boxeo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Pequeño ajuste para resaltar link activo */
        .navbar-nav .nav-link.active {
            font-weight: bold;
            /* text-decoration: underline; */
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">🥊 Gestión Veladas</a> <?php // Asumimos que index.php está en la raíz del proyecto ?>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0"> <?php // 'me-auto' para alinear a la izquierda ?>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>" href="/index.php">Dashboard</a>
        </li>
        <li class="nav-item">
           <a class="nav-link <?php echo ($current_dir === 'clubes') ? 'active' : ''; ?>" href="/clubes/listar_clubes.php">Clubes</a>
        </li>
         <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'pugiles') ? 'active' : ''; ?>" href="/pugiles/listar_pugiles.php">Púgiles</a>
        </li>
        <li class="nav-item dropdown"> <?php // Agrupar Eventos en Dropdown ?>
          <a class="nav-link dropdown-toggle <?php echo ($current_dir === 'eventos') ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Eventos
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/eventos/listar_eventos.php">Listar Eventos</a></li>
            <li><a class="dropdown-item" href="/eventos/crear_evento.php">Crear Nuevo Evento</a></li>
            <?php /* <li><hr class="dropdown-divider"></li> */ ?>
            <?php /* <li><a class="dropdown-item" href="#">Otra Acción Eventos</a></li> */ ?>
          </ul>
        </li>
		  		
					<li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo ($current_dir === 'informes') ? 'active' : ''; ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-graph-up"></i> Informes
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item <?php echo ($current_page === 'pugiles_por_club.php') ? 'active' : ''; ?>" href="/informes/pugiles_por_club.php">Púgiles por Club</a></li>
            <?php /* <li><a class="dropdown-item" href="#">Otro Informe</a></li> */ ?>
          </ul>
        </li>

      
      </ul>
      <?php /* Puedes añadir elementos a la derecha aquí si quieres, ej: user/logout */ ?>
    </div>
  </div>
</nav>

<div class="container mt-4"> <?php // Añadido mt-4 para margen superior ?>
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
					
			