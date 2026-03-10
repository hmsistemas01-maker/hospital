<?php
require_once '../../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['producto'])) {
    echo json_encode([]);
    exit;
}

$producto_id = $_GET['producto'];

try {
    $stmt = $pdo->prepare("
        SELECT id_lote, numero_lote, fecha_vencimiento, stock_actual
        FROM lote
        WHERE id_producto = ? AND stock_actual > 0
        ORDER BY fecha_vencimiento ASC
    ");
    $stmt->execute([$producto_id]);
    $lotes = $stmt->fetchAll();
    
    echo json_encode($lotes);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>