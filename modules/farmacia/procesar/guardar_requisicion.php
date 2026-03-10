<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../productos.php?tab=nueva_requisicion");
    exit;
}

// Obtener datos del formulario
$proveedor_id = $_POST['proveedor_id'] ?? null;
$fecha_requerida = $_POST['fecha_requerida'] ?? null;
$observaciones = trim($_POST['observaciones'] ?? '');
$solicitante_id = $_SESSION['user_id'];

$productos = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];

try {
    // Validaciones
    if (empty($proveedor_id)) {
        throw new Exception("Debe seleccionar un proveedor");
    }
    
    if (empty($productos) || count($productos) == 0) {
        throw new Exception("Debe agregar al menos un producto");
    }

    $pdo->beginTransaction();

    // Generar número de requisición único
    $fecha = date('Ymd');
    $numero = rand(100, 999);
    $numero_requisicion = "REQ-{$fecha}-{$numero}";

    // Insertar requisición
    $stmt = $pdo->prepare("
        INSERT INTO requisiciones 
        (numero_requisicion, solicitante_id, fecha_solicitud, estado, observaciones)
        VALUES (?, ?, NOW(), 'pendiente', ?)
    ");
    $stmt->execute([$numero_requisicion, $solicitante_id, $observaciones]);
    $requisicion_id = $pdo->lastInsertId();

    // Insertar detalles de la requisición
    $stmt = $pdo->prepare("
        INSERT INTO requisicion_detalle 
        (requisicion_id, producto_id, cantidad_solicitada, cantidad_entregada)
        VALUES (?, ?, ?, 0)
    ");

    foreach ($productos as $i => $producto_id) {
        if (empty($producto_id)) continue;
        
        $cantidad = $cantidades[$i] ?? 1;
        $stmt->execute([$requisicion_id, $producto_id, $cantidad]);
    }

    $pdo->commit();
    
    header("Location: ../productos.php?tab=requisiciones&success=Requisición $numero_requisicion creada correctamente");

} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: ../productos.php?tab=nueva_requisicion&error=" . urlencode($e->getMessage()));
}

exit;