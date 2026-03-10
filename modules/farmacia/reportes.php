<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// ============================================
// PRODUCTOS MÁS DESPACHADOS
// ============================================
$top_productos = $pdo->query("
    SELECT 
        p.nombre,
        p.codigo,
        c.nombre as categoria,
        COUNT(*) as veces_despachado,
        SUM(rd.cantidad) as total_unidades
    FROM receta_detalles rd
    JOIN productos p ON rd.id_producto = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE rd.despachado = 1
    GROUP BY p.id
    ORDER BY total_unidades DESC
    LIMIT 10
")->fetchAll();

// ============================================
// PRODUCTOS CON STOCK BAJO
// ============================================
$stock_bajo = $pdo->query("
    SELECT 
        p.nombre,
        p.codigo,
        c.nombre as categoria,
        COALESCE(SUM(l.cantidad_actual), 0) as stock_actual,
        p.stock_minimo,
        (p.stock_minimo - COALESCE(SUM(l.cantidad_actual), 0)) as deficit
    FROM productos p
    LEFT JOIN lote l ON p.id = l.producto_id AND l.activo = 1 AND (l.fecha_vencimiento IS NULL OR l.fecha_vencimiento > CURDATE())
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    GROUP BY p.id
    HAVING stock_actual <= p.stock_minimo
    ORDER BY deficit DESC
")->fetchAll();

// ============================================
// ALERTAS DE VENCIMIENTO
// ============================================
$hoy = date('Y-m-d');
$seis_meses = date('Y-m-d', strtotime('+6 months'));
$tres_meses = date('Y-m-d', strtotime('+3 months'));

// Lotes que vencen en los próximos 6 meses (alerta amarilla)
$lotes_6_meses = $pdo->prepare("
    SELECT 
        l.*,
        p.nombre as producto,
        p.codigo,
        c.nombre as categoria,
        DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM lote l
    JOIN productos p ON l.producto_id = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE l.activo = 1 
      AND l.fecha_vencimiento IS NOT NULL
      AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
      AND l.cantidad_actual > 0
    ORDER BY l.fecha_vencimiento ASC
");
$lotes_6_meses->execute();
$lotes_6_meses = $lotes_6_meses->fetchAll();

// Lotes que vencen en los próximos 3 meses (alerta roja - prioridad)
$lotes_3_meses = $pdo->prepare("
    SELECT 
        l.*,
        p.nombre as producto,
        p.codigo,
        c.nombre as categoria,
        DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM lote l
    JOIN productos p ON l.producto_id = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE l.activo = 1 
      AND l.fecha_vencimiento IS NOT NULL
      AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
      AND l.cantidad_actual > 0
    ORDER BY l.fecha_vencimiento ASC
");
$lotes_3_meses->execute();
$lotes_3_meses = $lotes_3_meses->fetchAll();

// Lotes ya vencidos
$lotes_vencidos = $pdo->prepare("
    SELECT 
        l.*,
        p.nombre as producto,
        p.codigo,
        c.nombre as categoria,
        DATEDIFF(CURDATE(), l.fecha_vencimiento) as dias_vencidos
    FROM lote l
    JOIN productos p ON l.producto_id = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE l.activo = 1 
      AND l.fecha_vencimiento IS NOT NULL
      AND l.fecha_vencimiento < CURDATE()
      AND l.cantidad_actual > 0
    ORDER BY l.fecha_vencimiento ASC
");
$lotes_vencidos->execute();
$lotes_vencidos = $lotes_vencidos->fetchAll();

// ============================================
// CONSUMO POR CATEGORÍA
// ============================================
$consumo_categoria = $pdo->query("
    SELECT 
        c.nombre as categoria,
        COUNT(DISTINCT rd.id) as total_recetas,
        SUM(rd.cantidad) as total_unidades
    FROM receta_detalles rd
    JOIN productos p ON rd.id_producto = p.id
    JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE rd.despachado = 1
    GROUP BY c.id
    ORDER BY total_unidades DESC
")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📈 Reportes y Alertas</h1>
            <p style="color: var(--gray-600);">Análisis de inventario y alertas de vencimiento</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <!-- ===== ALERTAS DE VENCIMIENTO ===== -->
    <?php if (!empty($lotes_3_meses) || !empty($lotes_6_meses) || !empty($lotes_vencidos)): ?>
        <div class="card" style="border-left: 4px solid #dc3545;">
            <h3 style="color: #dc3545;">🚨 ALERTAS DE VENCIMIENTO</h3>
            
            <!-- Alertas rojas (3 meses o menos) -->
            <?php if (!empty($lotes_3_meses)): ?>
                <div style="background: #f8d7da; border-radius: var(--radius-md); padding: var(--spacing-lg); margin-bottom: var(--spacing-md);">
                    <h4 style="color: #721c24; margin-bottom: var(--spacing-md);">
                        🔴 ¡URGENTE! Productos a vencer en 3 MESES o menos
                    </h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr style="background: #f5c6cb;">
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes_3_meses as $l): ?>
                                <tr style="background: #fff3f3;">
                                    <td>
                                        <strong><?= htmlspecialchars($l['producto']) ?></strong>
                                        <br><small><?= $l['codigo'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($l['numero_lote']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['fecha_vencimiento'])) ?></td>
                                    <td><strong><?= $l['dias_restantes'] ?> días</strong></td>
                                    <td><?= $l['cantidad_actual'] ?> uds</td>
                                    <td>
                                        <a href="despacho.php" class="btn btn-sm btn-warning">💊 Priorizar despacho</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Alertas amarillas (3-6 meses) -->
            <?php if (!empty($lotes_6_meses)): ?>
                <div style="background: #fff3cd; border-radius: var(--radius-md); padding: var(--spacing-lg); margin-bottom: var(--spacing-md);">
                    <h4 style="color: #856404; margin-bottom: var(--spacing-md);">
                        🟡 PRECAUCIÓN: Productos a vencer en 6 MESES
                    </h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr style="background: #ffeeba;">
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes_6_meses as $l): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($l['producto']) ?></strong>
                                        <br><small><?= $l['codigo'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($l['numero_lote']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['fecha_vencimiento'])) ?></td>
                                    <td><?= $l['dias_restantes'] ?> días</td>
                                    <td><?= $l['cantidad_actual'] ?> uds</td>
                                    <td>
                                        <span class="badge badge-warning">Planear uso</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lotes vencidos -->
            <?php if (!empty($lotes_vencidos)): ?>
                <div style="background: #f8d7da; border-radius: var(--radius-md); padding: var(--spacing-lg);">
                    <h4 style="color: #721c24; margin-bottom: var(--spacing-md);">
                        ⚠️ ¡ATENCIÓN! Productos VENCIDOS en inventario
                    </h4>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr style="background: #f5c6cb;">
                                    <th>Producto</th>
                                    <th>Lote</th>
                                    <th>Venció</th>
                                    <th>Días vencido</th>
                                    <th>Stock</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lotes_vencidos as $l): ?>
                                <tr style="background: #ffebee;">
                                    <td>
                                        <strong><?= htmlspecialchars($l['producto']) ?></strong>
                                        <br><small><?= $l['codigo'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($l['numero_lote']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['fecha_vencimiento'])) ?></td>
                                    <td><strong><?= $l['dias_vencidos'] ?> días</strong></td>
                                    <td><?= $l['cantidad_actual'] ?> uds</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="marcarBaja(<?= $l['id'] ?>)">
                                            🗑️ Dar de baja
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ===== TOP PRODUCTOS ===== -->
    <div class="card">
        <h3>🏆 Top 10 Productos más Despachados</h3>
        <?php if (empty($top_productos)): ?>
            <p style="text-align: center; padding: var(--spacing-lg); color: var(--gray-500);">
                No hay datos suficientes
            </p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Veces</th>
                            <th>Unidades</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($top_productos as $p): ?>
                        <tr>
                            <td><strong><?= $i++ ?></strong></td>
                            <td>
                                <?= htmlspecialchars($p['nombre']) ?>
                                <br><small><?= $p['codigo'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></td>
                            <td><?= $p['veces_despachado'] ?> veces</td>
                            <td><strong><?= $p['total_unidades'] ?></strong> uds</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== STOCK BAJO ===== -->
    <div class="card">
        <h3>⚠️ Productos con Stock Bajo</h3>
        <?php if (empty($stock_bajo)): ?>
            <div class="alert alert-success" style="text-align: center;">
                ✅ No hay productos con stock bajo
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Déficit</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_bajo as $s): ?>
                        <tr class="danger">
                            <td>
                                <strong><?= htmlspecialchars($s['nombre']) ?></strong>
                                <br><small><?= $s['codigo'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($s['categoria'] ?? 'Sin categoría') ?></td>
                            <td style="color: var(--danger); font-weight: bold;"><?= $s['stock_actual'] ?></td>
                            <td><?= $s['stock_minimo'] ?></td>
                            <td><?= $s['deficit'] ?> uds</td>
                            <td>
                                <a href="requisiciones.php" class="btn btn-sm btn-warning">
                                    📝 Solicitar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== CONSUMO POR CATEGORÍA ===== -->
    <div class="card">
        <h3>📊 Consumo por Categoría</h3>
        <?php if (empty($consumo_categoria)): ?>
            <p style="text-align: center; padding: var(--spacing-lg); color: var(--gray-500);">
                No hay datos de consumo
            </p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-lg);">
                <?php foreach ($consumo_categoria as $c): ?>
                    <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
                        <h4 style="color: var(--primary); margin-bottom: var(--spacing-sm);"><?= htmlspecialchars($c['categoria']) ?></h4>
                        <p style="font-size: 2rem; font-weight: bold; margin: 0;"><?= $c['total_unidades'] ?></p>
                        <p style="color: var(--gray-600);">unidades en <?= $c['total_recetas'] ?> recetas</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function marcarBaja(loteId) {
    if (confirm('¿Está seguro de dar de baja este lote vencido? Esta acción no se puede deshacer.')) {
        window.location.href = 'procesar/baja_lote.php?id=' + loteId;
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>