<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: entrada.php");
    exit;
}

$proveedor_id = $_POST['proveedor_id'] ?? null;
$referencia = $_POST['referencia'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$observaciones = $_POST['observaciones'] ?? '';
$usuario_id = $_SESSION['user_id'];
$requisicion_id = $_POST['requisicion_id'] ?? null;

$productos = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$precios = $_POST['precio_unitario'] ?? [];
$lotes = $_POST['lote'] ?? [];
$vencimientos = $_POST['vencimiento'] ?? [];

try {
    // Validar que haya productos
    if (empty($productos)) {
        throw new Exception("No hay productos para registrar");
    }

    $pdo->beginTransaction();

    foreach ($productos as $i => $producto_id) {
        $cantidad = intval($cantidades[$i] ?? 0);
        $precio = floatval($precios[$i] ?? 0);
        $lote_numero = $lotes[$i] ?? null;
        $vencimiento = $vencimientos[$i] ?? null;

        if ($cantidad <= 0) continue;

        // Obtener información del producto
        $stmt = $pdo->prepare("
            SELECT p.*, cf.control_lote, cf.control_vencimiento 
            FROM productos p
            LEFT JOIN categorias_farmacia cf ON p.categoria_farmacia_id = cf.id
            WHERE p.id = ? AND p.departamento = 'farmacia'
        ");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("Producto ID $producto_id no encontrado en farmacia");
        }

        // Si el producto requiere control por lote
        if ($producto['control_lote']) {
            if (empty($lote_numero)) {
                throw new Exception("El producto {$producto['nombre']} requiere número de lote");
            }
            if ($producto['control_vencimiento'] && empty($vencimiento)) {
                throw new Exception("El producto {$producto['nombre']} requiere fecha de vencimiento");
            }

            // Verificar si el lote ya existe
            $stmt = $pdo->prepare("
                SELECT id, cantidad_actual 
                FROM farmacia_lotes 
                WHERE producto_id = ? AND numero_lote = ?
            ");
            $stmt->execute([$producto_id, $lote_numero]);
            $lote_existente = $stmt->fetch();

            if ($lote_existente) {
                // Actualizar lote existente
                $lote_id = $lote_existente['id'];
                $stmt = $pdo->prepare("
                    UPDATE farmacia_lotes SET 
                        cantidad_actual = cantidad_actual + ?,
                        fecha_entrada = ?
                    WHERE id = ?
                ");
                $stmt->execute([$cantidad, $fecha, $lote_id]);
            } else {
                // Crear nuevo lote
                $stmt = $pdo->prepare("
                    INSERT INTO farmacia_lotes 
                    (producto_id, numero_lote, fecha_vencimiento, cantidad_inicial, 
                     cantidad_actual, proveedor_id, fecha_entrada, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $producto_id, 
                    $lote_numero, 
                    $vencimiento, 
                    $cantidad, 
                    $cantidad, 
                    $proveedor_id, 
                    $fecha
                ]);
                $lote_id = $pdo->lastInsertId();
            }
        } else {
            // Productos sin control de lote (usar lote genérico)
            $lote_numero = 'SIN-LOTE-' . date('Ymd');
            $vencimiento = null;
            
            // Buscar o crear lote genérico
            $stmt = $pdo->prepare("
                SELECT id FROM farmacia_lotes 
                WHERE producto_id = ? AND numero_lote = 'GENERAL'
            ");
            $stmt->execute([$producto_id]);
            $lote_general = $stmt->fetch();
            
            if ($lote_general) {
                $lote_id = $lote_general['id'];
                $stmt = $pdo->prepare("
                    UPDATE farmacia_lotes 
                    SET cantidad_actual = cantidad_actual + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$cantidad, $lote_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO farmacia_lotes 
                    (producto_id, numero_lote, cantidad_inicial, cantidad_actual, 
                     proveedor_id, fecha_entrada, activo)
                    VALUES (?, 'GENERAL', ?, ?, ?, ?, 1)
                ");
                $stmt->execute([$producto_id, $cantidad, $cantidad, $proveedor_id, $fecha]);
                $lote_id = $pdo->lastInsertId();
            }
        }

        // Registrar movimiento en inventario
        $motivo = "Factura: $referencia" . ($precio > 0 ? ", Precio: $$precio" : "");
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario 
            (departamento, producto_id, lote_id, tipo_movimiento, cantidad, 
             motivo, referencia, usuario_id, fecha_movimiento, destino, observaciones)
            VALUES ('farmacia', ?, ?, 'entrada', ?, ?, ?, ?, NOW(), 'farmacia', ?)
        ");
        $stmt->execute([
            $producto_id, 
            $lote_id, 
            $cantidad, 
            $motivo, 
            $referencia, 
            $usuario_id, 
            $observaciones
        ]);

        // Si viene de requisición, actualizar cantidad entregada
        if ($requisicion_id) {
            $stmt = $pdo->prepare("
                UPDATE requisicion_detalle 
                SET cantidad_entregada = ? 
                WHERE requisicion_id = ? AND producto_id = ?
            ");
            $stmt->execute([$cantidad, $requisicion_id, $producto_id]);
        }
    }

    // Verificar si la requisición está completa
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
    
    $_SESSION['success'] = "Entrada registrada correctamente";
    header("Location: productos.php?success=1");
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en entrada farmacia: " . $e->getMessage());
    $_SESSION['error'] = "Error al registrar: " . $e->getMessage();
    header("Location: entrada.php" . ($requisicion_id ? "?requisicion=$requisicion_id" : ""));
}
exit;
?>