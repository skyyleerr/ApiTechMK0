<?php
session_start();
include("../../CONEXION/conexion.php");

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../LOGIN/login.php");
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: listar_colmenas.php?error=Método no permitido");
    exit;
}

// Obtener y sanitizar datos
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$ubicacion = isset($_POST['ubicacion']) ? trim($_POST['ubicacion']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : 'Estable';

// Validaciones
if (empty($nombre)) {
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=El nombre de la colmena es requerido");
    exit;
}

if (strlen($nombre) < 3) {
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=El nombre debe tener al menos 3 caracteres");
    exit;
}

if (strlen($nombre) > 100) {
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=El nombre no puede exceder 100 caracteres");
    exit;
}

if (empty($ubicacion)) {
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=La ubicación es requerida");
    exit;
}

if (strlen($ubicacion) > 150) {
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=La ubicación no puede exceder 150 caracteres");
    exit;
}

// Validar estado
$estados_validos = ['Estable', 'Advertencia', 'Crítico'];
if (!in_array($estado, $estados_validos)) {
    $estado = 'Estable';
}

// Escapar para evitar SQL injection
$nombre = $conn->real_escape_string($nombre);
$ubicacion = $conn->real_escape_string($ubicacion);
$id_usuario = intval($_SESSION['usuario_id']);

// Insertar colmena
$sql = "INSERT INTO colmenas (id_usuario, nombre, ubicacion, estado) 
        VALUES ($id_usuario, '$nombre', '$ubicacion', '$estado')";

if ($conn->query($sql)) {
    $id_colmena = $conn->insert_id;
    
    // Registrar actividad
    $tipo_actividad = 'crear_colmena';
    $descripcion = "Creó la colmena: $nombre";
    $sql_actividad = "INSERT INTO actividad (id_usuario, tipo, descripcion) 
                     VALUES ($id_usuario, '$tipo_actividad', '$descripcion')";
    $conn->query($sql_actividad);
    
    header("Location: listar_colmenas.php?success=Colmena creada exitosamente");
    exit;
} else {
    $error = urlencode("Error al crear la colmena: " . $conn->error);
    header("Location: ../../FORMULARIOS/COLMENAS/formulario_colmena.php?error=$error");
    exit;
}
?>
