<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'produccion';

if ($tipo === 'produccion') {
    $nombre_archivo = 'Plantilla_Produccion_' . date('Y-m-d') . '.csv';
    
    $contenido = "ID Colmena,Cantidad Miel (kg),Fecha (YYYY-MM-DD),Notas\n";
    $contenido .= "1,25.5,2024-06-07,Producción normal\n";
    $contenido .= "2,18.3,2024-06-08,Producción baja\n";
    $contenido .= "3,22.1,2024-06-09,Producción normal\n";
    $contenido .= "4,28.9,2024-06-10,Producción excelente\n";
    $contenido .= "5,15.2,2024-06-11,Colmena nueva\n";
    $contenido .= "\n";
    $contenido .= "INSTRUCCIONES:\n";
    $contenido .= "- ID Colmena: Número identificador de tu colmena (debe existir en ApiTech)\n";
    $contenido .= "- Cantidad Miel: Entre 0 y 1000 kg\n";
    $contenido .= "- Fecha: Formato YYYY-MM-DD (ejemplo: 2024-06-07)\n";
    $contenido .= "- Notas: Campo opcional para observaciones\n";
    $contenido .= "- No elimines los encabezados de la primera fila\n";
    $contenido .= "- Usa punto (.) para decimales\n";
    $contenido .= "- Completa todos los campos requeridos\n";
    
} elseif ($tipo === 'colmenas') {
    $nombre_archivo = 'Plantilla_Colmenas_' . date('Y-m-d') . '.csv';
    
    $contenido = "Nombre,Ubicación,Estado,Notas\n";
    $contenido .= "Colmena Principal,Jardín Trasero,Estable,Primera colmena\n";
    $contenido .= "Colmena 2,Huerto,Estable,En buen estado\n";
    $contenido .= "Colmena 3,Terraza,Advertencia,Requiere inspección\n";
    $contenido .= "Colmena 4,Patio Frontal,Estable,Nueva colmena\n";
    $contenido .= "Colmena 5,Zona Nueva,Problema,En observación\n";
    $contenido .= "\n";
    $contenido .= "INSTRUCCIONES:\n";
    $contenido .= "- Nombre: Identificador único (3-100 caracteres)\n";
    $contenido .= "- Ubicación: Donde está ubicada la colmena (opcional)\n";
    $contenido .= "- Estado: DEBE ser uno de estos: Estable, Advertencia, Problema\n";
    $contenido .= "- Notas: Campo opcional para observaciones\n";
    $contenido .= "- Los nombres de colmenas DEBEN ser únicos\n";
    $contenido .= "- No elimines los encabezados de la primera fila\n";
    $contenido .= "- No uses comillas ni caracteres especiales\n";
    
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

// BOM para UTF-8
echo "\xEF\xBB\xBF";
echo $contenido;
exit;
?>