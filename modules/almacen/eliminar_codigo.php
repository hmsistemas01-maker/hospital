<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: productos.php");
    exit;
}

$id = $_GET['id'];

try {
    // Obtener producto_id antes de eliminar
    $stmt = $pdo->prepare("SELECT producto_id FROM productos_codigos WHERE id = ?");
    $stmt->execute([$id]);
    $codigo = $stmt->fetch();
    
    if (!$codigo) {
        header("Location: productos.php?error=Código no encontrado");
        exit;
    }
    
    $producto_id = $codigo['producto_id'];
    
    // Eliminar código
    $stmt = $pdo->prepare("DELETE FROM productos_codigos WHERE id = ? AND es_principal = 0");
    $stmt->execute([$id]);
    
    header("Location: editar_producto.php?id=$producto_id&msg=codigo_eliminado");
    
} catch (PDOException $e) {
    header("Location: productos.php?error=" . urlencode($e->getMessage()));
}
exit;
?>