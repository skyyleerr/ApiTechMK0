<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

require_once(__DIR__ . '/../LOGIN/auth.php'); // habilita esAdmin() para el menú

// Todos los usuarios de la MISMA empresa ven las mismas colmenas
$idEmpresa = intval($_SESSION['empresa_id']);

$colmenas_count = 0;
$resultado = $conn->query("
    SELECT COUNT(*) as total
    FROM colmenas c
    INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
    WHERE u.id_empresa = " . $idEmpresa
);
if ($resultado) {
    $row = $resultado->fetch_assoc();
    $colmenas_count = $row['total'];
}

// Tabla de colmenas de TODA la empresa (no solo del usuario que inició sesión)
$tabla_colmenas = $conn->query("
SELECT 
c.id_colmena,
c.nombre,
c.ubicacion,
c.estado,
c.fecha_creacion
FROM colmenas c
INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
WHERE u.id_empresa = " . $idEmpresa . "
ORDER BY c.id_colmena DESC
");

// Obtener usuario logueado
// Refrescar nombre/correo desde la BD por si fueron editados en el CRUD de usuarios
$stmtRefresh = $conn->prepare("SELECT nombre, correo FROM usuarios WHERE id_usuario = ?");
$stmtRefresh->bind_param("i", $_SESSION['usuario_id']);
$stmtRefresh->execute();
$datosActuales = $stmtRefresh->get_result()->fetch_assoc();
$stmtRefresh->close();

if ($datosActuales) {
    $_SESSION['usuario_nombre'] = $datosActuales['nombre'];
    $_SESSION['usuario_correo'] = $datosActuales['correo'];
}

$usuario_nombre = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$usuario_correo = htmlspecialchars($_SESSION['usuario_correo'] ?? 'usuario@apitech.com');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ApiTech - Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
/* Estilos mejorados manteniendo la estética */

/* SPLASH */
.splash {
    animation: fadeOut 0.8s ease-out 1.2s forwards;
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; pointer-events: none; }
}

.splash-logo {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

/* CARDS MEJORADAS */
.cards {
    animation: slideUp 0.6s ease 0.1s both;
}

.card {
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    animation: slideUp 0.6s ease;
    position: relative;
    border-top: 3px solid #FFC72C;
}

.card:nth-child(1) { animation-delay: 0.05s; }
.card:nth-child(2) { animation-delay: 0.1s; }
.card:nth-child(3) { animation-delay: 0.15s; }
.card:nth-child(4) { animation-delay: 0.2s; }

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

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(255, 199, 44, 0.2);
    border-top: 4px solid #FFC72C;
}

.card-icon {
    transition: all 0.3s ease;
}

.card:hover .card-icon {
    transform: scale(1.15) rotate(-5deg);
    filter: drop-shadow(0 0 10px rgba(255, 199, 44, 0.3));
}

/* ESTADO SIN DATOS */
.card-empty {
    color: #999;
    font-size: 18px;
    font-weight: 500;
}

/* TABLAS MEJORADAS */
.table-container {
    animation: slideUp 0.6s ease 0.3s both;
    border-radius: 14px;
    overflow: hidden;
    background: white;
}

table {
    border-collapse: collapse;
}

tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

tbody tr:hover {
    background: linear-gradient(90deg, rgba(255, 199, 44, 0.05) 0%, rgba(255, 199, 44, 0.02) 100%);
    transform: scale(1.01);
}

/* GRÁFICAS - SOLO SI HAY COLMENAS */
.graficas-container {
    animation: slideUp 0.6s ease 0.4s both;
}

.grafica {
    border-radius: 14px;
    overflow: hidden;
    background: white;
    transition: all 0.3s ease;
}

.grafica:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(255, 199, 44, 0.15);
}

.grafica-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 320px;
    color: #999;
    flex-direction: column;
}

.grafica-empty i {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.4;
    color: #FFC72C;
}

