<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: productos.php");
    exit;
}

try {
    // Verificar si el código ya existe (pero no es el mismo producto)
    $stmt = $pdo->prepare("SELECT id FROM productos WHERE codigo = ? AND id != ?");
    $stmt->execute([$_POST['codigo'], $_POST['id']]);
    if ($stmt->fetch()) {
        header("Location: editar_producto.php?id=" . $_POST['id'] . "&error=El código ya existe");
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE productos 
        SET codigo = ?,
            nombre = ?,
            descripcion = ?,
            categoria_id = ?,
            unidad_medida = ?,
            stock_minimo = ?,
            stock_maximo = ?,
            precio_unitario = ?,
            ubicacion = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['codigo'],
        $_POST['nombre'],
        $_POST['descripcion'] ?? null,
        $_POST['categoria_id'],
        $_POST['unidad_medida'],
        $_POST['stock_minimo'],
        $_POST['stock_maximo'],
        $_POST['precio_unitario'] ?? 0,
        $_POST['ubicacion'] ?? null,
        $_POST['id']
    ]);
    
    header("Location: productos.php?msg=actualizado");
    
} catch (PDOException $e) {
    header("Location: editar_producto.php?id=" . $_POST['id'] . "&error=" . urlencode($e->getMessage()));
}
exit;
?>