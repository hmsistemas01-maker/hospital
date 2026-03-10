<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "ID de excepción no válido";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Obtener doctor_id antes de cancelar
$stmt = $pdo->prepare("SELECT doctor_id FROM doctor_excepciones WHERE id = ?");
$stmt->execute([$id]);
$excepcion = $stmt->fetch();

if (!$excepcion) {
    $_SESSION['error'] = "Excepción no encontrada";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

try {
    // Verificar que la excepción no haya terminado ya
    $stmt = $pdo->prepare("
        SELECT * FROM doctor_excepciones 
        WHERE id = ? AND fecha_fin < CURDATE()
    ");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "No se puede cancelar una excepción que ya terminó";
        header("Location: horarios.php?doctor_id=" . $excepcion['doctor_id'] . "&action=excepciones");
        exit;
    }

    // Cancelar la excepción (soft delete)
    $stmt = $pdo->prepare("UPDATE doctor_excepciones SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Excepción cancelada correctamente";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al cancelar: " . $e->getMessage();
}

header("Location: horarios.php?doctor_id=" . $excepcion['doctor_id'] . "&action=excepciones");
exit;