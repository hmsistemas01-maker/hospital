<?php
require_once '../../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

$termino = $_GET['q'] ?? '';

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Búsqueda por código de barras o nombre
    $stmt = $pdo->prepare("
        SELECT 
            p.id_producto,
            p.codigo_barras,
            p.nombre,
            p.categoria,
            p.unidad_medida,
            p.stock_minimo,
            p.requiere_lote,
            COALESCE(SUM(l.stock_actual), 0) as stock_actual
        FROM productos p
        LEFT JOIN lote l ON p.id_producto = l.id_producto
        WHERE p.estado = 1 
        AND (p.codigo_barras LIKE ? OR p.nombre LIKE ?)
        GROUP BY p.id_producto
        ORDER BY 
            CASE 
                WHEN p.codigo_barras = ? THEN 1
                WHEN p.codigo_barras LIKE ? THEN 2
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