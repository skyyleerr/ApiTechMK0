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

// Obtener colmenas del usuario para la plantilla
$colmenas = $conn->query("
    SELECT id_colmena, nombre 
    FROM colmenas 
    WHERE id_usuario = $id_usuario 
    ORDER BY id_colmena ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Descargar Plantillas - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/dashboard.css">
<style>
.plantillas-container {
    animation: slideUp 0.6s ease both;
    max-width: 1200px;
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

.plantillas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.plantilla-card {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.plantilla-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    border-color: #FFC72C;
}

.plantilla-header {
    padding: 24px;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    text-align: center;
}

.plantilla-icon {
    font-size: 48px;
    margin-bottom: 12px;
}

.plantilla-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0;
}

.plantilla-body {
    padding: 24px;
}

.plantilla-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.6;
}

.plantilla-info {
    background: #f9f9f9;
    border-left: 4px solid #3b82f6;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #1d4ed8;
}

.plantilla-info strong {
    display: block;
    margin-bottom: 4px;
}

.plantilla-preview {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 16px;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    overflow-x: auto;
    white-space: pre-wrap;
    word-break: break-all;
    color: #374151;
}

.plantilla-buttons {
    display: flex;
    gap: 10px;
}

.btn-descargar {
    flex: 1;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.btn-descargar:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(255, 199, 44, 0.3);
}

