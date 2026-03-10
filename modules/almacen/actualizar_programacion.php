<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: programaciones.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE entradas_programadas 
        SET producto_id = ?,
            cantidad = ?,
            fecha_programada = ?,
            proveedor_id = ?,
            observaciones = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['producto_id'],
        $_POST['cantidad'],
        $_POST['fecha_programada'],
        $_POST['proveedor_id'] ?: null,
        $_POST['observaciones'] ?? null,
        $_POST['id']
    ]);
    
    header("Location: programaciones.php?msg=actualizado");
    
} catch (PDOException $e) {
    header("Location: editar_programacion.php?id=" . $_POST['id'] . "&error=" . urlencode($e->getMessage()));
}
exit;
?>