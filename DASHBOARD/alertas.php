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
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alertas - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
.alertas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.alertas-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    text-align: center;
    border-top: 4px solid #FFC72C;
    animation: slideUp 0.6s ease;
}

.stat-card h3 {
    margin: 10px 0 5px 0;
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
}

.stat-card p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.stat-card.error {
    border-top-color: #ef4444;
}

.stat-card.warning {
    border-top-color: #f59e0b;
}

.stat-card.info {
    border-top-color: #3b82f6;
}

.filter-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.filter-btn {
    background: #f3f4f6;
    border: 2px solid transparent;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s ease;
    color: #333;
}

.filter-btn:hover,
.filter-btn.active {
    border-color: #FFC72C;
    background: rgba(255, 199, 44, 0.1);
    color: #FFC72C;
}

#alertas-container {
    display: grid;
    gap: 15px;
}

.alerta-card {
    padding: 16px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #FFC72C;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    animation: slideIn 0.3s ease;
}

.alerta-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

.alerta-card.error {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.02) 100%);
}

.alerta-card.warning {
    border-left-color: #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(245, 158, 11, 0.02) 100%);
}

.alerta-card.info {
    border-left-color: #3b82f6;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.02) 100%);
}

.alerta-header {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 10px;
}

.alerta-icon {
    font-size: 20px;
    margin-top: 2px;
    flex-shrink: 0;
}

.alerta-icon.error {
    color: #ef4444;
}

.alerta-icon.warning {
    color: #f59e0b;
}

.alerta-icon.info {
    color: #3b82f6;
}

.alerta-content {
    flex: 1;
}

.alerta-titulo {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 6px 0;
    display: block;
}

.alerta-titulo.error {
    color: #ef4444;
}

.alerta-titulo.warning {
    color: #f59e0b;
}

.alerta-titulo.info {
    color: #3b82f6;
}

.alerta-mensaje {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.alerta-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.alerta-time {
    color: #999;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.alerta-actions {
    display: flex;
    gap: 8px;
}

.btn-accion {
    background: #f3f4f6;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    transition: all 0.2s ease;
}

.btn-accion:hover {
    background: #e5e7eb;
    color: #333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    display: block;
    color: #ddd;
}

.empty-state p {
    font-size: 16px;
    margin: 0;
}

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

#badge-alertas {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
}

@media (max-width: 768px) {
    .alertas-stats {
        grid-template-columns: 1fr;
    }

    .alerta-footer {
        flex-direction: column;
        align-items: flex-start;
    }

    .filter-group {
        width: 100%;
    }

    .filter-btn {
        flex: 1;
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
<li>
<a href="../DASHBOARD/alertas.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-triangle-exclamation"></i> Alertas
</a>
</li>
<li><a href="../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>

<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
<li><a href="../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
</ul>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<input class="search" placeholder="Buscar alertas..." id="search">
<div style="display: flex; gap: 20px; align-items: center;">
<div style="position: relative;">
<i class="fa-solid fa-bell" id="btn-alertas"></i>
<span id="badge-alertas"></span>
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>⚠️ Centro de Alertas</h1>
<p style="color: #666; margin-top: 8px; font-size: 14px;">Monitorea todos los eventos importantes de tus colmenas</p>
</div>

<div class="dashboard">

<!-- ESTADÍSTICAS -->
<div class="alertas-stats">
<div class="stat-card error">
<i class="fa-solid fa-circle-xmark" style="font-size: 24px; color: #ef4444;"></i>
<h3 id="stat-error">0</h3>
<p>Críticas</p>
</div>
<div class="stat-card warning">
<i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #f59e0b;"></i>
<h3 id="stat-warning">0</h3>
<p>Advertencias</p>
</div>
<div class="stat-card info">
<i class="fa-solid fa-info-circle" style="font-size: 24px; color: #3b82f6;"></i>
<h3 id="stat-info">0</h3>
<p>Informativas</p>
</div>
</div>

<!-- FILTROS -->
<div style="margin-bottom: 30px;">
<h3 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 600; color: #333;">Filtrar por tipo:</h3>
<div class="filter-group">
<button class="filter-btn active" onclick="filtrarAlertas('todas')">
<i class="fa-solid fa-list"></i> Todas
</button>
<button class="filter-btn" onclick="filtrarAlertas('error')">
<i class="fa-solid fa-circle-xmark"></i> Críticas
</button>
<button class="filter-btn" onclick="filtrarAlertas('warning')">
<i class="fa-solid fa-triangle-exclamation"></i> Advertencias
</button>
<button class="filter-btn" onclick="filtrarAlertas('info')">
<i class="fa-solid fa-info-circle"></i> Informativas
</button>
</div>
</div>

<!-- ALERTAS CONTAINER -->
<div id="alertas-container">
<div class="empty-state">
<i class="fa-solid fa-hourglass-start"></i>
<p>Cargando alertas...</p>
</div>
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
<a href="../DASHBOARD/perfil.php" class="modal-usuario-item">
<i class="fa-solid fa-user-circle"></i>
<span>Mi Perfil</span>
</a>

<a href="../DASHBOARD/configuracion.php" class="modal-usuario-item">
<i class="fa-solid fa-gear"></i>
<span>Configuración</span>
</a>

<a href="../DASHBOARD/ayuda.php" class="modal-usuario-item">
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
let filtroActual = 'todas';
let alertasGlobales = [];

function cargarAlertas() {
    fetch('../DASHBOARD/obtener_alertas.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                mostrarErrorAlertas();
                return;
            }

            alertasGlobales = data.alertas || [];
            actualizarEstadisticas();
            mostrarAlertas(filtroActual);
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarErrorAlertas();
        });
}

