<?php
// /ajax_handler.php (v5.6 - Completo con filtrado profesional funcional + Mayúsculas/Apellido Compuesto en Add + get_procedencias action)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'data' => [], 'message' => 'Acción no válida'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

try {
    if (!isset($pdo)) {
        throw new Exception("PDO no disponible.");
    }

    switch ($action) {
        case 'get_pugil_details':
            // ... (código existente para get_pugil_details - SIN CAMBIOS ADICIONALES)
            $pugil_id = filter_input(INPUT_GET, 'id_pugil', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
            if (!$pugil_id) {
                $response['message'] = 'ID de púgil inválido.';
            } else {
                $sql = "SELECT id_pugil, nombre_pugil, apellido_pugil, fecha_nacimiento, sexo, es_profesional
                        FROM pugiles WHERE id_pugil = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$pugil_id]);
                $pugil = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$pugil) {
                    $response['message'] = 'Púgil no encontrado.';
                } else {
                    if ($pugil['es_profesional']) {
                        $pugil['categoria_calculada'] = 'Profesional';
                    } else {
                        $pugil['categoria_calculada'] = calcularCategoriaEdad($pugil['fecha_nacimiento']);
                        if (!$pugil['categoria_calculada'] || $pugil['categoria_calculada'] === '?') {
                            $pugil['categoria_calculada'] = 'Desconocida';
                        }
                    }
                    $response['success'] = true;
                    $response['data'] = $pugil;
                }
            }
            break;

       case 'get_pugiles_por_club':
            // ... (código existente para get_pugiles_por_club - SIN CAMBIOS ADICIONALES)
            $club_id = filter_input(INPUT_GET, 'club_id', FILTER_VALIDATE_INT);
            $categoria = filter_input(INPUT_GET, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS);
            $sexo = filter_input(INPUT_GET, 'sexo', FILTER_SANITIZE_SPECIAL_CHARS);
            $exclude_id = filter_input(INPUT_GET, 'exclude_id', FILTER_VALIDATE_INT);

            if (!$club_id) {
                $response['message'] = 'ID club inválido.';
            } else {
                $sql = "SELECT id_pugil, nombre_pugil, apellido_pugil, fecha_nacimiento, sexo
                        FROM pugiles
                        WHERE id_club_pertenencia = ? AND es_profesional = 0";

                $params = [$club_id];

                if ($exclude_id) {
                    $sql .= " AND id_pugil != ?";
                    $params[] = $exclude_id;
                }

                $sql .= " ORDER BY nombre_pugil, apellido_pugil";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $pugiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $formatted_data = [];
                foreach ($pugiles as $pugil) {
                    $cat = calcularCategoriaEdad($pugil['fecha_nacimiento']) ?: '?';

                    if (($categoria && $cat !== $categoria) || ($sexo && $pugil['sexo'] !== $sexo)) {
                        continue;
                    }

                    $formatted_data[] = [
                        'id' => $pugil['id_pugil'],
                        'text' => htmlspecialchars($pugil['nombre_pugil'].' '.$pugil['apellido_pugil'].' ('.$cat.')')
                    ];
                }

                $response['success'] = true;
                $response['data'] = $formatted_data;
            }
            break;

        case 'get_profesionales':
            // ... (código existente para get_profesionales - SIN CAMBIOS ADICIONALES)
            $sexo = filter_input(INPUT_GET, 'sexo', FILTER_SANITIZE_SPECIAL_CHARS);
            $exclude_id = filter_input(INPUT_GET, 'exclude_id', FILTER_VALIDATE_INT);

            $sexos_validos = ['Masculino', 'Femenino'];
            $filtro_sexo = ($sexo && in_array($sexo, $sexos_validos)) ? $sexo : null;

            $sql = "SELECT id_pugil, nombre_pugil, apellido_pugil, sexo
                    FROM pugiles
                    WHERE es_profesional = 1";

            $params = [];

            if ($filtro_sexo) {
                $sql .= " AND sexo = ?";
                $params[] = $filtro_sexo;
            }

            if ($exclude_id) {
                $sql .= " AND id_pugil != ?";
                $params[] = $exclude_id;
            }

            $sql .= " ORDER BY nombre_pugil, apellido_pugil";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $profesionales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['data'] = array_map(function($pro) {
                return [
                    'id' => $pro['id_pugil'],
                    'text' => htmlspecialchars($pro['nombre_pugil'].' '.$pro['apellido_pugil'].' (Profesional)')
                ];
            }, $profesionales);
            break;

        case 'get_pesos_por_categoria':
            // ... (código existente para get_pesos_por_categoria - SIN CAMBIOS ADICIONALES)
            $cat = filter_input(INPUT_GET, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS);
            $sex = filter_input(INPUT_GET, 'sexo', FILTER_SANITIZE_SPECIAL_CHARS);
            $vC = ['Profesional','Elite','Joven','Junior'];
            $vS = ['Masculino','Femenino'];

            if (!$cat || !$sex || !in_array($cat, $vC) || !in_array($sex, $vS)) {
                $response['message'] = 'Cat/sexo inválido.';
            } else {
                $sql = "SELECT id_categoria_peso, descripcion_peso
                        FROM categorias_peso
                        WHERE categoria = ? AND sexo = ?
                        ORDER BY id_categoria_peso";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cat, $sex]);
                $pesos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['data'] = array_map(function($p) {
                    return [
                        'id' => $p['id_categoria_peso'],
                        'text' => htmlspecialchars($p['descripcion_peso'])
                    ];
                }, $pesos);
            }
            break;

        case 'get_recintos_por_municipio':
            // ... (código existente para get_recintos_por_municipio - SIN CAMBIOS ADICIONALES)
            $mun_id = filter_input(INPUT_GET, 'id_municipio', FILTER_VALIDATE_INT);
            if (!$mun_id || $mun_id <= 0) {
                $response['message'] = 'ID municipio inválido.';
            } else {
                $sql = "SELECT id_recinto, nombre_recinto
                        FROM recintos
                        WHERE id_municipio = ?
                        ORDER BY nombre_recinto ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$mun_id]);
                $recintos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['data'] = array_map(function($r) {
                    return [
                        'id' => $r['id_recinto'],
                        'text' => htmlspecialchars($r['nombre_recinto'])
                    ];
                }, $recintos);
            }
            break;

        // === INICIO NUEVA ACCIÓN: Obtener lista de Procedencias ===
        case 'get_procedencias':
            try {
                $sql = "SELECT id, nombre, tipo, grupo
                        FROM procedencias
                        ORDER BY
                            CASE tipo
                                WHEN 'Comunidad Autónoma' THEN 1
                                WHEN 'País' THEN 2
                                ELSE 3 -- Para cualquier otro tipo futuro
                            END,
                            grupo ASC, -- Ordena por continente/grupo
                            nombre ASC; -- Ordena alfabéticamente dentro del grupo";

                $stmt = $pdo->query($sql);
                $procedencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $formatted_data = [];
                foreach ($procedencias as $proc) {
                    $formatted_data[] = [
                        'id' => $proc['id'],
                        'text' => htmlspecialchars($proc['nombre']),
                        'tipo' => $proc['tipo'],
                        'grupo' => $proc['grupo'] // Este será NULL para CCAA, nombre del continente para Países
                    ];
                }

                $response['success'] = true;
                $response['data'] = $formatted_data;

            } catch (Exception $e) {
                $response['message'] = 'Error al cargar procedencias: '.$e->getMessage();
                error_log("Error AJAX (get_procedencias): ".$e->getMessage());
            }
            break;
        // === FIN NUEVA ACCIÓN ===


        case 'add_club':
            // ... (código existente para add_club con modificación de MAYÚSCULAS)
            $nom = trim($_POST['nombre_club'] ?? '');

            // === Convertir a MAYÚSCULAS (para AJAX add_club) ===
            $nom = strtoupper($nom);
            // === FIN MODIFICACIÓN ===

            if (empty($nom)) {
                $response['message'] = 'Nombre vacío.';
            } else {
                $sC = $pdo->prepare("SELECT COUNT(*) FROM clubes WHERE nombre_club = ?");
                $sC->execute([$nom]);

                if ($sC->fetchColumn() > 0) {
                    $response['message'] = "Club '".htmlspecialchars($nom)."' ya existe.";
                } else {
                    $sI = $pdo->prepare("INSERT INTO clubes(nombre_club) VALUES(?)");
                    if ($sI->execute([$nom])) {
                        $nid = $pdo->lastInsertId();
                        $response['success'] = true;
                        $response['data'] = [
                            'new_club_id' => $nid,
                            'new_club_name' => $nom // Devolver el nombre en mayúsculas
                        ];
                    }
                }
            }
            break;

        case 'add_profesional':
            $nom = trim($_POST['nombre_pugil'] ?? '');
            $ape = trim($_POST['apellido_pugil'] ?? '');
            $sex = trim($_POST['sexo'] ?? '');
            $fdef = '1970-01-01'; // Fecha de nacimiento por defecto para profesionales
            $err = [];

            // === Procesar Apellido Compuesto y Convertir a MAYÚSCULAS ===
            $apellido_original = $ape;
            $ape = '';
            if (!empty($apellido_original)) {
                $apellido_parts = explode(' ', $apellido_original);
                $processed_parts = [];
                if (!empty($apellido_parts[0])) {
                    $processed_parts[] = $apellido_parts[0];
                    $first_word_upper = strtoupper($apellido_parts[0]);
                    $compound_prefixes = ['DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS'];
                    if (in_array($first_word_upper, $compound_prefixes) && count($apellido_parts) > 1) {
                        $second_word_upper = strtoupper($apellido_parts[1]);
                        if ($first_word_upper === 'DE' && in_array($second_word_upper, ['LA', 'LOS', 'LAS']) && count($apellido_parts) > 2) {
                             $processed_parts[] = $apellido_parts[1];
                             $processed_parts[] = $apellido_parts[2];
                        } else {
                             $processed_parts[] = $apellido_parts[1];
                        }
                    }
                }
                $ape = implode(' ', $processed_parts);
            }
            $nom = strtoupper($nom);
            $ape = strtoupper($ape);
            // === FIN MODIFICACIÓN ===

            if (empty($nom)) $err[] = "Nombre";
            if (empty($ape)) $err[] = "Apellido";
            if (empty($sex) || !in_array($sex, ['Masculino','Femenino'])) $err[] = "Sexo";

            if (!empty($err)) {
                $response['message'] = "Obligatorios: ".implode(', ', $err);
            } else {
                $sCP = $pdo->prepare("SELECT id_pugil FROM pugiles WHERE nombre_pugil = ? AND apellido_pugil = ? AND es_profesional = 1");
                $sCP->execute([$nom, $ape]);

                if ($sCP->fetch()) {
                    $response['message'] = "Profesional ya existe.";
                } else {
                    $sIP = $pdo->prepare("INSERT INTO pugiles(nombre_pugil, apellido_pugil, fecha_nacimiento, sexo, es_profesional, id_club_pertenencia)
                                         VALUES(?, ?, ?, ?, 1, NULL)");
                    if ($sIP->execute([$nom, $ape, $fdef, $sex])) {
                        $nid = $pdo->lastInsertId();
                        $response['success'] = true;
                        $response['data'] = [
                            'new_pugil_id' => $nid,
                            'new_pugil_name' => htmlspecialchars($nom.' '.$ape.' (Profesional)')
                        ];
                    }
                }
            }
            break;

        case 'add_pugil_inline':
            $nom = trim($_POST['nombre_pugil'] ?? '');
            $ape = trim($_POST['apellido_pugil'] ?? '');
            $dob = trim($_POST['fecha_nacimiento'] ?? '');
            $sex = trim($_POST['sexo'] ?? '');
            $id_c = filter_input(INPUT_POST, 'id_club_pertenencia', FILTER_VALIDATE_INT);
            $err = [];

             // === Procesar Apellido Compuesto y Convertir a MAYÚSCULAS ===
             $apellido_original = $ape;
             $ape = '';
             if (!empty($apellido_original)) {
                 $apellido_parts = explode(' ', $apellido_original);
                 $processed_parts = [];
                 if (!empty($apellido_parts[0])) {
                     $processed_parts[] = $apellido_parts[0];
                     $first_word_upper = strtoupper($apellido_parts[0]);
                     $compound_prefixes = ['DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS'];
                     if (in_array($first_word_upper, $compound_prefixes) && count($apellido_parts) > 1) {
                         $second_word_upper = strtoupper($apellido_parts[1]);
                         if ($first_word_upper === 'DE' && in_array($second_word_upper, ['LA', 'LOS', 'LAS']) && count($apellido_parts) > 2) {
                              $processed_parts[] = $apellido_parts[1];
                              $processed_parts[] = $apellido_parts[2];
                         } else {
                              $processed_parts[] = $apellido_parts[1];
                         }
                     }
                 }
                 $ape = implode(' ', $processed_parts);
             }
             $nom = strtoupper($nom);
             $ape = strtoupper($ape);
             // === FIN MODIFICACIÓN ===


            if (empty($nom)) $err[] = "Nombre";
            if (empty($ape)) $err[] = "Apellido";
            if (empty($dob)) $err[] = "Fecha";
            elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dob)) $err[] = "Formato Fecha";
            if (empty($sex) || !in_array($sex, ['Masculino','Femenino'])) $err[] = "Sexo";
            if (empty($id_c) || $id_c <= 0) $err[] = "Club Inválido";

            if (!empty($err)) {
                $response['message'] = implode(' ', $err);
            } else {
                $sCP = $pdo->prepare("SELECT id_pugil FROM pugiles
                                     WHERE nombre_pugil = ? AND apellido_pugil = ?
                                     AND fecha_nacimiento = ? AND id_club_pertenencia = ?
                                     AND es_profesional = 0");
                $sCP->execute([$nom, $ape, $dob, $id_c]);

                if ($sCP->fetch()) {
                    $response['message'] = "Púgil ya existe en este club.";
                } else {
                    $sIP = $pdo->prepare("INSERT INTO pugiles(nombre_pugil, apellido_pugil, fecha_nacimiento, sexo, es_profesional, id_club_pertenencia)
                                         VALUES(?, ?, ?, ?, 0, ?)");
                    if ($sIP->execute([$nom, $ape, $dob, $sex, $id_c])) {
                        $nid = $pdo->lastInsertId();
                        $cat = calcularCategoriaEdad($dob) ?: '?';
                        $response['success'] = true;
                        $response['data'] = [
                            'new_pugil_id' => $nid,
                            'new_pugil_name' => htmlspecialchars($nom.' '.$ape.' ('.$cat.')')
                        ];
                    }
                }
            }
            break;

        default:
            $response['message'] = 'Acción desconocida.';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error general: '.$e->getMessage();
    error_log("Error AJAX ($action): ".$e->getMessage());
}

if (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode($response);
exit;
?>