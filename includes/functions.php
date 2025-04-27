<?php
// /includes/functions.php (Función calcularCategoriaEdad CORREGIDA - Basada en AÑO)

/**
 * Calcula la categoría de edad (Junior, Joven, Elite) basada en el AÑO de nacimiento
 * para la temporada del AÑO ACTUAL, según las reglas estándar de la FEB.
 * Se adapta automáticamente al año en curso.
 *
 * @param string|null $fechaNacimiento Fecha de nacimiento en formato 'YYYY-MM-DD'.
 * @return string|null La categoría ('Junior', 'Joven', 'Elite', 'Fuera de Rango') o null si la fecha es inválida.
 */
function calcularCategoriaEdad(?string $fechaNacimiento): ?string {
    if (!$fechaNacimiento || $fechaNacimiento === '0000-00-00') {
        return null; // O 'Falta Fecha' si prefieres
    }

    try {
        // Solo necesitamos el año de nacimiento
        $nacimiento = new DateTime($fechaNacimiento);
        $anoNacimiento = (int)$nacimiento->format('Y');

        // Obtenemos el año actual REAL del servidor
        $anoActual = (int)date('Y');

        // Definir rangos de AÑO DE NACIMIENTO basados en el AÑO ACTUAL
        // Estos rangos son para la categoría en la que compiten DURANTE TODO el $anoActual
        $ano_elite_fin = $anoActual - 19;
        $ano_elite_inicio = $anoActual - 40;
        $ano_joven_fin = $anoActual - 17;
        $ano_joven_inicio = $anoActual - 18;
        $ano_junior_fin = $anoActual - 15;
        $ano_junior_inicio = $anoActual - 16;

        // Comprobar rangos (del más joven al más viejo)
        if ($anoNacimiento >= $ano_junior_inicio && $anoNacimiento <= $ano_junior_fin) { // 15-16 años
            return 'Junior';
        } elseif ($anoNacimiento >= $ano_joven_inicio && $anoNacimiento <= $ano_joven_fin) { // 17-18 años
            return 'Joven';
        } elseif ($anoNacimiento >= $ano_elite_inicio && $anoNacimiento <= $ano_elite_fin) { // 19-40 años
            return 'Elite';
        } else {
            // Si no encaja en ninguna categoría amateur definida
            return 'Fuera de Rango';
        }

    } catch (Exception $e) {
        error_log("Error al calcular categoría por año para fecha '$fechaNacimiento': " . $e->getMessage());
        return '?'; // Error al procesar fecha
    }
}

// --- Puedes añadir más funciones útiles aquí en el futuro ---

?>