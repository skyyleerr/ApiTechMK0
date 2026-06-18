<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../LOGIN/login.php");
    exit();
}

include("../../CONEXION/conexion.php");

// Obtener mensajes de éxito o error
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// Obtener colmenas del usuario actual
$id_usuario = intval($_SESSION['usuario_id']);
$colmenas = $conn->query("
    SELECT id_colmena, nombre, ubicacion, estado, fecha_creacion 
    FROM colmenas 
    WHERE id_usuario = $id_usuario 
    ORDER BY id_colmena DESC
");

// Contar colmenas
$count_result = $conn->query("SELECT COUNT(*) as total FROM colmenas WHERE id_usuario = $id_usuario");
$count_row = $count_result->fetch_assoc();
$total_colmenas = $count_row['total'];

// Obtener usuario logueado
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Colmenas - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../../CSS/dashboard.css">
<style>
/* Estilos mejorados */
.page-container {
    display: flex;
    height: 100vh;
}

.main {
    flex: 1;
    overflow-y: auto;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.btn-icon-edit {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.btn-icon-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-icon-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-icon-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-icon:active {
    transform: translateY(0);
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

/* Acciones */
.actions-cell {
    display: flex;
    gap: 8px;
    align-items: center;
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

/* Stats */
.stats-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 16px;
    border-radius: 10px;
    border-left: 4px solid #FFC72C;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.stat-card h3 {
    margin: 0 0 8px 0;
    font-size: 13px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card p {
    margin: 0;
    font-size: 28px;
    font-weight: 700;
    color: #111827;
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

@media (max-width: 768px) {
    .modal-usuario {
        width: 90vw;
        max-width: 320px;
    }

    .actions-cell {
        flex-direction: column;
        width: 100%;
    }

    .btn-icon {
        width: 100%;
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

<li><a href="../../DASHBOARD/importar_datos.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-upload"></i> Importar Datos</a></li>

<li><a href="../../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
</ul>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<input class="search" placeholder="Buscar colmena..." id="search">
<div style="display: flex; gap: 20px; align-items: center;">
<div style="position: relative;">
<i class="fa-solid fa-bell" id="btn-alertas" style="font-size: 20px; color: #666; cursor: pointer; transition: all 0.3s;"></i>
<span id="badge-alertas" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; display: none;">0</span>
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>📦 Gestión de Colmenas</h1>
<a href="../../FORMULARIOS/COLMENAS/formulario_colmena.php">
<button class="btn">
<i class="fa-solid fa-plus"></i> Nueva Colmena
</button>
</a>
</div>

<!-- Alertas de éxito/error -->
<?php if (!empty($success)) { ?>
<div class="alert alert-success">
<i class="fa-solid fa-check-circle"></i>
<span><?php echo $success; ?></span>
</div>
<?php } ?>

<?php if (!empty($error)) { ?>
<div class="alert alert-error">
<i class="fa-solid fa-exclamation-circle"></i>
<span><?php echo $error; ?></span>
</div>
<?php } ?>

<!-- Estadísticas -->
<?php if ($total_colmenas > 0) { ?>
<div class="stats-container">
<div class="stat-card">
<h3>Total de colmenas</h3>
<p><?php echo $total_colmenas; ?></p>
</div>
</div>
<?php } ?>

<div class="dashboard">
<div class="table-container">

<h3 style="margin: 0 16px 16px 16px; padding-top: 16px; font-size: 18px; font-weight: 700;">Colmenas Registradas</h3>

<table id="tabla-colmenas">
<thead>
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Ubicación</th>
<th>Estado</th>
<th>Fecha Registro</th>
<th>Acciones</th>
</tr>
</thead>
<tbody id="tbody-colmenas">
<?php 
if ($colmenas && $colmenas->num_rows > 0) {
    while($col = $colmenas->fetch_assoc()) { 
        $estado_clase = 'ok';
        if ($col['estado'] === 'Advertencia') {
            $estado_clase = 'warn';
        } elseif ($col['estado'] === 'Crítico') {
            $estado_clase = 'bad';
        }
?>
<tr data-id="<?php echo $col['id_colmena']; ?>">
<td><strong>#<?php echo $col['id_colmena']; ?></strong></td>
<td><strong><?php echo htmlspecialchars($col['nombre']); ?></strong></td>
<td><?php echo htmlspecialchars($col['ubicacion']); ?></td>
<td>
<span class="estado <?php echo $estado_clase; ?>">
<?php echo htmlspecialchars($col['estado']); ?>
</span>
</td>
<td><?php echo date('d/m/Y H:i', strtotime($col['fecha_creacion'])); ?></td>
<td>
<div class="actions-cell">
<a href="../../FORMULARIOS/COLMENAS/formulario_colmena.php?id=<?php echo $col['id_colmena']; ?>" class="btn-icon btn-icon-edit" title="Editar colmena">
<i class="fa-solid fa-pencil"></i>
</a>
<button type="button" class="btn-icon btn-icon-delete" onclick="eliminarColmena(<?php echo $col['id_colmena']; ?>, '<?php echo htmlspecialchars($col['nombre']); ?>')" title="Eliminar colmena">
<i class="fa-solid fa-trash-alt"></i>
</button>
</div>
</td>
</tr>
<?php 
    }
} else {
?>
<tr>
<td colspan="6">
<div class="empty-state">
<i class="fa-solid fa-inbox"></i>
<p>No hay colmenas registradas</p>
<p style="font-size: 12px; margin-top: 12px;">Crea una nueva colmena para empezar a monitorear</p>
</div>
</td>
</tr>
<?php } ?>
</tbody>
</table>

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
  <div style="padding: 12px; border-top: 1px solid #eee; background: #f9f9f9;">
    <button id="btn-limpiar-alertas" style="width: 100%; background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%); color: #1a1a1a; border: none; padding: 10px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 700; transition: all 0.3s;">
      <i class="fa-solid fa-broom"></i> Limpiar alertas
    </button>
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
    <a href="../../DASHBOARD/configuracion.php" class="modal-usuario-item">
      <i class="fa-solid fa-user-circle"></i>
      <span>Mi Perfil</span>
    </a>
    
    <a href="../../DASHBOARD/configuracion.php" class="modal-usuario-item">
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
function eliminarColmena(id, nombre) {
    if (confirm(`¿Estás seguro de que deseas eliminar la colmena "${nombre}"? Esta acción no se puede deshacer.`)) {
        window.location.href = `eliminar_colmena.php?id=${id}`;
    }
}

function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../../LOGIN/logout.php';
    }
}

function mostrarAyuda(e) {
    e.preventDefault();
    alert('📚 Ayuda - Gestión de Colmenas\n\n✓ Editar: Modifica los datos de la colmena\n✓ Eliminar: Elimina la colmena definitivamente\n✓ Nueva: Crea una nueva colmena\n\n¿Necesitas más ayuda? Contacta al soporte.');
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
        document.querySelectorAll('#tbody-colmenas tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
}

// Limpiar alertas
const btnLimpiar = document.getElementById('btn-limpiar-alertas');
if (btnLimpiar) {
    btnLimpiar.addEventListener('click', function() {
        document.getElementById('badge-alertas').style.display = 'none';
        document.getElementById('alertas-lista').innerHTML = `
            <div style="text-align: center; color: #999; padding: 30px 20px;">
                <i class="fa-solid fa-check-circle" style="font-size: 40px; margin-bottom: 10px; opacity: 0.5; display: block;"></i>
                <p style="margin-top: 10px;">No hay alertas activas</p>
            </div>
        `;
    });
}

console.log('✓ Lista de colmenas cargada');
</script>

</body>
</html>
