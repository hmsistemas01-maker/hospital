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

try {
    $pdo->beginTransaction();
    
    // Activar usuario
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
    $stmt->execute([$id_usuario]);
    
    // Si es doctor, activar también en tabla doctores
    $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $stmt = $pdo->prepare("UPDATE doctores SET activo = 1 WHERE nombre = ?");
        $stmt->execute([$usuario['nombre']]);
    }
    
    $pdo->commit();
    
    header("Location: usuarios.php?success=Usuario activado correctamente");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: usuarios.php?error=Error al activar: " . urlencode($e->getMessage()));
}

exit;