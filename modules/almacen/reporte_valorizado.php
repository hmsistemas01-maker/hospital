<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener valor total del inventario
$valor_total = $pdo->query("
    SELECT 
        SUM(p.precio_unitario * COALESCE(l.stock, 0)) as total
    FROM productos p
    LEFT JOIN (SELECT producto_id, SUM(cantidad_actual) as stock FROM lote GROUP BY producto_id) l ON p.id = l.producto_id
    WHERE p.activo = 1
")->fetchColumn() ?: 0;

// Valor por categoría
$por_categoria = $pdo->query("
    SELECT 
        c.nombre as categoria,
        COUNT(DISTINCT p.id) as total_productos,
        SUM(p.precio_unitario * COALESCE(l.stock, 0)) as valor_total,
        SUM(COALESCE(l.stock, 0)) as unidades_totales
    FROM categorias_productos c
    LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = 1
    LEFT JOIN (SELECT producto_id, SUM(cantidad_actual) as stock FROM lote GROUP BY producto_id) l ON p.id = l.producto_id
    GROUP BY c.id, c.nombre
    ORDER BY valor_total DESC
")->fetchAll();

// Top 10 productos más valiosos
$top_productos = $pdo->query("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        c.nombre as categoria,
        p.precio_unitario,
        COALESCE(l.stock, 0) as stock_actual,
        (p.precio_unitario * COALESCE(l.stock, 0)) as valor_total
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    LEFT JOIN (SELECT producto_id, SUM(cantidad_actual) as stock FROM lote GROUP BY producto_id) l ON p.id = l.producto_id
    WHERE p.activo = 1
    ORDER BY valor_total DESC
    LIMIT 10
")->fetchAll();

// Productos sin valor (stock 0)
$sin_stock = $pdo->query("
    SELECT COUNT(*) FROM productos p
    LEFT JOIN (SELECT producto_id, SUM(cantidad_actual) as stock FROM lote GROUP BY producto_id) l ON p.id = l.producto_id
    WHERE p.activo = 1 AND (l.stock IS NULL OR l.stock = 0)
")->fetchColumn();
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>💰 Reporte Valorizado de Inventario</h1>
            <p style="color: var(--gray-600);">Valor económico del inventario por categorías</p>
        </div>
        <div>
            <a href="reportes.php" class="btn btn-outline">← Volver a Reportes</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir</button>
        </div>
    </div>

    <!-- Tarjeta de valor total -->
    <div class="card" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; text-align: center;">
        <h2 style="color: white; margin-bottom: var(--spacing-sm);">Valor Total del Inventario</h2>
        <div style="font-size: 4rem; font-weight: bold;">$<?= number_format($valor_total, 2) ?></div>
        <p style="margin-top: var(--spacing-md);">Productos sin stock: <?= $sin_stock ?></p>
    </div>

    <!-- Gráfico de categorías (simulado con CSS) -->
    <div class="card">
        <h3>📊 Distribución por Categorías</h3>
        <div style="margin-top: var(--spacing-lg);">
            <?php 
            $max_valor = max(array_column($por_categoria, 'valor_total') ?: [1]);
            foreach ($por_categoria as $cat): 
                $porcentaje = ($cat['valor_total'] / $max_valor) * 100;
                $ancho = max(10, ($cat['valor_total'] / $valor_total) * 100);
            ?>
                <div style="margin-bottom: var(--spacing-md);">
                    <div style="display: flex; justify-content: space-between;">
                        <span><strong><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></strong></span>
                        <span>$<?= number_format($cat['valor_total'] ?: 0, 2) ?> (<?= number_format($ancho, 1) ?>%)</span>
                    </div>
                    <div style="width: 100%; height: 30px; background: var(--gray-200); border-radius: var(--radius-md); margin-top: var(--spacing-xs);">
                        <div style="width: <?= $ancho ?>%; height: 100%; background: linear-gradient(90deg, #3498db, #2980b9); border-radius: var(--radius-md); display: flex; align-items: center; padding-left: var(--spacing-sm); color: white; font-weight: bold;">
                            <?= $cat['unidades_totales'] ?> uds
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla de categorías -->
    <div class="card">
        <h3>📋 Detalle por Categorías</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Productos</th>
                        <th>Unidades</th>
                        <th>Valor Total</th>
                        <th>% del Inventario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($por_categoria as $cat): 
                        $porcentaje = $valor_total > 0 ? ($cat['valor_total'] / $valor_total) * 100 : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></strong></td>
                            <td><?= $cat['total_productos'] ?></td>
                            <td><?= $cat['unidades_totales'] ?: 0 ?></td>
                            <td>$<?= number_format($cat['valor_total'] ?: 0, 2) ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                    <div style="width: 100px; height: 10px; background: var(--gray-200); border-radius: 5px;">
                                        <div style="width: <?= $porcentaje ?>%; height: 100%; background: var(--primary); border-radius: 5px;"></div>
                                    </div>
                                    <?= number_format($porcentaje, 1) ?>%
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="font-weight: bold;">
                        <td>TOTAL</td>
                        <td></td>
                        <td></td>
                        <td>$<?= number_format($valor_total, 2) ?></td>
                        <td>100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Top 10 productos más valiosos -->
    <div class="card">
        <h3>🏆 Top 10 Productos más Valiosos</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio Unit.</th>
                        <th>Stock</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_productos as $i => $p): ?>
                        <tr>
                            <td><strong>#<?= $i + 1 ?></strong></td>
                            <td><?= htmlspecialchars($p['codigo']) ?></td>
                            <td><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                            <td>$<?= number_format($p['precio_unitario'], 2) ?></td>
                            <td><?= $p['stock_actual'] ?></td>
                            <td><strong>$<?= number_format($p['valor_total'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .btn { display: none; }
    body { background: white; }
    .card { box-shadow: none; border: 1px solid #ddd; break-inside: avoid; }
}
</style>

<?php require_once '../../includes/footer.php'; ?>