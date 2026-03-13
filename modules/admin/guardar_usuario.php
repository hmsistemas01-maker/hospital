<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: usuario_nuevo.php");
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? '';
$activo = isset($_POST['activo']) ? 1 : 0;
$modulos = $_POST['modulos'] ?? [];

// Validaciones
if (empty($nombre) || empty($usuario) || empty($password) || empty($rol)) {
    $_SESSION['error'] = "Todos los campos obligatorios deben ser llenados";
    header("Location: usuario_nuevo.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = "La contraseña debe tener al menos 6 caracteres";
    header("Location: usuario_nuevo.php");
    exit;
}

try {
    // Verificar si el usuario ya existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $check->execute([$usuario]);
    if ($check->fetch()) {
        $_SESSION['error'] = "El nombre de usuario ya está en uso";
        header("Location: usuario_nuevo.php");
        exit;
    }
    
    // ANTES DE INSERTAR: Verificar que no haya ID 0
    $pdo->exec("DELETE FROM usuarios WHERE id = 0");
    $pdo->exec("DELETE FROM doctores WHERE id = 0");
    
    $pdo->beginTransaction();
    
    // Hash de contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // 1. INSERTAR USUARIO
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, usuario, password, rol, activo, fecha_registro)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$nombre, $usuario, $password_hash, $rol, $activo]);
    $usuario_id = $pdo->lastInsertId();
    
    // 2. SI ES DOCTOR, guardar también en doctores
    if ($rol == 'doctor') {
        $especialidad = $_POST['especialidad'] ?? 'General';
        $telefono = $_POST['telefono_doctor'] ?? '';
        $email = $_POST['email_doctor'] ?? '';
        
        // Verificar si ya existe un doctor con ese nombre
        $check_doctor = $pdo->prepare("SELECT id FROM doctores WHERE nombre = ?");
        $check_doctor->execute([$nombre]);
        $doctor_existente = $check_doctor->fetch();
        
        if ($doctor_existente) {
            // Actualizar doctor existente
            $update_doctor = $pdo->prepare("
                UPDATE doctores SET 
                    especialidad = ?, 
                    telefono = ?, 
                    email = ?, 
                    activo = ? 
                WHERE id = ?
            ");
            $update_doctor->execute([$especialidad, $telefono, $email, $activo, $doctor_existente['id']]);
        } else {
            // Insertar nuevo doctor
            $insert_doctor = $pdo->prepare("
                INSERT INTO doctores (nombre, especialidad, telefono, email, activo, fecha_registro)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $insert_doctor->execute([$nombre, $especialidad, $telefono, $email, $activo]);
        }
    }
    
    // 3. GUARDAR MÓDULOS
    if (!empty($modulos)) {
        $stmt_mod = $pdo->prepare("INSERT INTO usuario_modulos (usuario_id, modulo_id) VALUES (?, ?)");
        foreach ($modulos as $modulo_id) {
            $stmt_mod->execute([$usuario_id, $modulo_id]);
        }
    }
    
    $pdo->commit();
    
    $_SESSION['success'] = "Usuario creado correctamente (ID: $usuario_id)";
    header("Location: usuarios.php");
    exit;
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error: " . $e->getMessage());
    $_SESSION['error'] = "Error al crear usuario: " . $e->getMessage();
    header("Location: usuario_nuevo.php");
    exit;
}