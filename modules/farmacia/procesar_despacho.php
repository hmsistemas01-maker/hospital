<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: despacho.php");
    exit;
}

$receta_id = $_POST['receta_id'] ?? 0;
$despachar = $_POST['despachar'] ?? [];
$lotes_seleccionados = $_POST['lote'] ?? [];
$cantidades_surtidas = $_POST['cantidad_surtida'] ?? [];
$observaciones = $_POST['observaciones'] ?? '';

if (empty($receta_id) || empty($despachar)) {
    $_SESSION['error'] = "Datos incompletos para el despacho";
    header("Location: despacho.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Verificar que la receta existe y está pendiente
    $stmt = $pdo->prepare("SELECT id, paciente_id FROM recetas WHERE id = ? AND estado = 'pendiente'");
    $stmt->execute([$receta_id]);
    if (!$stmt->fetch()) {
        throw new Exception("Receta no encontrada o ya fue despachada");
    }

    foreach ($despachar as $detalle_id) {
        // Obtener información del detalle
        $stmt = $pdo->prepare("
            SELECT rd.*, p.id as producto_id, p.nombre as producto_nombre,
                   cf.control_lote, cf.control_vencimiento
            FROM receta_detalles rd
            JOIN productos p ON rd.producto_id = p.id
            LEFT JOIN categorias_farmacia cf ON p.categoria_farmacia_id = cf.id
            WHERE rd.id = ? AND rd.despachado = 0
        ");
        $stmt->execute([$detalle_id]);
        $detalle = $stmt->fetch();

        if (!$detalle) {
            throw new Exception("Detalle de receta no encontrado o ya despachado");
        }

        $cantidad_solicitada = $detalle['cantidad'];
        $cantidad_surtir = intval($cantidades_surtidas[$detalle_id] ?? $cantidad_solicitada);
        $lote_id = $lotes_seleccionados[$detalle_id] ?? null;

        if ($cantidad_surtir <= 0 || $cantidad_surtir > $cantidad_solicitada) {
            throw new Exception("Cantidad no válida para el producto {$detalle['producto_nombre']}");
        }

        // Variables para el movimiento
        $lote_usado = null;

        // Si requiere lote, validar y descontar del lote específico
        if ($detalle['control_lote']) {
            if (!$lote_id) {
                throw new Exception("Debe seleccionar un lote para {$detalle['producto_nombre']}");
            }

            // Verificar stock en el lote
            $stmt = $pdo->prepare("
                SELECT cantidad_actual, numero_lote FROM farmacia_lotes
                WHERE id = ? AND activo = 1 
                AND (fecha_vencimiento IS NULL OR fecha_vencimiento > CURDATE())
            ");
            $stmt->execute([$lote_id]);
            $lote = $stmt->fetch();

            if (!$lote || $lote['cantidad_actual'] < $cantidad_surtir) {
                throw new Exception("Stock insuficiente en el lote seleccionado para {$detalle['producto_nombre']}");
            }

            // Descontar del lote
            $stmt = $pdo->prepare("
                UPDATE farmacia_lotes 
                SET cantidad_actual = cantidad_actual - ? 
                WHERE id = ?
            ");
            $stmt->execute([$cantidad_surtir, $lote_id]);
            
            $lote_usado = $lote['numero_lote'];

        } else {
            // Si no requiere lote, usar FIFO (primero en entrar, primero en salir)
            $stmt = $pdo->prepare("
                SELECT id, cantidad_actual, numero_lote FROM farmacia_lotes
                WHERE producto_id = ? AND activo = 1 AND cantidad_actual > 0
                ORDER BY fecha_entrada ASC, fecha_vencimiento ASC
            ");
            $stmt->execute([$detalle['producto_id']]);
            $lotes = $stmt->fetchAll();

            $cantidad_pendiente = $cantidad_surtir;
            $lotes_usados = [];

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
                $lotes_usados[] = $l['numero_lote'] . "($descontar)";
                $lote_id = $l['id']; // Último lote usado para el movimiento
            }

            if ($cantidad_pendiente > 0) {
                throw new Exception("Stock insuficiente para {$detalle['producto_nombre']}");
            }

            $lote_usado = implode(', ', $lotes_usados);
        }

        // Registrar movimiento en inventario
        $motivo = "Despacho de receta #$receta_id - Lote: $lote_usado";
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
        $stmt = $pdo->prepare("
            UPDATE receta_detalles 
            SET despachado = 1, 
                cantidad_dispensada = ? 
            WHERE id = ?
        ");
        $stmt->execute([$cantidad_surtir, $detalle_id]);
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
        $mensaje = "Receta despachada completamente";
    } else {
        $mensaje = "Despacho parcial realizado";
    }

    $pdo->commit();
    
    $_SESSION['success'] = "$mensaje correctamente";
    header("Location: despacho.php");

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en despacho: " . $e->getMessage());
    $_SESSION['error'] = "Error al despachar: " . $e->getMessage();
    header("Location: despacho.php?receta=$receta_id");
}
exit;
?>