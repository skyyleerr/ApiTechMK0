<?php
/**
 * Script para generar reportes en CSV
 * Descarga un archivo CSV con estadísticas y datos de colmenas
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
            COALESCE(AVG(p.cantidad_miel), 0) as produccion_promedio
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
            COALESCE(SUM(p.cantidad_miel), 0) as total_produccion,
            COUNT(p.id_produccion) as registros_produccion
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
            p.id_produccion,
            p.cantidad_miel,
            p.fecha,
            c.nombre,
            c.id_colmena
        FROM produccion p
        INNER JOIN colmenas c ON p.id_colmena = c.id_colmena
        WHERE c.id_usuario = $id_usuario
        ORDER BY p.fecha DESC
        LIMIT 100
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

    if (!$alertas) {
        $alertas_count = 0;
    } else {
        $alertas_count = $alertas->num_rows;
    }

    // Configurar headers para descarga de CSV
    $filename = 'reporte_apitech_' . strtolower(str_replace(' ', '_', $usuario_nombre)) . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // BOM para UTF-8 (asegura que Excel interprete bien los acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // ========== SECCIÓN: ENCABEZADO GENERAL ==========
    fputcsv($output, ['REPORTE APITECH'], ';');
    fputcsv($output, ['Usuario: ' . $usuario_nombre], ';');
    fputcsv($output, ['Generado: ' . date('d/m/Y H:i:s')], ';');
    fputcsv($output, ['Versión: 1.0.0'], ';');
    fputcsv($output, [], ';');

    // ========== SECCIÓN: ESTADÍSTICAS GENERALES ==========
    fputcsv($output, ['ESTADÍSTICAS GENERALES'], ';');
    fputcsv($output, ['Métrica', 'Valor'], ';');
    fputcsv($output, ['Total de Colmenas', $stats_row['total_colmenas']], ';');
    fputcsv($output, ['Colmenas Produciendo', $stats_row['colmenas_produciendo']], ';');
    fputcsv($output, ['Producción Total (kg)', round($stats_row['produccion_total'], 2)], ';');
    fputcsv($output, ['Promedio por Colmena (kg)', round($stats_row['produccion_promedio'], 2)], ';');
    fputcsv($output, ['Alertas Activas', $alertas_count], ';');
    fputcsv($output, [], ';');

    // ========== SECCIÓN: DETALLE DE COLMENAS ==========
    fputcsv($output, ['DETALLE DE COLMENAS'], ';');
    fputcsv($output, ['Nombre', 'Ubicación', 'Estado', 'Fecha Creación', 'Producción Total (kg)', 'Registros'], ';');

    $colmenas->data_seek(0);
    while ($col = $colmenas->fetch_assoc()) {
        fputcsv($output, [
            htmlspecialchars_decode($col['nombre']),
            htmlspecialchars_decode($col['ubicacion'] ?? '-'),
            ucfirst($col['estado']),
            date('d/m/Y', strtotime($col['fecha_creacion'])),
            round($col['total_produccion'], 2),
            $col['registros_produccion']
        ], ';');
    }

    fputcsv($output, [], ';');

    // ========== SECCIÓN: HISTORIAL DE PRODUCCIÓN ==========
    if ($produccion->num_rows > 0) {
        fputcsv($output, ['HISTORIAL DE PRODUCCIÓN'], ';');
        fputcsv($output, ['Colmena', 'Cantidad (kg)', 'Fecha'], ';');

        $produccion->data_seek(0);
        while ($prod = $produccion->fetch_assoc()) {
            fputcsv($output, [
                htmlspecialchars_decode($prod['nombre']),
                round($prod['cantidad_miel'], 2),
                date('d/m/Y', strtotime($prod['fecha']))
            ], ';');
        }

        fputcsv($output, [], ';');
    }

    // ========== SECCIÓN: ALERTAS ACTIVAS ==========
    if ($alertas && $alertas_count > 0) {
        fputcsv($output, ['ALERTAS ACTIVAS'], ';');
        fputcsv($output, ['Colmena', 'Tipo', 'Descripción', 'Fecha'], ';');

        $alertas->data_seek(0);
        while ($alerta = $alertas->fetch_assoc()) {
            fputcsv($output, [
                htmlspecialchars_decode($alerta['nombre']),
                ucfirst($alerta['tipo']),
                htmlspecialchars_decode($alerta['descripcion']),
                date('d/m/Y H:i:s', strtotime($alerta['fecha']))
            ], ';');
        }

        fputcsv($output, [], ';');
    }

    // ========== SECCIÓN: PIE ==========
    fputcsv($output, [], ';');
    fputcsv($output, ['INFORMACIÓN'], ';');
    fputcsv($output, ['Este reporte fue generado automáticamente por ApiTech'], ';');
    fputcsv($output, ['Para más información, visita: https://apitech.com'], ';');
    fputcsv($output, ['© 2026 ApiTech - Todos los derechos reservados'], ';');

    fclose($output);
    exit();

} catch (Exception $e) {
    error_log("Error en generar_csv.php: " . $e->getMessage());
    header("Location: reportes.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
