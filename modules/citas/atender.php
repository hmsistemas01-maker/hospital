<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_GET['id'];

// Verificar que la cita exista y esté pendiente
$stmt = $pdo->prepare("
    SELECT c.*, p.nombre as paciente_nombre 
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    WHERE c.id = ? AND c.estado = 'pendiente'
");
$stmt->execute([$id]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: lista.php?error=Cita no encontrada o ya no está pendiente");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Actualizar estado de la cita
    $stmt = $pdo->prepare("UPDATE citas SET estado = 'atendida' WHERE id = ?");
    $stmt->execute([$id]);
    
    // Crear entrada en historial clínico
    $stmt = $pdo->prepare("
        INSERT INTO historial_clinico (paciente_id, doctor_id, fecha, diagnostico) 
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $cita['paciente_id'],
        $cita['doctor_id'],
        "Atención de cita programada"
    ]);
    
    $historial_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    header("Location: ../doctor/atender_cita.php?id=$id&historial_id=$historial_id&success=Cita atendida correctamente");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: detalle.php?id=$id&error=" . urlencode($e->getMessage()));
}

exit;