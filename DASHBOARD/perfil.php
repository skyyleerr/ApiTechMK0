<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
$usuario_correo = isset($_SESSION['usuario_correo']) ? htmlspecialchars($_SESSION['usuario_correo']) : 'usuario@apitech.com';
$success = '';
$error = '';

// Obtener información del usuario de la BD
$user_query = $conn->query("SELECT correo, fecha_registro FROM usuarios WHERE id_usuario = $id_usuario");
$user_data = $user_query ? $user_data = $user_query->fetch_assoc() : null;

if (!$user_data) {
    $error = "No se pudo cargar la información del usuario";
}

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'actualizar_perfil') {
    
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $empresa = isset($_POST['empresa']) ? trim($_POST['empresa']) : '';
    
    // Validaciones
    if (empty($nombre)) {
        $error = "El nombre no puede estar vacío";
    } elseif (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } elseif (strlen($nombre) > 100) {
        $error = "El nombre no puede exceder 100 caracteres";
    } else {
        // Actualizar nombre en la sesión y BD
        $nombre_esc = $conn->real_escape_string($nombre);
        $telefono_esc = $conn->real_escape_string($telefono);
        $empresa_esc = $conn->real_escape_string($empresa);
        
        $sql = "UPDATE usuarios SET nombre = '$nombre_esc', telefono = '$telefono_esc', empresa = '$empresa_esc' 
                WHERE id_usuario = $id_usuario";
        
        if ($conn->query($sql)) {
            // Actualizar sesión
            $_SESSION['usuario_nombre'] = $nombre;
            $usuario_nombre = htmlspecialchars($nombre);
            $success = "✓ Perfil actualizado correctamente";
        } else {
            $error = "Error al actualizar el perfil: " . $conn->error;
        }
    }
}

