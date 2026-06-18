<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../LOGIN/login.php");
    exit();
}

include("../../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
$error = '';
$colmena = null;
$es_edicion = false;

// Obtener ID si viene como parámetro
$id_colmena = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si viene ID, es edición
if ($id_colmena > 0) {
    $es_edicion = true;
    
    // Cargar colmena de la BD
    $sql = "SELECT id_colmena, nombre, ubicacion, estado FROM colmenas 
            WHERE id_colmena = $id_colmena AND id_usuario = $id_usuario";
    $resultado = $conn->query($sql);
    
    if (!$resultado || $resultado->num_rows === 0) {
        header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?error=Colmena no encontrada o sin permiso");
        exit;
    }
    
    $colmena = $resultado->fetch_assoc();
}

// Procesar formulario (crear o editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'Estable';

    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre de la colmena es requerido";
    } elseif (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } elseif (strlen($nombre) > 100) {
        $error = "El nombre no puede exceder 100 caracteres";
    } elseif (empty($ubicacion)) {
        $error = "La ubicación es requerida";
    } elseif (strlen($ubicacion) > 150) {
        $error = "La ubicación no puede exceder 150 caracteres";
    } else {
        // Validar estado
        $estados_validos = ['Estable', 'Advertencia', 'Crítico'];
        if (!in_array($estado, $estados_validos)) {
            $estado = 'Estable';
        }

        // Escapar para evitar SQL injection
        $nombre = $conn->real_escape_string($nombre);
        $ubicacion = $conn->real_escape_string($ubicacion);
        
        if ($es_edicion) {
            // EDITAR COLMENA EXISTENTE
            $id_colmena = intval($_POST['id']);
            
            // Verificar que la colmena pertenece al usuario
            $check = $conn->query("SELECT id_colmena FROM colmenas WHERE id_colmena=$id_colmena AND id_usuario=$id_usuario");
            if (!$check || $check->num_rows === 0) {
                $error = "No tienes permiso para editar esta colmena";
            } else {
                $sql = "UPDATE colmenas SET nombre='$nombre', ubicacion='$ubicacion', estado='$estado' 
                        WHERE id_colmena=$id_colmena AND id_usuario=$id_usuario";

                if ($conn->query($sql)) {
                    // Registrar actividad
                    $tipo_actividad = 'editar_colmena';
                    $descripcion = "Editó la colmena: $nombre";
                    $sql_actividad = "INSERT INTO actividad (id_usuario, tipo, descripcion) 
                                     VALUES ($id_usuario, '$tipo_actividad', '$descripcion')";
                    $conn->query($sql_actividad);

                    header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?success=Colmena actualizada exitosamente");
                    exit;
                } else {
                    $error = "Error al actualizar la colmena: " . $conn->error;
                }
            }
        } else {
            // CREAR COLMENA NUEVA
            $sql = "INSERT INTO colmenas (id_usuario, nombre, ubicacion, estado) 
                    VALUES ($id_usuario, '$nombre', '$ubicacion', '$estado')";

            if ($conn->query($sql)) {
                $id_colmena_nuevo = $conn->insert_id;
                
                // Registrar actividad
                $tipo_actividad = 'crear_colmena';
                $descripcion = "Creó la colmena: $nombre";
                $sql_actividad = "INSERT INTO actividad (id_usuario, tipo, descripcion) 
                                 VALUES ($id_usuario, '$tipo_actividad', '$descripcion')";
                $conn->query($sql_actividad);
                
                header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?success=Colmena creada exitosamente");
                exit;
            } else {
                $error = "Error al crear la colmena: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $es_edicion ? 'Editar' : 'Nueva'; ?> Colmena - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../../CSS/dashboard.css">
<style>
body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.page-wrapper {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    position: relative;
}

.main-content {
    flex: 1;
    padding: 40px 20px;
    overflow-y: auto;
}

.form-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    padding: 40px;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    animation: slideUp 0.6s ease;
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

.form-header {
    text-align: center;
    margin-bottom: 32px;
}

.form-header h1 {
    margin: 0 0 12px 0;
    color: #1a1a1a;
    font-size: 28px;
    font-weight: 800;
}

.form-header p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group small {
    display: block;
    margin-top: 4px;
    color: #999;
    font-size: 12px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    box-sizing: border-box;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #FFC72C;
    box-shadow: 0 0 0 4px rgba(255, 199, 44, 0.1);
    background: #fffbf0;
}

.form-group input::placeholder {
    color: #bbb;
}

.form-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 32px;
}

.btn-submit {
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-volver {
    background: #e5e7eb;
    color: #374151;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.btn-volver:hover {
    background: #d1d5db;
}

.btn-volver:active {
    transform: translateY(0);
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease;
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

.alert-error {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    color: #7f1d1d;
    border: 1px solid #fca5a5;
}

.alert i {
    font-size: 18px;
}

.info-box {
    background: linear-gradient(135deg, #f0f4f8 0%, #e5ecf1 100%);
    border-left: 4px solid #3b82f6;
    padding: 12px 14px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #1e3a8a;
}

.info-box i {
    margin-right: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        padding: 20px 15px;
    }

    .form-container {
        padding: 30px 20px;
    }

    .form-actions {
        grid-template-columns: 1fr;
    }

    .form-header h1 {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
<div class="logo-container">
<img src="../../IMG/apitech_logo.png" class="logo-img">
<div class="logo-text">ApiTech</div>
</div>

<ul class="menu">
<li><a href="../../DASHBOARD/dashboard.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
<li>
<a href="../../CRUD/COLMENAS/listar_colmenas.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-box"></i> Colmenas
</a>
</li>
<li><a href="../../DASHBOARD/sensores.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-microchip"></i> Sensores</a></li>
<li><a href="../../DASHBOARD/produccion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-jar"></i> Producción</a></li>
<li><a href="../../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
<li><a href="../../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>
<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
<li><a href="../../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
</ul>
</div>

<!-- MAIN -->
<div class="main-content">

<div class="form-container">

<div class="form-header">
<h1>
<?php if ($es_edicion): ?>
    <i class="fa-solid fa-pencil" style="color: #FFC72C;"></i> Editar Colmena
<?php else: ?>
    <i class="fa-solid fa-plus" style="color: #FFC72C;"></i> Nueva Colmena
<?php endif; ?>
</h1>
<p>
<?php if ($es_edicion): ?>
    Actualiza los datos de tu colmena #<?php echo $colmena['id_colmena']; ?>
<?php else: ?>
    Registra una nueva colmena en tu apiario
<?php endif; ?>
</p>
</div>

<?php if (!empty($error)) { ?>
<div class="alert alert-error">
<i class="fa-solid fa-exclamation-circle"></i>
<span><?php echo htmlspecialchars($error); ?></span>
</div>
<?php } ?>

<div class="info-box">
<i class="fa-solid fa-info-circle"></i>
<?php if ($es_edicion): ?>
    Actualiza los campos que desees cambiar
<?php else: ?>
    Completa todos los campos para crear una nueva colmena
<?php endif; ?>
</div>

<form action="formulario_colmena.php<?php if ($es_edicion) echo '?id=' . $colmena['id_colmena']; ?>" method="POST">

<?php if ($es_edicion): ?>
    <input type="hidden" name="id" value="<?php echo $colmena['id_colmena']; ?>">
<?php endif; ?>

<div class="form-group">
<label for="nombre">
<i class="fa-solid fa-tag" style="color: #FFC72C; margin-right: 6px;"></i>Nombre de la colmena
</label>
<input 
    type="text" 
    id="nombre" 
    name="nombre" 
    placeholder="Ej: Colmena Principal"
    value="<?php echo $es_edicion ? htmlspecialchars($colmena['nombre']) : ''; ?>"
    minlength="3"
    maxlength="100"
    required
>
<small>Entre 3 y 100 caracteres</small>
</div>

<div class="form-group">
<label for="ubicacion">
<i class="fa-solid fa-map-pin" style="color: #FFC72C; margin-right: 6px;"></i>Ubicación
</label>
<input 
    type="text" 
    id="ubicacion" 
    name="ubicacion" 
    placeholder="Ej: Patio Sur, Granja Central"
    value="<?php echo $es_edicion ? htmlspecialchars($colmena['ubicacion']) : ''; ?>"
    maxlength="150"
    required
>
<small>Máximo 150 caracteres</small>
</div>

<div class="form-group">
<label for="estado">
<i class="fa-solid fa-traffic-light" style="color: #FFC72C; margin-right: 6px;"></i>Estado
</label>
<select id="estado" name="estado" required>
    <option value="Estable" <?php echo ($es_edicion && $colmena['estado'] === 'Estable') ? 'selected' : ''; ?>>
        🟢 Estable - Funcionando normalmente
    </option>
    <option value="Advertencia" <?php echo ($es_edicion && $colmena['estado'] === 'Advertencia') ? 'selected' : ''; ?>>
        🟡 Advertencia - Requiere atención
    </option>
    <option value="Crítico" <?php echo ($es_edicion && $colmena['estado'] === 'Crítico') ? 'selected' : ''; ?>>
        🔴 Crítico - Requiere acción inmediata
    </option>
</select>
<small><?php echo $es_edicion ? 'Actualiza el estado de la colmena' : 'Selecciona el estado inicial de la colmena'; ?></small>
</div>

<div class="form-actions">
<button type="submit" class="btn-submit">
<?php if ($es_edicion): ?>
    <i class="fa-solid fa-save"></i> Actualizar
<?php else: ?>
    <i class="fa-solid fa-plus"></i> Crear Colmena
<?php endif; ?>
</button>

<a href="../../CRUD/COLMENAS/listar_colmenas.php" class="btn-volver">
<i class="fa-solid fa-arrow-left"></i> Cancelar
</a>
</div>

</form>

</div>

</div>

</body>
</html>
