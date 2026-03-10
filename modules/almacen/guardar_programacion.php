<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: programar_entrada.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO entradas_programadas (
            producto_id, 
            cantidad, 
            fecha_programada, 
            proveedor_id, 
            observaciones, 
            usuario_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['producto_id'],
        $_POST['cantidad'],
        $_POST['fecha_programada'],
        $_POST['proveedor_id'] ?: null,
        $_POST['observaciones'] ?? null,
        $_SESSION['user_id']
    ]);
    
    header("Location: index.php?msg=programado");
    
} catch (PDOException $e) {
    header("Location: programar_entrada.php?error=" . urlencode($e->getMessage()));
}
exit;
?>