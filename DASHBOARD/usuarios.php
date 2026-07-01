<?php
session_start();
include("../CONEXION/conexion.php");
require_once(__DIR__ . '/../LOGIN/auth.php');

// Solo el administrador puede ver y usar esta página.
// Si un usuario normal intenta entrar por URL, lo regresa al dashboard.
requerirAdmin();

$idEmpresa = $_SESSION['empresa_id'];
$mensaje = "";
$error = "";

// ---------------------------------------------------------------
// CREAR usuario
// ---------------------------------------------------------------
if (isset($_POST['crear'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = $_POST['rol'] ?? 'usuario';

    if (empty($nombre) || empty($correo) || empty($password)) {
        $error = "Todos los campos son obligatorios";
    } else if (strlen($nombre) < 3) {
        $error = "El nombre debe tener al menos 3 caracteres";
    } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo inválido";
    } else if (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } else if (!in_array($rol, ['admin', 'usuario'], true)) {
        $error = "Rol inválido";
    } else {
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo=?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Ya existe un usuario con ese correo";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = $conn->prepare(
                "INSERT INTO usuarios (id_empresa, nombre, correo, password, rol, fecha_registro)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt2->bind_param("issss", $idEmpresa, $nombre, $correo, $hash, $rol);
            if ($stmt2->execute()) {
                $mensaje = "Usuario creado correctamente";
            } else {
                $error = "Error al crear el usuario";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

// ---------------------------------------------------------------
// EDITAR usuario (nombre, correo, rol y opcionalmente contraseña)
// ---------------------------------------------------------------
if (isset($_POST['editar'])) {
    $id = (int)($_POST['id_usuario'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = $_POST['rol'] ?? 'usuario';
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($correo)) {
        $error = "Nombre y correo son obligatorios";
    } else if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "Correo inválido";
    } else if (!in_array($rol, ['admin', 'usuario'], true)) {
        $error = "Rol inválido";
    } else {
        // Evita que el correo choque con el de otro usuario distinto
        $stmt = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo=? AND id_usuario<>?");
        $stmt->bind_param("si", $correo, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Ese correo ya lo usa otro usuario";
        } else {
            // Evita que el admin se quite a sí mismo el rol de admin (te dejaría sin acceso)
            if ($id === (int)$_SESSION['usuario_id'] && $rol !== 'admin') {
                $error = "No puedes quitarte el rol de administrador a ti mismo";
            } else if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = "La contraseña debe tener al menos 6 caracteres";
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt2 = $conn->prepare(
                        "UPDATE usuarios SET nombre=?, correo=?, rol=?, password=?
                         WHERE id_usuario=? AND id_empresa=?"
                    );
                    $stmt2->bind_param("ssssii", $nombre, $correo, $rol, $hash, $id, $idEmpresa);
                    $stmt2->execute();
                    $stmt2->close();
                    $mensaje = "Usuario actualizado correctamente";
                }
            } else {
                $stmt2 = $conn->prepare(
                    "UPDATE usuarios SET nombre=?, correo=?, rol=?
                     WHERE id_usuario=? AND id_empresa=?"
                );
                $stmt2->bind_param("sssii", $nombre, $correo, $rol, $id, $idEmpresa);
                $stmt2->execute();
                $stmt2->close();
                $mensaje = "Usuario actualizado correctamente";
            }
        }
        $stmt->close();
    }
}

// ---------------------------------------------------------------
// ELIMINAR usuario
// ---------------------------------------------------------------
if (isset($_POST['eliminar'])) {
    $id = (int)($_POST['id_usuario'] ?? 0);

    if ($id === (int)$_SESSION['usuario_id']) {
        $error = "No puedes eliminar tu propia cuenta";
    } else {
        $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario=? AND id_empresa=?");
        $stmt->bind_param("ii", $id, $idEmpresa);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Usuario eliminado correctamente";
    }
}

// ---------------------------------------------------------------
// LISTAR usuarios de la empresa (siempre filtrado por id_empresa)
// ---------------------------------------------------------------
$stmt = $conn->prepare(
    "SELECT id_usuario, nombre, correo, rol, fecha_registro
     FROM usuarios WHERE id_empresa=? ORDER BY nombre"
);
$stmt->bind_param("i", $idEmpresa);
$stmt->execute();
$usuarios = $stmt->get_result();
$stmt->close();

// Si viene ?editar_id=X en la URL, cargamos ese usuario para el formulario
$usuarioEditar = null;
if (isset($_GET['editar_id'])) {
    $idEditar = (int)$_GET['editar_id'];
    $stmt = $conn->prepare("SELECT id_usuario, nombre, correo, rol FROM usuarios WHERE id_usuario=? AND id_empresa=?");
    $stmt->bind_param("ii", $idEditar, $idEmpresa);
    $stmt->execute();
    $usuarioEditar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ApiTech - Gestión de Usuarios</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="../CSS/estilos.css">
<style>
    /* Paleta alineada con el dashboard: dorado #FFC72C sobre fondo claro,
       texto oscuro #1a1a1a, tarjetas blancas con sombra suave. */
    body { background: #f7f7f9; margin: 0; }

    .usuarios-wrap { max-width: 960px; margin: 0 auto; padding: 28px 24px 60px; }

    .usuarios-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 22px;
    }

    .btn-volver {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        color: #1a1a1a;
        border: 1px solid #eee;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    .btn-volver:hover {
        background: #FFC72C;
        border-color: #FFC72C;
        transform: translateX(-2px);
    }

    .usuarios-header h2 {
        margin: 0 0 4px;
        font-size: 24px;
        font-weight: 800;
        color: #1a1a1a;
    }
    .usuarios-header p { margin: 0; color: #888; font-size: 14px; }

    .usuarios-tabla {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }
    .usuarios-tabla th, .usuarios-tabla td { color: black; text-align: left; padding: 13px 14px; border-bottom: 1px solid #f0f0f0; }
    .usuarios-tabla th {
        background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
        color: #1a1a1a;
        font-weight: 700;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .usuarios-tabla tr:hover td { background: rgba(255, 199, 44, 0.06); }

    .badge-rol { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
    .badge-admin { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
    .badge-usuario { background: rgba(37, 99, 235, 0.12); color: #2563eb; }

    .form-usuario {
        background: #fff;
        border: none;
        border-top: 3px solid #FFC72C;
        border-radius: 14px;
        padding: 24px;
        margin-top: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    }
    .form-usuario h3 { margin-top: 0; color: #1a1a1a; }
    .form-usuario label { font-weight: 600; font-size: 13px; color: #444; }
    .form-usuario input, .form-usuario select {
        width: 100%; padding: 10px 12px; margin: 6px 0 14px;
        border: 1px solid #e2e2e2; border-radius: 8px; font-size: 14px;
    }
    .form-usuario input:focus, .form-usuario select:focus {
        outline: none; border-color: #FFC72C; box-shadow: 0 0 0 3px rgba(255,199,44,0.15);
    }

    .btn-primary {
        background: linear-gradient(135deg, #FFC72C 0%, #ffb700 100%);
        color: #1a1a1a; border: none; padding: 11px 22px; border-radius: 8px;
        font-weight: 700; font-size: 14px; cursor: pointer; text-decoration: none;
        display: inline-block; transition: all 0.2s ease;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(255,199,44,0.35); }

    .btn-outline {
        background: #fff; color: #666; border: 1px solid #ddd; padding: 11px 22px;
        border-radius: 8px; font-weight: 600; font-size: 14px; text-decoration: none;
        display: inline-block; margin-left: 8px;
    }
    .btn-outline:hover { border-color: #999; color: #333; }

    .acciones a { color: #2563eb; font-weight: 600; text-decoration: none; margin-right: 14px; font-size: 13px; }
    .acciones a:hover { text-decoration: underline; }
    .btn-eliminar {
        background: none; border: none; color: #dc2626; font-weight: 600;
        font-size: 13px; cursor: pointer; padding: 0;
    }
    .btn-eliminar:hover { text-decoration: underline; }

    .alert-danger { background:#fde2e2;color:#a33;padding:12px 16px;border-radius:8px;margin-top:14px; border-left: 4px solid #ef4444; }
    .alert-success { background:#e2f5e2;color:#2a7a2a;padding:12px 16px;border-radius:8px;margin-top:14px; border-left: 4px solid #22c55e; }
</style>
</head>
<body>

<div class="usuarios-wrap">
    <div class="usuarios-topbar">
        <div class="usuarios-header">
            <h2><i class="fa-solid fa-users" style="color:#FFC72C;"></i> Gestión de Usuarios</h2>
            <p>Solo tú, como administrador, puedes ver y usar esta sección.</p>
        </div>
        <a href="dashboard.php" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>

    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($mensaje): ?><div class="alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

    <!-- Formulario: crea o edita según si venimos con ?editar_id= -->
    <div class="form-usuario">
        <h3><?= $usuarioEditar ? 'Editar usuario' : 'Crear nuevo usuario' ?></h3>
        <form method="POST">
            <?php if ($usuarioEditar): ?>
                <input type="hidden" name="id_usuario" value="<?= (int)$usuarioEditar['id_usuario'] ?>">
            <?php endif; ?>

            <label>Nombre completo</label>
            <input type="text" name="nombre" required minlength="3"
                   value="<?= htmlspecialchars($usuarioEditar['nombre'] ?? '') ?>">

            <label>Correo electrónico</label>
            <input type="email" name="correo" required
                   value="<?= htmlspecialchars($usuarioEditar['correo'] ?? '') ?>">

            <label>Rol</label>
            <select name="rol" required>
                <option value="usuario" <?= (($usuarioEditar['rol'] ?? '') === 'usuario') ? 'selected' : '' ?>>Usuario</option>
                <option value="admin" <?= (($usuarioEditar['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
            </select>

            <label>Contraseña <?= $usuarioEditar ? '(déjala vacía para no cambiarla)' : '' ?></label>
            <input type="password" name="password" minlength="6" <?= $usuarioEditar ? '' : 'required' ?>>

            <button type="submit" name="<?= $usuarioEditar ? 'editar' : 'crear' ?>" class="btn-primary">
                <?= $usuarioEditar ? 'Guardar cambios' : 'Crear usuario' ?>
            </button>
            <?php if ($usuarioEditar): ?>
                <a href="usuarios.php" class="btn-outline">Cancelar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Listado -->
    <table class="usuarios-tabla">
        <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Registrado</th><th>Acciones</th></tr>
        <?php while ($u = $usuarios->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>
            <td>
                <span class="badge-rol <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-usuario' ?>">
                    <?= htmlspecialchars($u['rol']) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($u['fecha_registro']) ?></td>
            <td class="acciones">
                <a href="usuarios.php?editar_id=<?= (int)$u['id_usuario'] ?>">Editar</a>
                <?php if ((int)$u['id_usuario'] !== (int)$_SESSION['usuario_id']): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars($u['nombre']) ?>?');">
                    <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                    <button type="submit" name="eliminar" class="btn-eliminar">Eliminar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>