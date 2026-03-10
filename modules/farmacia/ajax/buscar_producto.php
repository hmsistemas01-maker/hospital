<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

$termino = $_GET['q'] ?? '';

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.codigo,
            p.nombre,
            p.control_lote,
            p.control_vencimiento,
            COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock
        FROM productos p
        WHERE p.activo = 1 
        AND (p.codigo LIKE ? OR p.nombre LIKE ?)
        ORDER BY 
            CASE 
                WHEN p.codigo = ? THEN 1
                WHEN p.codigo LIKE ? THEN 2
                ELSE 3
            END,
            p.nombre
        LIMIT 10
    ");
    
    $busqueda = "%$termino%";
    $stmt->execute([$busqueda, $busqueda, $termino, "$termino%"]);
    $resultados = $stmt->fetchAll();
    
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>