<?php
session_start();

// Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../LOGIN/login.php");
    exit();
}

include("../CONEXION/conexion.php");

$id_usuario = intval($_SESSION['usuario_id']);
$success = '';
$error = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_contrasena'])) {
    
    $nueva_contrasena = trim($_POST['nueva_contrasena']);
    
    // Validaciones
    if (empty($nueva_contrasena)) {
        $error = "La contraseña no puede estar vacía";
    } elseif (strlen($nueva_contrasena) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Hashear la contraseña
        $password_hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);
        
        // Actualizar contraseña en la BD
        $sql = "UPDATE usuarios SET password = ? WHERE id_usuario = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("si", $password_hash, $id_usuario);
            
            if ($stmt->execute()) {
                $success = "✓ Contraseña actualizada correctamente";
                // Redirigir después de 2 segundos a configuración
                header("refresh:2;url=configuracion.php");
            } else {
                $error = "Error al actualizar la contraseña: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Error en la preparación de la consulta: " . $conn->error;
        }
    }
}

// Si no es POST, redirigir a configuración
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: configuracion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cambiar Contraseña - ApiTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.2);
    padding: 40px;
    max-width: 400px;
    width: 90%;
    animation: slideUp 0.6s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.container-header {
    text-align: center;
    margin-bottom: 30px;
}

.icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    margin: 0 auto 16px;
    box-shadow: 0 4px 12px rgba(255, 199, 44, 0.3);
}

h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.subtitle {
    font-size: 14px;
    color: #666;
}

.alert {
    padding: 14px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-error {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    color: #7f1d1d;
    border: 1px solid #fca5a5;
}

.alert i {
    font-size: 18px;
    flex-shrink: 0;
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.btn {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
    color: #1a1a1a;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 199, 44, 0.3);
}

.btn-secondary {
    background: #f3f4f6;
    color: #333;
}

.btn-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-2px);
}

@media (max-width: 480px) {
    .container {
        padding: 30px 20px;
    }

    h1 {
        font-size: 20px;
    }

    .button-group {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<div class="container">
    <div class="container-header">
        <div class="icon">
            <i class="fa-solid fa-lock"></i>
        </div>
        <h1>Contraseña Actualizada</h1>
        <p class="subtitle">Tu contraseña ha sido procesada</p>
    </div>

    <?php if (!empty($success)) { ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <p style="text-align: center; color: #666; font-size: 13px; margin-bottom: 20px;">
            Serás redirigido a la configuración en unos segundos...
        </p>
    <?php } ?>

    <?php if (!empty($error)) { ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php } ?>

    <div class="button-group">
        <a href="configuracion.php" class="btn btn-primary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Configuración
        </a>
        <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary">
            <i class="fa-solid fa-home"></i> Ir al Dashboard
        </a>
    </div>
</div>

</body>
</html>