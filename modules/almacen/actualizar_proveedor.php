<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: proveedores.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE proveedores 
        SET nombre = ?, rfc = ?, contacto = ?, telefono = ?, email = ?, direccion = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['nombre'],
        $_POST['rfc'] ?? null,
        $_POST['contacto'] ?? null,
        $_POST['telefono'] ?? null,
        $_POST['email'] ?? null,
        $_POST['direccion'] ?? null,
        $_POST['id']
    ]);
    
    header("Location: proveedores.php?msg=actualizado");
    
} catch (PDOException $e) {
    header("Location: editar_proveedor.php?id=" . $_POST['id'] . "&error=" . urlencode($e->getMessage()));
}
exit;
?>