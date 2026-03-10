<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'requiere_receta' => 0,
        'control_lote' => 0,
        'control_vencimiento' => 0
    ]);
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT requiere_receta, control_lote, control_vencimiento FROM categorias_productos WHERE id = ?");
    $stmt->execute([$id]);
    $categoria = $stmt->fetch();
    
    if ($categoria) {
        echo json_encode([
            'requiere_receta' => $categoria['requiere_receta'],
            'control_lote' => $categoria['control_lote'],
            'control_vencimiento' => $categoria['control_vencimiento']
        ]);
    } else {
        echo json_encode([
            'requiere_receta' => 0,
            'control_lote' => 0,
            'control_vencimiento' => 0
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'requiere_receta' => 0,
        'control_lote' => 0,
        'control_vencimiento' => 0,
        'error' => $e->getMessage()
    ]);
}
?>