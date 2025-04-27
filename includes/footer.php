<?php
// /includes/footer.php (v5.1 - Con Toast Container y toast.js include CORRECTO)
?>
</div> <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100"> <?php // z-index alto por si acaso ?>
  <?php // Los toasts se añadirán aquí dinámicamente con JavaScript ?>
</div>
<footer class="bg-dark text-white text-center p-3 mt-4">
    Gestión de Veladas de Boxeo &copy; <?php echo date("Y"); ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<?php
    // --- Helper para generar rutas y versionado (SIN CAMBIOS) ---
    $ruta_base_web = ''; // Ajusta si tu proyecto está en subcarpeta, ej: '/boxeo'
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
    function get_script_tag($base_path, $script_rel_path, $defer = false) {
        $web_path = $base_path . $script_rel_path;
        $server_path = $_SERVER['DOCUMENT_ROOT'] . $web_path; // Corregido aquí para usar $doc_root
        $version = @file_exists($server_path) ? @filemtime($server_path) : time();
        $defer_attr = $defer ? ' defer' : '';
        // Asegurarse que la ruta web empieza con / si $base_path está vacío o no empieza con /
        if (strpos($web_path, '/') !== 0 && $base_path === '') {
            $web_path = '/' . $web_path;
        } elseif (strpos($web_path, $base_path) !== 0 && $base_path !== '') {
             // En caso de que base_path se añada y la ruta relativa también empiece con / (menos común)
             $web_path = rtrim($base_path, '/') . '/' . ltrim($script_rel_path,'/');
        }

        return '<script src="' . htmlspecialchars($web_path) . '?v=' . $version . '"' . $defer_attr . '></script>';
    }
?>

<?php echo get_script_tag($ruta_base_web, '/js/combate_form.js', true); ?>

<?php echo get_script_tag($ruta_base_web, '/js/bs-validation.js', false); ?>

<?php echo get_script_tag($ruta_base_web, '/js/evento_form.js', true); ?>

<?php echo get_script_tag($ruta_base_web, '/js/toasts.js', true); ?>

<?php echo get_script_tag($ruta_base_web, '/js/inscripcion_velada_form.js', true); ?>

<?php // Aquí podrían ir otros scripts si fueran necesarios para páginas específicas ?>

</body>
</html>