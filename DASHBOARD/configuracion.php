<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);

// Refrescar nombre/correo desde la BD por si fueron editados en el CRUD de usuarios
$stmtRefresh = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id_usuario = ?");
$stmtRefresh->bind_param("i", $id_usuario);
$stmtRefresh->execute();
$datosActuales = $stmtRefresh->get_result()->fetch_assoc();
$stmtRefresh->close();

if ($datosActuales) {
    $_SESSION['usuario_nombre'] = $datosActuales['nombre'];
    $_SESSION['usuario_correo'] = $datosActuales['correo'];
}

$usuario_nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$usuario_correo = htmlspecialchars($_SESSION['usuario_correo'] ?? 'usuario@apitech.com');
$success = '';
$error = '';

// Verificar y crear columnas si no existen
$check_columns = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'notif_%'");
if ($check_columns->num_rows == 0) {
    // Agregar columnas de notificaciones si no existen
    $alter_sql = "ALTER TABLE usuarios ADD COLUMN notif_temperatura INT DEFAULT 1,
                   ADD COLUMN notif_produccion INT DEFAULT 1,
                   ADD COLUMN notif_mantenimiento INT DEFAULT 1,
                   ADD COLUMN notif_alertas INT DEFAULT 1";
    
    if (!$conn->query($alter_sql)) {
        $error = "Advertencia: No se pudieron actualizar las configuraciones de notificaciones";
    }
}

// Obtener configuración del usuario
$config = $conn->query("
    SELECT 
        COALESCE(notif_temperatura, 1) as notif_temperatura,
        COALESCE(notif_produccion, 1) as notif_produccion,
        COALESCE(notif_mantenimiento, 1) as notif_mantenimiento,
        COALESCE(notif_alertas, 1) as notif_alertas
    FROM usuarios 
    WHERE id_usuario = $id_usuario
");

$config_row = $config ? $config->fetch_assoc() : [
    'notif_temperatura' => 1,
    'notif_produccion' => 1,
    'notif_mantenimiento' => 1,
    'notif_alertas' => 1
];

// Procesar cambios de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar_notificaciones') {
    $notif_temperatura = isset($_POST['notif_temperatura']) ? 1 : 0;
    $notif_produccion = isset($_POST['notif_produccion']) ? 1 : 0;
    $notif_mantenimiento = isset($_POST['notif_mantenimiento']) ? 1 : 0;
    $notif_alertas = isset($_POST['notif_alertas']) ? 1 : 0;
    
    $sql = "UPDATE usuarios SET 
            notif_temperatura = $notif_temperatura,
            notif_produccion = $notif_produccion,
            notif_mantenimiento = $notif_mantenimiento,
            notif_alertas = $notif_alertas
            WHERE id_usuario = $id_usuario";
    
    if ($conn->query($sql)) {
        $success = "Configuración de notificaciones actualizada correctamente";
        $config_row['notif_temperatura'] = $notif_temperatura;
        $config_row['notif_produccion'] = $notif_produccion;
        $config_row['notif_mantenimiento'] = $notif_mantenimiento;
        $config_row['notif_alertas'] = $notif_alertas;
    } else {
        $error = "Error al actualizar la configuración: " . $conn->error;
    }
}

// Obtener información del usuario
$user_info = $conn->query("SELECT correo, fecha_registro FROM usuarios WHERE id_usuario = $id_usuario");
$user_row = $user_info->fetch_assoc();

// Obtener información de la BD
$db_info = $conn->query("SELECT DATABASE() as db_name");
$db_row = $db_info->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuración - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
/* Estilos mejorados */
.config-container {
    max-width: 800px;
    margin: 0 auto;
}

.config-section {
    background: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    animation: slideUp 0.6s ease;
}

.config-section h2 {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #FFC72C;
}

.config-section h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 20px 0 15px 0;
}

.config-group {
    margin-bottom: 20px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
}

.checkbox-item:hover {
    background: rgba(255, 199, 44, 0.1);
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-right: 12px;
    accent-color: #FFC72C;
}

