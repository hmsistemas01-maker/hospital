<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nueva.php");
    exit;
}

$paciente_id = (int) $_POST['paciente_id'];
$doctor_id = (int) $_POST['doctor_id'];
$fecha = $_POST['fecha'];
$hora = $_POST['hora'];
$motivo = $_POST['motivo'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;
$created_by = $_SESSION['user_id'];

// Array para almacenar errores
$errores = [];

// 1. Validar que la fecha no sea pasada
if ($fecha < date('Y-m-d')) {
    $errores[] = "No se pueden agendar citas en fechas pasadas.";
}

// 2. Obtener día de la semana en español
$dias = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miercoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sabado',
    'Sunday' => 'Domingo'
];
$dia_semana = $dias[date('l', strtotime($fecha))] ?? null;

// 3. Verificar que el doctor trabaje ese día
$stmt = $pdo->prepare("
    SELECT * FROM doctor_horarios 
    WHERE doctor_id = ? AND dia = ?
");
$stmt->execute([$doctor_id, $dia_semana]);
$horarios = $stmt->fetchAll();

if (!$horarios) {
    $errores[] = "El doctor no trabaja el día seleccionado.";
}

// 4. Verificar que la hora esté dentro del horario del doctor
$hora_valida = false;
foreach ($horarios as $h) {
    if ($hora >= $h['hora_inicio'] && $hora < $h['hora_fin']) {
        $hora_valida = true;
        break;
    }
}

if (!$hora_valida) {
    $errores[] = "La hora seleccionada está fuera del horario del doctor.";
}

// 5. Verificar que no haya otra cita en el mismo horario
$stmt = $pdo->prepare("
    SELECT id FROM citas 
    WHERE doctor_id = ? AND fecha = ? AND hora = ? AND estado != 'cancelada'
");
$stmt->execute([$doctor_id, $fecha, $hora]);
if ($stmt->fetch()) {
    $errores[] = "El doctor ya tiene una cita agendada en ese horario.";
}

// 6. Verificar que el paciente no tenga cita a la misma hora
$stmt = $pdo->prepare("
    SELECT id FROM citas 
    WHERE paciente_id = ? AND fecha = ? AND hora = ? AND estado != 'cancelada'
");
$stmt->execute([$paciente_id, $fecha, $hora]);
if ($stmt->fetch()) {
    $errores[] = "El paciente ya tiene una cita agendada en ese horario.";
}

// Si hay errores, redirigir con mensaje
if (!empty($errores)) {
    $_SESSION['error'] = implode(" ", $errores);
    header("Location: nueva.php?" . http_build_query([
        'paciente_id' => $paciente_id,
        'doctor_id' => $doctor_id,
        'fecha' => $fecha
    ]));
    exit;
}

// Insertar la cita con todos los campos
try {
    $stmt = $pdo->prepare("
        INSERT INTO citas (
            paciente_id, doctor_id, fecha, hora, motivo, 
            estado, created_at, created_by, observaciones, fecha_creacion
        ) VALUES (
            ?, ?, ?, ?, ?, 
            'pendiente', NOW(), ?, ?, CURDATE()
        )
    ");
    
    $stmt->execute([
        $paciente_id,
        $doctor_id,
        $fecha,
        $hora,
        $motivo,
        $created_by,
        $observaciones
    ]);
    
    $cita_id = $pdo->lastInsertId();
    
    $_SESSION['success'] = "Cita agendada correctamente";
    header("Location: detalle.php?id=$cita_id");
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al guardar la cita: " . $e->getMessage();
    header("Location: nueva.php");
}

exit;