<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: lista.php");
    exit;
}

$id = (int) $_POST['id'];
$paciente_id = (int) $_POST['paciente_id'];
$doctor_id = (int) $_POST['doctor_id'];
$fecha = $_POST['fecha'];
$hora = $_POST['hora'];
$motivo = $_POST['motivo'] ?? null;

// Validar que la cita aún esté pendiente
$stmt = $pdo->prepare("SELECT estado FROM citas WHERE id = ?");
$stmt->execute([$id]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: lista.php?error=Cita no encontrada");
    exit;
}

if ($cita['estado'] != 'pendiente') {
    header("Location: editar.php?id=$id&error=No se puede editar una cita que ya fue atendida o cancelada");
    exit;
}

// Validar fecha
if ($fecha < date('Y-m-d')) {
    header("Location: editar.php?id=$id&error=No se puede agendar en fechas pasadas");
    exit;
}

// Obtener día de la semana para validar horario del doctor
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

// Verificar que el doctor trabaje ese día
$stmt = $pdo->prepare("SELECT * FROM doctor_horarios WHERE doctor_id = ? AND dia = ?");
$stmt->execute([$doctor_id, $dia_semana]);
if (!$stmt->fetch()) {
    header("Location: editar.php?id=$id&error=El doctor no trabaja el día seleccionado");
    exit;
}

// Verificar disponibilidad (excluyendo la cita actual)
$stmt = $pdo->prepare("
    SELECT id FROM citas 
    WHERE doctor_id = ? AND fecha = ? AND hora = ? AND id != ? AND estado != 'cancelada'
");
$stmt->execute([$doctor_id, $fecha, $hora, $id]);
if ($stmt->fetch()) {
    header("Location: editar.php?id=$id&error=El doctor ya tiene una cita en ese horario");
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE citas 
        SET paciente_id = ?, doctor_id = ?, fecha = ?, hora = ?, motivo = ?
        WHERE id = ?
    ");
    $stmt->execute([$paciente_id, $doctor_id, $fecha, $hora, $motivo, $id]);
    
    header("Location: detalle.php?id=$id&success=Cita actualizada correctamente");
    
} catch (PDOException $e) {
    header("Location: editar.php?id=$id&error=" . urlencode("Error en la base de datos: " . $e->getMessage()));
}

exit;