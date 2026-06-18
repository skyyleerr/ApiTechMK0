<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sesión no válida', 'alertas' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$id_usuario = intval($_SESSION['usuario_id']);

// Incluir conexión a BD
try {
    if (file_exists("../CONEXION/conexion.php")) {
        include("../CONEXION/conexion.php");
    } else {
        throw new Exception("Archivo de conexión no encontrado");
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => 'Error de conexión', 'alertas' => [], 'total' => 0], JSON_UNESCAPED_UNICODE);
    exit();
}

$alertas = [];
$alertas_bd = [];

try {
    // Obtener colmenas del usuario
    $query_colmenas = "SELECT id_colmena, nombre FROM colmenas WHERE id_usuario = $id_usuario";
    $resultado_colmenas = $conn->query($query_colmenas);
    
    if (!$resultado_colmenas) {
        throw new Exception("Error en consulta de colmenas: " . $conn->error);
    }
    
    // Obtener alertas activas de la BD
    if ($resultado_colmenas->num_rows > 0) {
        $ids_colmenas = [];
        $colmenas_map = [];
        
        while ($fila = $resultado_colmenas->fetch_assoc()) {
            $ids_colmenas[] = intval($fila['id_colmena']);
            $colmenas_map[intval($fila['id_colmena'])] = $fila['nombre'];
        }
        
        if (!empty($ids_colmenas)) {
            $ids_string = implode(',', $ids_colmenas);
            
            // Obtener alertas activas de BD
            $query_alertas = "SELECT id_alerta, id_colmena, tipo, descripcion, fecha 
                             FROM alertas 
                             WHERE id_colmena IN ($ids_string) 
                             AND estado = 'activa'
                             ORDER BY fecha DESC
                             LIMIT 20";
            
            $resultado_alertas = $conn->query($query_alertas);
            
            if ($resultado_alertas && $resultado_alertas->num_rows > 0) {
                while ($fila = $resultado_alertas->fetch_assoc()) {
                    $id_colmena = intval($fila['id_colmena']);
                    $nombre_colmena = $colmenas_map[$id_colmena] ?? 'Colmena desconocida';
                    $tipo = htmlspecialchars($fila['tipo']);
                    $descripcion = htmlspecialchars($fila['descripcion']);
                    
                    // Mapear tipo a icono y estilo
                    $tipo_icono = '⚠️';
                    $tipo_estilo = 'warning';
                    
                    if (stripos($tipo, 'crítica') !== false || stripos($tipo, 'critica') !== false || stripos($tipo, 'error') !== false) {
                        $tipo_icono = '🔴';
                        $tipo_estilo = 'error';
                    } elseif (stripos($tipo, 'temperatura') !== false) {
                        $tipo_icono = '🌡️';
                        $tipo_estilo = 'warning';
                    } elseif (stripos($tipo, 'producción') !== false || stripos($tipo, 'produccion') !== false) {
                        $tipo_icono = '📉';
                        $tipo_estilo = 'warning';
                    } elseif (stripos($tipo, 'mantenimiento') !== false) {
                        $tipo_icono = '🔧';
                        $tipo_estilo = 'info';
                    }
                    
                    $alertas_bd[] = [
                        'id' => 'bd_' . intval($fila['id_alerta']),
                        'tipo' => $tipo_estilo,
                        'titulo' => $tipo_icono . ' ' . ucfirst($tipo),
                        'mensaje' => $nombre_colmena . ': ' . $descripcion,
                        'colmena_id' => $id_colmena,
                        'timestamp' => date('H:i:s', strtotime($fila['fecha']))
                    ];
                }
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error en obtener_alertas.php: " . $e->getMessage());
}

// Si hay alertas en BD, usarlas. Si no, obtener datos en tiempo real
if (!empty($alertas_bd)) {
    $alertas = $alertas_bd;
} else {
    // Obtener datos en tiempo real de generar_datos.php
    $datos = null;
    
    try {
        // Llamar a generar_datos.php con curl
        $url = 'http://localhost/ApiTech/DASHBOARD/generar_datos.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $datos = json_decode($response, true);
        }
    } catch (Exception $e) {
        error_log("Error al llamar generar_datos.php: " . $e->getMessage());
    }
    
    // Generar alertas basadas en datos en tiempo real
    if ($datos && isset($datos['colmenas'])) {
        foreach ($datos['colmenas'] as $colmena) {
            
            // ALERTA: Temperatura crítica (> 38.5°C)
            if ($colmena['temperatura'] > 38.5) {
                $alertas[] = [
                    'id' => 'temp_crit_' . $colmena['id'],
                    'tipo' => 'error',
                    'titulo' => '🔴 Temperatura Crítica',
                    'mensaje' => $colmena['nombre'] . ': ' . $colmena['temperatura'] . '°C (¡CRÍTICA!)',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            // ADVERTENCIA: Temperatura elevada (37.5-38.5°C)
            elseif ($colmena['temperatura'] > 37.5) {
                $alertas[] = [
                    'id' => 'temp_adv_' . $colmena['id'],
                    'tipo' => 'warning',
                    'titulo' => '🟡 Temperatura Elevada',
                    'mensaje' => $colmena['nombre'] . ': ' . $colmena['temperatura'] . '°C',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            
            // ALERTA: Temperatura baja (< 35°C)
            if ($colmena['temperatura'] < 35) {
                $alertas[] = [
                    'id' => 'temp_baja_' . $colmena['id'],
                    'tipo' => 'warning',
                    'titulo' => '❄️ Temperatura Baja',
                    'mensaje' => $colmena['nombre'] . ': ' . $colmena['temperatura'] . '°C',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            
            // ALERTA: Baja producción (< 40 kg)
            if ($colmena['produccion'] < 40) {
                $alertas[] = [
                    'id' => 'prod_' . $colmena['id'],
                    'tipo' => 'warning',
                    'titulo' => '📉 Baja Producción',
                    'mensaje' => $colmena['nombre'] . ': Solo ' . $colmena['produccion'] . ' kg',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            
            // ALERTA: Producción muy baja (< 35 kg)
            if ($colmena['produccion'] < 35) {
                $alertas[] = [
                    'id' => 'prod_critica_' . $colmena['id'],
                    'tipo' => 'error',
                    'titulo' => '📉 Producción Crítica',
                    'mensaje' => $colmena['nombre'] . ': Muy baja producción (' . $colmena['produccion'] . ' kg)',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            
            // ALERTA: Colmena con problema
            if ($colmena['estado'] === 'Problema') {
                $alertas[] = [
                    'id' => 'estado_' . $colmena['id'],
                    'tipo' => 'error',
                    'titulo' => '⚠️ Problema Detectado',
                    'mensaje' => $colmena['nombre'] . ' presenta problemas en su estado',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
            
            // ADVERTENCIA: Colmena en advertencia
            if ($colmena['estado'] === 'Advertencia') {
                $alertas[] = [
                    'id' => 'estado_adv_' . $colmena['id'],
                    'tipo' => 'warning',
                    'titulo' => '⚠️ Advertencia de Estado',
                    'mensaje' => $colmena['nombre'] . ' requiere atención',
                    'colmena_id' => $colmena['id'],
                    'timestamp' => date('H:i:s')
                ];
            }
        }
    }
}

// Eliminar alertas duplicadas por ID
$alertasUnicas = [];
$idsVistas = [];

foreach ($alertas as $alerta) {
    if (!in_array($alerta['id'], $idsVistas)) {
        $alertasUnicas[] = $alerta;
        $idsVistas[] = $alerta['id'];
    }
}

// Ordenar por tipo (error primero)
usort($alertasUnicas, function($a, $b) {
    $orden = ['error' => 0, 'warning' => 1, 'info' => 2];
    $tipo_a = $orden[$a['tipo']] ?? 3;
    $tipo_b = $orden[$b['tipo']] ?? 3;
    return $tipo_a <=> $tipo_b;
});

// Limitar a las últimas 20 alertas
$alertasUnicas = array_slice($alertasUnicas, 0, 20);

echo json_encode([
    'success' => true,
    'alertas' => $alertasUnicas,
    'total' => count($alertasUnicas),
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE);
exit();
?>
