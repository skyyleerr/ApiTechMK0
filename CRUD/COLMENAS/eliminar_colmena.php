<?php
include("../../CONEXION/conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Verificar que existe
    $sql_check = "SELECT id_colmena FROM colmenas WHERE id_colmena=$id";
    $resultado_check = $conn->query($sql_check);
    
    if ($resultado_check && $resultado_check->num_rows > 0) {
        // Eliminar (CASCADE eliminará automáticamente registros relacionados)
        $sql = "DELETE FROM colmenas WHERE id_colmena=$id";
        
        if ($conn->query($sql)) {
            header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?success=Colmena eliminada exitosamente");
            exit;
        } else {
            header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?error=" . urlencode($conn->error));
            exit;
        }
    } else {
        header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?error=La colmena no existe");
        exit;
    }
} else {
    header("Location: ../../CRUD/COLMENAS/listar_colmenas.php?error=ID inválido");
    exit;
}
?>