/* MODAL USUARIO MEJORADO */
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
    padding: 24px 20px;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    text-align: center;
    position: relative;
}

.modal-usuario-header .avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 28px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes popIn {
    0% { transform: scale(0.8); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}

.modal-usuario-header h3 {
    margin: 8px 0 4px;
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
}

.modal-usuario-header p {
    margin: 0;
    font-size: 13px;
    opacity: 0.85;
    color: #1a1a1a;
}

.modal-usuario-body {
    padding: 12px 0;
}

.modal-usuario-item {
    padding: 13px 16px;
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
    font-weight: 500;
}

.modal-usuario-item:hover {
    background: rgba(255, 199, 44, 0.1);
    color: #FFC72C;
    padding-left: 20px;
}

.modal-usuario-item i {
    width: 18px;
    text-align: center;
    color: inherit;
}

.modal-usuario-divider {
    height: 1px;
    background: #eee;
    margin: 8px 0;
}

.modal-usuario-footer {
    padding: 12px;
}

.btn-logout {
    width: 100%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-logout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
}

/* MODAL ALERTAS MEJORADO */
#modal-alertas {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 350px;
    max-height: 500px;
    background: white;
    border-radius: 14px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    z-index: 1000;
    display: none !important;
    flex-direction: column;
    overflow: hidden;
    animation: slideInRight 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

#modal-alertas.active {
    display: flex !important;
}

#modal-alertas > div:first-child {
    padding: 16px;
    border-bottom: 2px solid #f0f0f0;
    background: linear-gradient(135deg, #f9f9f9 0%, #f5f5f5 100%);
}

#modal-alertas > div:first-child h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: #2c3e50;
}

#alertas-lista {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
}

#alertas-lista > div:first-child {
    text-align: center;
    color: #999;
    padding: 20px;
}

.alerta-item {
    padding: 12px;
    border-left: 4px solid;
    background: #f9f9f9;
    margin-bottom: 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    animation: slideUp 0.3s ease;
}

.alerta-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.alerta-item.error {
    border-left-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
}

.alerta-item.error:hover {
    background: rgba(239, 68, 68, 0.12);
}

.alerta-item.warning {
    border-left-color: #f59e0b;
    background: rgba(245, 158, 11, 0.08);
}

.alerta-item.warning:hover {
    background: rgba(245, 158, 11, 0.12);
}

.alerta-item strong {
    display: block;
    font-size: 13px;
    margin-bottom: 4px;
}

.alerta-item p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.alerta-item span {
    font-size: 11px;
    color: #999;
}

#btn-limpiar-alertas {
    width: 100%;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    border: none;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
}

#btn-limpiar-alertas:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 199, 44, 0.3);
}

/* BADGE ALERTAS CON ANIMACIÓN */
#badge-alertas {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

#badge-alertas.hide {
    display: none !important;
}

/* MEJORAR USER ICON */
.user-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFC72C, #ffb700);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    color: #1a1a1a;
    font-weight: 700;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
}

.user-icon:hover {
    transform: scale(1.1) translateY(-2px);
    box-shadow: 0 6px 18px rgba(255, 199, 44, 0.4);
}

/* MEJORA GENERAL */
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

/* RESPONSIVE */
@media (max-width: 768px) {
    .modal-usuario,
    #modal-alertas {
        position: fixed;
        right: 10px;
        width: 90vw;
        max-width: 320px;
    }
}
</style>
</head>

<body>

<!-- SPLASH -->
<div class="splash">
<img src="../IMG/apitech.png" class="splash-logo">
<h1>ApiTech</h1>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
<div class="logo-container">
<img src="../IMG/apitech_logo.png" class="logo-img">
<div class="logo-text">ApiTech</div>
</div>

