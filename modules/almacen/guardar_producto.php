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
    // Verificar si el código principal ya existe
    $stmt = $pdo->prepare("SELECT id FROM productos_codigos WHERE codigo_barras = ?");
    $stmt->execute([$_POST['codigo']]);
    if ($stmt->fetch()) {
        header("Location: productos.php?error=El código '" . $_POST['codigo'] . "' ya existe");
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Insertar producto
    $stmt = $pdo->prepare("
        INSERT INTO productos (
            codigo, nombre, descripcion, categoria_id, unidad_medida,
            stock_actual, stock_minimo, stock_maximo, precio_unitario, ubicacion, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $_POST['codigo'],
        $_POST['nombre'],
        $_POST['descripcion'] ?? null,
        $_POST['categoria_id'],
        $_POST['unidad_medida'],
        $_POST['stock_actual'] ?? 0,
        $_POST['stock_minimo'] ?? 5,
        $_POST['stock_maximo'] ?? 100,
        $_POST['precio_unitario'] ?? 0,
        $_POST['ubicacion'] ?? null
    ]);
    
    $producto_id = $pdo->lastInsertId();
    
    // Registrar código principal
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$producto_id, $_POST['codigo']]);
    
    // Registrar código secundario si existe
    if (!empty($_POST['codigo_secundario'])) {
        // Verificar que el código secundario no exista
        $stmt = $pdo->prepare("SELECT id FROM productos_codigos WHERE codigo_barras = ?");
        $stmt->execute([$_POST['codigo_secundario']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$producto_id, $_POST['codigo_secundario']]);
        }
    }
    
    $pdo->commit();
    
    header("Location: productos.php?msg=guardado");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) {
        header("Location: productos.php?error=El código ya existe");
    } else {
        header("Location: productos.php?error=" . urlencode($e->getMessage()));
    }
}
exit;
?>