.btn-ver-ejemplo {
    flex: 1;
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 13px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-ver-ejemplo:hover {
    background: #d1d5db;
    transform: translateY(-2px);
}

.ejemplo-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.ejemplo-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.ejemplo-content {
    background: white;
    padding: 30px;
    border-radius: 14px;
    width: 90%;
    max-width: 900px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.ejemplo-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

.ejemplo-header h2 {
    margin: 0;
    font-size: 20px;
    color: #1a1a1a;
}

.close-modal {
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    transition: all 0.3s ease;
}

.close-modal:hover {
    color: #000;
}

.tabla-ejemplo {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.tabla-ejemplo thead {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
}

.tabla-ejemplo th {
    padding: 12px;
    text-align: left;
    font-weight: 700;
    color: #374151;
    font-size: 13px;
    border-bottom: 2px solid #FFC72C;
}

.tabla-ejemplo td {
    padding: 10px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.tabla-ejemplo tbody tr:hover {
    background: rgba(255, 199, 44, 0.05);
}

.recomendaciones {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
    margin-top: 40px;
    border-left: 4px solid #10b981;
}

.recomendaciones h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
}

.recomendaciones ul {
    margin: 0;
    padding-left: 20px;
}

.recomendaciones li {
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
    line-height: 1.6;
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

@media (max-width: 768px) {
    .plantillas-grid {
        grid-template-columns: 1fr;
    }

    .plantilla-buttons {
        flex-direction: column;
    }

    .btn-descargar,
    .btn-ver-ejemplo {
        width: 100%;
    }

    .ejemplo-content {
        width: 95%;
        max-height: 90vh;
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
        <input class="search" placeholder="Buscar..." id="search">
        <div style="display: flex; gap: 20px; align-items: center;">
            <div class="user-icon" id="btn-usuario">
                <?php echo strtoupper(substr($usuario_nombre, 0, 1)); ?>
            </div>
        </div>
    </div>

    <div class="header">
        <h1>📋 Descargar Plantillas</h1>
        <p style="color: #666; margin-top: 8px; font-size: 14px;">Plantillas CSV profesionales para importar datos fácilmente</p>
    </div>

    <div class="dashboard">
        <div class="plantillas-container">

            <div class="plantillas-grid">

                <!-- PLANTILLA PRODUCCIÓN -->
                <div class="plantilla-card">
                    <div class="plantilla-header">
                        <div class="plantilla-icon">📊</div>
                        <h3 class="plantilla-title">Producción</h3>
                    </div>
                    
                    <div class="plantilla-body">
                        <p class="plantilla-description">
                            Descarga esta plantilla profesional para registrar la producción de miel de tus colmenas de forma masiva.
                        </p>
                        
                        <div class="plantilla-info">
                            <strong>📌 Columnas:</strong>
                            ID Colmena | Cantidad Miel (kg) | Fecha | Notas
                        </div>

                        <div class="plantilla-preview">ID Colmena: 1, 2, 3, 4, 5
Cantidad: 25.5, 18.3, 22.1, 28.9, 15.2
Fecha: 2024-06-07, 2024-06-08, ...</div>

                        <div class="plantilla-buttons">
                            <a href="generar_plantilla_excel.php?tipo=produccion" class="btn-descargar" download="Plantilla_Produccion.csv">
                                <i class="fa-solid fa-file-csv"></i> Descargar CSV
                            </a>
                            <button class="btn-ver-ejemplo" onclick="verEjemploProduccion()">
                                <i class="fa-solid fa-eye"></i> Ver Ejemplo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PLANTILLA COLMENAS -->
                <div class="plantilla-card">
                    <div class="plantilla-header">
                        <div class="plantilla-icon">🐝</div>
                        <h3 class="plantilla-title">Colmenas</h3>
                    </div>
                    
                    <div class="plantilla-body">
                        <p class="plantilla-description">
                            Descarga esta plantilla para crear nuevas colmenas en tu apiario de forma rápida y eficiente.
                        </p>
                        
                        <div class="plantilla-info">
                            <strong>📌 Columnas:</strong>
                            Nombre | Ubicación | Estado | Notas
                        </div>

                        <div class="plantilla-preview">Nombre: Colmena Principal, Colmena 2
Ubicación: Jardín Trasero, Huerto
Estado: Estable, Estable</div>

                        <div class="plantilla-buttons">
                            <a href="generar_plantilla_excel.php?tipo=colmenas" class="btn-descargar" download="Plantilla_Colmenas.csv">
                                <i class="fa-solid fa-file-csv"></i> Descargar CSV
                            </a>
                            <button class="btn-ver-ejemplo" onclick="verEjemploColmenas()">
                                <i class="fa-solid fa-eye"></i> Ver Ejemplo
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RECOMENDACIONES -->
            <div class="recomendaciones">
                <h3><i class="fa-solid fa-lightbulb" style="margin-right: 8px; color: #FFC72C;"></i>✨ Recomendaciones Importantes</h3>
                <ul>
                    <li><strong>✓ Formato CSV:</strong> Las plantillas se descargan en formato .csv (compatible con Excel y LibreOffice)</li>
                    <li><strong>✓ Datos pre-cargados:</strong> Incluyen ejemplos realistas que puedes eliminar y reemplazar</li>
                    <li><strong>✓ Instrucciones incluidas:</strong> Cada plantilla incluye las reglas de validación</li>
                    <li><strong>✓ Fechas:</strong> Usa el formato YYYY-MM-DD (ejemplo: 2024-06-07)</li>
                    <li><strong>✓ Cantidad de miel:</strong> Debe estar entre 0 y 1000 kg</li>
                    <li><strong>✓ Estados válidos:</strong> Solo se aceptan: Estable, Advertencia, Problema</li>
                    <li><strong>✓ Nombres únicos:</strong> No puedes importar colmenas con nombres duplicados</li>
                    <li><strong>✓ Tamaño máximo:</strong> El archivo no puede superar 5MB</li>
                    <li><strong>✓ Columnas obligatorias:</strong> No agregues ni elimines columnas, solo llena los datos</li>
                    <li><strong>✓ Abre en Excel:</strong> Haz clic derecho en el archivo descargado y selecciona "Abrir con Excel"</li>
                </ul>
            </div>

        </div>
    </div>

</div>

<!-- MODAL EJEMPLO PRODUCCIÓN -->
<div id="modalProduccion" class="ejemplo-modal">
    <div class="ejemplo-content">
        <div class="ejemplo-header">
            <h2>📊 Ejemplo: Plantilla de Producción</h2>
            <button class="close-modal" onclick="cerrarModalProduccion()">&times;</button>
        </div>
        
        <p style="color: #666; margin-bottom: 16px;">
            A continuación se muestra cómo debe verse correctamente un archivo de producción. 
            Cada fila representa un registro de producción para una colmena.
        </p>

        <table class="tabla-ejemplo">
            <thead>
                <tr>
                    <th>ID Colmena</th>
                    <th>Cantidad Miel (kg)</th>
                    <th>Fecha (YYYY-MM-DD)</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>25.5</td>
                    <td>2024-06-07</td>
                    <td>ID de tu colmena registrada</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>18.3</td>
                    <td>2024-06-08</td>
                    <td>Cantidad entre 0 y 1000 kg</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>22.1</td>
                    <td>2024-06-09</td>
                    <td>Formato YYYY-MM-DD</td>
                </tr>
                <tr>
                    <td>1</td>
                    <td>28.9</td>
                    <td>2024-06-10</td>
                    <td>Puedes registrar múltiples veces</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>15.2</td>
                    <td>2024-06-11</td>
                    <td>La misma fecha con diferente colmena</td>
                </tr>
            </tbody>
        </table>

        <div style="background: #f9f9f9; padding: 16px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #3b82f6;">
            <strong style="color: #1d4ed8; display: block; margin-bottom: 8px;">💡 Consejos:</strong>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #666;">
                <li>La primera fila DEBE ser el encabezado</li>
                <li>No dejes filas vacías entre registros</li>
                <li>Verifica que los IDs de colmenas existan en tu apiario</li>
                <li>Las fechas deben ser válidas (no fechas futuras lejanas)</li>
                <li>Usa punto (.) para decimales, no coma (,)</li>
            </ul>
        </div>
    </div>
</div>

<!-- MODAL EJEMPLO COLMENAS -->
<div id="modalColmenas" class="ejemplo-modal">
    <div class="ejemplo-content">
        <div class="ejemplo-header">
            <h2>🐝 Ejemplo: Plantilla de Colmenas</h2>
            <button class="close-modal" onclick="cerrarModalColmenas()">&times;</button>
        </div>
        
        <p style="color: #666; margin-bottom: 16px;">
            A continuación se muestra cómo debe verse correctamente un archivo de colmenas. 
            Cada fila representa una nueva colmena a crear.
        </p>

        <table class="tabla-ejemplo">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Colmena Principal</td>
                    <td>Jardín Trasero</td>
                    <td>Estable</td>
                    <td>Nombre descriptivo</td>
                </tr>
                <tr>
                    <td>Colmena 2</td>
                    <td>Huerto</td>
                    <td>Estable</td>
                    <td>Entre 3 y 100 caracteres</td>
                </tr>
                <tr>
                    <td>Colmena de Prueba</td>
                    <td>Terraza</td>
                    <td>Advertencia</td>
                    <td>Ubicación es opcional</td>
                </tr>
                <tr>
                    <td>Colmena Experimental</td>
                    <td>Patio Frontal</td>
                    <td>Problema</td>
                    <td>Estados: Estable, Advertencia, Problema</td>
                </tr>
                <tr>
                    <td>Nueva Colmena</td>
                    <td>Zona Nueva</td>
                    <td>Estable</td>
                    <td>Sin espacios en blanco extra</td>
                </tr>
            </tbody>
        </table>

        <div style="background: #f9f9f9; padding: 16px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #3b82f6;">
            <strong style="color: #1d4ed8; display: block; margin-bottom: 8px;">💡 Consejos:</strong>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #666;">
                <li>La primera fila DEBE ser el encabezado</li>
                <li>El nombre es obligatorio y único (no pueden haber duplicados)</li>
                <li>La ubicación es opcional pero recomendada</li>
                <li>Solo acepta estos estados: Estable, Advertencia, Problema</li>
                <li>No uses comillas ni caracteres especiales en los nombres</li>
            </ul>
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
    </div>
    
    <div class="modal-usuario-body">
        <a href="../DASHBOARD/configuracion.php" class="modal-usuario-item">
            <i class="fa-solid fa-user-circle"></i>
            <span>Mi Perfil</span>
        </a>
        
        <a href="../DASHBOARD/importar_datos.php" class="modal-usuario-item">
            <i class="fa-solid fa-upload"></i>
            <span>Importar Datos</span>
        </a>
    </div>
    
    <div class="modal-usuario-footer">
        <button class="btn-logout" onclick="cerrarSesion()">
            <i class="fa-solid fa-sign-out-alt"></i> Cerrar Sesión
        </button>
    </div>
</div>

<script>
function cerrarSesion() {
    if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
        window.location.href = '../LOGIN/logout.php';
    }
}

function verEjemploProduccion() {
    document.getElementById('modalProduccion').classList.add('active');
}

function cerrarModalProduccion() {
    document.getElementById('modalProduccion').classList.remove('active');
}

function verEjemploColmenas() {
    document.getElementById('modalColmenas').classList.add('active');
}

function cerrarModalColmenas() {
    document.getElementById('modalColmenas').classList.remove('active');
}

// Cerrar modales al hacer clic afuera
window.onclick = function(event) {
    const modalProd = document.getElementById('modalProduccion');
    const modalCol = document.getElementById('modalColmenas');
    if (event.target == modalProd) {
        modalProd.classList.remove('active');
    }
    if (event.target == modalCol) {
        modalCol.classList.remove('active');
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

console.log('✓ Centro de plantillas cargado');
</script>

</body>
</html>