<?php
/**
 * LOGIN/auth.php
 *
 * Inclúyelo al INICIO de cualquier página que deba estar protegida
 * (dashboard.php, colmenas.php, sensores.php, reportes.php, etc.)
 *
 * Ejemplo de uso en DASHBOARD/dashboard.php:
 *
 *     <?php
 *     session_start();
 *     require_once(__DIR__ . '/../LOGIN/auth.php');
 *     requerirLogin(); // cualquier usuario logueado puede ver esta página
 *     ?>
 *
 * Para una página que SOLO el admin debe poder abrir (por ejemplo,
 * gestión de usuarios, o eliminar colmenas):
 *
 *     requerirAdmin();
 *
 * IMPORTANTE: ajusta la ruta relativa de header('Location: ...') más
 * abajo según desde qué carpeta se incluya este archivo (ver comentarios).
 */
 
/**
 * Exige que haya una sesión de usuario activa.
 * Si no la hay, redirige al login.
 */
function requerirLogin(): void
{
    if (empty($_SESSION['usuario_id'])) {
        // Si incluyes este archivo desde DASHBOARD/ o cualquier carpeta
        // al mismo nivel que LOGIN/, esta ruta relativa es correcta.
        // Si lo incluyes desde una subcarpeta más profunda, agrega los
        // "../" que hagan falta.
        header("Location: ../LOGIN/login.php");
        exit();
    }
}
 
/**
 * Exige que el usuario en sesión sea 'admin'.
 * Si no lo es, lo regresa al dashboard (no lo deja pasar).
 */
function requerirAdmin(): void
{
    requerirLogin();
    if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
        header("Location: ../DASHBOARD/dashboard.php?error=sin_permiso");
        exit();
    }
}
 
/**
 * true/false — útil para mostrar u ocultar botones dentro del HTML,
 * por ejemplo:
 *   <?php if (esAdmin()): ?>
 *       <button>Eliminar colmena</button>
 *   <?php endif; ?>
 */
function esAdmin(): bool
{
    return ($_SESSION['usuario_rol'] ?? '') === 'admin';
}