<ul class="menu">
<li><a href="../DASHBOARD/dashboard.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
<li>
<a href="../CRUD/COLMENAS/listar_colmenas.php" style="display: flex; align-items: center; gap: 10px;">
<i class="fa-solid fa-box"></i> Colmenas
</a>
</li>
<li><a href="../DASHBOARD/sensores.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-microchip"></i> Sensores</a></li>
<li><a href="../DASHBOARD/produccion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-jar"></i> Producción</a></li>
<li><a href="../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
<li><a href="../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>
<?php if (esAdmin()): ?>
<li><a href="../DASHBOARD/usuarios.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-users"></i> Usuarios</a></li>
<?php endif; ?>
<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
<li><a href="../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
</ul>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<input class="search" placeholder="Buscar colmena..." id="search">
<div style="display: flex; gap: 20px; align-items: center;">
<div style="position: relative;">
<i class="fa-solid fa-bell" id="btn-alertas"></i>
<span id="badge-alertas" class="hide">0</span>
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>📊 Monitoreo del Apiario</h1>
<a href="../FORMULARIOS/COLMENAS/formulario_colmena.php">
<button class="btn">
<i class="fa-solid fa-plus"></i> Nueva Colmena
</button>
</a>
</div>

<div class="dashboard">

<!-- TARJETAS -->
<div class="cards" id="cards-container">

<div class="card">
<div class="card-icon"><i class="fa-solid fa-box"></i></div>
<div class="card-title">Colmenas activas</div>
<div class="card-value" id="colmenas-activas"><?php echo $colmenas_count; ?></div>
</div>

<div class="card">
<div class="card-icon"><i class="fa-solid fa-jar"></i></div>
<div class="card-title">Producción total</div>
<div class="card-value" id="produccion-total"><span class="card-empty">Sin datos</span></div>
</div>

<div class="card">
<div class="card-icon"><i class="fa-solid fa-temperature-half"></i></div>
<div class="card-title">Temperatura promedio</div>
<div class="card-value" id="temperatura-promedio"><span class="card-empty">Sin datos</span></div>
</div>

<div class="card">
<div class="card-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
<div class="card-title">Alertas activas</div>
<div class="card-value" id="alertas-activas"><span class="card-empty">0</span></div>
</div>

</div>

<!-- TABLA DE COLMENAS -->
<div class="table-container">

<h3 style="margin-bottom: 20px; font-size: 18px; font-weight: 700;">Colmenas Registradas</h3>

<table id="tabla-colmenas">

<thead>
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Ubicación</th>
<th>Temperatura</th>
<th>Producción</th>
<th>Estado</th>
<th>Fecha</th>
</tr>
</thead>

<tbody id="tbody-colmenas">
<?php 
if ($tabla_colmenas && $tabla_colmenas->num_rows > 0) {
    while($fila = $tabla_colmenas->fetch_assoc()) { 
?>
<tr data-id="<?php echo $fila['id_colmena']; ?>">
<td>#<?php echo htmlspecialchars($fila['id_colmena']); ?></td>
<td><strong><?php echo htmlspecialchars($fila['nombre']); ?></strong></td>
<td><?php echo htmlspecialchars($fila['ubicacion']); ?></td>
<td><strong class="temp-cell">0°C</strong></td>
<td class="prod-cell">0 kg</td>
<td class="estado-cell"><span class="ok">Cargando...</span></td>
<td><?php echo date('d/m/Y', strtotime($fila['fecha_creacion'])); ?></td>
</tr>
<?php 
    }
} else {
    echo '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>No hay colmenas registradas</td></tr>';
}
?>
</tbody>

</table>

</div>

<!-- GRÁFICAS - SOLO SI HAY COLMENAS -->
<?php if ($tabla_colmenas && $tabla_colmenas->num_rows > 0): ?>
<div class="graficas-container">

<div class="grafica">
<h3 style="padding: 16px; margin: 0; border-bottom: 1px solid #f0f0f0;">📈 Temperatura en los últimos 7 días</h3>
<canvas id="graficaTemp"></canvas>
</div>

