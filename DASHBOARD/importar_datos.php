<?php
/**
 * Script para importar datos desde archivos CSV
 * Soporta importación de producción y colmenas
 * Guarda auditoría de importaciones
 */

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
$usuario_correo = isset($_SESSION['usuario_correo']) ? htmlspecialchars($_SESSION['usuario_correo']) : 'usuario@apitech.com';
$error = "";
$mensaje = "";
$resultados = [];

// Crear carpeta de uploads si no existe
$upload_dir = "../UPLOADS/imports/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Crear tabla de auditoría si no existe
$conn->query("
    CREATE TABLE IF NOT EXISTS importaciones_log (
        id_importacion INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        tipo_importacion VARCHAR(50),
        nombre_archivo VARCHAR(255),
        registros_exitosos INT DEFAULT 0,
        registros_errores INT DEFAULT 0,
        contenido_archivo LONGTEXT,
        fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    )
");

/**
 * Limpia datos del CSV de caracteres especiales
 */
function limpiarDatos($dato) {
    $dato = trim($dato);
    $dato = str_replace(["\r", "\n", "\t", ";;;", chr(0), chr(239), chr(187), chr(191)], "", $dato);
    return $dato;
}

/**
 * Importa datos de producción desde CSV
 * Formato esperado: id_colmena,cantidad_miel,fecha
 */
function importarProduccion($archivo, $id_usuario, $conn) {
    $resultado = [
        'exito' => 0,
        'errores' => 0,
        'detalles' => []
    ];
    
    $handle = fopen($archivo, 'r');
    if (!$handle) {
        return $resultado;
    }
    
    // Saltar encabezado
    $header = fgetcsv($handle, 1000, ',');
    
    $linea = 2;
    while (($datos = fgetcsv($handle, 1000, ',')) !== false) {
        
        // Ignorar líneas vacías
        if (count($datos) < 3 || empty($datos[0])) {
            $linea++;
            continue;
        }
        
        // Limpiar datos
        $id_colmena = intval(limpiarDatos($datos[0]));
        $cantidad_miel = floatval(limpiarDatos($datos[1]));
        $fecha = limpiarDatos($datos[2]);
        
        // Validar que tenga valores
        if (empty($id_colmena) || empty($cantidad_miel) || empty($fecha)) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Faltan datos requeridos";
            $linea++;
            continue;
        }
        
        // Validar que la colmena pertenezca al usuario
        $check = $conn->query("SELECT id_colmena FROM colmenas WHERE id_colmena = $id_colmena AND id_usuario = $id_usuario");
        
        if (!$check || $check->num_rows === 0) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Colmena #$id_colmena no encontrada o no te pertenece";
            $linea++;
            continue;
        }
        
        // Validar fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Fecha inválida '$fecha' (usar YYYY-MM-DD)";
            $linea++;
            continue;
        }
        
        // Validar cantidad
        if ($cantidad_miel < 0 || $cantidad_miel > 1000) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Cantidad debe estar entre 0 y 1000 kg";
            $linea++;
            continue;
        }
        
        // Insertar producción
        $stmt = $conn->prepare("INSERT INTO produccion (id_colmena, cantidad_miel, fecha) VALUES (?, ?, ?)");
        
        if (!$stmt) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Error de preparación";
            $linea++;
            continue;
        }
        
        $stmt->bind_param("ids", $id_colmena, $cantidad_miel, $fecha);
        
        if ($stmt->execute()) {
            $resultado['exito']++;
        } else {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: " . $stmt->error;
        }
        
        $stmt->close();
        $linea++;
    }
    
    fclose($handle);
    return $resultado;
}

/**
 * Importa colmenas desde CSV
 * Formato esperado: nombre,ubicacion,estado
 */
