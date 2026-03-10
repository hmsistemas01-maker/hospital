<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Obtener datos de la programación
    $stmt = $pdo->prepare("SELECT * FROM entradas_programadas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $programacion = $stmt->fetch();
    
    if (!$programacion) {
        header("Location: index.php?error=Programación no encontrada");
        exit;
    }
    
    // Crear entrada real
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario (
            producto_id, 
            tipo_movimiento, 
            cantidad, 
            motivo, 
            proveedor_id, 
            usuario_id,
            fecha_movimiento
        ) VALUES (?, 'entrada', ?, 'Entrada programada', ?, ?, NOW())
    ");
    
    $stmt->execute([
        $programacion['producto_id'],
        $programacion['cantidad'],
        $programacion['proveedor_id'],
        $_SESSION['user_id']
    ]);
    
    // Actualizar programación
    $stmt = $pdo->prepare("
        UPDATE entradas_programadas 
        SET estado = 'recibido' 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    
    $pdo->commit();
    
    header("Location: index.php?msg=recibido");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit;
?>