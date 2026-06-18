<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    die("Sin sesión");
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);

echo "<h2>VERIFICACIÓN DE IMPORTACIÓN</h2>";
echo "<hr>";

// Colmenas del usuario
echo "<h3>🐝 Colmenas</h3>";
$colmenas = $conn->query("SELECT * FROM colmenas WHERE id_usuario = $id_usuario ORDER BY fecha_creacion DESC LIMIT 10");
if ($colmenas->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Ubicación</th><th>Estado</th><th>Fecha</th></tr>";
    while ($row = $colmenas->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_colmena'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['ubicacion'] . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "<td>" . $row['fecha_creacion'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay colmenas</p>";
}

echo "<br><hr>";

// Producción del usuario
echo "<h3>📊 Producción</h3>";
$produccion = $conn->query("
    SELECT p.*, c.nombre 
    FROM produccion p 
    INNER JOIN colmenas c ON p.id_colmena = c.id_colmena 
    WHERE c.id_usuario = $id_usuario 
    ORDER BY p.fecha DESC 
    LIMIT 10
");
if ($produccion->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Colmena</th><th>Cantidad (kg)</th><th>Fecha</th></tr>";
    while ($row = $produccion->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id_produccion'] . "</td>";
        echo "<td>" . $row['nombre'] . "</td>";
        echo "<td>" . $row['cantidad_miel'] . "</td>";
        echo "<td>" . $row['fecha'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No hay producción registrada</p>";
}

echo "<br><br>";
echo "<a href='importar_datos.php'><button>← Volver a Importar</button></a>";
?>