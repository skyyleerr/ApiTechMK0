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

// Obtener colmenas del usuario actual
$colmenas = $conn->query("
    SELECT id_colmena, nombre, ubicacion 
    FROM colmenas 
    WHERE id_usuario = $id_usuario 
    ORDER BY id_colmena ASC
");

// Contar colmenas
$count_result = $conn->query("SELECT COUNT(*) as total FROM colmenas WHERE id_usuario = $id_usuario");
$count_row = $count_result->fetch_assoc();
$total_colmenas = $count_row['total'];

// Obtener producción del usuario actual (todos los registros, no solo 30 días)
$produccion = $conn->query("
    SELECT 
        p.id_produccion,
        p.id_colmena,
        p.cantidad_miel,
        p.fecha,
        c.nombre,
        c.estado
    FROM produccion p
    INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
    WHERE c.id_usuario = $id_usuario
    ORDER BY p.fecha DESC
    LIMIT 500
");

// Obtener producción total por colmena
$total_produccion = $conn->query("
    SELECT 
        c.id_colmena,
        c.nombre,
        COALESCE(SUM(p.cantidad_miel), 0) as total_miel,
        COUNT(p.id_produccion) as registros
    FROM colmenas c
    LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
    WHERE c.id_usuario = $id_usuario
    GROUP BY c.id_colmena, c.nombre
    ORDER BY total_miel DESC
");

// Estadísticas generales
$stats = $conn->query("
    SELECT 
        COUNT(DISTINCT p.id_colmena) as colmenas_produciendo,
        COALESCE(SUM(p.cantidad_miel), 0) as produccion_total,
        COALESCE(AVG(p.cantidad_miel), 0) as produccion_promedio,
        COUNT(p.id_produccion) as total_registros
    FROM produccion p
    INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
    WHERE c.id_usuario = $id_usuario
");

$stats_row = $stats ? $stats->fetch_assoc() : ['colmenas_produciendo' => 0, 'produccion_total' => 0, 'produccion_promedio' => 0, 'total_registros' => 0];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Producción - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
/* Estilos mejorados */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    animation: slideUp 0.6s ease 0.1s both;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    border-left: 4px solid #FFC72C;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-label {
    font-size: 13px;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    color: #1a1a1a;
}

.stat-unit {
    font-size: 14px;
    color: #666;
    margin-left: 6px;
}

.stat-icon {
    font-size: 24px;
    color: #FFC72C;
    margin-bottom: 12px;
}

/* Tabla mejorada */
.table-container {
    animation: slideUp 0.6s ease 0.2s both;
    border-radius: 14px;
    overflow: hidden;
    background: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

thead th {
    padding: 16px;
    text-align: left;
    font-weight: 700;
    color: #374151;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f0f0;
}

tbody tr:hover {
    background: linear-gradient(90deg, rgba(255, 199, 44, 0.05) 0%, rgba(255, 199, 44, 0.02) 100%);
}

tbody td {
    padding: 14px 16px;
    color: #1f2937;
    font-size: 14px;
}

tbody td strong {
    color: #111827;
    font-weight: 600;
}

/* Estados */
.estado {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.estado.ok {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.estado.warn {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.estado.bad {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Gráficas */
.chart-container {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-top: 24px;
    animation: slideUp 0.6s ease 0.3s both;
}

.chart-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    margin: 8px 0;
    font-size: 16px;
}

/* Info badge */
.info-badge {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
    border: 1px solid #c3e6cb;
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

/* User icon */
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

/* Alertas */
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
    .stats-container {
        grid-template-columns: 1fr;
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
<li>
<a href="../DASHBOARD/produccion.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-jar"></i> Producción
</a>
</li>
<li><a href="../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
<li><a href="../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>
<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
<li><a href="../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
</ul>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<input class="search" placeholder="Buscar producción..." id="search">
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
<h1>🐝 Registro de Producción <span class="info-badge" style="<?php echo $stats_row['total_registros'] > 0 ? 'background: #d4edda; color: #155724; border-color: #c3e6cb;' : 'background: #e2e8f0; color: #475569; border-color: #cbd5e1;'; ?>">📊 <?php echo $stats_row['total_registros']; ?> registros</span></h1>
<p style="color: #666; margin-top: 8px; font-size: 14px;">Historial completo de producción de todas tus colmenas</p>
</div>

<!-- ESTADÍSTICAS -->
<div class="stats-container">
<div class="stat-card">
<div class="stat-icon"><i class="fa-solid fa-jar"></i></div>
<div class="stat-label">Producción Total</div>
<div class="stat-value">
<?php echo round($stats_row['produccion_total'], 1); ?>
<span class="stat-unit">kg</span>
</div>
</div>

<div class="stat-card">
<div class="stat-icon"><i class="fa-solid fa-box"></i></div>
<div class="stat-label">Colmenas Produciendo</div>
<div class="stat-value">
<?php echo $stats_row['colmenas_produciendo']; ?>
<span class="stat-unit">colmenas</span>
</div>
</div>

<div class="stat-card">
<div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
<div class="stat-label">Promedio por Registro</div>
<div class="stat-value">
<?php echo round($stats_row['produccion_promedio'], 1); ?>
<span class="stat-unit">kg</span>
</div>
</div>

<div class="stat-card">
<div class="stat-icon"><i class="fa-solid fa-list"></i></div>
<div class="stat-label">Total Registros</div>
<div class="stat-value">
<?php echo $stats_row['total_registros']; ?>
<span class="stat-unit">registros</span>
</div>
</div>
</div>

<div class="dashboard">

<?php if ($total_colmenas > 0 && $stats_row['total_registros'] > 0): ?>

<!-- TABLA DE PRODUCCIÓN -->
<div class="table-container">

<h3 style="margin: 0 16px 16px 16px; padding-top: 16px; font-size: 18px; font-weight: 700;">Historial de Producción</h3>

<table id="tabla-produccion">
<thead>
<tr>
<th>Colmena</th>
<th>Producción</th>
<th>Fecha</th>
<th>Estado</th>
</tr>
</thead>
<tbody id="tbody-produccion">
<?php 
if ($produccion && $produccion->num_rows > 0) {
    while($prod = $produccion->fetch_assoc()) { 
        $estado_clase = 'ok';
        if ($prod['estado'] === 'Advertencia') {
            $estado_clase = 'warn';
        } elseif ($prod['estado'] === 'Problema') {
            $estado_clase = 'bad';
        }
?>
<tr data-id="<?php echo $prod['id_produccion']; ?>">
<td><strong><?php echo htmlspecialchars($prod['nombre']); ?></strong></td>
<td><?php echo round($prod['cantidad_miel'], 2); ?> kg</td>
<td><?php echo date('d/m/Y H:i', strtotime($prod['fecha'])); ?></td>
<td>
<span class="estado <?php echo $estado_clase; ?>">
<?php echo htmlspecialchars($prod['estado']); ?>
</span>
</td>
</tr>
<?php 
    }
} else {
    echo '<tr><td colspan="4" style="text-align: center; padding: 40px; color: #999;"><i class="fa-solid fa-inbox" style="font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.5;"></i>No hay registros de producción</td></tr>';
}
?>
</tbody>
</table>

</div>

<!-- GRÁFICA DE PRODUCCIÓN POR COLMENA -->
<?php 
if ($total_produccion && $total_produccion->num_rows > 0):
    $total_produccion->data_seek(0);
    $tieneProduccion = false;
    while ($row = $total_produccion->fetch_assoc()) {
        if ($row['total_miel'] > 0) {
            $tieneProduccion = true;
            break;
        }
    }
    
    if ($tieneProduccion):
?>
<div class="chart-container">
<div class="chart-title"><i class="fa-solid fa-chart-bar" style="margin-right: 8px; color: #FFC72C;"></i>Producción Total por Colmena</div>
<canvas id="graficaProduccionPorColmena" style="max-height: 300px;"></canvas>
</div>
<?php 
    endif;
endif; 
?>

<?php elseif ($total_colmenas > 0): ?>

<div class="empty-state">
<i class="fa-solid fa-inbox"></i>
<p>No hay registros de producción</p>
<p style="font-size: 12px; margin-top: 12px;">
Importa datos de producción o crea nuevos registros
</p>
<p style="font-size: 12px; margin-top: 8px; color: #666;">
<a href="importar_datos.php" style="color: #FFC72C; text-decoration: none; font-weight: 600;">📤 Importar datos</a>
</p>
</div>

<?php else: ?>

<div class="empty-state">
<i class="fa-solid fa-jar"></i>
<p>No hay colmenas registradas</p>
<p style="font-size: 12px; margin-top: 12px;">Crea una colmena para empezar a registrar producción</p>
</div>

<?php endif; ?>

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

<script>
let chartProduccion = null;

// Funciones de utilidad
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
}

function mostrarAyuda(e) {
    e.preventDefault();
    alert('📚 Ayuda - Registro de Producción\n\n✓ Producción Total: Suma de toda la miel producida\n✓ Colmenas Produciendo: Número de colmenas con registros\n✓ Promedio: Producción promedio por registro\n✓ Total Registros: Número total de registros\n✓ Importa datos: Usa la opción Importar Datos para cargar registros en lote\n\n¿Necesitas más ayuda? Contacta al soporte.');
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

// Búsqueda en tabla
const searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('#tbody-produccion tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
}

// Gráfica de producción por colmena
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficaProduccionPorColmena');
    
    if (ctx) {
        // Datos del PHP
        const datosJson = <?php 
        if ($total_produccion && $total_produccion->num_rows > 0) {
            $datos = [];
            $total_produccion->data_seek(0);
            while ($row = $total_produccion->fetch_assoc()) {
                if ($row['total_miel'] > 0) {
                    $datos[] = [
                        'nombre' => $row['nombre'],
                        'total' => floatval($row['total_miel'])
                    ];
                }
            }
            echo json_encode($datos);
        } else {
            echo '[]';
        }
        ?>;
        
        if (datosJson.length > 0) {
            const labelosColmenas = [];
            const datosProduccion = [];
            
            datosJson.forEach(item => {
                labelosColmenas.push(item.nombre);
                datosProduccion.push(item.total);
            });
            
            chartProduccion = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelosColmenas,
                    datasets: [{
                        label: 'Producción (kg)',
                        data: datosProduccion,
                        backgroundColor: datosProduccion.map((_, idx) => {
                            const opacity = 0.7 + (idx * 0.08);
                            return `rgba(255, 199, 44, ${Math.min(opacity, 1)})`;
                        }),
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
    }
    
    console.log('✓ Página de Producción cargada');
});
</script>

</body>
</html>