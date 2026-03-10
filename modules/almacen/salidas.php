<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener producto si viene por URL
$producto_id = $_GET['producto'] ?? null;
$producto = null;

if ($producto_id) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
}

// Obtener productos con stock para el select
$productos = $pdo->query("
    SELECT 
        p.id, 
        p.codigo, 
        p.nombre, 
        p.unidad_medida,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual
    FROM productos p
    WHERE p.activo = 1 
    HAVING stock_actual > 0
    ORDER BY p.nombre
")->fetchAll();

// Estadísticas del día
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, SUM(cantidad) as total_unidades
    FROM movimientos_inventario 
    WHERE tipo_movimiento = 'salida' 
    AND DATE(fecha_movimiento) = CURDATE()
");
$stmt->execute();
$salidas_hoy = $stmt->fetch();
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl); flex-wrap: wrap;">
        <div>
            <h1>⬇️ Registrar Salida de Productos</h1>
            <p style="color: var(--gray-600);">Disminuye el stock por consumo, uso o desperfecto</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <span class="badge badge-info">Hoy: <?= $salidas_hoy['total'] ?? 0 ?> salidas</span>
            <span class="badge badge-warning"><?= $salidas_hoy['total_unidades'] ?? 0 ?> unidades</span>
            <a href="productos.php" class="btn btn-sm btn-outline">← Volver</a>
        </div>
    </div>

    <?php if ($producto): ?>
        <div class="alert alert-info" style="margin-bottom: var(--spacing-lg);">
            <strong>Producto seleccionado:</strong> <?= htmlspecialchars($producto['nombre']) ?> 
            (Stock actual: <strong><?= $producto['stock_actual'] ?> <?= $producto['unidad_medida'] ?></strong>)
            <?php if ($producto['stock_actual'] == 0): ?>
                <span class="badge badge-danger">⚠️ Sin stock</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Formulario principal -->
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h3 style="color: var(--primary); margin-bottom: var(--spacing-lg);">📝 Registrar nueva salida</h3>
        
        <form method="POST" action="procesar_salida.php" id="formSalida">
            <!-- Producto -->
            <div class="form-group">
                <label class="required">Producto</label>
                <?php if ($producto): ?>
                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                    <div class="form-control" style="background: var(--gray-100);">
                        <strong><?= htmlspecialchars($producto['codigo']) ?></strong> - 
                        <?= htmlspecialchars($producto['nombre']) ?>
                        (Stock disponible: <strong id="stockDisponible"><?= $producto['stock_actual'] ?></strong>)
                    </div>
                <?php else: ?>
                    <select name="producto_id" id="productoSelect" class="form-control" required>
                        <option value="">-- Seleccionar producto con stock --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-stock="<?= $p['stock_actual'] ?>"
                                    data-unidad="<?= $p['unidad_medida'] ?>">
                                <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?> 
                                (Stock: <?= $p['stock_actual'] ?> <?= $p['unidad_medida'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <!-- Cantidad -->
            <div class="form-group">
                <label class="required">Cantidad a retirar</label>
                <input type="number" name="cantidad" id="cantidad" class="form-control" 
                       required min="1" max="<?= $producto['stock_actual'] ?? '' ?>" step="1">
                <small id="stockInfo" class="text-muted">
                    <?php if ($producto): ?>
                        Stock disponible: <?= $producto['stock_actual'] ?> <?= $producto['unidad_medida'] ?>
                    <?php endif; ?>
                </small>
            </div>

            <!-- Motivo -->
            <div class="form-group">
                <label>Motivo / Observaciones</label>
                <input type="text" name="motivo" class="form-control" 
                       placeholder="Ej: Uso en consultorio, desperfecto, donación...">
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-warning" style="flex: 1; padding: var(--spacing-md);">
                    <span>💾</span> Registrar Salida
                </button>
                <a href="productos.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- Salidas recientes -->
    <div class="card" style="margin-top: var(--spacing-xl);">
        <h3>📋 Últimas salidas registradas</h3>
        <?php
        $recientes = $pdo->query("
            SELECT m.*, p.nombre as producto, p.codigo, u.nombre as usuario
            FROM movimientos_inventario m
            JOIN productos p ON m.producto_id = p.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.tipo_movimiento = 'salida'
            ORDER BY m.fecha_movimiento DESC
            LIMIT 10
        ")->fetchAll();
        ?>
        
        <?php if (empty($recientes)): ?>
            <p class="text-muted">No hay salidas registradas aún</p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recientes as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['fecha_movimiento'])) ?></td>
                            <td><?= htmlspecialchars($r['codigo']) ?> - <?= htmlspecialchars($r['producto']) ?></td>
                            <td><strong>-<?= $r['cantidad'] ?></strong></td>
                            <td><?= htmlspecialchars($r['usuario']) ?></td>
                            <td><?= htmlspecialchars($r['motivo'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Actualizar información cuando se selecciona producto
document.getElementById('productoSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stock = selected.getAttribute('data-stock');
    const unidad = selected.getAttribute('data-unidad');
    
    if (stock) {
        document.getElementById('cantidad').max = stock;
        document.getElementById('stockInfo').textContent = 
            'Stock disponible: ' + stock + ' ' + (unidad || 'unidades');
    }
});

// Validar cantidad
document.getElementById('formSalida')?.addEventListener('submit', function(e) {
    const cantidad = document.getElementById('cantidad').value;
    const max = document.getElementById('cantidad').max;
    
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    if (max && parseInt(cantidad) > parseInt(max)) {
        e.preventDefault();
        alert('No hay suficiente stock disponible');
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>