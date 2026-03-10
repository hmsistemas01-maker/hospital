<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Obtener y sanitizar datos
$doctor_id = (int) $_POST['doctor_id'];
$tipo = $_POST['tipo'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$motivo = trim($_POST['motivo'] ?? '');
$hora_entrada = $_POST['hora_entrada'] ?? null;
$hora_salida = $_POST['hora_salida'] ?? null;

// Validaciones básicas
if (!$doctor_id || !$tipo || !$fecha_inicio || !$fecha_fin || !$motivo) {
    $_SESSION['error'] = "Todos los campos obligatorios deben ser llenados";
    header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
    exit;
}

// Validar que el doctor exista
$stmt = $pdo->prepare("SELECT id FROM doctores WHERE id = ?");
$stmt->execute([$doctor_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "El doctor no existe";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Validar fechas
if ($fecha_fin < $fecha_inicio) {
    $_SESSION['error'] = "La fecha fin no puede ser menor a la fecha inicio";
    header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
    exit;
}

// Validar que la fecha inicio no sea demasiado antigua
if ($fecha_inicio < date('Y-m-d', strtotime('-1 year'))) {
    $_SESSION['error'] = "No se pueden registrar excepciones con más de un año de antigüedad";
    header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
    exit;
}

// Para horario especial, validar horas
if ($tipo == 'horario_especial') {
    if (!$hora_entrada || !$hora_salida) {
        $_SESSION['error'] = "Para horario especial debe especificar hora de entrada y salida";
        header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
        exit;
    }
    if ($hora_salida <= $hora_entrada) {
        $_SESSION['error'] = "La hora de salida debe ser mayor a la hora de entrada";
        header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
        exit;
    }
}

try {
    // Verificar si ya existe una excepción activa en el mismo período
    $stmt = $pdo->prepare("
        SELECT id FROM doctor_excepciones 
        WHERE doctor_id = ? 
        AND activo = 1
        AND (
            (fecha_inicio BETWEEN ? AND ?) OR
            (fecha_fin BETWEEN ? AND ?) OR
            (? BETWEEN fecha_inicio AND fecha_fin)
        )
    ");
    $stmt->execute([$doctor_id, $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin, $fecha_inicio]);
    $existe = $stmt->fetch();

    if ($existe) {
        throw new Exception("Ya existe una excepción activa en este período");
    }

    // Insertar la excepción
    $stmt = $pdo->prepare("
        INSERT INTO doctor_excepciones 
        (doctor_id, fecha_inicio, fecha_fin, tipo, motivo, hora_entrada, hora_salida, creado_por, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $doctor_id,
        $fecha_inicio,
        $fecha_fin,
        $tipo,
        $motivo,
        $hora_entrada,
        $hora_salida,
        $_SESSION['user_id']
    ]);
    
    $_SESSION['success'] = "Excepción guardada correctamente";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error al guardar: " . $e->getMessage();
}

header("Location: horarios.php?doctor_id=$doctor_id&action=excepciones");
exit;