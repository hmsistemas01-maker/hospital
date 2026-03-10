<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener estadísticas
$stats = [];

// Total de productos activos
$stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
$stats['total_productos'] = $stmt->fetchColumn();

// Productos con stock bajo (usando cálculo directo)
$productos_bajo_stock = $pdo->query("
    SELECT 
        p.*,
        c.nombre as categoria_nombre,
        COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0) as stock_actual,
        (p.stock_minimo - COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id), 0)) as faltante
    FROM productos p
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.activo = 1
    HAVING stock_actual < p.stock_minimo
    ORDER BY faltante DESC
")->fetchAll();

$stats['stock_bajo'] = count($productos_bajo_stock);

// Total de proveedores activos
$stmt = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE activo = 1");
$stats['total_proveedores'] = $stmt->fetchColumn();

// Movimientos de hoy
$movimientos_hoy = $pdo->prepare("
    SELECT 
        m.*, 
        p.nombre as producto_nombre,
        p.codigo,
        u.nombre as usuario_nombre
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha_movimiento) = CURDATE()
    ORDER BY m.fecha_movimiento DESC
    LIMIT 10
");
$movimientos_hoy->execute();
$movimientos_hoy = $movimientos_hoy->fetchAll();

// Entradas programadas (nuevo)
$entradas_programadas = $pdo->prepare("
    SELECT 
        ep.*,
        p.nombre as producto_nombre,
        p.codigo,
        prov.nombre as proveedor_nombre
    FROM entradas_programadas ep
    JOIN productos p ON ep.producto_id = p.id
    LEFT JOIN proveedores prov ON ep.proveedor_id = prov.id
    WHERE ep.fecha_programada >= CURDATE()
    AND ep.estado = 'pendiente'
    ORDER BY ep.fecha_programada ASC
");
$entradas_programadas->execute();
$entradas_programadas = $entradas_programadas->fetchAll();
?>

<div class="fade-in">
    <!-- Header del dashboard -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl); flex-wrap: wrap; gap: var(--spacing-md);">
        <div>
            <h1>🏢 Módulo de Almacén</h1>
            <p style="color: var(--gray-600);">Gestión de inventarios, productos y movimientos</p>
        </div>
        <div>
            <span class="badge badge-primary"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">📦</div>
            <div class="stat-value"><?= $stats['total_productos'] ?></div>
            <div class="stat-label">Productos Activos</div>
        </div>
        
        <div class="stat-card <?= $stats['stock_bajo'] > 0 ? 'warning' : 'success' ?>">
            <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">⚠️</div>
            <div class="stat-value">
                <a href="reporte_stock_bajo.php" style="color: inherit; text-decoration: none;">
                    <?= $stats['stock_bajo'] ?>
                </a>
            </div>
            <div class="stat-label">
                <a href="reporte_stock_bajo.php" style="color: inherit; text-decoration: none;">
                    Productos Stock Bajo
                </a>
            </div>
        </div>
        
        <div class="stat-card info">
            <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">🤝</div>
            <div class="stat-value"><?= $stats['total_proveedores'] ?></div>
            <div class="stat-label">Proveedores</div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <h2 style="margin-top: var(--spacing-xl);">Accesos Rápidos</h2>
    <div class="grid-modulos">
        <a href="productos.php" class="card-modulo">
            <div style="font-size: 2rem;">📦</div>
            <div>Productos</div>
        </a>
        <a href="categorias.php" class="card-modulo">
            <div style="font-size: 2rem;">📂</div>
            <div>Categorías</div>
        </a>
        <a href="proveedores.php" class="card-modulo">
            <div style="font-size: 2rem;">🤝</div>
            <div>Proveedores</div>
        </a>
        <a href="entradas.php" class="card-modulo">
            <div style="font-size: 2rem;">⬆️</div>
            <div>Entradas</div>
        </a>
        <a href="salidas.php" class="card-modulo">
            <div style="font-size: 2rem;">⬇️</div>
            <div>Salidas</div>
        </a>
        </a>
        <a href="movimientos.php" class="card-modulo">
            <div style="font-size: 2rem;">📊</div>
            <div>Movimientos</div>
        </a>
        <a href="reportes.php" class="card-modulo">
            <div style="font-size: 2rem;">📈</div>
            <div>Reportes</div>
        </a>
        <a href="programaciones.php" class="card-modulo">
    <div style="font-size: 2rem;">📋</div>
    <div>Programaciones</div>
