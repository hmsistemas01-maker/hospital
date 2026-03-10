<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
} else {
    // 4. Verificar que la hora esté dentro del horario del doctor
    $hora_valida = false;
    foreach ($horarios as $h) {
        if ($hora >= $h['hora_inicio'] && $hora < $h['hora_fin']) {
            $hora_valida = true;
            break;
        }
    }
    if (!$hora_valida) {
        $horarios_str = [];
        foreach ($horarios as $h) {
            $horarios_str[] = substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5);
        }
        $errores[] = "La hora seleccionada está fuera del horario del doctor. Horarios: " . implode(', ', $horarios_str);
    }
}

// ========== NUEVA VALIDACIÓN: VERIFICAR EXCEPCIONES ==========
$stmt = $pdo->prepare("
    SELECT * FROM doctor_excepciones 
    WHERE doctor_id = ? 
    AND ? BETWEEN fecha_inicio AND fecha_fin
    AND activo = 1
");
$stmt->execute([$doctor_id, $fecha]);
$excepcion = $stmt->fetch();

if ($excepcion) {
    if ($excepcion['tipo'] == 'horario_especial') {
        // Para horario especial, validar que la hora esté dentro del rango especial
        if ($hora < $excepcion['hora_entrada'] || $hora > $excepcion['hora_salida']) {
            $errores[] = "El doctor tiene un horario especial ese día de " . 
                         substr($excepcion['hora_entrada'], 0, 5) . " a " . 
                         substr($excepcion['hora_salida'], 0, 5);
        }
    } else {
        // Vacaciones, permiso, capacitación, festivo
        $tipos = [
            'vacaciones' => '🏖️ está de vacaciones',
            'permiso' => '📋 tiene un permiso',
            'capacitacion' => '📚 está en capacitación',
            'festivo' => '🎉 es día festivo'
        ];
        $mensaje = $tipos[$excepcion['tipo']] ?? 'no está disponible';
        $errores[] = "El doctor $mensaje en la fecha seleccionada.\nMotivo: " . $excepcion['motivo'];
    }
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

// Insertar la cita
try {
    $stmt = $pdo->prepare("
        INSERT INTO citas (
            paciente_id, doctor_id, fecha, hora, motivo, 
            estado, created_at, created_by, observaciones, fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW(), ?, ?, CURDATE())
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

exit;<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
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
} else {
    // 4. Verificar que la hora esté dentro del horario del doctor
    $hora_valida = false;
    foreach ($horarios as $h) {
        if ($hora >= $h['hora_inicio'] && $hora < $h['hora_fin']) {
            $hora_valida = true;
            break;
        }
    }
    if (!$hora_valida) {
        $horarios_str = [];
        foreach ($horarios as $h) {
            $horarios_str[] = substr($h['hora_inicio'], 0, 5) . ' - ' . substr($h['hora_fin'], 0, 5);
        }
        $errores[] = "La hora seleccionada está fuera del horario del doctor. Horarios: " . implode(', ', $horarios_str);
    }
}

// 5. VERIFICAR EXCEPCIONES (VACACIONES, PERMISOS)
$stmt = $pdo->prepare("
    SELECT * FROM doctor_excepciones 
    WHERE doctor_id = ? 
    AND ? BETWEEN fecha_inicio AND fecha_fin
    AND activo = 1
");
$stmt->execute([$doctor_id, $fecha]);
$excepcion = $stmt->fetch();

if ($excepcion) {
    if ($excepcion['tipo'] == 'horario_especial') {
        // Para horario especial, validar que la hora esté dentro del rango especial
        if ($hora < $excepcion['hora_entrada'] || $hora > $excepcion['hora_salida']) {
            $errores[] = "El doctor tiene un horario especial ese día de " . 
                         substr($excepcion['hora_entrada'], 0, 5) . " a " . 
                         substr($excepcion['hora_salida'], 0, 5);
        }
    } else {
        // Vacaciones, permiso, capacitación, festivo
        $tipos = [
            'vacaciones' => '🏖️ está de vacaciones',
            'permiso' => '📋 tiene un permiso',
            'capacitacion' => '📚 está en capacitación',
            'festivo' => '🎉 es día festivo'
        ];
        $mensaje = $tipos[$excepcion['tipo']] ?? 'no está disponible';
        $errores[] = "El doctor $mensaje en la fecha seleccionada.\nMotivo: " . $excepcion['motivo'];
    }
}

// 6. Verificar que no haya otra cita en el mismo horario
$stmt = $pdo->prepare("
    SELECT id FROM citas
    WHERE doctor_id = ? AND fecha = ? AND hora = ? AND estado != 'cancelada'
");
$stmt->execute([$doctor_id, $fecha, $hora]);
if ($stmt->fetch()) {
    $errores[] = "El doctor ya tiene una cita agendada en ese horario.";
}

// 7. Verificar que el paciente no tenga cita a la misma hora
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

// Insertar la cita
try {
    $stmt = $pdo->prepare("
        INSERT INTO citas (
            paciente_id, doctor_id, fecha, hora, motivo, 
            estado, created_at, created_by, observaciones, fecha_creacion
        ) VALUES (?, ?, ?, ?, ?, 'pendiente', NOW(), ?, ?, CURDATE())
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
