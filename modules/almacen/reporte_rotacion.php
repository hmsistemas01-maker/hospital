<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Productos más movidos (últimos 30 días)
$mas_movidos = $pdo->query("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        c.nombre as categoria,
        COUNT(*) as total_movimientos,
        SUM(CASE WHEN m.tipo_movimiento = 'entrada' THEN m.cantidad ELSE 0 END) as total_entradas,
        SUM(CASE WHEN m.tipo_movimiento = 'salida' THEN m.cantidad ELSE 0 END) as total_salidas,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE m.fecha_movimiento >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.id, p.codigo, p.nombre, c.nombre
    ORDER BY total_movimientos DESC
    LIMIT 20
")->fetchAll();

// Productos sin movimiento (últimos 90 días)
$sin_movimiento = $pdo->query("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        c.nombre as categoria,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual,
        p.ubicacion,
        DATEDIFF(NOW(), MAX(m.fecha_movimiento)) as dias_sin_movimiento
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    LEFT JOIN movimientos_inventario m ON p.id = m.producto_id
    WHERE p.activo = 1
    GROUP BY p.id
    HAVING dias_sin_movimiento >= 90 OR dias_sin_movimiento IS NULL
    ORDER BY dias_sin_movimiento DESC
")->fetchAll();
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🔄 Reporte de Rotación de Inventario</h1>
            <p style="color: var(--gray-600);">Productos más y menos movidos en los últimos 30 días</p>
        </div>
        <a href="reportes.php" class="btn btn-outline">← Volver a Reportes</a>
    </div>

    <!-- Top productos más movidos -->
    <div class="card">
        <h3>📈 Top 20 Productos más Activos (últimos 30 días)</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Entradas</th>
                        <th>Salidas</th>
                        <th>Total Mov.</th>
                        <th>Rotación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mas_movidos as $i => $p): 
                        $rotacion = $p['stock_actual'] > 0 ? round(($p['total_salidas'] / $p['stock_actual']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><strong>#<?= $i + 1 ?></strong></td>
                            <td><?= htmlspecialchars($p['codigo']) ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                            <td><?= $p['stock_actual'] ?></td>
                            <td class="text-success">+<?= $p['total_entradas'] ?></td>
                            <td class="text-warning">-<?= $p['total_salidas'] ?></td>
                            <td><strong><?= $p['total_movimientos'] ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                    <div style="width: 80px; height: 8px; background: var(--gray-200); border-radius: 4px;">
                                        <div style="width: <?= min($rotacion, 100) ?>%; height: 100%; background: <?= $rotacion > 50 ? '#27ae60' : '#f39c12' ?>; border-radius: 4px;"></div>
                                    </div>
                                    <?= $rotacion ?>%
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Productos sin movimiento -->
    <div class="card">
        <h3>⚠️ Productos sin Movimiento (más de 90 días)</h3>
        <?php if (empty($sin_movimiento)): ?>
            <div class="alert alert-success">✅ No hay productos sin movimiento</div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Ubicación</th>
                            <th>Días sin mov.</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sin_movimiento as $p): 
                            $clase = $p['dias_sin_movimiento'] > 180 ? 'danger' : 'warning';
                        ?>
                            <tr class="<?= $clase ?>">
                                <td><?= htmlspecialchars($p['codigo']) ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                                <td><?= $p['stock_actual'] ?></td>
                                <td><?= htmlspecialchars($p['ubicacion'] ?? '-') ?></td>
                                <td><strong><?= $p['dias_sin_movimiento'] ?? 'Nunca' ?></strong></td>
                                <td>
                                    <a href="ajustes.php?producto=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Revisar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>