<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: entrada.php");
    exit;
}

$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);
$precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
$proveedor_id = $_POST['proveedor_id'] ?? null;
$referencia = $_POST['referencia'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$usuario_id = $_SESSION['user_id'];

if ($cantidad <= 0) {
    header("Location: entrada.php?error=La cantidad debe ser mayor a 0");
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que el producto existe y es de almacén
    $stmt = $pdo->prepare("
        SELECT * FROM productos 
        WHERE id = ? AND departamento = 'almacen' AND activo = 1
    ");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        throw new Exception("Producto no encontrado en almacén");
    }

    // Buscar o crear lote general para almacén
    $lote_numero = 'ALM-' . date('Ymd') . '-' . rand(100, 999);
    
    $stmt = $pdo->prepare("
        INSERT INTO almacen_lotes 
        (producto_id, numero_lote, cantidad_inicial, cantidad_actual, 
         proveedor_id, fecha_entrada, ubicacion, activo)
        VALUES (?, ?, ?, ?, ?, CURDATE(), ?, 1)
    ");
    $stmt->execute([
        $producto_id, 
        $lote_numero, 
        $cantidad, 
        $cantidad, 
        $proveedor_id, 
        $producto['ubicacion'] ?? null
    ]);
    $lote_id = $pdo->lastInsertId();

    // Registrar movimiento
    $motivo = "Entrada a almacén" . ($referencia ? " - Ref: $referencia" : "");
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario 
        (departamento, producto_id, lote_id, tipo_movimiento, cantidad, 
         motivo, referencia, usuario_id, fecha_movimiento, destino, observaciones)
        VALUES ('almacen', ?, ?, 'entrada', ?, ?, ?, ?, NOW(), 'almacen', ?)
    ");
    $stmt->execute([
        $producto_id, 
        $lote_id, 
        $cantidad, 
        $motivo, 
        $referencia, 
        $usuario_id, 
        $observaciones
    ]);

    $pdo->commit();
    
    $_SESSION['success'] = "Entrada registrada correctamente";
    header("Location: productos.php?success=1");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en entrada almacén: " . $e->getMessage());
    header("Location: entrada.php?error=" . urlencode($e->getMessage()));
}
exit;
?>