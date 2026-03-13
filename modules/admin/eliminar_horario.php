<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "ID de horario no válido";
    header("Location: usuarios.php");
    exit;
}

// Obtener información antes de eliminar para redirigir correctamente
$stmt = $pdo->prepare("SELECT doctor_id, usuario_id FROM doctor_horarios WHERE id = ?");
$stmt->execute([$id]);
$horario = $stmt->fetch();

if (!$horario) {
    $_SESSION['error'] = "Horario no encontrado";
    header("Location: usuarios.php");
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM doctor_horarios WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Horario eliminado correctamente";
    
    // Redirigir según corresponda
    if ($horario['doctor_id']) {
        header("Location: horarios.php?doctor_id=" . $horario['doctor_id'] . "&action=ver");
    } else {
        header("Location: horarios.php?usuario_id=" . $horario['usuario_id'] . "&action=ver");
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
    header("Location: usuarios.php");
}
exit;