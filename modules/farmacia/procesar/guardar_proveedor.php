<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../productos.php");
    exit;
}

$rfc = $_POST['rfc'];
$nombre = $_POST['nombre'];
$contacto = $_POST['contacto'] ?? null;
$telefono = $_POST['telefono'] ?? null;
$email = $_POST['email'] ?? null;
$direccion = $_POST['direccion'] ?? null;

try {
    // Verificar si el RFC ya existe
    $stmt = $pdo->prepare("SELECT id FROM proveedores WHERE rfc = ?");
    $stmt->execute([$rfc]);
    if ($stmt->fetch()) {
        header("Location: ../productos.php?tab=nuevo_proveedor&error=El RFC ya existe");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO proveedores (rfc, nombre, contacto, telefono, email, direccion, activo)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([$rfc, $nombre, $contacto, $telefono, $email, $direccion]);
    
    header("Location: ../productos.php?tab=proveedores&success=Proveedor guardado correctamente");
    
} catch (PDOException $e) {
    header("Location: ../productos.php?tab=nuevo_proveedor&error=" . urlencode($e->getMessage()));
}

exit;