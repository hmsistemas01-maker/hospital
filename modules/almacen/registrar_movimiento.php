<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$tipo_movimiento = $_POST['tipo_movimiento'];
$id_producto = $_POST['id_producto'];
$cantidad = $_POST['cantidad'];
$observacion = $_POST['observacion'] ?? null;
$id_area_destino = $_POST['id_area_destino'] ?? null;
$id_usuario = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Verificar que el producto existe
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ? AND estado = 1");
    $stmt->execute([$id_producto]);
    $producto = $stmt->fetch();
    
    if (!$producto) {
        throw new Exception("Producto no encontrado");
    }
    
    $id_lote = null;
    
    // Si es ENTRADA
    if ($tipo_movimiento == 'Entrada') {
        if ($producto['requiere_lote']) {
            // Validar datos de lote
            if (empty($_POST['numero_lote']) || empty($_POST['fecha_vencimiento'])) {
                throw new Exception("Para productos con lote, debe especificar número de lote y fecha de vencimiento");
            }
            
            // Crear nuevo lote
            $stmt = $pdo->prepare("
                INSERT INTO lote (id_producto, numero_lote, fecha_vencimiento, stock_actual) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_producto,
                $_POST['numero_lote'],
                $_POST['fecha_vencimiento'],
                $cantidad
            ]);
            $id_lote = $pdo->lastInsertId();
        } else {
            // Para productos sin lote, crear un lote genérico o usar existente
            // Buscar si ya existe un lote genérico para este producto
            $stmt = $pdo->prepare("
                SELECT id_lote FROM lote 
                WHERE id_producto = ? AND numero_lote = 'GENERAL'
            ");
            $stmt->execute([$id_producto]);
            $loteExistente = $stmt->fetch();
            
            if ($loteExistente) {
                // Actualizar lote existente
                $id_lote = $loteExistente['id_lote'];
                $stmt = $pdo->prepare("
                    UPDATE lote SET stock_actual = stock_actual + ? 
                    WHERE id_lote = ?
                ");
                $stmt->execute([$cantidad, $id_lote]);
            } else {
                // Crear lote general
                $stmt = $pdo->prepare("
                    INSERT INTO lote (id_producto, numero_lote, stock_actual) 
                    VALUES (?, 'GENERAL', ?)
                ");
                $stmt->execute([$id_producto, $cantidad]);
                $id_lote = $pdo->lastInsertId();
            }
        }
    }
    
    // Si es SALIDA
    if ($tipo_movimiento == 'Salida') {
        if ($producto['requiere_lote']) {
            // Usar lote específico
            $id_lote = $_POST['id_lote'];
            
            // Verificar stock suficiente
            $stmt = $pdo->prepare("
                SELECT stock_actual FROM lote 
                WHERE id_lote = ? AND id_producto = ?
            ");
            $stmt->execute([$id_lote, $id_producto]);
            $lote = $stmt->fetch();
            
            if (!$lote || $lote['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente en el lote seleccionado");
            }
            
            // Actualizar stock del lote
            $stmt = $pdo->prepare("
                UPDATE lote SET stock_actual = stock_actual - ? 
                WHERE id_lote = ?
            ");
            $stmt->execute([$cantidad, $id_lote]);
            
        } else {
            // Para productos sin lote, buscar lote general
            $stmt = $pdo->prepare("
                SELECT id_lote, stock_actual FROM lote 
                WHERE id_producto = ? AND numero_lote = 'GENERAL'
            ");
            $stmt->execute([$id_producto]);
            $lote = $stmt->fetch();
            
            if (!$lote || $lote['stock_actual'] < $cantidad) {
                throw new Exception("Stock insuficiente");
            }
            
            $id_lote = $lote['id_lote'];
            
            // Actualizar stock
            $stmt = $pdo->prepare("
                UPDATE lote SET stock_actual = stock_actual - ? 
                WHERE id_lote = ?
            ");
            $stmt->execute([$cantidad, $id_lote]);
        }
    }
    
    // Registrar movimiento
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario 
        (id_producto, id_lote, tipo_movimiento, cantidad, id_usuario, id_area_destino, observacion) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $id_producto,
        $id_lote,
        $tipo_movimiento,
        $cantidad,
        $id_usuario,
        $id_area_destino,
        $observacion
    ]);
    
    $pdo->commit();
    
    // Redirigir según el tipo
    if ($tipo_movimiento == 'Entrada') {
        header("Location: productos.php?msg=entrada_ok");
    } else {
        header("Location: productos.php?msg=salida_ok");
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: " . strtolower($tipo_movimiento) . "s.php?error=" . urlencode($e->getMessage()));
}

exit;