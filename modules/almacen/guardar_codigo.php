<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: productos.php");
    exit;
}

$producto_id = $_POST['producto_id'];
$codigo = $_POST['codigo'];

try {
    // Verificar que el código no exista ya
    $stmt = $pdo->prepare("SELECT id FROM productos_codigos WHERE codigo_barras = ?");
    $stmt->execute([$codigo]);
    if ($stmt->fetch()) {
        header("Location: editar_producto.php?id=$producto_id&error=El código ya existe");
        exit;
    }
    
    // Insertar código secundario
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 0)
    ");
    $stmt->execute([$producto_id, $codigo]);
    
    header("Location: editar_producto.php?id=$producto_id&msg=codigo_agregado");
    
} catch (PDOException $e) {
    header("Location: editar_producto.php?id=$producto_id&error=" . urlencode($e->getMessage()));
}
exit;
?>