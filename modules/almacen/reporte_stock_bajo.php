<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener todos los productos con stock bajo
$productos = $pdo->query("
    SELECT 
        p.*,
        c.nombre as categoria_nombre,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual,
        (p.stock_minimo - COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0)) as faltante,
        (p.stock_maximo - COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0)) as capacidad_restante
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    HAVING stock_actual < p.stock_minimo
    ORDER BY faltante DESC
")->fetchAll();

$total_faltante = array_sum(array_column($productos, 'faltante'));
$costo_reposicion = array_sum(array_map(function($p) {
    return $p['faltante'] * $p['precio_unitario'];
}, $productos));
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📊 Reporte de Stock Bajo</h1>
            <p style="color: var(--gray-600);">Productos que requieren reposición urgente</p>
        </div>
        <div>
            <a href="index.php" class="btn btn-outline">← Volver</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir</button>
        </div>
    </div>

    <!-- Resumen -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
        <div class="stat-card danger">
            <div class="stat-value"><?= count($productos) ?></div>
            <div class="stat-label">Productos con Stock Bajo</div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-value"><?= $total_faltante ?></div>
            <div class="stat-label">Unidades Faltantes</div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-value">$<?= number_format($costo_reposicion, 2) ?></div>
            <div class="stat-label">Costo de Reposición</div>
        </div>
    </div>

    <?php if (empty($productos)): ?>
        <div class="alert alert-success" style="text-align: center; padding: var(--spacing-xl);">
            <p style="font-size: 1.2rem;">✅ No hay productos con stock bajo</p>
            <p>Todos los productos están dentro de sus niveles óptimos</p>
        </div>
    <?php else: ?>
        <!-- Tabla detallada -->
        <div class="card">
            <h3>📋 Detalle de Productos</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Faltante</th>
                            <th>Precio Unit.</th>
                            <th>Costo Reposición</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): 
                            $costo_producto = $p['faltante'] * $p['precio_unitario'];
                        ?>
                        <tr class="danger">
                            <td><strong><?= htmlspecialchars($p['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['categoria_nombre'] ?? '') ?></td>
                            <td class="text-danger"><?= $p['stock_actual'] ?></td>
                            <td><?= $p['stock_minimo'] ?></td>
                            <td><strong><?= $p['faltante'] ?></strong></td>
                            <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                            <td>$<?= number_format($costo_producto, 2) ?></td>
                            <td>
                                <div style="display: flex; gap: var(--spacing-xs);">
                                    <a href="entradas.php?producto=<?= $p['id'] ?>" class="btn btn-sm btn-success" title="Registrar entrada">⬆️</a>
                                    <a href="programar_entrada.php?producto=<?= $p['id'] ?>&faltante=<?= $p['faltante'] ?>" class="btn btn-sm btn-primary" title="Programar entrada">📅</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="7" style="text-align: right;">Totales:</th>
                            <th>$<?= number_format($costo_reposicion, 2) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Sugerencia de compra -->
        <div class="card">
            <h3>📝 Sugerencia de Compra</h3>
            <p>Para normalizar el inventario, se recomienda realizar las siguientes compras:</p>
            
            <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                <?php foreach ($productos as $p): ?>
                    <div style="display: flex; justify-content: space-between; padding: var(--spacing-sm); border-bottom: 1px dashed var(--gray-300);">
                        <span><strong><?= htmlspecialchars($p['nombre']) ?></strong> (<?= htmlspecialchars($p['codigo']) ?>)</span>
                        <span>Comprar <?= $p['faltante'] ?> <?= $p['unidad_medida'] ?> - $<?= number_format($p['faltante'] * $p['precio_unitario'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div style="display: flex; justify-content: space-between; margin-top: var(--spacing-md); font-weight: bold;">
                    <span>TOTAL SUGERIDO:</span>
                    <span>$<?= number_format($costo_reposicion, 2) ?></span>
                </div>
            </div>
            
            <div style="margin-top: var(--spacing-lg); display: flex; gap: var(--spacing-md);">
                <a href="programar_entrada.php?todos=1" class="btn btn-success">Programar todas las compras</a>
                <a href="proveedores.php" class="btn btn-outline">Contactar proveedores</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .btn, .grid-modulos, .card:last-child a { display: none; }
    body { background: white; }
    .card { box-shadow: none; border: 1px solid #ccc; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>