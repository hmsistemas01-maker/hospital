<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_GET['id'];

// Verificar que la cita exista
$stmt = $pdo->prepare("SELECT * FROM citas WHERE id = ?");
$stmt->execute([$id]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: lista.php?error=Cita no encontrada");
    exit;
}

if ($cita['estado'] != 'pendiente') {
    header("Location: detalle.php?id=$id&error=Solo se pueden cancelar citas pendientes");
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: detalle.php?id=$id&success=Cita cancelada correctamente");
    
} catch (PDOException $e) {
    header("Location: detalle.php?id=$id&error=" . urlencode($e->getMessage()));
}

exit;