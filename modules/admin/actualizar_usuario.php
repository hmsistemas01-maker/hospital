<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php");
    exit;
}

$id = (int) $_POST['id'];
$nombre = trim($_POST['nombre']);
$usuario = trim($_POST['usuario']);
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'];
$activo = (int)($_POST['activo'] ?? 1);
$modulos = $_POST['modulos'] ?? [];

// Validaciones básicas
if (empty($nombre) || empty($usuario) || empty($rol)) {
    $_SESSION['error'] = "Todos los campos obligatorios deben ser llenados";
    header("Location: editar_usuario.php?id=$id");
    exit;
}

// Verificar que no sea el propio usuario desactivándose
if ($id == $_SESSION['user_id'] && $activo == 0) {
    $_SESSION['error'] = "No puedes desactivar tu propio usuario";
    header("Location: editar_usuario.php?id=$id");
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar si el usuario ya existe (para otro usuario)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? AND id != ?");
    $stmt->execute([$usuario, $id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "El nombre de usuario ya está en uso";
        header("Location: editar_usuario.php?id=$id");
        $pdo->rollBack();
        exit;
    }

    // Actualizar datos básicos del usuario
    if (!empty($password)) {
        // Si se proporcionó nueva contraseña
        if (strlen($password) < 6) {
            $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
            header("Location: editar_usuario.php?id=$id");
            $pdo->rollBack();
            exit;
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nombre = ?, usuario = ?, password = ?, rol = ?, activo = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $usuario, $password_hash, $rol, $activo, $id]);
    } else {
        // Sin cambiar contraseña
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nombre = ?, usuario = ?, rol = ?, activo = ? 
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $usuario, $rol, $activo, $id]);
    }

    // Si el rol es doctor, actualizar en la tabla doctores
    if ($rol === 'doctor') {
        $especialidad = $_POST['especialidad'] ?? null;
        $telefono = $_POST['telefono_doctor'] ?? null;
        $email = $_POST['email_doctor'] ?? null;
        
        // Verificar si ya existe un registro en doctores con este nombre
        $stmt = $pdo->prepare("SELECT id FROM doctores WHERE nombre = ?");
        $stmt->execute([$nombre]);
        $doctor_existente = $stmt->fetch();
        
        if ($doctor_existente) {
            // Actualizar doctor existente
            $stmt = $pdo->prepare("
                UPDATE doctores 
                SET especialidad = ?, telefono = ?, email = ?, activo = ? 
                WHERE id = ?
            ");
            $stmt->execute([$especialidad, $telefono, $email, $activo, $doctor_existente['id']]);
        } else {
            // Crear nuevo doctor
            $stmt = $pdo->prepare("
                INSERT INTO doctores (nombre, especialidad, telefono, email, activo, fecha_registro) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nombre, $especialidad, $telefono, $email, $activo]);
        }
    } else {
        // Si ya no es doctor, pero tenía registro en doctores, lo desactivamos
        $stmt = $pdo->prepare("UPDATE doctores SET activo = 0 WHERE nombre = ?");
        $stmt->execute([$nombre]);
    }

    // Actualizar permisos de módulos
    // Primero eliminar todos los permisos actuales
    $stmt = $pdo->prepare("DELETE FROM usuario_modulos WHERE usuario_id = ?");
    $stmt->execute([$id]);

    // Luego insertar los nuevos permisos
    if (!empty($modulos)) {
        $stmt = $pdo->prepare("
            INSERT INTO usuario_modulos (usuario_id, modulo_id) 
            VALUES (?, ?)
        ");
        foreach ($modulos as $modulo_id) {
            $stmt->execute([$id, $modulo_id]);
        }
    }

    // Si es el propio usuario el que se editó, actualizar sesión
    if ($id == $_SESSION['user_id']) {
        $_SESSION['usuario'] = $nombre;
        $_SESSION['rol'] = $rol;
    }

    $pdo->commit();

    $_SESSION['success'] = "Usuario actualizado correctamente";
    header("Location: usuarios.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error al actualizar: " . $e->getMessage();
    header("Location: editar_usuario.php?id=$id");
    exit;
}