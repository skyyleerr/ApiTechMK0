<?php
/**
 * Script de Cierre de Sesión
 * Destruye la sesión del usuario y redirige al login
 */

session_start();

// Log de cierre de sesión (opcional)
if (isset($_SESSION['usuario_id'])) {
    error_log("Usuario {$_SESSION['usuario_id']} cerró sesión en " . date('Y-m-d H:i:s'));
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Eliminar la cookie de sesión del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destruir la sesión en el servidor
session_destroy();

// Redirigir a la página de login
header("Location: ../index.html");
exit();
?>
