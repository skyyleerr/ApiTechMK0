<?php
session_start();
include("../CONEXION/conexion.php");
 
$error = "";
 
// No se puede llegar al login sin haber validado antes el NIT de la empresa
if (!isset($_SESSION['nit_validado']) || !isset($_SESSION['empresa_id'])) {
    header("Location: verificar_nit.php");
    exit();
}
 
// Redirigir si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit();
}
 
if (isset($_POST['login'])) {
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (empty($correo) || empty($password)) {
        $error = "Por favor completa todos los campos";
    } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor ingresa un correo válido";
    } else {
        // El usuario debe existir Y pertenecer a la empresa ya validada por NIT
        $stmt = $conn->prepare("SELECT id_usuario, nombre, correo, password, rol FROM usuarios WHERE correo=? AND id_empresa=?");
        $stmt->bind_param("si", $correo, $_SESSION['empresa_id']);
        $stmt->execute();
        $result = $stmt->get_result();
 
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
 
            if (password_verify($password, $usuario['password']) || $usuario['password'] === $password) {
                // Sesión completa del usuario
                session_regenerate_id(true);
 
                $_SESSION['usuario_id']     = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_correo'] = $usuario['correo'];
                $_SESSION['usuario_rol']    = $usuario['rol'];
 
                error_log("Usuario {$usuario['id_usuario']} ({$usuario['rol']}) inició sesión en " . date('Y-m-d H:i:s'));
 
                $stmt->close();
 
                // Redirección según el rol
                if ($usuario['rol'] === 'admin') {
                    header("Location: ../DASHBOARD/dashboard.php");
                } else {
                    header("Location: ../DASHBOARD/dashboard.php"); // ver nota en README sobre vista limitada
                }
                exit();
            } else {
                $error = "Correo o contraseña incorrectos";
            }
        } else {
            $error = "Correo o contraseña incorrectos";
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
<meta name="description" content="ApiTech - Acceso a la plataforma de monitoreo de colmenas">
<meta name="theme-color" content="#FFC72C">

<title>ApiTech - Acceso</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/login.css">

<style>
    .alert {
        padding: 14px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease;
        border-left: 4px solid transparent;
    }

    .alert i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .alert-danger {
        background: rgba(239, 68, 68, 0.12);
        border-left-color: #ef4444;
        color: #fca5a5;
    }

    .alert-success {
        background: rgba(16, 185, 129, 0.12);
        border-left-color: #10b981;
        color: #86efac;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Mejoras adicionales */
    .input-group input:focus {
        outline: none;
        border-color: var(--primary, #FFC72C);
        box-shadow: 0 0 0 3px rgba(255, 199, 44, 0.1);
    }

    .input-group input::placeholder {
        opacity: 0.7;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
    }

    .btn:active {
        transform: translateY(0);
    }

    .switch span {
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .switch span:hover {
        color: var(--primary, #FFC72C);
    }

    /* Estilos para mejor visibilidad */
    .slide, .hidden {
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .hidden {
        opacity: 0;
        visibility: hidden;
        position: absolute;
    }

    .slide {
        opacity: 1;
        visibility: visible;
    }

    .title {
        font-weight: 700;
        font-size: 24px;
    }

    .logo {
        margin-bottom: 30px;
    }

    .logo h2 {
        color: var(--primary, #FFC72C);
    }
</style>

</head>

<!DOCTYPE html>
<html lang="es">
 
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="ApiTech - Acceso a la plataforma de monitoreo de colmenas">
<meta name="theme-color" content="#FFC72C">
 
<title>ApiTech - Acceso</title>
 
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
 
    <p style="text-align:center; margin-bottom: 10px; color:#666; font-size: 14px;">
        <?php echo htmlspecialchars($_SESSION['empresa_nombre']); ?>
        &nbsp;·&nbsp;
        <a href="verificar_nit.php" style="color:#FFC72C;">Cambiar de empresa</a>
    </p>
 
    <!-- Mostrar alertas -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" id="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
 
    <!-- LOGIN FORM -->
    <div id="login" class="slide">
        <div class="title">Iniciar Sesión</div>
 
        <form method="POST" novalidate>
            <div class="input-group">
                <input
                    type="email"
                    name="correo"
                    placeholder="Correo electrónico"
                    required
                    autocomplete="email">
                <i class="fa-solid fa-envelope"></i>
            </div>
 
            <div class="input-group">
                <input
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required
                    autocomplete="current-password">
                <i class="fa-solid fa-lock"></i>
            </div>
 
            <button type="submit" class="btn" name="login">
                <i class="fa-solid fa-sign-in-alt"></i> Ingresar
            </button>
        </form>
    </div>
 
</div>
 
</body>
</html>





<script>
/**
 * Alterna entre formulario de login y registro
 */
function toggle(){
    const loginForm = document.getElementById("login");
    const registerForm = document.getElementById("register");
    
    loginForm.classList.toggle("hidden");
    registerForm.classList.toggle("hidden");
    
    // Limpiar alertas al cambiar de formulario
    const alert = document.getElementById('alert');
    if(alert) {
        alert.style.display = 'none';
    }
}

/**
 * Ocultar alertas después de 5 segundos
 */
document.addEventListener('DOMContentLoaded', function(){
    const alert = document.getElementById('alert');
    if(alert){
        setTimeout(function(){
            alert.style.opacity = '0';
            setTimeout(function(){
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    }
});

console.log('✓ Login page cargada correctamente');
</script>