.checkbox-item label {
    flex: 1;
    cursor: pointer;
    font-weight: 500;
    color: #333;
    margin: 0;
}

.checkbox-item small {
    display: block;
    color: #999;
    margin-top: 4px;
    font-weight: normal;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
    font-size: 14px;
}

.info-value {
    color: #1a1a1a;
    font-weight: 500;
    font-size: 14px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-success {
    background: #d4edda;
    color: #155724;
}

.profile-card {
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 20px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 800;
    margin: 0 auto 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.profile-name {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 4px;
}

.profile-email {
    font-size: 13px;
    opacity: 0.9;
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-save {
    flex: 1;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
}

.btn-logout {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.btn-change-password {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-change-password:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
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
}

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

.modal-usuario-footer {
    padding: 12px;
    border-top: 1px solid #eee;
}

.btn-logout-modal {
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

.btn-logout-modal:hover {
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
}

#btn-alertas:hover {
    color: #FFC72C;
    transform: scale(1.15);
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

@media (max-width: 768px) {
    .config-container {
        padding: 0 15px;
    }

    .button-group {
        flex-direction: column;
    }

    .modal-usuario {
        width: 90vw;
        max-width: 320px;
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
<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
<a href="../DASHBOARD/configuracion.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-gear"></i> Configuración
</a>
</li>
</ul>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<input class="search" placeholder="Buscar..." id="search">
<div style="display: flex; gap: 20px; align-items: center;">
<div style="position: relative;">
<i class="fa-solid fa-bell" id="btn-alertas"></i>
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>⚙️ Configuración del Sistema</h1>
<p style="color: #666; margin-top: 8px; font-size: 14px;">Personaliza tu experiencia en ApiTech</p>
</div>

<div class="dashboard">
<div class="config-container">

<!-- ALERTAS DE ÉXITO/ERROR -->
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

<!-- TARJETA DE PERFIL -->
<div class="profile-card">
<div class="profile-avatar"><?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?></div>
<div class="profile-name"><?php echo htmlspecialchars($usuario_nombre); ?></div>
<div class="profile-email"><?php echo htmlspecialchars($usuario_correo); ?></div>
</div>

<!-- INFORMACIÓN DEL USUARIO -->
<div class="config-section">
<h2><i class="fa-solid fa-user" style="margin-right: 8px; color: #FFC72C;"></i>Mi Perfil</h2>

<div class="info-item">
<span class="info-label">Nombre:</span>
<span class="info-value"><?php echo htmlspecialchars($usuario_nombre); ?></span>
</div>

<div class="info-item">
<span class="info-label">Email:</span>
<span class="info-value"><?php echo htmlspecialchars($usuario_correo); ?></span>
</div>

<div class="info-item">
<span class="info-label">Registrado:</span>
<span class="info-value"><?php echo date('d/m/Y', strtotime($user_row['fecha_registro'])); ?></span>
</div>

<div class="button-group" style="margin-top: 16px;">
<button type="button" class="btn-change-password" onclick="cambiarContrasena()">
<i class="fa-solid fa-lock"></i> Cambiar Contraseña
</button>
</div>
</div>

<!-- CONFIGURACIÓN DE NOTIFICACIONES -->
<form method="POST" action="configuracion.php">
<input type="hidden" name="action" value="guardar_notificaciones">

<div class="config-section">
<h2><i class="fa-solid fa-bell" style="margin-right: 8px; color: #FFC72C;"></i>Notificaciones</h2>

<div class="config-group">
<h3>Alertas del Sistema</h3>

<div class="checkbox-item">
<input type="checkbox" id="notif_temperatura" name="notif_temperatura" 
       <?php echo $config_row['notif_temperatura'] ? 'checked' : ''; ?>>
<label for="notif_temperatura">
<strong>🌡️ Notificaciones de Temperatura</strong>
<small>Recibe alertas cuando la temperatura cambia significativamente</small>
</label>
</div>

<div class="checkbox-item">
<input type="checkbox" id="notif_produccion" name="notif_produccion" 
       <?php echo $config_row['notif_produccion'] ? 'checked' : ''; ?>>
<label for="notif_produccion">
<strong>📊 Notificaciones de Producción</strong>
<small>Recibe alertas de cambios en la producción de miel</small>
</label>
</div>

<div class="checkbox-item">
<input type="checkbox" id="notif_mantenimiento" name="notif_mantenimiento" 
       <?php echo $config_row['notif_mantenimiento'] ? 'checked' : ''; ?>>
<label for="notif_mantenimiento">
<strong>🔧 Alertas de Mantenimiento</strong>
<small>Recordatorios para mantenimiento de colmenas</small>
</label>
</div>

<div class="checkbox-item">
<input type="checkbox" id="notif_alertas" name="notif_alertas" 
       <?php echo $config_row['notif_alertas'] ? 'checked' : ''; ?>>
<label for="notif_alertas">
<strong>⚠️ Alertas Críticas</strong>
<small>Alertas urgentes sobre problemas graves</small>
</label>
</div>
</div>
</div>

<!-- INFORMACIÓN DEL SISTEMA -->
<div class="config-section">
<h2><i class="fa-solid fa-server" style="margin-right: 8px; color: #FFC72C;"></i>Información del Sistema</h2>

<div class="info-item">
<span class="info-label">Versión ApiTech:</span>
<span class="info-value">1.0.0</span>
</div>

<div class="info-item">
<span class="info-label">Estado Base de Datos:</span>
<span class="info-value">
<span class="status-badge status-success">
<i class="fa-solid fa-check"></i> Conectada
</span>
</span>
</div>

<div class="info-item">
<span class="info-label">Última Actualización:</span>
<span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
</div>

<div class="info-item">
<span class="info-label">Base de Datos:</span>
<span class="info-value"><?php echo htmlspecialchars($db_row['db_name']); ?></span>
</div>
</div>

<!-- BOTONES DE ACCIÓN -->
<div class="config-section">
<div class="button-group">
<button type="submit" class="btn-save">
<i class="fa-solid fa-save"></i> Guardar Cambios
</button>
<button type="button" class="btn-logout" onclick="cerrarSesion()">
<i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
</button>
</div>
</div>
</form>

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
  </div>
  
  <div class="modal-usuario-body">
    <a href="../DASHBOARD/configuracion.php" class="modal-usuario-item">
      <i class="fa-solid fa-user-circle"></i>
      <span>Mi Perfil</span>
    </a>
    
    <a href="#" class="modal-usuario-item" onclick="cambiarContrasena(event)">
      <i class="fa-solid fa-lock"></i>
      <span>Cambiar Contraseña</span>
    </a>
    
    <a href="#" class="modal-usuario-item" onclick="mostrarAyuda(event)">
      <i class="fa-solid fa-circle-question"></i>
      <span>Ayuda</span>
    </a>
  </div>
  
  <div class="modal-usuario-footer">
    <button class="btn-logout-modal" onclick="cerrarSesion()">
      <i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
    </button>
  </div>
</div>

<script>
// Funciones de utilidad
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
}

function cambiarContrasena(e) {
    if (e) e.preventDefault();
    
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

function mostrarAyuda(e) {
    e.preventDefault();
    alert('📚 Ayuda - Configuración\n\n✓ Notificaciones: Personaliza qué alertas recibir\n✓ Perfil: Ve tu información de usuario\n✓ Contraseña: Cambia tu contraseña de forma segura\n\n¿Necesitas más ayuda? Contacta al soporte.');
}

// Modal de usuario
const btnUsuario = document.getElementById('btn-usuario');
const modalUsuario = document.getElementById('modal-usuario');

if (btnUsuario) {
    btnUsuario.addEventListener('click', function(e) {
        e.stopPropagation();
        modalUsuario.classList.toggle('active');
    });
}

document.addEventListener('click', function(e) {
    if (btnUsuario && modalUsuario) {
        if (!btnUsuario.contains(e.target) && !modalUsuario.contains(e.target)) {
            modalUsuario.classList.remove('active');
        }
    }
});

console.log('✓ Página de Configuración cargada');
</script>

</body>
</html>