// Obtener datos del perfil
$profile_query = $conn->query("SELECT nombre, correo, telefono, empresa, fecha_registro FROM usuarios WHERE id_usuario = $id_usuario");
$profile_data = $profile_query ? $profile_query->fetch_assoc() : [
    'nombre' => $usuario_nombre,
    'correo' => $usuario_correo,
    'telefono' => '',
    'empresa' => '',
    'fecha_registro' => date('Y-m-d H:i:s')
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Perfil - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
body {
    background: #f5f5f5;
}

.perfil-container {
    max-width: 800px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

.perfil-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.perfil-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #1a1a1a;
    font-weight: bold;
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
}

.perfil-info h2 {
    margin: 0 0 5px 0;
    color: #1a1a1a;
    font-size: 20px;
    font-weight: 700;
}

.perfil-info p {
    margin: 0;
    color: #999;
    font-size: 14px;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
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

.alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    color: #7f1d1d;
    border: 1px solid #fca5a5;
}

.alert i {
    font-size: 18px;
    flex-shrink: 0;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h3 {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 15px;
}

.form-row.full {
    grid-template-columns: 1fr;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #FFC72C;
    box-shadow: 0 0 0 3px rgba(255, 199, 44, 0.1);
}

.form-group input:disabled,
.form-group textarea:disabled {
    background: #f9f9f9;
    color: #666;
    cursor: not-allowed;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
}

.info-value {
    color: #1a1a1a;
    font-weight: 500;
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 30px;
}

.btn-save {
    flex: 1;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    padding: 12px 24px;
    border: none;
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

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
}

.btn-volver {
    flex: 1;
    background: #f3f4f6;
    color: #333;
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-weight: 700;
    font-size: 14px;
}

.btn-volver:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

.btn-cambiar-contrasena {
    background: #3b82f6;
    color: white;
    padding: 12px 24px;
    border: none;
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

.btn-cambiar-contrasena:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
    background: #2563eb;
}

@media (max-width: 768px) {
    .perfil-container {
        margin: 20px;
        padding: 20px;
    }

    .perfil-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .button-group {
        flex-direction: column;
    }

    .info-item {
        flex-direction: column;
        gap: 5px;
    }
}
</style>
</head>

<body>

<div class="perfil-container">

<!-- ALERTAS -->
<?php if (!empty($success)) { ?>
<div class="alert alert-success">
    <i class="fa-solid fa-check-circle"></i>
    <span><?php echo htmlspecialchars($success); ?></span>
</div>
<?php } ?>

<?php if (!empty($error)) { ?>
<div class="alert alert-error">
    <i class="fa-solid fa-exclamation-circle"></i>
    <span><?php echo htmlspecialchars($error); ?></span>
</div>
<?php } ?>

<!-- HEADER PERFIL -->
<div class="perfil-header">
    <div class="perfil-avatar">
        <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
    </div>
    <div class="perfil-info">
        <h2>Mi Perfil</h2>
        <p>Gestiona tu información personal</p>
    </div>
</div>

<!-- FORMULARIO -->
<form method="POST" action="perfil.php">
    <input type="hidden" name="action" value="actualizar_perfil">

    <!-- INFORMACIÓN PERSONAL -->
    <div class="form-section">
        <h3><i class="fa-solid fa-user" style="margin-right: 8px; color: #FFC72C;"></i>Información Personal</h3>
        
        <div class="form-row full">
            <div class="form-group">
                <label for="nombre">Nombre completo *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($profile_data['nombre'] ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-row full">
            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($profile_data['correo'] ?? ''); ?>" disabled>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="empresa">Empresa</label>
                <input type="text" id="empresa" name="empresa" placeholder="Nombre de tu empresa" value="<?php echo htmlspecialchars($profile_data['empresa'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" placeholder="+1 234 567 8900" value="<?php echo htmlspecialchars($profile_data['telefono'] ?? ''); ?>">
            </div>
        </div>
    </div>

    <!-- INFORMACIÓN DE CUENTA -->
    <div class="form-section">
        <h3><i class="fa-solid fa-shield" style="margin-right: 8px; color: #FFC72C;"></i>Información de Cuenta</h3>
        
        <div class="info-item">
            <span class="info-label">ID de Usuario:</span>
            <span class="info-value">#<?php echo $id_usuario; ?></span>
        </div>
        
        <div class="info-item">
            <span class="info-label">Registrado:</span>
            <span class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($profile_data['fecha_registro'] ?? 'now')); ?></span>
        </div>
        
        <div class="info-item">
            <span class="info-label">Estado:</span>
            <span class="info-value"><span style="background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Activo</span></span>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div class="button-group">
        <button type="submit" class="btn-save">
            <i class="fa-solid fa-save"></i> Guardar cambios
        </button>
        <a href="../DASHBOARD/dashboard.php" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</form>

<!-- SECCIÓN DE CONTRASEÑA -->
<div class="form-section" style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #f0f0f0;">
    <h3><i class="fa-solid fa-lock" style="margin-right: 8px; color: #FFC72C;"></i>Seguridad</h3>
    
    <p style="color: #666; font-size: 14px; margin-bottom: 15px;">
        Cambia tu contraseña regularmente para mantener tu cuenta segura.
    </p>
    
    <button type="button" class="btn-cambiar-contrasena" onclick="cambiarContrasena()">
        <i class="fa-solid fa-key"></i> Cambiar Contraseña
    </button>
</div>

</div>

<script>
function cambiarContrasena() {
    const nuevaContrasena = prompt('Introduce tu nueva contraseña (mínimo 6 caracteres):');
    if (nuevaContrasena && nuevaContrasena.length >= 6) {
        const confirmacion = prompt('Confirma tu nueva contraseña:');
        if (confirmacion === nuevaContrasena) {
            // Crear un formulario y enviarlo
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'cambiar_contrasena.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'nueva_contrasena';
            input.value = nuevaContrasena;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        } else {
            alert('❌ Las contraseñas no coinciden');
        }
    } else if (nuevaContrasena) {
        alert('❌ La contraseña debe tener al menos 6 caracteres');
    }
}

console.log('✓ Página de Perfil cargada');
</script>

</body>
</html>