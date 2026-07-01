<?php
session_start();
include("../CONEXION/conexion.php");
 
// Si ya hay una sesión de usuario completa, saltamos directo al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit();
}
 
$error = "";
 
if (isset($_POST['nit'])) {
    $nit = trim($_POST['nit'] ?? '');
 
    if (empty($nit)) {
        $error = "Por favor ingresa el NIT de la empresa.";
    } else {
        $stmt = $conn->prepare("SELECT id_empresa, nombre_empresa, estado FROM empresas WHERE nit=?");
        $stmt->bind_param("s", $nit);
        $stmt->execute();
        $result = $stmt->get_result();
 
        if ($result->num_rows > 0) {
            $empresa = $result->fetch_assoc();
 
            if ($empresa['estado'] !== 'activa') {
                $error = "Esta empresa se encuentra inactiva. Contacta al administrador.";
            } else {
                // NIT válido: guardamos la empresa en sesión y pasamos al login
                $_SESSION['empresa_id']     = $empresa['id_empresa'];
                $_SESSION['empresa_nombre'] = $empresa['nombre_empresa'];
                $_SESSION['nit_validado']   = true;
 
                $stmt->close();
                header("Location: login.php");
                exit();
            }
        } else {
            $error = "El NIT ingresado no está registrado en el sistema.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
 
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="ApiTech - Verificación de empresa apicultora">
<meta name="theme-color" content="#FFC72C">
 
<title>ApiTech - Verificar empresa</title>
 
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/login.css">
</head>
<body>
 
<div class="container">
 
    <!-- LOGO -->
    <div class="logo">
        <img src="../IMG/apitech_logo.png" alt="ApiTech Logo">
        <h2>ApiTech</h2>
    </div>
 
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" id="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
 
    <div id="verificar-nit" class="slide">
        <div class="title">Verifica tu empresa</div>
        <p style="text-align:center; margin-bottom: 20px; color:#666;">
            Ingresa el NIT de tu empresa apicultora para continuar.
        </p>
 
        <form method="POST" novalidate>
            <div class="input-group">
                <input
                    type="text"
                    name="nit"
                    placeholder="NIT de la empresa (Ej: 900123456-7)"
                    required
                    autofocus>
                <i class="fa-solid fa-building"></i>
            </div>
 
            <button type="submit" class="btn">
                <i class="fa-solid fa-arrow-right"></i> Continuar
            </button>
        </form>
    </div>
 
</div>
 
</body>
</html>
 