function actualizarEstadisticas() {
    const stats = {
        error: alertasGlobales.filter(a => a.tipo === 'error').length,
        warning: alertasGlobales.filter(a => a.tipo === 'warning').length,
        info: alertasGlobales.filter(a => a.tipo === 'info').length
    };

    document.getElementById('stat-error').textContent = stats.error;
    document.getElementById('stat-warning').textContent = stats.warning;
    document.getElementById('stat-info').textContent = stats.info;

    // Mostrar badge si hay alertas críticas
    const badge = document.getElementById('badge-alertas');
    if (stats.error > 0) {
        badge.textContent = stats.error;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

function filtrarAlertas(tipo) {
    filtroActual = tipo;
    
    // Actualizar botones activos
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.filter-btn').classList.add('active');
    
    mostrarAlertas(tipo);
}

function mostrarAlertas(tipo) {
    const container = document.getElementById('alertas-container');
    
    let alertasFiltradas = alertasGlobales;
    if (tipo !== 'todas') {
        alertasFiltradas = alertasGlobales.filter(a => a.tipo === tipo);
    }

    if (alertasFiltradas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fa-solid fa-check-circle"></i>
                <p>No hay ${tipo !== 'todas' ? 'alertas ' + tipo : 'alertas'}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = alertasFiltradas.map(alerta => `
        <div class="alerta-card ${alerta.tipo}">
            <div class="alerta-header">
                <i class="fa-solid ${getIconoTipo(alerta.tipo)} alerta-icon ${alerta.tipo}"></i>
                <div class="alerta-content">
                    <strong class="alerta-titulo ${alerta.tipo}">${alerta.titulo}</strong>
                    <p class="alerta-mensaje">${alerta.mensaje}</p>
                </div>
            </div>
            <div class="alerta-footer">
                <div class="alerta-time">
                    <i class="fa-solid fa-clock"></i>
                    ${alerta.timestamp}
                </div>
                <div class="alerta-actions">
                    <button class="btn-accion" onclick="marcarLeida('${alerta.id}')">
                        <i class="fa-solid fa-check"></i> Marcar leída
                    </button>
                    <button class="btn-accion" onclick="descartarAlerta('${alerta.id}')">
                        <i class="fa-solid fa-times"></i> Descartar
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function getIconoTipo(tipo) {
    const iconos = {
        'error': 'fa-circle-xmark',
        'warning': 'fa-triangle-exclamation',
        'info': 'fa-info-circle'
    };
    return iconos[tipo] || 'fa-bell';
}

function mostrarErrorAlertas() {
    const container = document.getElementById('alertas-container');
    container.innerHTML = `
        <div class="empty-state">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <p>Error al cargar las alertas</p>
        </div>
    `;
}

function marcarLeida(idAlerta) {
    console.log('Alerta marcada como leída:', idAlerta);
    // Aquí irá la lógica para marcar como leída en BD
}

function descartarAlerta(idAlerta) {
    console.log('Alerta descartada:', idAlerta);
    // Aquí irá la lógica para descartar en BD
}

function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
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

// Cargar alertas al iniciar y cada 10 segundos
document.addEventListener('DOMContentLoaded', function() {
    cargarAlertas();
    setInterval(cargarAlertas, 10000);
});

console.log('✓ Página de Alertas cargada');
</script>

</body>
</html>