</a>
    </div>

    <!-- Sección de dos columnas -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg); margin-top: var(--spacing-xl);">
        <!-- Columna izquierda: Stock bajo -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>⚠️ Productos con Stock Bajo</h3>
                <a href="reporte_stock_bajo.php" class="btn btn-sm btn-outline">Ver todos</a>
            </div>
            
            <?php if (empty($productos_bajo_stock)): ?>
                <p class="text-success" style="text-align: center; padding: var(--spacing-lg);">
                    ✅ No hay productos con stock bajo
                </p>
            <?php else: ?>
                <div style="margin-top: var(--spacing-md);">
                    <?php 
                    $top5 = array_slice($productos_bajo_stock, 0, 5);
                    foreach ($top5 as $p): 
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);">
                            <div>
                                <strong><?= htmlspecialchars($p['codigo']) ?></strong> - <?= htmlspecialchars($p['nombre']) ?>
                                <br>
                                <small>Stock: <?= $p['stock_actual'] ?> / Mínimo: <?= $p['stock_minimo'] ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge badge-danger">Faltan <?= $p['faltante'] ?></span>
                                <br>
                                <a href="programar_entrada.php?producto=<?= $p['id'] ?>" class="btn btn-sm btn-success" style="margin-top: var(--spacing-xs);">Programar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($productos_bajo_stock) > 5): ?>
                    <p style="text-align: center; margin-top: var(--spacing-md);">
                        <a href="reporte_stock_bajo.php">Y <?= count($productos_bajo_stock) - 5 ?> más...</a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Columna derecha: Entradas programadas -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>📅 Entradas Programadas</h3>
                <a href="programar_entrada.php" class="btn btn-sm btn-success">+ Nueva</a>
            </div>
            
            <?php if (empty($entradas_programadas)): ?>
                <p class="text-muted" style="text-align: center; padding: var(--spacing-lg);">
                    No hay entradas programadas
                </p>
            <?php else: ?>
                <div style="margin-top: var(--spacing-md);">
                    <?php foreach ($entradas_programadas as $ep): 
                        $dias = (strtotime($ep['fecha_programada']) - time()) / 86400;
                        $clase_dias = $dias <= 2 ? 'badge-danger' : ($dias <= 5 ? 'badge-warning' : 'badge-info');
                    ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-sm); border-bottom: 1px solid var(--gray-200);">
                            <div>
                                <strong><?= date('d/m/Y', strtotime($ep['fecha_programada'])) ?></strong>
                                <br>
                                <small><?= htmlspecialchars($ep['producto_codigo']) ?> - <?= htmlspecialchars($ep['producto_nombre']) ?></small>
                                <br>
                                <small>Cantidad: <?= $ep['cantidad'] ?> | Proveedor: <?= htmlspecialchars($ep['proveedor_nombre'] ?? 'N/A') ?></small>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge <?= $clase_dias ?>">
                                    <?= round($dias) ?> días
                                </span>
                                <br>
                                <a href="marcar_recibido.php?id=<?= $ep['id'] ?>" class="btn btn-sm btn-success" style="margin-top: var(--spacing-xs);" onclick="return confirm('¿Marcar como recibido?')">✓ Recibido</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Movimientos del día -->
    <div class="card" style="margin-top: var(--spacing-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>📋 Movimientos de Hoy</h3>
            <a href="movimientos.php?fecha_desde=<?= date('Y-m-d') ?>&fecha_hasta=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline">Ver todos</a>
        </div>
        
        <?php if (empty($movimientos_hoy)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-lg);">
                No hay movimientos registrados hoy
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_hoy as $m): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($m['fecha_movimiento'])) ?></td>
                            <td>
                                <?php if ($m['tipo_movimiento'] == 'entrada'): ?>
                                    <span class="badge badge-success">⬆️ Entrada</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">⬇️ Salida</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($m['codigo']) ?> - <?= htmlspecialchars($m['producto_nombre']) ?></td>
                            <td><?= $m['cantidad'] ?></td>
                            <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>