<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'produccion';

if ($tipo === 'produccion') {
    $nombre_archivo = 'plantilla_produccion_' . date('Y-m-d') . '.csv';
    $contenido = "id_colmena,cantidad_miel,fecha\n";
    $contenido .= "1,25.5,2024-06-07\n";
    $contenido .= "2,18.3,2024-06-08\n";
    $contenido .= "3,22.1,2024-06-09\n";
    $contenido .= "4,15.8,2024-06-10\n";
    $contenido .= "5,28.9,2024-06-11\n";
} elseif ($tipo === 'colmenas') {
    $nombre_archivo = 'plantilla_colmenas_' . date('Y-m-d') . '.csv';
    $contenido = "nombre,ubicacion,estado\n";
    $contenido .= "Colmena Principal,Jardín Trasero,Estable\n";
    $contenido .= "Colmena 2,Huerto,Estable\n";
    $contenido .= "Colmena 3,Terraza,Advertencia\n";
    $contenido .= "Colmena 4,Patio Frontal,Estable\n";
    $contenido .= "Colmena 5,Zona Nueva,Estable\n";
} else {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

// Sin BOM para UTF-8
echo $contenido;
exit;
?>