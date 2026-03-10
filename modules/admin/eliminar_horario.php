<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "ID de horario no válido";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Obtener doctor_id antes de eliminar para redirigir
$stmt = $pdo->prepare("SELECT doctor_id FROM doctor_horarios WHERE id = ?");
$stmt->execute([$id]);
$horario = $stmt->fetch();

if (!$horario) {
    $_SESSION['error'] = "Horario no encontrado";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

$doctor_id = $horario['doctor_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM doctor_horarios WHERE id = ?");
    $resultado = $stmt->execute([$id]);
    
    if ($resultado) {
        $_SESSION['success'] = "Horario eliminado correctamente";
    } else {
        $_SESSION['success'] = "Horario eliminado correctamente";
    }
    
    header("Location: horarios.php?doctor_id=$doctor_id");
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
    header("Location: horarios.php?doctor_id=$doctor_id");
}

exit;