<div class="grafica">
<h3 style="padding: 16px; margin: 0; border-bottom: 1px solid #f0f0f0;">📊 Producción por Colmena</h3>
<canvas id="graficaProduccion"></canvas>
</div>

</div>
<?php else: ?>
<div class="graficas-container">

<div class="grafica grafica-empty">
<i class="fa-solid fa-chart-line"></i>
<p>Las gráficas aparecerán cuando registres colmenas</p>
</div>

<div class="grafica grafica-empty">
<i class="fa-solid fa-chart-bar"></i>
<p>Monitorea la producción en tiempo real</p>
</div>

</div>
<?php endif; ?>

</div>

<!-- MODAL DE ALERTAS -->
<div id="modal-alertas">
  
  <div>
    <h3><i class="fa-solid fa-exclamation-triangle" style="color: #FFC72C; margin-right: 8px;"></i>Alertas del Sistema</h3>
  </div>
  
  <div id="alertas-lista" style="flex: 1; overflow-y: auto; padding: 12px;">
    <div style="text-align: center; color: #999; padding: 30px 20px;">
      <i class="fa-solid fa-check-circle" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5; display: block;"></i>
      <p style="margin-top: 10px;">No hay alertas activas</p>
    </div>
  </div>
  
  <div style="padding: 12px; border-top: 1px solid #eee; background: #f9f9f9;">
    <button id="btn-limpiar-alertas">
      <i class="fa-solid fa-broom"></i> Limpiar
    </button>
  </div>
</div>

<!-- MODAL DE USUARIO -->
<div id="modal-usuario" class="modal-usuario">
  <div class="modal-usuario-header">
    <div class="avatar">
      <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
    </div>
    <h3><?php echo htmlspecialchars($usuario_nombre); ?></h3>
    <p><?php echo htmlspecialchars($usuario_correo); ?></p>
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

</div>

<script>
let chartTemp = null;
let chartProduccion = null;
const datosTemperatura = [33, 34, 35, 36, 35, 34, 33];
let datosProduccion = [];
let alertasActivas = [];
let tieneColmenas = <?php echo $colmenas_count > 0 ? 'true' : 'false'; ?>;

