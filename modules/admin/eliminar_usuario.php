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
    header("Location: usuario_nuevo.php");
    exit;
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre']);
$usuario = trim($_POST['usuario']);
$password = $_POST['password'];
$rol = $_POST['rol'];
$activo = (int)($_POST['activo'] ?? 1);
$modulos = $_POST['modulos'] ?? [];

// Validaciones básicas
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
    $pdo->beginTransaction();

    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "El nombre de usuario ya está en uso";
        header("Location: usuario_nuevo.php");
        $pdo->rollBack();
        exit;
    }

    // Hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nombre, usuario, password, rol, activo, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$nombre, $usuario, $password_hash, $rol, $activo]);
    $usuario_id = $pdo->lastInsertId();

    // Si el rol es doctor, insertar en la tabla doctores
    if ($rol === 'doctor') {
        $especialidad = $_POST['especialidad'] ?? null;
        $telefono = $_POST['telefono_doctor'] ?? null;
        $email = $_POST['email_doctor'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO doctores (nombre, especialidad, telefono, email, activo, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nombre, $especialidad, $telefono, $email, $activo]);
    }

    // Insertar permisos de módulos
    if (!empty($modulos)) {
        $stmt = $pdo->prepare("
            INSERT INTO usuario_modulos (usuario_id, modulo_id) 
            VALUES (?, ?)
        ");
        foreach ($modulos as $modulo_id) {
            $stmt->execute([$usuario_id, $modulo_id]);
        }
    }

    $pdo->commit();

    $_SESSION['success'] = "Usuario creado correctamente";
    header("Location: usuarios.php");
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error al crear usuario: " . $e->getMessage();
    header("Location: usuario_nuevo.php");
    exit;
}