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

// Obtener estadísticas
$stats = $conn->query("
    SELECT 
        COUNT(c.id_colmena) as total_colmenas,
        COUNT(DISTINCT p.id_colmena) as colmenas_produciendo,
        COALESCE(SUM(p.cantidad_miel), 0) as produccion_total,
        COALESCE(AVG(p.cantidad_miel), 0) as produccion_promedio,
        COALESCE(MAX(p.cantidad_miel), 0) as produccion_maxima,
        COALESCE(MIN(p.cantidad_miel), 0) as produccion_minima
    FROM colmenas c
    LEFT JOIN produccion p ON c.id_colmena = p.id_colmena
    WHERE c.id_usuario = $id_usuario
");

$stats_row = $stats->fetch_assoc();

// Obtener colmenas con producción
$colmenas = $conn->query("
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
$produccion = $conn->query("
    SELECT 
        p.cantidad_miel,
        p.fecha,
        c.nombre
    FROM produccion p
    INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
    WHERE c.id_usuario = $id_usuario
    ORDER BY p.fecha DESC
    LIMIT 100
");

// Crear PDF sin dependencias externas usando FPDF
class SimplePDF {
    private $width;
    private $height;
    private $y;
    private $x;
    private $font_size;
    private $content;
    
    public function __construct() {
        $this->width = 210;  // A4 width in mm
        $this->height = 297; // A4 height in mm
        $this->y = 10;
        $this->x = 10;
        $this->font_size = 12;
        $this->content = "%PDF-1.4\n";
    }
    
    public function output() {
        return $this->content;
    }
}

// Alternativa: Crear HTML y mostrar como PDF usando navegador
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte ApiTech</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @media print {
            body {
                background: white;
            }
            .print-button {
                display: none;
            }
            .page-break {
                page-break-after: always;
            }
        }
        
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #FFC72C;
            color: #1a1a1a;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #ffb700;
        }
        
        .page-break {
            page-break-after: always;
            margin: 40px 0;
            padding: 40px 0;
            border-top: 1px dashed #ccc;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #FFC72C;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #1a1a1a;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 12px;
        }
        
        .logo-text {
            color: #FFC72C;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .section-title {
            background-color: #FFC72C;
            color: #1a1a1a;
            padding: 10px 15px;
            margin: 25px 0 15px 0;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #f9f9f9;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }
        
        .stat-unit {
            font-size: 12px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        table th {
            background-color: #FFC72C;
            color: #1a1a1a;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #FFC72C;
        }
        
        table td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        
        .info-box {
            background: #f0f4f8;
            border-left: 4px solid #3b82f6;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 11px;
            color: #1e3a8a;
        }
        
        .estado-estable {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .estado-advertencia {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .estado-critico {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="imprimirPDF()">
        <i class="fa-solid fa-download"></i> Descargar PDF
    </button>
    
    <div class="container">
        <!-- PÁGINA 1: PORTADA Y ESTADÍSTICAS -->
        <div class="header">
            <div class="logo-text">🐝 ApiTech</div>
            <h1>REPORTE DEL APIARIO</h1>
            <p>Generado: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Usuario: <?php echo htmlspecialchars($usuario_nombre); ?></p>
        </div>
        
        <div class="info-box">
            <strong>ℹ️ Información del Reporte:</strong><br>
            Este reporte contiene un resumen completo de tu apiario, incluyendo estadísticas de colmenas, 
            producción de miel y análisis detallado de cada unidad. Los datos se actualizan automáticamente 
            mediante los sensores integrados en el sistema.
        </div>
        
        <div class="section-title">📊 Estadísticas Generales</div>
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total de Colmenas</div>
                <div class="stat-value"><?php echo $stats_row['total_colmenas']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Colmenas Produciendo</div>
                <div class="stat-value"><?php echo $stats_row['colmenas_produciendo']; ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Producción Total</div>
                <div class="stat-value"><?php echo round($stats_row['produccion_total'], 1); ?> <span class="stat-unit">kg</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Promedio por Colmena</div>
                <div class="stat-value"><?php echo round($stats_row['produccion_promedio'], 1); ?> <span class="stat-unit">kg</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Producción Máxima</div>
                <div class="stat-value"><?php echo round($stats_row['produccion_maxima'], 1); ?> <span class="stat-unit">kg</span></div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Producción Mínima</div>
                <div class="stat-value"><?php echo round($stats_row['produccion_minima'], 1); ?> <span class="stat-unit">kg</span></div>
            </div>
        </div>
        
        <div class="page-break"></div>
        
        <!-- PÁGINA 2: DETALLE DE COLMENAS -->
        <div class="header">
            <h1>DETALLE DE COLMENAS</h1>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Colmena</th>
                    <th>Ubicación</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Registros</th>
                    <th>Producción (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $colmenas->data_seek(0);
                while ($col = $colmenas->fetch_assoc()) {
                    $estado_class = 'estado-estable';
                    if ($col['estado'] === 'Advertencia') {
                        $estado_class = 'estado-advertencia';
                    } elseif ($col['estado'] === 'Crítico') {
                        $estado_class = 'estado-critico';
                    }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($col['nombre']); ?></strong></td>
                    <td><?php echo htmlspecialchars($col['ubicacion']); ?></td>
                    <td><span class="<?php echo $estado_class; ?>"><?php echo htmlspecialchars($col['estado']); ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($col['fecha_creacion'])); ?></td>
                    <td><?php echo $col['registros_produccion']; ?></td>
                    <td><?php echo round($col['total_produccion'], 2); ?> kg</td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <div class="page-break"></div>
        
        <!-- PÁGINA 3: HISTORIAL DE PRODUCCIÓN -->
        <div class="header">
            <h1>HISTORIAL DE PRODUCCIÓN</h1>
            <p>Últimos 100 registros</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Colmena</th>
                    <th>Producción (kg)</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $produccion->data_seek(0);
                while ($prod = $produccion->fetch_assoc()) {
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                    <td><?php echo round($prod['cantidad_miel'], 2); ?> kg</td>
                    <td><?php echo date('d/m/Y', strtotime($prod['fecha'])); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p>© <?php echo date('Y'); ?> ApiTech - Sistema de Monitoreo de Apiarios</p>
            <p>Este reporte fue generado automáticamente y contiene información confidencial</p>
        </div>
    </div>

    <script>
        function imprimirPDF() {
            // Obtener el nombre del archivo
            const fecha = new Date();
            const nombre = 'reporte_apitech_' + fecha.getFullYear() + '-' + 
                          String(fecha.getMonth() + 1).padStart(2, '0') + '-' + 
                          String(fecha.getDate()).padStart(2, '0');
            
            // Usar la función de impresión del navegador
            const ventana = window.open('', '_blank');
            ventana.document.write(document.documentElement.innerHTML);
            ventana.document.close();
            
            // Esperar a que cargue y luego imprimir
            setTimeout(() => {
                ventana.focus();
                ventana.print();
                // El navegador abrirá el diálogo de impresión
                // El usuario puede elegir guardar como PDF
            }, 500);
        }
    </script>
</body>
</html>
<?php
?>
