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
            r.id,
            COALESCE(p.nombre, 'Sin paciente') as paciente,
            DATE_FORMAT(r.fecha, '%d/%m/%Y %H:%i') as fecha
        FROM recetas r
        LEFT JOIN pacientes p ON r.id_paciente = p.id
        WHERE r.estado = 'pendiente'
        AND (r.id LIKE ? OR p.nombre LIKE ?)
        ORDER BY r.fecha DESC
        LIMIT 10
    ");
    
    $busqueda = "%$termino%";
    $stmt->execute([$busqueda, $busqueda]);
    $resultados = $stmt->fetchAll();
    
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>