function importarColmenas($archivo, $id_usuario, $conn) {
    $resultado = [
        'exito' => 0,
        'errores' => 0,
        'detalles' => []
    ];
    
    $handle = fopen($archivo, 'r');
    if (!$handle) {
        return $resultado;
    }
    
    // Saltar encabezado
    $header = fgetcsv($handle, 1000, ',');
    
    $linea = 2;
    while (($datos = fgetcsv($handle, 1000, ',')) !== false) {
        
        // Ignorar líneas vacías
        if (empty($datos[0])) {
            $linea++;
            continue;
        }
        
        // Limpiar datos
        $nombre = limpiarDatos($datos[0]);
        $ubicacion = isset($datos[1]) ? limpiarDatos($datos[1]) : '';
        $estado = isset($datos[2]) ? limpiarDatos($datos[2]) : 'Estable';
        
        // Validar nombre
        if (strlen($nombre) < 3 || strlen($nombre) > 100) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Nombre debe tener entre 3 y 100 caracteres";
            $linea++;
            continue;
        }
        
        // Validar estado
        if (!in_array($estado, ['Estable', 'Advertencia', 'Problema'])) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Estado inválido (usa Estable, Advertencia o Problema)";
            $linea++;
            continue;
        }
        
        // Verificar nombre único
        $checkNombre = $conn->query("SELECT id_colmena FROM colmenas WHERE nombre = '" . $conn->real_escape_string($nombre) . "' AND id_usuario = $id_usuario");
        if ($checkNombre && $checkNombre->num_rows > 0) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Ya existe una colmena con nombre '$nombre'";
            $linea++;
            continue;
        }
        
        // Insertar colmena
        $stmt = $conn->prepare("INSERT INTO colmenas (id_usuario, nombre, ubicacion, estado, fecha_creacion) VALUES (?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: Error de preparación";
            $linea++;
            continue;
        }
        
        $stmt->bind_param("isss", $id_usuario, $nombre, $ubicacion, $estado);
        
        if ($stmt->execute()) {
            $resultado['exito']++;
        } else {
            $resultado['errores']++;
            $resultado['detalles'][] = "Línea $linea: " . $stmt->error;
        }
        
        $stmt->close();
        $linea++;
    }
    
    fclose($handle);
    return $resultado;
}

// PROCESAR IMPORTACIÓN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    
    $tipo_importacion = isset($_POST['tipo_importacion']) ? $_POST['tipo_importacion'] : 'produccion';
    $archivo = $_FILES['archivo'];
    
    // Leer contenido del archivo ANTES de procesarlo
    $contenido_archivo = '';
    if ($archivo['error'] === UPLOAD_ERR_OK) {
        $contenido_archivo = file_get_contents($archivo['tmp_name']);
    }
    
    // Validaciones
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo (Código: " . $archivo['error'] . ")";
    } elseif ($archivo['size'] == 0) {
        $error = "El archivo está vacío";
    } elseif ($archivo['size'] > 5 * 1024 * 1024) {
        $error = "El archivo es demasiado grande (máximo 5MB)";
    } else {
        // Procesar archivo
        $nombre_archivo = "import_" . time() . "_" . md5(uniqid()) . ".csv";
        $ruta_archivo = $upload_dir . $nombre_archivo;
        
        if (move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
            
            // Procesar según tipo de importación
            if ($tipo_importacion === 'produccion') {
                $resultados = importarProduccion($ruta_archivo, $id_usuario, $conn);
            } elseif ($tipo_importacion === 'colmenas') {
                $resultados = importarColmenas($ruta_archivo, $id_usuario, $conn);
            }
            
            // GUARDAR EN TABLA DE AUDITORÍA
            $nombre_archivo_original = htmlspecialchars($archivo['name']);
            $exitosos = $resultados['exito'];
            $errores = $resultados['errores'];
            $contenido_escapado = $conn->real_escape_string($contenido_archivo);
            
            $insert_audit = "INSERT INTO importaciones_log 
                (id_usuario, tipo_importacion, nombre_archivo, registros_exitosos, registros_errores, contenido_archivo) 
                VALUES 
                ($id_usuario, '$tipo_importacion', '$nombre_archivo_original', $exitosos, $errores, '$contenido_escapado')";
            
            $conn->query($insert_audit);
            
            // Eliminar archivo después de procesar
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
            
            if ($resultados['exito'] > 0) {
                $mensaje = "✓ Importación completada: " . $resultados['exito'] . " registros insertados";
                if ($resultados['errores'] > 0) {
                    $mensaje .= ", " . $resultados['errores'] . " errores";
                }
            } else {
                $error = "No se pudieron importar los registros. Verifica el formato del archivo.";
            }
        } else {
            $error = "Error al procesar el archivo. Verifica los permisos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Importar Datos - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">

<style>
/* Modal usuario */
.modal-usuario {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 280px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    z-index: 1000;
    display: none;
    flex-direction: column;
    overflow: hidden;
    animation: slideInRight 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.modal-usuario.active {
    display: flex;
}

.modal-usuario-header {
    padding: 20px;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    text-align: center;
}

.modal-usuario-header .avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 24px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.modal-usuario-header h3 {
    margin: 8px 0 4px;
    font-size: 16px;
    font-weight: 700;
}

.modal-usuario-header p {
    margin: 0;
    font-size: 13px;
    opacity: 0.85;
}

.modal-usuario-body {
    padding: 12px 0;
}

.modal-usuario-item {
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #333;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-size: 14px;
}

.modal-usuario-item:hover {
    background: rgba(255, 199, 44, 0.1);
    color: #FFC72C;
    padding-left: 20px;
}

.modal-usuario-divider {
    height: 1px;
    background: #eee;
    margin: 8px 0;
}

.modal-usuario-footer {
    padding: 12px;
    border-top: 1px solid #eee;
}

.btn-logout {
    width: 100%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
}

.user-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFC72C, #ffb700);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 700;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
}

.user-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 18px rgba(255, 199, 44, 0.4);
}

#btn-alertas {
    transition: all 0.3s ease;
    color: #666;
    font-size: 20px;
    cursor: pointer;
    position: relative;
}

