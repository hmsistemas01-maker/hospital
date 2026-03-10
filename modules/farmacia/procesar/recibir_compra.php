<?php
require_once '../../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../entrada.php");
    exit;
}

$proveedor_id = $_POST['proveedor_id'] ?? null;
$referencia = $_POST['referencia'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$observaciones = $_POST['observaciones'] ?? '';
$usuario_id = $_SESSION['user_id'] ?? 1;
$requisicion_id = $_POST['requisicion_id'] ?? null;

$productos = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$precios = $_POST['precio_unitario'] ?? [];
$lotes = $_POST['lote'] ?? [];
$vencimientos = $_POST['vencimiento'] ?? [];
$requisicion_detalle_ids = $_POST['requisicion_detalle_id'] ?? [];

try {
    // Validar que haya productos
    if (empty($productos)) {
        throw new Exception("No hay productos para registrar");
    }

    $pdo->beginTransaction();

    foreach ($productos as $i => $producto_id) {
        $cantidad = $cantidades[$i] ?? 0;
        $precio = $precios[$i] ?? 0;
        $lote_numero = $lotes[$i] ?? null;
        $vencimiento = $vencimientos[$i] ?? null;

        if ($cantidad <= 0) continue;

        // Obtener información del producto
        $stmt = $pdo->prepare("
            SELECT p.*, c.control_lote, c.control_vencimiento
            FROM productos p
            LEFT JOIN categorias_productos c ON p.categoria_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("Producto ID $producto_id no encontrado");
        }

        $lote_id = null;

        // ============================================
        // VERSIÓN MEJORADA - CREACIÓN DE LOTES
        // ============================================
        
        if ($producto['control_lote'] && !empty($lote_numero)) {
            // Verificar si el lote YA EXISTE con la MISMA fecha de vencimiento
            $stmt = $pdo->prepare("
                SELECT id, cantidad_actual, fecha_vencimiento FROM lote 
                WHERE producto_id = ? AND numero_lote = ? 
                AND (
                    (fecha_vencimiento IS NULL AND ? IS NULL) 
                    OR fecha_vencimiento = ?
                )
            ");
            $stmt->execute([$producto_id, $lote_numero, $vencimiento, $vencimiento]);
            $lote_existente = $stmt->fetch();

            if ($lote_existente) {
                // Mismo lote y misma fecha -> actualizar cantidad
                $lote_id = $lote_existente['id'];
                $stmt = $pdo->prepare("
                    UPDATE lote SET 
                        cantidad_actual = cantidad_actual + ?,
                        cantidad_inicial = cantidad_inicial + ?
                    WHERE id = ?
                ");
                $stmt->execute([$cantidad, $cantidad, $lote_id]);
                
                // Registrar en log (opcional)
                error_log("Lote actualizado: ID $lote_id, nueva cantidad: " . ($lote_existente['cantidad_actual'] + $cantidad));
                
            } else {
                // Verificar si existe el mismo número de lote pero con diferente fecha
                $stmt = $pdo->prepare("
                    SELECT id, fecha_vencimiento FROM lote 
                    WHERE producto_id = ? AND numero_lote = ? 
                    AND fecha_vencimiento IS NOT NULL
                ");
                $stmt->execute([$producto_id, $lote_numero]);
                $lotes_diferentes = $stmt->fetchAll();
                
                if (count($lotes_diferentes) > 0) {
                    // Ya existe el mismo lote con otra fecha - crear nuevo registro
                    error_log("Creando nuevo lote para $lote_numero con fecha $vencimiento (diferente a las existentes)");
                }
                
                // Crear NUEVO lote (aunque tenga el mismo número, es diferente fecha)
                $stmt = $pdo->prepare("
                    INSERT INTO lote 
                    (producto_id, numero_lote, fecha_vencimiento, cantidad_inicial, cantidad_actual, fecha_entrada, activo)
                    VALUES (?, ?, ?, ?, ?, NOW(), 1)
                ");
                $stmt->execute([$producto_id, $lote_numero, $vencimiento, $cantidad, $cantidad]);
                $lote_id = $pdo->lastInsertId();
                
                error_log("Nuevo lote creado: ID $lote_id, $lote_numero, vence: $vencimiento");
            }
            
        } elseif (!$producto['control_lote']) {
            // Lote general para productos sin control
            $stmt = $pdo->prepare("
                SELECT id, cantidad_actual FROM lote 
                WHERE producto_id = ? AND numero_lote = 'GENERAL'
            ");
            $stmt->execute([$producto_id]);
            $lote_general = $stmt->fetch();

            if ($lote_general) {
                $lote_id = $lote_general['id'];
                $stmt = $pdo->prepare("
                    UPDATE lote SET 
                        cantidad_actual = cantidad_actual + ?,
                        cantidad_inicial = cantidad_inicial + ?
                    WHERE id = ?
                ");
                $stmt->execute([$cantidad, $cantidad, $lote_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO lote 
                    (producto_id, numero_lote, cantidad_inicial, cantidad_actual, fecha_entrada, activo)
                    VALUES (?, 'GENERAL', ?, ?, NOW(), 1)
                ");
                $stmt->execute([$producto_id, $cantidad, $cantidad]);
                $lote_id = $pdo->lastInsertId();
            }
        }

        // Registrar movimiento en inventario - El precio va en el motivo
        $motivo = "Factura: " . $referencia . ", Precio: $" . number_format($precio, 2);
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario 
            (producto_id, lote_id, tipo_movimiento, cantidad, motivo, usuario_id, destino, fecha_movimiento)
            VALUES (?, ?, 'entrada', ?, ?, ?, 'farmacia', NOW())
        ");
        $stmt->execute([$producto_id, $lote_id, $cantidad, $motivo, $usuario_id]);

        // Si viene de requisición, actualizar detalle (SOLO cantidad_entregada)
        if (isset($requisicion_detalle_ids[$i])) {
            $detalle_id = $requisicion_detalle_ids[$i];
            $stmt = $pdo->prepare("
                UPDATE requisicion_detalle 
                SET cantidad_entregada = ? 
                WHERE id = ?
            ");
            $stmt->execute([$cantidad, $detalle_id]);
        }
    }

    // Si viene de requisición, verificar si está completa
    if ($requisicion_id) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM requisicion_detalle 
            WHERE requisicion_id = ? AND cantidad_solicitada > cantidad_entregada
        ");
        $stmt->execute([$requisicion_id]);
        $pendientes = $stmt->fetchColumn();

        if ($pendientes == 0) {
            $stmt = $pdo->prepare("
                UPDATE requisiciones SET estado = 'surtida' WHERE id = ?
            ");
            $stmt->execute([$requisicion_id]);
        }
    }

    $pdo->commit();
    header("Location: ../entrada.php?success=1");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en recibir_compra: " . $e->getMessage());
    header("Location: ../entrada.php?error=" . urlencode($e->getMessage()));
}

exit;