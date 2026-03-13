<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: usuarios.php");
    exit;
}

$doctor_id = isset($_POST['doctor_id']) && !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null;
$usuario_id = isset($_POST['usuario_id']) && !empty($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : null;
$dia = $_POST['dia'] ?? '';
$hora_inicio = $_POST['hora_inicio'] ?? '';
$hora_fin = $_POST['hora_fin'] ?? '';

// Validar campos obligatorios
if (!$dia || !$hora_inicio || !$hora_fin) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    $redirect_id = $doctor_id ?: $usuario_id;
    $redirect_param = $doctor_id ? 'doctor_id' : 'usuario_id';
    header("Location: horarios.php?$redirect_param=$redirect_id&action=ver");
    exit;
}

// Validar que al menos un ID esté presente
if (!$doctor_id && !$usuario_id) {
    $_SESSION['error'] = "ID de usuario no válido";
    header("Location: usuarios.php");
    exit;
}

// Validar que la hora fin sea mayor
if ($hora_fin <= $hora_inicio) {
    $_SESSION['error'] = "La hora fin debe ser mayor a la hora inicio";
    $redirect_id = $doctor_id ?: $usuario_id;
    $redirect_param = $doctor_id ? 'doctor_id' : 'usuario_id';
    header("Location: horarios.php?$redirect_param=$redirect_id&action=ver");
    exit;
}

try {
    // Verificar si ya existe un horario para ese día
    if ($doctor_id) {
        $stmt = $pdo->prepare("SELECT id FROM doctor_horarios WHERE doctor_id = ? AND dia = ?");
        $stmt->execute([$doctor_id, $dia]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM doctor_horarios WHERE usuario_id = ? AND dia = ?");
        $stmt->execute([$usuario_id, $dia]);
    }
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Ya existe un horario para este día";
        $redirect_id = $doctor_id ?: $usuario_id;
        $redirect_param = $doctor_id ? 'doctor_id' : 'usuario_id';
        header("Location: horarios.php?$redirect_param=$redirect_id&action=ver");
        exit;
    }
    
    // Eliminar posibles registros con ID 0
    $pdo->exec("DELETE FROM doctor_horarios WHERE id = 0");
    
    // Insertar horario
    if ($doctor_id) {
        $stmt = $pdo->prepare("
            INSERT INTO doctor_horarios (doctor_id, usuario_id, dia, hora_inicio, hora_fin)
            VALUES (?, NULL, ?, ?, ?)
        ");
        $stmt->execute([$doctor_id, $dia, $hora_inicio, $hora_fin]);
        $redirect_id = $doctor_id;
        $redirect_param = 'doctor_id';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO doctor_horarios (doctor_id, usuario_id, dia, hora_inicio, hora_fin)
            VALUES (NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario_id, $dia, $hora_inicio, $hora_fin]);
        $redirect_id = $usuario_id;
        $redirect_param = 'usuario_id';
    }
    
    $_SESSION['success'] = "Horario guardado correctamente";
    
} catch (PDOException $e) {
    error_log("Error en guardar_horario.php: " . $e->getMessage());
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    $redirect_id = $doctor_id ?: $usuario_id;
    $redirect_param = $doctor_id ? 'doctor_id' : 'usuario_id';
}

header("Location: horarios.php?$redirect_param=$redirect_id&action=ver");
exit;