#btn-alertas:hover {
    color: #FFC72C;
    transform: scale(1.15);
}

#badge-alertas {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 800;
}

.import-content {
    animation: slideUp 0.6s ease both;
    max-width: 800px;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.import-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.import-card h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.import-card p {
    color: #666;
    margin-bottom: 30px;
    font-size: 14px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group select,
.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.form-group select:focus,
.form-group input:focus {
    outline: none;
    border-color: #FFC72C;
    box-shadow: 0 0 0 3px rgba(255, 199, 44, 0.1);
}

.file-input-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.file-input-wrapper input[type="file"] {
    display: none;
}

.file-input-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 30px 20px;
    border: 2px dashed #FFC72C;
    border-radius: 8px;
    background: rgba(255, 199, 44, 0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    color: #333;
    flex-direction: column;
}

.file-input-label:hover {
    background: rgba(255, 199, 44, 0.1);
    border-color: #ffb700;
    transform: translateY(-2px);
}

.file-input-label i {
    font-size: 32px;
    color: #FFC72C;
}

.file-name {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease;
    border-left: 4px solid transparent;
}

.alert i {
    font-size: 18px;
    flex-shrink: 0;
}

.alert-danger {
    background: rgba(239, 68, 68, 0.12);
    border-left-color: #ef4444;
    color: #dc2626;
}

.alert-success {
    background: rgba(16, 185, 129, 0.12);
    border-left-color: #10b981;
    color: #059669;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.btn-submit {
    width: 100%;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.help-text {
    background: #f9f9f9;
    padding: 16px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 4px solid #3b82f6;
}

.help-text h4 {
    margin: 0 0 12px 0;
    color: #333;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.help-text ul {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: #666;
    line-height: 1.8;
}

.help-text li {
    margin-bottom: 8px;
}

.resultados {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
    border-left: 4px solid #10b981;
}

.resultados h3 {
    margin: 0 0 15px 0;
    color: #1a1a1a;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.resultados-item {
    padding: 8px 0;
    font-size: 13px;
    color: #666;
    border-bottom: 1px solid #f0f0f0;
}

.resultados-item:last-child {
    border-bottom: none;
}

.resultados-success {
    color: #059669;
    font-weight: 600;
}

.resultados-error {
    color: #ef4444;
}

.resultados-detalles {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.plantilla-descarga {
    text-align: center;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid #f0f0f0;
}

.plantilla-descarga p {
    color: #666;
    font-size: 13px;
    margin-bottom: 15px;
    font-weight: 600;
}

.btn-descarga {
    background: #f3f4f6;
    color: #333;
    border: 2px solid #ddd;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
    margin: 0 8px 8px 0;
    cursor: pointer;
}

.btn-descarga:hover {
    background: #e5e7eb;
    border-color: #999;
    transform: translateY(-2px);
}

.info-box {
    background: rgba(59, 130, 246, 0.1);
    border-left: 4px solid #3b82f6;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #1d4ed8;
}

.info-box strong {
    display: block;
    margin-bottom: 4px;
}

.btn-historial {
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-historial:hover {
    background: #d1d5db;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .import-card {
        padding: 20px;
    }

    .import-content {
        padding: 0 15px;
    }

    .file-input-label {
        padding: 20px 15px;
    }

    .btn-descarga {
        display: block;
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
}
</style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo-container">
        <img src="../IMG/apitech_logo.png" class="logo-img">
        <div class="logo-text">ApiTech</div>
    </div>

    <ul class="menu">
        <li><a href="../DASHBOARD/dashboard.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
        <li><a href="../CRUD/COLMENAS/listar_colmenas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-box"></i> Colmenas</a></li>
        <li><a href="../DASHBOARD/sensores.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-microchip"></i> Sensores</a></li>
        <li><a href="../DASHBOARD/produccion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-jar"></i> Producción</a></li>
        <li><a href="../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
        <li><a href="../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>
        <li>
      
            <a href="../DASHBOARD/importar_datos.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
                <i class="fa-solid fa-upload"></i> Importar Datos
            </a>
        </li>
        <li><a href="../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
    </ul>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <input class="search" placeholder="Buscar..." id="search">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div style="position: relative;">
                <i class="fa-solid fa-bell" id="btn-alertas"></i>
                <span id="badge-alertas" style="display: none;">0</span>
            </div>
            <div class="user-icon" id="btn-usuario">
                <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="header">
        <h1>📤 Importar Datos</h1>
        <a href="historial_importaciones.php" class="btn-historial">
            <i class="fa-solid fa-history"></i> Ver Historial
        </a>
    </div>

    <div class="dashboard">
        <div class="import-content">
            <div class="import-card">
                <h2>
                    <i class="fa-solid fa-file-import" style="color: #FFC72C;"></i> 
                    Importar Datos
                </h2>
                <p>Carga datos de producción o colmenas desde un archivo CSV</p>

                <!-- INFO BOX -->
                <div class="info-box">
                    <strong>💡 Consejo:</strong>
                    Descarga una plantilla para entender el formato exacto. Máximo 5MB por archivo.
                </div>

                <!-- ALERTAS -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span><?php echo htmlspecialchars($mensaje); ?></span>
                    </div>
                <?php endif; ?>

                <!-- FORMULARIO -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="tipo_importacion">
                            <i class="fa-solid fa-list" style="margin-right: 6px; color: #FFC72C;"></i>
                            ¿Qué deseas importar?
                        </label>
                        <select name="tipo_importacion" id="tipo_importacion" required>
                            <option value="produccion">📊 Datos de Producción</option>
                            <option value="colmenas">🐝 Nuevas Colmenas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="archivo">
                            <i class="fa-solid fa-file" style="margin-right: 6px; color: #FFC72C;"></i>
                            Selecciona archivo CSV
                        </label>
                        <div class="file-input-wrapper">
                            <input type="file" id="archivo" name="archivo" accept=".csv" required>
                            <label for="archivo" class="file-input-label">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Arrastra tu archivo aquí o haz clic para seleccionar</span>
                            </label>
                            <div class="file-name" id="file-name"></div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-check"></i> Importar Datos
                    </button>
                </form>

                <!-- AYUDA -->
                <div class="help-text">
                    <h4>
                        <i class="fa-solid fa-circle-info"></i>
                        Formato esperado
                    </h4>
                    <ul id="help-text-list">
                        <li><strong>Producción:</strong> id_colmena,cantidad_miel,fecha</li>
                        <li><strong>Ejemplo:</strong> 1,25.5,2024-06-07</li>
                    </ul>
                </div>

                <!-- RESULTADOS DETALLADOS -->
                <?php if (!empty($resultados) && ($resultados['exito'] > 0 || $resultados['errores'] > 0)): ?>
                    <div class="resultados">
                        <h3>
                            <i class="fa-solid fa-clipboard-list"></i>
                            Detalles de la Importación
                        </h3>
                        <div class="resultados-item resultados-success">
                            <i class="fa-solid fa-check-circle" style="margin-right: 6px;"></i>
                            <strong>Registros exitosos:</strong> <?php echo $resultados['exito']; ?>
                        </div>
                        <div class="resultados-item resultados-error">
                            <i class="fa-solid fa-times-circle" style="margin-right: 6px;"></i>
                            <strong>Errores:</strong> <?php echo $resultados['errores']; ?>
                        </div>
                        
                        <?php if (!empty($resultados['detalles']) && count($resultados['detalles']) <= 10): ?>
                            <div class="resultados-detalles">
                                <strong style="color: #1a1a1a;">Detalles de errores:</strong>
                                <?php foreach ($resultados['detalles'] as $detalle): ?>
                                    <div class="resultados-item resultados-error" style="margin-top: 8px;">
                                        <i class="fa-solid fa-warning" style="margin-right: 6px;"></i>
                                        <?php echo htmlspecialchars($detalle); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($resultados['detalles'])): ?>
                            <div class="resultados-item resultados-error" style="margin-top: 8px;">
                                <i class="fa-solid fa-warning" style="margin-right: 6px;"></i>
                                Se encontraron <?php echo count($resultados['detalles']); ?> errores durante la importación
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

<!-- PLANTILLAS DE DESCARGA -->
<div class="plantilla-descarga" style="text-align: center;">
    <p><i class="fa-solid fa-download" style="margin-right: 8px;"></i><strong>¿Necesitas una plantilla?</strong></p>
    <p style="font-size: 12px; color: #999; margin-top: 8px; margin-bottom: 20px;">Descarga plantillas Excel profesionales y listas para usar:</p>
    <a href="generar_plantilla_excel.php?tipo=produccion" class="btn-descarga" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; display: inline-flex;">
        <i class="fa-solid fa-file-excel"></i> Excel Producción
    </a>
    <a href="generar_plantilla_excel.php?tipo=colmenas" class="btn-descarga" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; display: inline-flex;">
        <i class="fa-solid fa-file-excel"></i> Excel Colmenas
    </a>
    <br><br>
    <a href="descargar_plantillas.php" class="btn-descarga" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border: none; display: inline-flex;">
        <i class="fa-solid fa-palette"></i> Centro de Plantillas
    </a>
</div>
            </div>
        </div>
    </div>

</div>

<!-- MODAL DE ALERTAS -->
<div id="modal-alertas" style="position: fixed; top: 60px; right: 20px; width: 350px; max-height: 500px; background: white; border-radius: 14px; box-shadow: 0 12px 40px rgba(0,0,0,0.15); z-index: 1000; display: none; flex-direction: column; overflow: hidden;">
  <div style="padding: 16px; border-bottom: 1px solid #f0f0f0; background: linear-gradient(135deg, #f9f9f9 0%, #f5f5f5 100%);">
    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #2c3e50;"><i class="fa-solid fa-exclamation-triangle" style="color: #FFC72C; margin-right: 8px;"></i>Alertas del Sistema</h3>
  </div>
  <div id="alertas-lista" style="flex: 1; overflow-y: auto; padding: 12px;">
    <div style="text-align: center; color: #999; padding: 30px 20px;">
      <i class="fa-solid fa-check-circle" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5; display: block;"></i>
      <p style="margin-top: 10px;">No hay alertas activas</p>
    </div>
  </div>
</div>

<!-- MODAL DE USUARIO -->
<div id="modal-usuario" class="modal-usuario">
  <div class="modal-usuario-header">
    <div class="avatar">
      <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
    </div>
    <h3><?php echo $usuario_nombre; ?></h3>
    <p><?php echo $usuario_correo; ?></p>
  </div>
  
  <div class="modal-usuario-body">
    <a href="../DASHBOARD/configuracion.php" class="modal-usuario-item">
      <i class="fa-solid fa-user-circle"></i>
      <span>Mi Perfil</span>
    </a>
    
    <a href="../DASHBOARD/configuracion.php" class="modal-usuario-item">
      <i class="fa-solid fa-sliders"></i>
      <span>Configuración</span>
    </a>
    
    <a href="#" class="modal-usuario-item" onclick="mostrarAyuda(event)">
      <i class="fa-solid fa-circle-question"></i>
      <span>Ayuda</span>
    </a>

    <div class="modal-usuario-divider"></div>
    
    <div style="padding: 0 16px;">
      <p style="margin: 0 0 8px 0; font-size: 11px; color: #999; font-weight: 700; text-transform: uppercase;">Versión</p>
      <p style="margin: 0; font-size: 12px; color: #666;">ApiTech v1.0.0</p>
    </div>
  </div>
  
  <div class="modal-usuario-footer">
    <button class="btn-logout" onclick="cerrarSesion()">
      <i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
    </button>
  </div>
</div>

<script>
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
}

function mostrarAyuda(e) {
    e.preventDefault();
    alert('📚 Ayuda - Importar Datos\n\n✓ Producción: Carga datos de miel producida por colmena\n✓ Colmenas: Importa nuevas colmenas a tu apiario\n✓ Formato CSV: Asegúrate de usar el formato correcto\n✓ Plantillas: Descarga las plantillas para el formato exacto\n\n¿Necesitas más ayuda? Contacta al soporte.');
}

// Modal de usuario
const btnUsuario = document.getElementById('btn-usuario');
const modalUsuario = document.getElementById('modal-usuario');

if (btnUsuario) {
    btnUsuario.addEventListener('click', function(e) {
        e.stopPropagation();
        modalUsuario.classList.toggle('active');
        document.getElementById('modal-alertas').style.display = 'none';
    });
}

document.addEventListener('click', function(e) {
    if (btnUsuario && modalUsuario) {
        if (!btnUsuario.contains(e.target) && !modalUsuario.contains(e.target)) {
            modalUsuario.classList.remove('active');
        }
    }
});

// Modal de alertas
const btnAlertas = document.getElementById('btn-alertas');
const modalAlertas = document.getElementById('modal-alertas');

if (btnAlertas) {
    btnAlertas.addEventListener('click', function(e) {
        e.stopPropagation();
        modalAlertas.style.display = modalAlertas.style.display === 'flex' ? 'none' : 'flex';
        modalUsuario.classList.remove('active');
    });
}

// Actualizar nombre de archivo
document.getElementById('archivo').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    const fileNameDisplay = document.getElementById('file-name');
    if (fileName) {
        const file = e.target.files[0];
        const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
        fileNameDisplay.textContent = '📄 ' + fileName + ' (' + sizeMB + ' MB)';
    } else {
        fileNameDisplay.textContent = '';
    }
});

// Actualizar texto de ayuda según el tipo de importación
document.getElementById('tipo_importacion').addEventListener('change', function(e) {
    const helpList = document.getElementById('help-text-list');
    if (e.target.value === 'produccion') {
        helpList.innerHTML = `
            <li><strong>Producción:</strong> id_colmena,cantidad_miel,fecha</li>
            <li><strong>Ejemplo:</strong> 1,25.5,2024-06-07</li>
            <li><strong>Rango:</strong> cantidad 0-1000 kg</li>
        `;
    } else {
        helpList.innerHTML = `
            <li><strong>Colmenas:</strong> nombre,ubicacion,estado</li>
            <li><strong>Ejemplo:</strong> Colmena Principal,Jardín,Estable</li>
            <li><strong>Estados:</strong> Estable, Advertencia, Problema</li>
        `;
    }
});

console.log('✓ Página de importación cargada');
</script>

</body>
</html>