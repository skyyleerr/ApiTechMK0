<?php
/**
 * Script para validar archivos CSV antes de importar
 */

header('Content-Type: application/json');

if (!isset($_FILES['archivo'])) {
    echo json_encode(['valido' => false, 'error' => 'No se recibió archivo']);
    exit;
}

$archivo = $_FILES['archivo'];
$errores = [];

// Validar tipo
$tiposValidos = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
if (!in_array($archivo['type'], $tiposValidos)) {
    $errores[] = 'Tipo de archivo no permitido. Usa CSV';
}

// Validar tamaño
if ($archivo['size'] > 5 * 1024 * 1024) {
    $errores[] = 'Archivo demasiado grande (máximo 5MB)';
}

// Validar contenido
if ($archivo['error'] === UPLOAD_ERR_OK) {
    $handle = fopen($archivo['tmp_name'], 'r');
    $lineas = 0;
    $columnas = 0;
    
    if ($handle) {
        while (fgets($handle)) {
            $lineas++;
            if ($lineas > 1000) {
                $errores[] = 'Archivo con demasiadas líneas (máximo 1000)';
                break;
            }
        }
        fclose($handle);
    }
}

if (empty($errores)) {
    echo json_encode(['valido' => true]);
} else {
    echo json_encode(['valido' => false, 'errores' => $errores]);
}
exit;
?>