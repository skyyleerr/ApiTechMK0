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

// Obtener últimos datos de sensores para cada colmena del usuario
$sensores_data = [];
if ($total_colmenas > 0) {
    $result = $conn->query("
        SELECT 
            s.id_colmena, 
            s.tipo, 
            s.valor,
            s.fecha,
            c.nombre
        FROM sensores s
        INNER JOIN colmenas c ON s.id_colmena = c.id_colmena
        WHERE c.id_usuario = $id_usuario
        AND s.fecha = (
            SELECT MAX(fecha) FROM sensores 
            WHERE id_colmena = s.id_colmena AND tipo = s.tipo
        )
        ORDER BY s.id_colmena, s.tipo
    ");
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id_col = $row['id_colmena'];
            if (!isset($sensores_data[$id_col])) {
                $sensores_data[$id_col] = ['nombre' => $row['nombre']];
            }
            $sensores_data[$id_col][$row['tipo']] = [
                'valor' => $row['valor'],
                'fecha' => $row['fecha']
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sensores - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
/* Estilos mejorados */
.sensors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    animation: slideUp 0.6s ease 0.1s both;
}

.sensor-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-top: 4px solid #FFC72C;
}

.sensor-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 30px rgba(255, 199, 44, 0.15);
    border-top: 4px solid #FFC72C;
}

.sensor-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.sensor-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
}

.sensor-card-location {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.sensor-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.sensor-status.active {
    background: #d4edda;
    color: #155724;
}

.sensor-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.sensor-values {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sensor-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.sensor-item:hover {
    background: rgba(255, 199, 44, 0.1);
}

.sensor-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.sensor-icon {
    font-size: 16px;
    color: #FFC72C;
    width: 20px;
    text-align: center;
}

.sensor-value {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
}

.sensor-unit {
    font-size: 12px;
    color: #999;
    margin-left: 4px;
}

.sensor-timestamp {
    font-size: 11px;
    color: #bbb;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

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

/* Alertas */
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
    .sensors-grid {
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
<li>
<a href="../DASHBOARD/sensores.php" style="background: #FFC72C; color: #1a1a1a; display: flex; align-items: center; gap: 10px; border-radius: 8px;">
<i class="fa-solid fa-microchip"></i> Sensores
</a>
</li>
<li><a href="../DASHBOARD/produccion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-jar"></i> Producción</a></li>
<li><a href="../DASHBOARD/alertas.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-triangle-exclamation"></i> Alertas</a></li>
<li><a href="../DASHBOARD/reportes.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-file"></i> Reportes</a></li>

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
<span id="badge-alertas" style="display: none;">0</span>
</div>
<div class="user-icon" id="btn-usuario">
<?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
</div>
</div>
</div>

<div class="header">
<h1>🔧 Monitoreo de Sensores</h1>
<p style="color: #666; margin-top: 8px; font-size: 14px;">Total de colmenas: <strong><?php echo $total_colmenas; ?></strong></p>
</div>

<div class="dashboard">

<?php if ($total_colmenas > 0): ?>

    <div class="sensors-grid" id="sensores-container">
    <?php 
    if ($colmenas && $colmenas->num_rows > 0) {
        $colmenas->data_seek(0);
        while($col = $colmenas->fetch_assoc()) { 
            $id_col = $col['id_colmena'];
            $tiene_datos = isset($sensores_data[$id_col]);
            $temp = $tiene_datos && isset($sensores_data[$id_col]['temperatura']) ? $sensores_data[$id_col]['temperatura']['valor'] : null;
            $humedad = $tiene_datos && isset($sensores_data[$id_col]['humedad']) ? $sensores_data[$id_col]['humedad']['valor'] : null;
            $ultima_actualizacion = $tiene_datos ? (isset($sensores_data[$id_col]['temperatura']) ? $sensores_data[$id_col]['temperatura']['fecha'] : $sensores_data[$id_col]['humedad']['fecha']) : null;
    ?>
        <div class="sensor-card" data-id="<?php echo $id_col; ?>">
            <div class="sensor-card-header">
                <div>
                    <div class="sensor-card-title">
                        <i class="fa-solid fa-box" style="color: #FFC72C; margin-right: 8px;"></i>
                        <?php echo htmlspecialchars($col['nombre']); ?>
                    </div>
                    <div class="sensor-card-location">
                        <i class="fa-solid fa-map-pin" style="color: #999; margin-right: 4px;"></i>
                        <?php echo htmlspecialchars($col['ubicacion']); ?>
                    </div>
                </div>
                <span class="sensor-status <?php echo $tiene_datos ? 'active' : 'inactive'; ?>">
                    <?php echo $tiene_datos ? '🟢 Activo' : '🔴 Inactivo'; ?>
                </span>
            </div>

            <div class="sensor-values">
                <!-- Temperatura -->
                <div class="sensor-item">
                    <div class="sensor-label">
                        <i class="fa-solid fa-thermometer-half sensor-icon"></i>
                        Temperatura
                    </div>
                    <div class="sensor-value">
                        <?php 
                        if ($temp !== null) {
                            echo round($temp, 1);
                            echo '<span class="sensor-unit">°C</span>';
                        } else {
                            echo '<span style="color: #999;">--</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Humedad -->
                <div class="sensor-item">
                    <div class="sensor-label">
                        <i class="fa-solid fa-droplet sensor-icon"></i>
                        Humedad
                    </div>
                    <div class="sensor-value">
                        <?php 
                        if ($humedad !== null) {
                            echo round($humedad, 1);
                            echo '<span class="sensor-unit">%</span>';
                        } else {
                            echo '<span style="color: #999;">--</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($ultima_actualizacion): ?>
                <div class="sensor-timestamp">
                    <i class="fa-solid fa-clock" style="margin-right: 4px;"></i>
                    Última actualización: <?php echo date('d/m/Y H:i', strtotime($ultima_actualizacion)); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php 
        }
    } 
    ?>
    </div>

<?php else: ?>

    <div class="empty-state">
        <i class="fa-solid fa-microchip"></i>
        <p>No hay colmenas registradas</p>
        <p style="font-size: 12px; margin-top: 12px;">
            Crea una colmena para empezar a monitorear sensores
        </p>
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
    alert('📚 Ayuda - Monitoreo de Sensores\n\n✓ Temperatura: Temperatura actual en °C\n✓ Humedad: Humedad relativa en %\n✓ Estado: Indica si el sensor está activo o inactivo\n✓ Última actualización: Fecha y hora del último registro\n\n¿Necesitas más ayuda? Contacta al soporte.');
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

// Búsqueda en sensores
const searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.sensor-card').forEach(card => {
            const text = card.textContent.toLowerCase();
            card.style.display = text.includes(term) ? '' : 'none';
        });
    });
}

console.log('✓ Página de Sensores cargada');
</script>

</body>
</html>