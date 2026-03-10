<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: categorias.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO categorias_productos (
            nombre, descripcion, tipo, 
            requiere_receta, control_lote, control_vencimiento
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['nombre'],
        $_POST['descripcion'] ?? null,
        $_POST['tipo'],
        isset($_POST['requiere_receta']) ? 1 : 0,
        isset($_POST['control_lote']) ? 1 : 0,
        isset($_POST['control_vencimiento']) ? 1 : 0
    ]);
    
    header("Location: categorias.php?msg=guardado");
    
} catch (PDOException $e) {
    header("Location: categorias.php?error=" . urlencode($e->getMessage()));
}
exit;
?>