<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../reportes.php?error=ID de lote no válido");
    exit;
}

$lote_id = $_GET['id'];

try {
    $pdo->beginTransaction();

    // Obtener información del lote
    $stmt = $pdo->prepare("SELECT * FROM lote WHERE id = ?");
    $stmt->execute([$lote_id]);
    $lote = $stmt->fetch();

    if (!$lote) {
        throw new Exception("Lote no encontrado");
    }

    // Registrar movimiento de baja por vencimiento
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario 
        (producto_id, lote_id, tipo_movimiento, cantidad, motivo, usuario_id, destino, fecha_movimiento)
        VALUES (?, ?, 'baja', ?, 'Producto vencido', ?, 'baja', NOW())
    ");
    $stmt->execute([$lote['producto_id'], $lote_id, $lote['cantidad_actual'], $_SESSION['user_id']]);

    // Marcar lote como inactivo (baja)
    $stmt = $pdo->prepare("UPDATE lote SET activo = 0, cantidad_actual = 0 WHERE id = ?");
    $stmt->execute([$lote_id]);

    $pdo->commit();
    header("Location: ../reportes.php?success=Lote dado de baja correctamente");

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: ../reportes.php?error=" . urlencode($e->getMessage()));
}

exit;