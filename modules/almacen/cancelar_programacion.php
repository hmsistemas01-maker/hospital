<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: programaciones.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE entradas_programadas 
        SET estado = 'cancelado' 
        WHERE id = ?
    ");
    $stmt->execute([$_GET['id']]);
    
    header("Location: programaciones.php?msg=cancelado");
    
} catch (PDOException $e) {
    header("Location: programaciones.php?error=" . urlencode($e->getMessage()));
}
exit;
?>