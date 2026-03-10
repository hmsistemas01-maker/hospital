<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: despacho.php");
    exit;
}

$receta_id = $_POST['receta_id'];
$despachar = $_POST['despachar'] ?? [];
$lotes_seleccionados = $_POST['lote'] ?? [];
$cantidades_surtidas = $_POST['cantidad_surtida'] ?? [];
$observaciones = $_POST['observaciones'] ?? '';

if (empty($despachar)) {
    header("Location: despacho.php?receta=$receta_id&error=Seleccione al menos un medicamento");
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($despachar as $detalle_id) {
        // Obtener información del detalle
        $stmt = $pdo->prepare("
            SELECT rd.*, p.id as producto_id, cf.control_lote
            FROM receta_detalles rd
            JOIN productos p ON rd.producto_id = p.id
            LEFT JOIN categorias_farmacia cf ON p.categoria_farmacia_id = cf.id
            WHERE rd.id = ?
        ");
        $stmt->execute([$detalle_id]);
        $detalle = $stmt->fetch();

        if (!$detalle) {
            throw new Exception("Detalle de receta no encontrado");
        }

        $cantidad_solicitada = $detalle['cantidad'];
        $cantidad_surtir = intval($cantidades_surtidas[$detalle_id] ?? $cantidad_solicitada);
        $lote_id = $lotes_seleccionados[$detalle_id] ?? null;

        if ($cantidad_surtir <= 0 || $cantidad_surtir > $cantidad_solicitada) {
            throw new Exception("Cantidad no válida para el producto");
        }

        // Si requiere lote, validar y descontar del lote específico
        if ($detalle['control_lote']) {
            if (!$lote_id) {
                throw new Exception("Debe seleccionar un lote para el producto");
            }

            // Verificar stock en el lote
            $stmt = $pdo->prepare("
                SELECT cantidad_actual FROM farmacia_lotes
                WHERE id = ? AND activo = 1 
                AND (fecha_vencimiento IS NULL OR fecha_vencimiento > CURDATE())
            ");
            $stmt->execute([$lote_id]);
            $lote = $stmt->fetch();

            if (!$lote || $lote['cantidad_actual'] < $cantidad_surtir) {
                throw new Exception("Stock insuficiente en el lote seleccionado");
            }

            // Descontar del lote
            $stmt = $pdo->prepare("
                UPDATE farmacia_lotes 
                SET cantidad_actual = cantidad_actual - ? 
                WHERE id = ?
            ");
            $stmt->execute([$cantidad_surtir, $lote_id]);
        } else {
            // Si no requiere lote, usar FIFO
            $stmt = $pdo->prepare("
                SELECT id, cantidad_actual FROM farmacia_lotes
                WHERE producto_id = ? AND activo = 1 AND cantidad_actual > 0
                ORDER BY fecha_entrada ASC, fecha_vencimiento ASC
            ");
            $stmt->execute([$detalle['producto_id']]);
            $lotes = $stmt->fetchAll();

            $cantidad_pendiente = $cantidad_surtir;
            foreach ($lotes as $l) {
                if ($cantidad_pendiente <= 0) break;
                
                $descontar = min($l['cantidad_actual'], $cantidad_pendiente);
                $stmt = $pdo->prepare("
                    UPDATE farmacia_lotes 
                    SET cantidad_actual = cantidad_actual - ? 
                    WHERE id = ?
                ");
                $stmt->execute([$descontar, $l['id']]);
                $cantidad_pendiente -= $descontar;
                $lote_id = $l['id']; // Último lote usado
            }

            if ($cantidad_pendiente > 0) {
                throw new Exception("Stock insuficiente para el producto");
            }
        }

        // Registrar movimiento en inventario
        $motivo = "Despacho de receta #$receta_id";
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario 
            (departamento, producto_id, lote_id, tipo_movimiento, cantidad, 
             motivo, referencia, usuario_id, fecha_movimiento, destino, observaciones)
            VALUES ('farmacia', ?, ?, 'salida', ?, ?, ?, ?, NOW(), 'paciente', ?)
        ");
        $stmt->execute([
            $detalle['producto_id'], 
            $lote_id, 
            $cantidad_surtir, 
            $motivo, 
            "Receta #$receta_id", 
            $_SESSION['user_id'], 
            $observaciones
        ]);

        // Marcar detalle como despachado
        $stmt = $pdo->prepare("UPDATE receta_detalles SET despachado = 1 WHERE id = ?");
        $stmt->execute([$detalle_id]);
    }

    // Verificar si todos los detalles están despachados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM receta_detalles
        WHERE receta_id = ? AND despachado = 0
    ");
    $stmt->execute([$receta_id]);
    $pendientes = $stmt->fetchColumn();

    if ($pendientes == 0) {
        // Actualizar estado de la receta
        $stmt = $pdo->prepare("UPDATE recetas SET estado = 'despachada' WHERE id = ?");
        $stmt->execute([$receta_id]);
    }

    $pdo->commit();
    
    $_SESSION['success'] = "Despacho realizado correctamente";
    header("Location: despacho.php?success=1");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en despacho: " . $e->getMessage());
    header("Location: despacho.php?receta=$receta_id&error=" . urlencode($e->getMessage()));
}
exit;
?>