function actualizarDatos() {
    console.log('🔄 Actualizando datos...');
    
    // Solo actualizar si hay colmenas
    if (!tieneColmenas) {
        console.log('⚠️ Sin colmenas registradas, saltando actualización');
        return;
    }
    
    fetch('generar_datos.php')
        .then(response => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(data => {
            console.log('✓ Datos:', data);
            
            // ===== TARJETAS =====
            document.getElementById('temperatura-promedio').innerHTML = 
                data.temperatura_promedio + ' <span style="font-size: 14px;">°C</span>';
            document.getElementById('produccion-total').innerHTML = 
                data.produccion_total + ' <span style="font-size: 14px;">kg</span>';
            document.getElementById('alertas-activas').textContent = data.alertas_activas;

            // ===== TABLA =====
            if (data.colmenas && data.colmenas.length > 0) {
                data.colmenas.forEach(colmena => {
                    const row = document.querySelector(`tr[data-id="${colmena.id}"]`);
                    if (row) {
                        // Temperatura
                        const tempCell = row.querySelector('.temp-cell');
                        tempCell.textContent = colmena.temperatura + '°C';
                        if (colmena.temperatura < 36) {
                            tempCell.style.color = '#10b981';
                        } else if (colmena.temperatura < 38) {
                            tempCell.style.color = '#f59e0b';
                        } else {
                            tempCell.style.color = '#ef4444';
                        }

                        // Producción
                        row.querySelector('.prod-cell').textContent = colmena.produccion + ' kg';

                        // Estado
                        const estadoCell = row.querySelector('.estado-cell');
                        const clase = colmena.estado === 'Estable' ? 'ok' : 
                                     colmena.estado === 'Advertencia' ? 'warn' : 'bad';
                        estadoCell.innerHTML = `<span class="${clase}">${colmena.estado}</span>`;
                    }
                });
            }

            // ===== GRÁFICA TEMPERATURA =====
            if (chartTemp) {
                datosTemperatura.shift();
                datosTemperatura.push(data.temperatura_promedio);
                chartTemp.data.datasets[0].data = [...datosTemperatura];
                chartTemp.update('none');
            }

            // ===== GRÁFICA PRODUCCIÓN =====
            if (chartProduccion && data.colmenas.length > 0) {
                if (data.colmenas.length !== datosProduccion.length) {
                    console.log('🐝 Colmenas detectadas:', data.colmenas.length);
                    datosProduccion = data.colmenas.map(c => c.produccion);
                    
                    chartProduccion.data.labels = data.colmenas.map(c => c.nombre);
                    chartProduccion.data.datasets[0].data = [...datosProduccion];
                    chartProduccion.data.datasets[0].backgroundColor = data.colmenas.map((_, idx) => {
                        const opacity = 0.7 + (idx * 0.08);
                        return `rgba(255, 199, 44, ${Math.min(opacity, 1)})`;
                    });
                    chartProduccion.update();
                } else {
                    chartProduccion.data.datasets[0].data = data.colmenas.map(c => c.produccion);
                    chartProduccion.update('none');
                }
            }
        })
        .catch(error => console.error('❌ Error:', error));
}

// ===== SISTEMA DE ALERTAS =====
function cargarAlertas() {
    // Solo cargar alertas si hay colmenas
    if (!tieneColmenas) {
        console.log('⚠️ Sin colmenas, alertas desactivadas');
        alertasActivas = [];
        actualizarVistaBadge();
        return;
    }

    fetch('obtener_alertas.php')
        .then(response => response.json())
        .then(data => {
            alertasActivas = data.alertas || [];
            actualizarVistaBadge();
            actualizarVistaModal();
            console.log('📢 Alertas cargadas:', alertasActivas.length);
        })
        .catch(error => console.error('Error al cargar alertas:', error));
}

function actualizarVistaBadge() {
    const badgeAlertas = document.getElementById('badge-alertas');
    const total = alertasActivas.length;
    if (total > 0) {
        badgeAlertas.textContent = total;
        badgeAlertas.classList.remove('hide');
    } else {
        badgeAlertas.classList.add('hide');
    }
}

function actualizarVistaModal() {
    const alertasLista = document.getElementById('alertas-lista');
    
    if (alertasActivas.length === 0) {
        alertasLista.innerHTML = `
            <div style="text-align: center; color: #999; padding: 30px 20px;">
                <i class="fa-solid fa-check-circle" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5; display: block;"></i>
                <p style="margin-top: 10px;">No hay alertas activas</p>
            </div>
        `;
        return;
    }
    
    alertasLista.innerHTML = alertasActivas.map((alerta, idx) => {
        const clase = alerta.tipo === 'error' ? 'error' : 'warning';
        const iconoTipo = alerta.tipo === 'error' ? 'fa-circle-xmark' : 'fa-triangle-exclamation';
        
        return `
            <div class="alerta-item ${clase}" style="animation-delay: ${idx * 0.05}s;">
                <i class="fa-solid ${iconoTipo}" style="margin-right: 8px;"></i>
                <strong>${alerta.titulo}</strong>
                <p>${alerta.mensaje}</p>
                <span>${alerta.timestamp}</span>
            </div>
        `;
    }).join('');
}

function eliminarAlerta(event, id) {
    event.stopPropagation();
    alertasActivas = alertasActivas.filter(a => a.id !== id);
    actualizarVistaBadge();
    actualizarVistaModal();
}

function marcarAlertaLeida(id) {
    console.log('✓ Alerta leída:', id);
}

function limpiarTodasAlertas() {
    alertasActivas = [];
    actualizarVistaBadge();
    actualizarVistaModal();
}

// ===== FUNCIONES DE USUARIO =====
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
}

