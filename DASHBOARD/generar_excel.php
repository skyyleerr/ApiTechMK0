<?php
/**
 * Script para generar reportes en Excel (XML)
 * Descarga un archivo Excel con estadísticas y datos de colmenas
 */

session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

try {
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

    if (!$stats) {
        throw new Exception("Error en consulta de estadísticas: " . $conn->error);
    }

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
        ORDER BY c.nombre ASC
    ");

    if (!$colmenas) {
        throw new Exception("Error en consulta de colmenas: " . $conn->error);
    }

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
        LIMIT 200
    ");

    if (!$produccion) {
        throw new Exception("Error en consulta de producción: " . $conn->error);
    }

    // Obtener alertas activas
    $alertas = $conn->query("
        SELECT 
            a.tipo,
            a.descripcion,
            a.fecha,
            c.nombre
        FROM alertas a
        INNER JOIN colmenas c ON a.id_colmena = c.id_colmena
        WHERE c.id_usuario = $id_usuario AND a.estado = 'activa'
        ORDER BY a.fecha DESC
        LIMIT 50
    ");

    $alertas_count = ($alertas && $alertas->num_rows > 0) ? $alertas->num_rows : 0;

    // Configurar headers para descarga de Excel
    $filename = 'reporte_apitech_' . strtolower(str_replace(' ', '_', $usuario_nombre)) . '_' . date('Y-m-d_H-i-s') . '.xlsx';
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

    // Crear documento XML Excel
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">
  <Title>Reporte ApiTech</Title>
  <Subject>Reporte de Colmenas y Producción</Subject>
  <Author>ApiTech</Author>
  <LastAuthor>ApiTech</LastAuthor>
  <Created>' . date('Y-m-dT00:00:00Z') . '</Created>
  <LastSaved>' . date('Y-m-dT00:00:00Z') . '</LastSaved>
  <Company>ApiTech</Company>
  <Version>16.00</Version>
 </DocumentProperties>
 <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
  <WindowHeight>9000</WindowHeight>
  <WindowWidth>13860</WindowWidth>
  <WindowTopX>240</WindowTopX>
  <WindowTopY>75</WindowTopY>
  <ProtectStructure>False</ProtectStructure>
  <ProtectWindows>False</ProtectWindows>
  <TabRatio>600</TabRatio>
 </ExcelWorkbook>
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Horizontal="Left" ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="Title" ss:Name="Título">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="16" ss:Bold="1" ss:Color="#FFC72C"/>
   <Interior ss:Color="#1a1a1a" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Left" ss:Vertical="Center" ss:WrapText="1"/>
  </Style>
  <Style ss:ID="SubTitle" ss:Name="Subtítulo">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Italic="1" ss:Color="#666666"/>
  </Style>
  <Style ss:ID="Header" ss:Name="Encabezado">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="12" ss:Bold="1" ss:Color="#FFFFFF"/>
   <Interior ss:Color="#FFC72C" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#000000"/>
   </Borders>
  </Style>
  <Style ss:ID="Data" ss:Name="Dato">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
   </Borders>
   <Alignment ss:Horizontal="Left" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="DataCenter" ss:Name="DatoCentrado">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
   </Borders>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <NumberFormat ss:Format="0.00"/>
  </Style>
  <Style ss:ID="Warning" ss:Name="Alerta">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#DC2626"/>
   <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CCCCCC"/>
   </Borders>
  </Style>
 </Styles>

 <!-- HOJA 1: RESUMEN Y ESTADÍSTICAS -->
 <Worksheet ss:Name="Resumen">
  <Table>
   <Column ss:Width="200"/>
   <Column ss:Width="150"/>
   <Column ss:Width="150"/>
   <Column ss:Width="150"/>
   <Column ss:Width="150"/>
   <Row ss:Height="30">
    <Cell ss:StyleID="Title"><Data ss:Type="String">REPORTE APITECH - ' . strtoupper($usuario_nombre) . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="SubTitle"><Data ss:Type="String">Generado: ' . date('d/m/Y H:i:s') . '</Data></Cell>
   </Row>
   <Row/>
   <Row ss:Height="25">
    <Cell ss:StyleID="Header"><Data ss:Type="String">ESTADÍSTICAS GENERALES</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Total de Colmenas</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $stats_row['total_colmenas'] . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Colmenas Produciendo</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $stats_row['colmenas_produciendo'] . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Producción Total (kg)</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . round($stats_row['produccion_total'], 2) . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Promedio por Colmena (kg)</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . round($stats_row['produccion_promedio'], 2) . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Producción Máxima (kg)</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . round($stats_row['produccion_maxima'], 2) . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Producción Mínima (kg)</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . round($stats_row['produccion_minima'], 2) . '</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">Alertas Activas</Data></Cell>
    <Cell ss:StyleID="' . ($alertas_count > 0 ? 'Warning' : 'DataCenter') . '"><Data ss:Type="Number">' . $alertas_count . '</Data></Cell>
   </Row>
   <Row/>
   <Row ss:Height="25">
    <Cell ss:StyleID="Header"><Data ss:Type="String">Nombre</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Ubicación</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Estado</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha Creación</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Total Producción (kg)</Data></Cell>
   </Row>';

    // Agregar colmenas
    $colmenas->data_seek(0);
    while ($col = $colmenas->fetch_assoc()) {
        $nombre = htmlspecialchars($col['nombre'] ?? '-');
        $ubicacion = htmlspecialchars($col['ubicacion'] ?? '-');
        $estado = htmlspecialchars($col['estado'] ?? '-');
        $fecha = date('d/m/Y', strtotime($col['fecha_creacion']));
        $produccion_total = round($col['total_produccion'], 2);

        $xml .= '
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . $nombre . '</Data></Cell>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . $ubicacion . '</Data></Cell>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . ucfirst($estado) . '</Data></Cell>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . $fecha . '</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $produccion_total . '</Data></Cell>
   </Row>';
    }

    $xml .= '
  </Table>
 </Worksheet>

 <!-- HOJA 2: HISTORIAL DE PRODUCCIÓN -->
 <Worksheet ss:Name="Historial">
  <Table>
   <Column ss:Width="200"/>
   <Column ss:Width="150"/>
   <Column ss:Width="150"/>
   <Row ss:Height="30">
    <Cell ss:StyleID="Title"><Data ss:Type="String">HISTORIAL DE PRODUCCIÓN</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="SubTitle"><Data ss:Type="String">Últimos 200 registros - ' . date('d/m/Y') . '</Data></Cell>
   </Row>
   <Row/>
   <Row ss:Height="25">
    <Cell ss:StyleID="Header"><Data ss:Type="String">Colmena</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Producción (kg)</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell>
   </Row>';

    // Agregar historial de producción
    $produccion->data_seek(0);
    while ($prod = $produccion->fetch_assoc()) {
        $nombre = htmlspecialchars($prod['nombre']);
        $cantidad = round($prod['cantidad_miel'], 2);
        $fecha = date('d/m/Y', strtotime($prod['fecha']));

        $xml .= '
   <Row>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . $nombre . '</Data></Cell>
    <Cell ss:StyleID="DataCenter"><Data ss:Type="Number">' . $cantidad . '</Data></Cell>
    <Cell ss:StyleID="Data"><Data ss:Type="String">' . $fecha . '</Data></Cell>
   </Row>';
    }

    $xml .= '
  </Table>
 </Worksheet>';

    // Agregar hoja de alertas si existen
    if ($alertas && $alertas_count > 0) {
        $xml .= '
 <!-- HOJA 3: ALERTAS ACTIVAS -->
 <Worksheet ss:Name="Alertas">
  <Table>
   <Column ss:Width="200"/>
   <Column ss:Width="150"/>
   <Column ss:Width="300"/>
   <Column ss:Width="150"/>
   <Row ss:Height="30">
    <Cell ss:StyleID="Title"><Data ss:Type="String">ALERTAS ACTIVAS</Data></Cell>
   </Row>
   <Row/>
   <Row ss:Height="25">
    <Cell ss:StyleID="Header"><Data ss:Type="String">Colmena</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Tipo</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Descripción</Data></Cell>
    <Cell ss:StyleID="Header"><Data ss:Type="String">Fecha</Data></Cell>
   </Row>';

        $alertas->data_seek(0);
        while ($alerta = $alertas->fetch_assoc()) {
            $nombre = htmlspecialchars($alerta['nombre']);
            $tipo = htmlspecialchars($alerta['tipo']);
            $descripcion = htmlspecialchars($alerta['descripcion']);
            $fecha = date('d/m/Y H:i:s', strtotime($alerta['fecha']));

            $xml .= '
   <Row>
    <Cell ss:StyleID="Warning"><Data ss:Type="String">' . $nombre . '</Data></Cell>
    <Cell ss:StyleID="Warning"><Data ss:Type="String">' . ucfirst($tipo) . '</Data></Cell>
    <Cell ss:StyleID="Warning"><Data ss:Type="String">' . $descripcion . '</Data></Cell>
    <Cell ss:StyleID="Warning"><Data ss:Type="String">' . $fecha . '</Data></Cell>
   </Row>';
        }

        $xml .= '
  </Table>
 </Worksheet>';
    }

    $xml .= '
</Workbook>';

    echo $xml;
    exit();

} catch (Exception $e) {
    error_log("Error en generar_excel.php: " . $e->getMessage());
    header("Location: reportes.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
