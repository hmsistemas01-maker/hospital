<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: salidas.php");
    exit;
}

// Validar datos obligatorios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    header("Location: salidas.php?error=Faltan datos obligatorios");
    exit;
}

$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);
$motivo = $_POST['motivo'] ?? null;
$usuario_id = $_SESSION['user_id'];

if ($cantidad <= 0) {
    header("Location: salidas.php?error=La cantidad debe ser mayor a 0");
    exit;
}

try {
    // Verificar stock suficiente (suma de todos los lotes)
    $stmt = $pdo->prepare("
        SELECT SUM(cantidad_actual) as stock_total 
        FROM lote 
        WHERE producto_id = ? AND activo = 1
    ");
    $stmt->execute([$producto_id]);
    $stock_total = $stmt->fetchColumn() ?: 0;
    
    if ($stock_total < $cantidad) {
        header("Location: salidas.php?error=Stock insuficiente. Disponible: $stock_total");
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Obtener lotes con stock (priorizando los más antiguos - FIFO)
    $stmt = $pdo->prepare("
        SELECT id, cantidad_actual 
        FROM lote 
        WHERE producto_id = ? AND cantidad_actual > 0 AND activo = 1
        ORDER BY fecha_entrada ASC, fecha_vencimiento ASC
    ");
    $stmt->execute([$producto_id]);
    $lotes = $stmt->fetchAll();
    
    $cantidad_restante = $cantidad;
    
    foreach ($lotes as $lote) {
        if ($cantidad_restante <= 0) break;
        
        $cantidad_a_restar = min($lote['cantidad_actual'], $cantidad_restante);
        
        // Actualizar lote
        $stmt = $pdo->prepare("
            UPDATE lote 
            SET cantidad_actual = cantidad_actual - ? 
            WHERE id = ?
        ");
        $stmt->execute([$cantidad_a_restar, $lote['id']]);
        
        // Registrar movimiento por cada lote afectado
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (
                producto_id, 
                lote_id,
                tipo_movimiento, 
                cantidad, 
                motivo, 
                usuario_id,
                fecha_movimiento
            ) VALUES (?, ?, 'salida', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $producto_id,
            $lote['id'],
            $cantidad_a_restar,
            $motivo,
            $usuario_id
        ]);
        
        $cantidad_restante -= $cantidad_a_restar;
    }
    
    $pdo->commit();
    
    header("Location: productos.php?msg=salida");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: salidas.php?error=" . urlencode("Error al registrar: " . $e->getMessage()));
}
exit;
?>