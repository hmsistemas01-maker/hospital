<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Reportes simplificados (sin lotes)
$reportes = [];

// Productos con stock bajo
$reportes['stock_bajo'] = $pdo->query("
    SELECT 
        p.*,
        c.nombre as categoria_nombre,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual,
        (p.stock_minimo - COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0)) as faltante
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    HAVING stock_actual <= p.stock_minimo
    ORDER BY faltante DESC
")->fetchAll();

// Total de productos
$reportes['total_productos'] = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();

// Valor total del inventario
$reportes['valor_total'] = $pdo->query("
    SELECT SUM(p.precio_unitario * COALESCE(l.stock, 0))
    FROM productos p
    LEFT JOIN (SELECT producto_id, SUM(cantidad_actual) as stock FROM lote GROUP BY producto_id) l ON p.id = l.producto_id
    WHERE p.activo = 1
")->fetchColumn() ?: 0;

// Movimientos por mes
$reportes['movimientos_mes'] = $pdo->query("
    SELECT 
        DATE_FORMAT(fecha_movimiento, '%Y-%m') as mes,
        COUNT(*) as total,
        SUM(CASE WHEN tipo_movimiento = 'entrada' THEN 1 ELSE 0 END) as entradas,
        SUM(CASE WHEN tipo_movimiento = 'salida' THEN 1 ELSE 0 END) as salidas
    FROM movimientos_inventario
    WHERE fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m')
    ORDER BY mes DESC
")->fetchAll();
?>

<div class="fade-in">
    <h1>📊 Reportes de Almacén</h1>
    
    <!-- Resumen general -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-value"><?= $reportes['total_productos'] ?></div>
            <div class="stat-label">Total Productos</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-value"><?= count($reportes['stock_bajo']) ?></div>
            <div class="stat-label">Stock Bajo</div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-value">$<?= number_format($reportes['valor_total'], 2) ?></div>
            <div class="stat-label">Valor Inventario</div>
        </div>
    </div>

    <!-- Productos con stock bajo -->
    <div class="card">
        <h3>⚠️ Productos con Stock Bajo</h3>
        <?php if (empty($reportes['stock_bajo'])): ?>
            <p class="text-success">✅ No hay productos con stock bajo</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Faltante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportes['stock_bajo'] as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['codigo']) ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></td>
                        <td class="text-danger"><?= $p['stock_actual'] ?></td>
                        <td><?= $p['stock_minimo'] ?></td>
                        <td><strong><?= $p['faltante'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>