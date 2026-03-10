<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: entradas.php");
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Insertar producto
    $stmt = $pdo->prepare("
        INSERT INTO productos (
            codigo, nombre, categoria_id, unidad_medida,
            stock_minimo, stock_maximo, precio_unitario, stock_actual, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1)
    ");
    
    $stmt->execute([
        $_POST['codigo_principal'],
        $_POST['nombre'],
        $_POST['categoria_id'],
        $_POST['unidad_medida'],
        $_POST['stock_minimo'],
        $_POST['stock_maximo'],
        $_POST['precio_unitario'] ?? 0
    ]);
    
    $producto_id = $pdo->lastInsertId();
    
    // Registrar código principal
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$producto_id, $_POST['codigo_principal']]);
    
    // Registrar código secundario si existe
    if (!empty($_POST['codigo_secundario'])) {
        $stmt = $pdo->prepare("
            INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
            VALUES (?, ?, 0)
        ");
        $stmt->execute([$producto_id, $_POST['codigo_secundario']]);
    }
    
    $pdo->commit();
    
    // Redirigir a entrada con el producto seleccionado
    header("Location: entradas.php?producto=$producto_id");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) {
        header("Location: entradas.php?codigo=" . urlencode($_POST['codigo_principal']) . "&error=duplicado");
    } else {
        header("Location: entradas.php?error=" . urlencode($e->getMessage()));
    }
}
exit;
?>