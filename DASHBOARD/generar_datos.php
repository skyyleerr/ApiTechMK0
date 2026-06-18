<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Sesión no válida'], JSON_UNESCAPED_UNICODE);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$id_usuario = intval($_SESSION['usuario_id']);

function obtenerEstado($temperatura) {
    if ($temperatura < 36) {
        return 'Estable';
    } elseif ($temperatura < 38) {
        return 'Advertencia';
    } else {
        return 'Problema';
    }
}

function generarTemperaturaAleatoria() {
    // Genera temperatura entre 35.0 y 38.5
    return round(35 + (rand(0, 35) / 10), 1);
}

function generarProduccionAleatoria() {
    // Genera producción entre 35 y 65
    return rand(35, 65);
}

$colmenas_data = [];
$temp_total = 0;
$prod_total = 0;
$count = 0;
$alertas = 0;

// Intentar obtener datos de BD
$bd_colmenas = [];
$bd_sensores = [];
$bd_produccion = [];

try {
    if (file_exists("../CONEXION/conexion.php")) {
        include("../CONEXION/conexion.php");
        
        if (isset($conn) && $conn) {
            // Obtener colmenas del usuario
            $query = "SELECT id_colmena, nombre FROM colmenas WHERE id_usuario = $id_usuario ORDER BY id_colmena ASC";
            $resultado = $conn->query($query);
            
            if ($resultado && $resultado->num_rows > 0) {
                while($fila = $resultado->fetch_assoc()) {
                    $bd_colmenas[] = $fila;
                }
            }
            
            // Obtener últimos sensores registrados
            if (!empty($bd_colmenas)) {
                $ids_colmenas = array_map(function($c) { return $c['id_colmena']; }, $bd_colmenas);
                $ids_string = implode(',', $ids_colmenas);
                
                $query_sensores = "SELECT id_colmena, tipo, valor, fecha 
                                   FROM sensores 
                                   WHERE id_colmena IN ($ids_string) 
                                   ORDER BY id_colmena, fecha DESC 
                                   LIMIT " . count($ids_colmenas);
                $resultado_sensores = $conn->query($query_sensores);
                
                if ($resultado_sensores && $resultado_sensores->num_rows > 0) {
                    while($fila = $resultado_sensores->fetch_assoc()) {
                        $bd_sensores[$fila['id_colmena']] = $fila;
                    }
                }
                
                // Obtener última producción registrada
                $query_produccion = "SELECT id_colmena, cantidad_miel, fecha 
                                     FROM produccion 
                                     WHERE id_colmena IN ($ids_string) 
                                     ORDER BY id_colmena, fecha DESC 
                                     LIMIT " . count($ids_colmenas);
                $resultado_produccion = $conn->query($query_produccion);
                
                if ($resultado_produccion && $resultado_produccion->num_rows > 0) {
                    while($fila = $resultado_produccion->fetch_assoc()) {
                        $bd_produccion[$fila['id_colmena']] = $fila;
                    }
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error BD en generar_datos.php: " . $e->getMessage());
}

// Procesar colmenas
if (!empty($bd_colmenas)) {
    foreach ($bd_colmenas as $col) {
        $id = intval($col['id_colmena']);
        $nombre = htmlspecialchars($col['nombre']);
        
        // Obtener temperatura del sensor si existe, si no generar aleatoria
        if (isset($bd_sensores[$id]) && $bd_sensores[$id]['tipo'] === 'temperatura') {
            $temp = floatval($bd_sensores[$id]['valor']);
        } else {
            $temp = generarTemperaturaAleatoria();
        }
        
        // Obtener producción si existe, si no generar aleatoria
        if (isset($bd_produccion[$id])) {
            $prod = floatval($bd_produccion[$id]['cantidad_miel']);
        } else {
            $prod = generarProduccionAleatoria();
        }
        
        $estado = obtenerEstado($temp);
        
        $temp_total += $temp;
        $prod_total += $prod;
        $count++;
        
        if ($estado === 'Problema') {
            $alertas++;
        }
        
        $colmenas_data[] = [
            'id' => $id,
            'nombre' => $nombre,
            'temperatura' => $temp,
            'produccion' => round($prod, 1),
            'estado' => $estado,
            'fecha_sensor' => isset($bd_sensores[$id]) ? $bd_sensores[$id]['fecha'] : null,
            'fecha_produccion' => isset($bd_produccion[$id]) ? $bd_produccion[$id]['fecha'] : null
        ];
    }
} else {
    // DATOS DE EJEMPLO (si no hay BD, no hay usuario autenticado o no hay colmenas registradas)
    $colmenas_data = [
        [
            'id' => 1,
            'nombre' => 'Colmena Principal',
            'temperatura' => generarTemperaturaAleatoria(),
            'produccion' => generarProduccionAleatoria(),
            'estado' => 'Estable',
            'fecha_sensor' => date('Y-m-d H:i:s'),
            'fecha_produccion' => date('Y-m-d')
        ],
        [
            'id' => 2,
            'nombre' => 'Colmena Secundaria',
            'temperatura' => generarTemperaturaAleatoria(),
            'produccion' => generarProduccionAleatoria(),
            'estado' => 'Estable',
            'fecha_sensor' => date('Y-m-d H:i:s'),
            'fecha_produccion' => date('Y-m-d')
        ],
        [
            'id' => 3,
            'nombre' => 'Colmena de Expansión',
            'temperatura' => generarTemperaturaAleatoria(),
            'produccion' => generarProduccionAleatoria(),
            'estado' => 'Estable',
            'fecha_sensor' => date('Y-m-d H:i:s'),
            'fecha_produccion' => date('Y-m-d')
        ]
    ];
    
    // Recalcular totales para datos de ejemplo
    foreach ($colmenas_data as $col) {
        $temp_total += $col['temperatura'];
        $prod_total += $col['produccion'];
        $count++;
        $estado = obtenerEstado($col['temperatura']);
        if ($estado === 'Problema') {
            $alertas++;
        }
    }
}

// Calcular promedios
$temp_promedio = $count > 0 ? round($temp_total / $count, 1) : 0;
$prod_total_rounded = round($prod_total, 1);

// Obtener alertas activas de BD si es posible
$alertas_bd = 0;
try {
    if (isset($conn) && $conn && !empty($bd_colmenas)) {
        $ids_colmenas = array_map(function($c) { return $c['id_colmena']; }, $bd_colmenas);
        $ids_string = implode(',', $ids_colmenas);
        
        $query_alertas = "SELECT COUNT(*) as total FROM alertas 
                         WHERE id_colmena IN ($ids_string) 
                         AND estado = 'activa'";
        $resultado_alertas = $conn->query($query_alertas);
        
        if ($resultado_alertas) {
            $fila_alertas = $resultado_alertas->fetch_assoc();
            $alertas_bd = intval($fila_alertas['total']);
            $alertas = $alertas_bd > 0 ? $alertas_bd : $alertas;
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener alertas: " . $e->getMessage());
}

// Respuesta JSON
$response = [
    'success' => true,
    'temperatura_promedio' => $temp_promedio,
    'produccion_total' => $prod_total_rounded,
    'alertas_activas' => $alertas,
    'colmenas_count' => $count,
    'timestamp' => date('Y-m-d H:i:s'),
    'colmenas' => $colmenas_data
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit();
?>
