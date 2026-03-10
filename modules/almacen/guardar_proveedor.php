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
        INSERT INTO proveedores (nombre, rfc, contacto, telefono, email, direccion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['nombre'],
        $_POST['rfc'] ?? null,
        $_POST['contacto'] ?? null,
        $_POST['telefono'] ?? null,
        $_POST['email'] ?? null,
        $_POST['direccion'] ?? null
    ]);
    
    header("Location: proveedores.php?msg=guardado");
    
} catch (PDOException $e) {
    header("Location: proveedores.php?error=" . urlencode($e->getMessage()));
}
exit;
?>