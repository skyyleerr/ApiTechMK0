<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$usuario_nombre = isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario';
$usuario_correo = isset($_SESSION['usuario_correo']) ? htmlspecialchars($_SESSION['usuario_correo']) : 'usuario@apitech.com';

// Crear tabla de auditoría si no existe
$conn->query("
    CREATE TABLE IF NOT EXISTS importaciones_log (
        id_importacion INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        tipo_importacion VARCHAR(50),
        nombre_archivo VARCHAR(255),
        registros_exitosos INT DEFAULT 0,
        registros_errores INT DEFAULT 0,
        contenido_archivo LONGTEXT,
        fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    )
");

// Obtener historial de importaciones
$historial = $conn->query("
    SELECT * FROM importaciones_log 
    WHERE id_usuario = $id_usuario 
    ORDER BY fecha_importacion DESC 
    LIMIT 100
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de Importaciones - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
.table-container {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow-x: auto;
    animation: slideUp 0.6s ease 0.1s both;
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
    background: rgba(255, 199, 44, 0.05);
}

tbody td {
    padding: 14px 16px;
    color: #1f2937;
    font-size: 14px;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.badge-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.badge-produccion {
    background: #cfe2ff;
    color: #084298;
}

.badge-colmenas {
    background: #d1e7f0;
    color: #055160;
}

.btn-ver {
    background: #FFC72C;
    color: #1a1a1a;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-ver:hover {
    background: #ffb700;
    transform: translateY(-2px);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    animation: fadeIn 0.3s ease;
}

.modal.active {
    display: block;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #1a1a1a;
}

.close {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    transition: all 0.3s ease;
}

.close:hover,
.close:focus {
    color: #000;
}

.contenido-archivo {
    background: #f9f9f9;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 400px;
    overflow-y: auto;
}

.resumen-importacion {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.resumen-item {
    background: #f3f4f6;
    padding: 12px;
    border-radius: 8px;
    border-left: 4px solid #FFC72C;
}

.resumen-item-label {
    font-size: 12px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
}

.resumen-item-valor {
    font-size: 24px;
    font-weight: 800;
    color: #1a1a1a;
    margin-top: 4px;
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .resumen-importacion {
        grid-template-columns: 1fr;
    }

    .modal-content {
        width: 95%;
        margin: 20% auto;
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
        <li><a href="../DASHBOARD/configuracion.php" style="display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gear"></i> Configuración</a></li>
    </ul>
</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <input class="search" placeholder="Buscar importación..." id="search">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="user-icon" id="btn-usuario">
                <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="header">
        <h1>📋 Historial de Importaciones</h1>
        <p style="color: #666; margin-top: 8px; font-size: 14px;">Historial completo de todos los archivos importados</p>
    </div>

    <div class="dashboard">
        <?php if ($historial && $historial->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Archivo</th>
                            <th>Exitosos</th>
                            <th>Errores</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-historial">
                        <?php while($row = $historial->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['fecha_importacion'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['tipo_importacion'] === 'produccion' ? 'badge-produccion' : 'badge-colmenas'; ?>">
                                        <?php echo $row['tipo_importacion'] === 'produccion' ? '📊 Producción' : '🐝 Colmenas'; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['nombre_archivo']); ?></strong></td>
                                <td>
                                    <span class="badge badge-success">
                                        ✓ <?php echo $row['registros_exitosos']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['registros_errores'] > 0): ?>
                                        <span class="badge badge-warning">
                                            ✗ <?php echo $row['registros_errores']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-ver" onclick="verContenido(this)">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </button>
                                </td>
                            </tr>
                            <tr style="display: none;" class="datos-importacion" data-exitosos="<?php echo $row['registros_exitosos']; ?>" data-errores="<?php echo $row['registros_errores']; ?>" data-contenido="<?php echo htmlspecialchars($row['contenido_archivo'], ENT_QUOTES, 'UTF-8'); ?>"></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-inbox"></i>
                <p>No hay importaciones registradas</p>
                <p style="font-size: 12px; margin-top: 12px;">
                    <a href="importar_datos.php" style="color: #FFC72C; text-decoration: none; font-weight: 600;">📤 Importar datos</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL VER CONTENIDO -->
<div id="modalContenido" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📄 Detalle de Importación</h2>
            <button class="close" onclick="cerrarModal()">&times;</button>
        </div>
        
        <div class="resumen-importacion">
            <div class="resumen-item">
                <div class="resumen-item-label">Registros Exitosos</div>
                <div class="resumen-item-valor" id="resumenExitosos">0</div>
            </div>
            <div class="resumen-item">
                <div class="resumen-item-label">Errores</div>
                <div class="resumen-item-valor" id="resumenErrores">0</div>
            </div>
            <div class="resumen-item">
                <div class="resumen-item-label">Total</div>
                <div class="resumen-item-valor" id="resumenTotal">0</div>
            </div>
        </div>

        <h4 style="margin-top: 20px; margin-bottom: 12px; color: #1a1a1a;">Contenido del Archivo:</h4>
        <div class="contenido-archivo" id="contenidoArchivo"></div>
    </div>
</div>

<!-- MODAL DE USUARIO -->
<div id="modal-usuario" class="modal-usuario">
    <div class="modal-usuario-header">
        <div class="avatar">
            <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
        </div>
        <h3><?php echo htmlspecialchars($usuario_nombre); ?></h3>
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
    </div>
    
    <div class="modal-usuario-footer">
        <button class="btn-logout" onclick="cerrarSesion()">
            <i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
        </button>
    </div>
</div>

<script>
const modal = document.getElementById('modalContenido');

function verContenido(btn) {
    // Obtener la fila de datos oculta siguiente
    const fila = btn.closest('tr');
    const datosRow = fila.nextElementSibling;
    
    if (datosRow && datosRow.classList.contains('datos-importacion')) {
        const exitosos = datosRow.dataset.exitosos;
        const errores = datosRow.dataset.errores;
        const contenido = datosRow.dataset.contenido;
        
        document.getElementById('resumenExitosos').textContent = exitosos;
        document.getElementById('resumenErrores').textContent = errores;
        document.getElementById('resumenTotal').textContent = (parseInt(exitosos) + parseInt(errores));
        document.getElementById('contenidoArchivo').textContent = contenido || 'No disponible';
        
        modal.classList.add('active');
    }
}

function cerrarModal() {
    modal.classList.remove('active');
}

window.onclick = function(event) {
    if (event.target == modal) {
        modal.classList.remove('active');
    }
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

// Búsqueda
const searchInput = document.getElementById('search');
if (searchInput) {
    searchInput.addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('#tbody-historial tr:not(.datos-importacion)').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
}

console.log('✓ Página de historial cargada');
</script>

</body>
</html>