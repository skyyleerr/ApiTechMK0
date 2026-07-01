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

// Obtener estadísticas del usuario
$stats = $conn->query("
    SELECT 
        COUNT(c.id_colmena) as total_colmenas,
        COUNT(DISTINCT p.id_colmena) as colmenas_produciendo,
        COALESCE(SUM(p.cantidad_miel), 0) as produccion_total,
        COALESCE(AVG(p.cantidad_miel), 0) as produccion_promedio,
        COALESCE(MAX(p.cantidad_miel), 0) as produccion_maxima
    FROM colmenas c
    LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
    WHERE c.id_usuario = $id_usuario
");

$stats_row = $stats ? $stats->fetch_assoc() : [
    'total_colmenas' => 0,
    'colmenas_produciendo' => 0,
    'produccion_total' => 0,
    'produccion_promedio' => 0,
    'produccion_maxima' => 0
];

// Obtener datos de colmenas con producción
$colmenas_data = $conn->query("
    SELECT 
        c.id_colmena,
        c.nombre,
        c.ubicacion,
        c.estado,
        c.fecha_creacion,
        COUNT(p.id_produccion) as registros_produccion,
        COALESCE(SUM(p.cantidad_miel), 0) as total_produccion,
        COALESCE(AVG(p.cantidad_miel), 0) as promedio_produccion
    FROM colmenas c
    LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
    WHERE c.id_usuario = $id_usuario
    GROUP BY c.id_colmena, c.nombre, c.ubicacion, c.estado, c.fecha_creacion
    ORDER BY c.nombre
");

// Obtener últimos registros de producción
$produccion_recent = $conn->query("
    SELECT 
        p.id_produccion,
        p.cantidad_miel,
        p.fecha,
        c.nombre,
        c.estado
    FROM produccion p
    INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
    WHERE c.id_usuario = $id_usuario
    ORDER BY p.fecha DESC
    LIMIT 30
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
/* Estilos mejorados */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    animation: slideUp 0.6s ease 0.1s both;
}

.report-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-top: 4px solid #FFC72C;
    cursor: pointer;
}

.report-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(255, 199, 44, 0.15);
    border-top: 4px solid #FFC72C;
}

.report-icon {
    font-size: 32px;
    color: #FFC72C;
    margin-bottom: 12px;
}

.report-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.report-description {
    font-size: 13px;
    color: #666;
    margin-bottom: 16px;
    line-height: 1.5;
}

.report-button {
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    width: 100%;
    justify-content: center;
}

.report-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 199, 44, 0.3);
}

/* Stats container */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    animation: slideUp 0.6s ease 0.1s both;
}

.stat-card {
    background: white;
    padding: 16px;
    border-radius: 10px;
    border-left: 4px solid #FFC72C;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stat-label {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: 800;
    color: #1a1a1a;
}

.stat-unit {
    font-size: 12px;
    color: #666;
    margin-left: 4px;
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
    .reports-grid {
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
<li><a href="../DASHBOARD/produccion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-jar"></i> Producción</a></li>
<li><a href="../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
<li>
<a href="../DASHBOARD/reportes.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-file"></i> Reportes
</a>

</li>
<li><a href="../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>
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
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>📊 Reportes y Estadísticas</h1>
<p style="color: #666; margin-top: 8px; font-size: 14px;">Genera y descarga reportes de tu apiario</p>
</div>

<!-- ESTADÍSTICAS GENERALES -->
<div class="stats-container">
<div class="stat-card">
<div class="stat-label">Total de Colmenas</div>
<div class="stat-value"><?php echo $stats_row['total_colmenas']; ?></div>
</div>

<div class="stat-card">
<div class="stat-label">Colmenas Produciendo</div>
<div class="stat-value"><?php echo $stats_row['colmenas_produciendo']; ?></div>
</div>

<div class="stat-card">
<div class="stat-label">Producción Total</div>
<div class="stat-value">
<?php echo round($stats_row['produccion_total'], 1); ?>
<span class="stat-unit">kg</span>
</div>
</div>

<div class="stat-card">
<div class="stat-label">Promedio por Colmena</div>
<div class="stat-value">
<?php echo round($stats_row['produccion_promedio'], 1); ?>
<span class="stat-unit">kg</span>
</div>
</div>
</div>

<div class="dashboard">

<!-- OPCIONES DE REPORTES -->
<div class="reports-grid">

<div class="report-card">
<div class="report-icon"><i class="fa-solid fa-file-pdf"></i></div>
<div class="report-title">Reporte en PDF</div>
<div class="report-description">Descarga un reporte completo en PDF con gráficas y estadísticas de tu apiario.</div>
<button class="report-button" onclick="descargarPDF()">
<i class="fa-solid fa-download"></i> Descargar PDF
</button>
</div>

<div class="report-card">
<div class="report-icon"><i class="fa-solid fa-file-excel"></i></div>
<div class="report-title">Exportar a Excel</div>
<div class="report-description">Exporta todos tus datos de colmenas y producción en formato Excel (.xlsx).</div>
<button class="report-button" onclick="descargarExcel()">
<i class="fa-solid fa-download"></i> Descargar Excel
</button>
</div>

<div class="report-card">
<div class="report-icon"><i class="fa-solid fa-file-csv"></i></div>
<div class="report-title">Exportar a CSV</div>
<div class="report-description">Exporta datos en formato CSV compatible con aplicaciones de hojas de cálculo.</div>
<button class="report-button" onclick="descargarCSV()">
<i class="fa-solid fa-download"></i> Descargar CSV
</button>
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
  </div>
  
  <div class="modal-usuario-footer">
    <button class="btn-logout" onclick="cerrarSesion()">
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

function mostrarAyuda(e) {
    e.preventDefault();
    alert('📚 Ayuda - Reportes\n\n✓ PDF: Incluye gráficas y análisis completo\n✓ Excel: Abre en Excel o Google Sheets\n✓ CSV: Compatible con cualquier aplicación\n\n¿Necesitas más ayuda? Contacta al soporte.');
}

// Descargar reportes
function descargarPDF() {
    window.location.href = 'generar_pdf.php';
}

function descargarExcel() {
    window.location.href = 'generar_excel.php';
}

function descargarCSV() {
    window.location.href = 'generar_csv.php';
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

console.log('✓ Página de Reportes cargada');
</script>

</body>
</html>
