<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "ID de usuario no válido";
    header("Location: usuarios.php");
    exit;
}

// Verificar que no sea el propio usuario
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = "No puedes eliminar tu propio usuario";
    header("Location: usuarios.php");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Obtener información del usuario antes de eliminar
    $stmt = $pdo->prepare("SELECT nombre, rol FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    // 1. Eliminar registros relacionados en usuario_modulos
    $stmt = $pdo->prepare("DELETE FROM usuario_modulos WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // 2. Eliminar registros relacionados en usuario_permisos
    $stmt = $pdo->prepare("DELETE FROM usuario_permisos WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // 3. Si es doctor, eliminar o desvincular de doctores
    if ($usuario['rol'] == 'doctor') {
        // Opción 1: Eliminar el doctor también
        $stmt = $pdo->prepare("DELETE FROM doctores WHERE nombre = ?");
        $stmt->execute([$usuario['nombre']]);
        
        // Opción 2: Si prefieres mantener el doctor pero desvincularlo:
        // $stmt = $pdo->prepare("UPDATE doctores SET activo = 0 WHERE nombre = ?");
        // $stmt->execute([$usuario['nombre']]);
    }
    
    // 4. Eliminar horarios relacionados (si los tiene)
    $stmt = $pdo->prepare("DELETE FROM doctor_horarios WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // 5. Eliminar excepciones relacionadas
    $stmt = $pdo->prepare("DELETE FROM doctor_excepciones WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // 6. Finalmente, eliminar el usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Usuario eliminado permanentemente";
    header("Location: usuarios.php");
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error al eliminar usuario: " . $e->getMessage());
    $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
    header("Location: usuarios.php");
}
exit;