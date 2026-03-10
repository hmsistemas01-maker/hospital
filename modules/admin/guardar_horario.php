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
    header("Location: usuarios.php?rol=doctor");
    exit;
}

$doctor_id = (int) $_POST['doctor_id'];
$dia = $_POST['dia'];
$hora_inicio = $_POST['hora_inicio'];
$hora_fin = $_POST['hora_fin'];

// Validaciones básicas
if (!$doctor_id || !$dia || !$hora_inicio || !$hora_fin) {
    $_SESSION['error'] = "Todos los campos son obligatorios";
    header("Location: horarios.php?doctor_id=$doctor_id");
    exit;
}

// Validar que hora fin sea mayor que hora inicio
if ($hora_fin <= $hora_inicio) {
    $_SESSION['error'] = "La hora fin debe ser mayor a la hora inicio";
    header("Location: horarios.php?doctor_id=$doctor_id");
    exit;
}

try {
    // Verificar si ya existe un horario para ese día
    $stmt = $pdo->prepare("SELECT id FROM doctor_horarios WHERE doctor_id = ? AND dia = ?");
    $stmt->execute([$doctor_id, $dia]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "Ya existe un horario para este día";
        header("Location: horarios.php?doctor_id=$doctor_id");
        exit;
    }
    
    // Insertar horario
    $stmt = $pdo->prepare("
        INSERT INTO doctor_horarios (doctor_id, dia, hora_inicio, hora_fin) 
        VALUES (?, ?, ?, ?)
    ");
    $resultado = $stmt->execute([$doctor_id, $dia, $hora_inicio, $hora_fin]);
    
    if ($resultado) {
        $_SESSION['success'] = "Horario guardado correctamente";
    } else {
        $_SESSION['error'] = "No se pudo guardar el horario";
    }
    
    header("Location: horarios.php?doctor_id=$doctor_id");
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
    header("Location: horarios.php?doctor_id=$doctor_id");
}

exit;