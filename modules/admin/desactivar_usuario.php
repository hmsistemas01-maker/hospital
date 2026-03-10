<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=ID no válido");
    exit;
}

$id_usuario = (int) $_GET['id'];

// Verificar que no sea el propio usuario
if ($id_usuario == $_SESSION['user_id']) {
    header("Location: usuarios.php?error=No puedes desactivarte a ti mismo");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Desactivar usuario
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->execute([$id_usuario]);
    
    // Si es doctor, desactivar también en tabla doctores
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $stmt = $pdo->prepare("UPDATE doctores SET activo = 0 WHERE nombre = ?");
        $stmt->execute([$usuario['nombre']]);
    }
    
    $pdo->commit();
    
    header("Location: usuarios.php?success=Usuario desactivado correctamente");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: usuarios.php?error=Error al desactivar: " . urlencode($e->getMessage()));
}

exit;