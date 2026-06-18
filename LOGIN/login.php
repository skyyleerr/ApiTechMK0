<?php
session_start();
include("../CONEXION/conexion.php");

$error = "";
$mensaje = "";

// Redirigir si ya está logueado
if(isset($_SESSION['usuario_id'])) {
    header("Location: ../DASHBOARD/dashboard.php");
    exit();
}

if(isset($_POST['login'])){
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validar que los campos no estén vacíos
    if(empty($correo) || empty($password)){
        $error = "Por favor completa todos los campos";
    } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor ingresa un correo válido";
    } else {
        // Usar prepared statements para evitar SQL Injection
        $stmt = $conn->prepare("SELECT id_usuario, nombre, correo, password FROM usuarios WHERE correo=?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $usuario = $result->fetch_assoc();
            // Verificar contraseña (preferentemente con password_verify)
            if(password_verify($password, $usuario['password']) || $usuario['password'] === $password){
                // Guardar datos en sesión
                $_SESSION['usuario_id'] = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_correo'] = $usuario['correo'];
                
                // Registrar actividad (opcional)
                error_log("Usuario {$usuario['id_usuario']} inició sesión en " . date('Y-m-d H:i:s'));
                
                header("Location: ../DASHBOARD/dashboard.php");
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

if(isset($_POST['registro'])){
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validar que los campos no estén vacíos
    if(empty($correo) || empty($password) || empty($nombre)){
        $error = "Todos los campos son obligatorios";
    } else if (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor ingresa un correo válido";
    } else if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo=?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0){
            $error = "Ya existe una cuenta registrada con ese correo";
        } else {
            // Hashear la contraseña antes de guardar
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios(nombre, correo, password, fecha_registro) VALUES(?, ?, ?, NOW())");
            $stmt->bind_param("sss", $nombre, $correo, $passwordHash);
            
            if($stmt->execute()){
                $mensaje = "✓ Cuenta creada correctamente. Por favor inicia sesión.";
                error_log("Nuevo usuario registrado: $correo en " . date('Y-m-d H:i:s'));
            } else {
                $error = "Error al registrar el usuario. Intenta nuevamente.";
            }
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

<body>

<div class="container">

    <!-- LOGO -->
    <div class="logo">
        <img src="../IMG/apitech_logo.png" alt="ApiTech Logo">
        <h2>ApiTech</h2>
    </div>

    <!-- Mostrar alertas -->
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger" id="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if(!empty($mensaje)): ?>
        <div class="alert alert-success" id="alert">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($mensaje); ?></span>
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

        <div class="switch">
            ¿No tienes cuenta? <span onclick="toggle()">Regístrate aquí</span>
        </div>
    </div>

    <!-- REGISTER FORM -->
    <div id="register" class="hidden">
        <div class="title">Crear Cuenta</div>

        <form method="POST" novalidate>
            <div class="input-group">
                <input 
                    type="text" 
                    name="nombre" 
                    placeholder="Nombre completo" 
                    required
                    minlength="3"
                    autocomplete="name">
                <i class="fa-solid fa-user"></i>
            </div>

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
                    placeholder="Contraseña (mínimo 6 caracteres)" 
                    required
                    minlength="6"
                    autocomplete="new-password">
                <i class="fa-solid fa-lock"></i>
            </div>

            <button type="submit" class="btn" name="registro">
                <i class="fa-solid fa-user-plus"></i> Registrarse
            </button>
        </form>

        <div class="switch">
            ¿Ya tienes cuenta? <span onclick="toggle()">Inicia sesión aquí</span>
        </div>
    </div>

</div>

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

</body>

</html>
