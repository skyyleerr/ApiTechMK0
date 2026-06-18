<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../LOGIN/login.php");
    exit();
}

include("../../CONEXION/conexion.php");

// Obtener ID de la colmena
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$usuario_id = $_SESSION['usuario_id'];

// Validar que la colmena pertenezca al usuario
$validar = $conn->query("SELECT id_colmena FROM colmenas WHERE id_colmena = $id AND id_usuario = $usuario_id");

if (!$validar || $validar->num_rows == 0) {
    die("❌ Acceso denegado. Esta colmena no existe o no te pertenece.");
}

// Obtener datos de la colmena
$sql = "SELECT * FROM colmenas WHERE id_colmena = $id AND id_usuario = $usuario_id";
$resultado = $conn->query($sql);
$colmena = $resultado->fetch_assoc();

if (!$colmena) {
    die("❌ Colmena no encontrada.");
}

$error = '';
$exito = '';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $estado = trim($_POST['estado'] ?? '');
    
    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre de la colmena es requerido.";
    } elseif (empty($ubicacion)) {
        $error = "La ubicación es requerida.";
    } elseif (empty($estado)) {
        $error = "El estado es requerido.";
    } else {
        // Usar prepared statements para seguridad
        $nombre_escaped = $conn->real_escape_string($nombre);
        $ubicacion_escaped = $conn->real_escape_string($ubicacion);
        $estado_escaped = $conn->real_escape_string($estado);
        
        $sql_update = "UPDATE colmenas SET 
                       nombre = '$nombre_escaped', 
                       ubicacion = '$ubicacion_escaped', 
                       estado = '$estado_escaped' 
                       WHERE id_colmena = $id AND id_usuario = $usuario_id";
        
        if ($conn->query($sql_update)) {
            $exito = "✓ Colmena actualizada correctamente.";
            // Actualizar datos locales
            $colmena['nombre'] = $nombre;
            $colmena['ubicacion'] = $ubicacion;
            $colmena['estado'] = $estado;
        } else {
            $error = "Error al actualizar: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualizar Colmena - ApiTech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../CSS/colmena.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 500px;
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

        .header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .header i {
            font-size: 32px;
            color: #FFC72C;
        }

        .header h2 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
            animation: slideUp 0.6s ease;
        }

        .form-group:nth-child(2) { animation-delay: 0.05s; }
        .form-group:nth-child(3) { animation-delay: 0.1s; }
        .form-group:nth-child(4) { animation-delay: 0.15s; }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #FFC72C;
            box-shadow: 0 0 0 3px rgba(255, 199, 44, 0.1);
            transform: translateY(-2px);
        }

        input::placeholder {
            color: #999;
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s ease;
            font-weight: 500;
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
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border-left: 4px solid #059669;
        }

        .alert i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            animation: slideUp 0.6s ease 0.2s both;
        }

        button, .btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            text-align: center;
        }

        button[type="submit"] {
            background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
            color: #1a1a1a;
            box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
        }

        button[type="submit"]:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(255, 199, 44, 0.4);
        }

        button[type="submit"]:active {
            transform: translateY(-1px);
        }

        .btn-volver {
            background: #f0f0f0;
            color: #666;
            text-decoration: none;
        }

        .btn-volver:hover {
            background: #e0e0e0;
            color: #333;
            transform: translateX(-4px);
        }

        .info-colmena {
            background: rgba(255, 199, 44, 0.05);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #FFC72C;
            animation: slideUp 0.6s ease;
        }

        .info-colmena p {
            margin: 8px 0;
            color: #666;
            font-size: 14px;
        }

        .info-colmena strong {
            color: #333;
            display: block;
            margin-bottom: 4px;
        }

        /* Estados */
        .estado-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .estado-activo {
            background: rgba(16, 185, 129, 0.2);
            color: #059669;
        }

        .estado-inactivo {
            background: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        .estado-alerta {
            background: rgba(245, 158, 11, 0.2);
            color: #d97706;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
                border-radius: 16px;
            }

            .header h2 {
                font-size: 20px;
            }

            button, .btn {
                padding: 12px 20px;
                font-size: 14px;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <i class="fa-solid fa-box"></i>
        <h2>Actualizar Colmena</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($exito): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i>
            <span><?php echo htmlspecialchars($exito); ?></span>
        </div>
    <?php endif; ?>

    <div class="info-colmena">
        <strong>📊 ID de la Colmena: #<?php echo htmlspecialchars($colmena['id_colmena']); ?></strong>
        <p>Creada: <?php echo date('d/m/Y H:i', strtotime($colmena['fecha_creacion'])); ?></p>
        <p>Estado: <span class="estado-badge estado-<?php echo strtolower($colmena['estado']); ?>"><?php echo htmlspecialchars($colmena['estado']); ?></span></p>
    </div>

    <form method="POST">

        <div class="form-group">
            <label for="nombre">
                <i class="fa-solid fa-tag"></i> Nombre de la Colmena
            </label>
            <input 
                type="text" 
                id="nombre"
                name="nombre" 
                value="<?php echo htmlspecialchars($colmena['nombre']); ?>" 
                placeholder="Ej: Colmena Principal"
                required
            >
        </div>

        <div class="form-group">
            <label for="ubicacion">
                <i class="fa-solid fa-location-dot"></i> Ubicación
            </label>
            <input 
                type="text" 
                id="ubicacion"
                name="ubicacion" 
                value="<?php echo htmlspecialchars($colmena['ubicacion']); ?>" 
                placeholder="Ej: Jardín trasero"
                required
            >
        </div>

        <div class="form-group">
            <label for="estado">
                <i class="fa-solid fa-heart-pulse"></i> Estado
            </label>
            <select id="estado" name="estado" required>
                <option value="">-- Selecciona un estado --</option>
                <option value="Activo" <?php echo $colmena['estado'] === 'Activo' ? 'selected' : ''; ?>>✓ Activo</option>
                <option value="Inactivo" <?php echo $colmena['estado'] === 'Inactivo' ? 'selected' : ''; ?>>✗ Inactivo</option>
                <option value="Alerta" <?php echo $colmena['estado'] === 'Alerta' ? 'selected' : ''; ?>>⚠ Alerta</option>
            </select>
        </div>

        <div class="btn-group">
            <button type="submit">
                <i class="fa-solid fa-floppy-disk"></i> Actualizar
            </button>
            <a href="listar_colmenas.php" class="btn btn-volver">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>

    </form>

</div>

<script>
    // Auto-validación del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        form.addEventListener('submit', function(e) {
            const nombre = document.getElementById('nombre').value.trim();
            const ubicacion = document.getElementById('ubicacion').value.trim();
            const estado = document.getElementById('estado').value;

            if (!nombre) {
                e.preventDefault();
                alert('⚠️ Por favor ingresa el nombre de la colmena.');
                document.getElementById('nombre').focus();
                return;
            }

            if (!ubicacion) {
                e.preventDefault();
                alert('⚠️ Por favor ingresa la ubicación.');
                document.getElementById('ubicacion').focus();
                return;
            }

            if (!estado) {
                e.preventDefault();
                alert('⚠️ Por favor selecciona un estado.');
                document.getElementById('estado').focus();
                return;
            }
        });

        // Agregar iconos dinámicos al estado
        const selectEstado = document.getElementById('estado');
        selectEstado.addEventListener('change', function() {
            console.log('Estado seleccionado:', this.value);
        });
    });
</script>

</body>
</html>