function mostrarAyuda(e) {
    if(e) e.preventDefault();
    alert('📚 Ayuda de ApiTech\n\n✓ Dashboard: Monitorea el estado general de tus colmenas\n✓ Colmenas: Gestiona todas tus colmenas\n✓ Sensores: Revisa los datos de los sensores\n✓ Producción: Registra y monitorea la producción\n✓ Alertas: Recibe notificaciones importantes\n\n¿Necesitas más ayuda? Contacta al soporte.');
}

document.addEventListener('DOMContentLoaded', function() {
    // ===== LIMPIAR ALERTAS =====
    const btnLimpiar = document.getElementById('btn-limpiar-alertas');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', limpiarTodasAlertas);
    }

    // ===== MODAL DE ALERTAS =====
    const btnAlertas = document.getElementById('btn-alertas');
    const modalAlertas = document.getElementById('modal-alertas');
    
    if (btnAlertas) {
        btnAlertas.addEventListener('click', function(e) {
            e.stopPropagation();
            modalAlertas.classList.toggle('active');
            document.getElementById('modal-usuario').classList.remove('active');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (btnAlertas && modalAlertas) {
            if (!btnAlertas.contains(e.target) && !modalAlertas.contains(e.target)) {
                modalAlertas.classList.remove('active');
            }
        }
    });

    // ===== MODAL DE USUARIO =====
    const btnUsuario = document.getElementById('btn-usuario');
    const modalUsuario = document.getElementById('modal-usuario');
    
    if (btnUsuario) {
        btnUsuario.addEventListener('click', function(e) {
            e.stopPropagation();
            modalUsuario.classList.toggle('active');
            modalAlertas.classList.remove('active');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (btnUsuario && modalUsuario) {
            if (!btnUsuario.contains(e.target) && !modalUsuario.contains(e.target)) {
                modalUsuario.classList.remove('active');
            }
        }
    });

    // ===== GRÁFICA TEMPERATURA =====
    const ctx = document.getElementById('graficaTemp');
    if (ctx) {
        chartTemp = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sab', 'Dom'],
                datasets: [{
                    label: 'Temperatura (°C)',
                    data: [...datosTemperatura],
                    borderColor: '#FFC72C',
                    backgroundColor: 'rgba(255, 199, 44, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FFC72C',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 300 },
                plugins: {
                    legend: { display: true, labels: { font: { size: 12, weight: '600' }, color: '#666' } }
                },
                scales: {
                    y: { 
                        min: 30, 
                        max: 40, 
                        ticks: { color: '#999', font: { size: 12 } }, 
                        grid: { color: 'rgba(0,0,0,0.05)' } 
                    },
                    x: { 
                        ticks: { color: '#999', font: { size: 12 } }, 
                        grid: { display: false } 
                    }
                }
            }
        });
    }

    // ===== GRÁFICA PRODUCCIÓN =====
    const ctx2 = document.getElementById('graficaProduccion');
    if (ctx2) {
        chartProduccion = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Producción (kg)',
                    data: [],
                    backgroundColor: [],
                    borderColor: '#FFC72C',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: { duration: 300 },
                plugins: {
                    legend: { display: true, labels: { font: { size: 12, weight: '600' }, color: '#666' } }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        ticks: { color: '#999', font: { size: 12 } }, 
                        grid: { color: 'rgba(0,0,0,0.05)' } 
                    },
                    x: { 
                        ticks: { color: '#999', font: { size: 12 } }, 
                        grid: { display: false } 
                    }
                }
            }
        });
    }

    // ===== BÚSQUEDA =====
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }

    // ===== INICIAR ACTUALIZACIONES =====
    console.log('🚀 Dashboard iniciado');
    
    if (tieneColmenas) {
        actualizarDatos();
        cargarAlertas();
        setInterval(actualizarDatos, 5000);
        setInterval(cargarAlertas, 5000);
    } else {
        console.log('⚠️ Dashboard sin colmenas');
    }
});
</script>

</body>
</html>