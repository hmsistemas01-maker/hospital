<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Parámetros
$meses = $_GET['meses'] ?? 6;

// Obtener movimientos por mes
$movimientos_mes = $pdo->prepare("
    SELECT 
        DATE_FORMAT(fecha_movimiento, '%Y-%m') as mes,
        DATE_FORMAT(fecha_movimiento, '%M %Y') as mes_nombre,
        COUNT(*) as total_movimientos,
        SUM(CASE WHEN tipo_movimiento = 'entrada' THEN cantidad ELSE 0 END) as total_entradas,
        SUM(CASE WHEN tipo_movimiento = 'salida' THEN cantidad ELSE 0 END) as total_salidas,
        COUNT(CASE WHEN tipo_movimiento = 'entrada' THEN 1 END) as num_entradas,
        COUNT(CASE WHEN tipo_movimiento = 'salida' THEN 1 END) as num_salidas
    FROM movimientos_inventario
    WHERE fecha_movimiento >= DATE_SUB(NOW(), INTERVAL ? MONTH)
    GROUP BY DATE_FORMAT(fecha_movimiento, '%Y-%m')
    ORDER BY mes ASC
");
$movimientos_mes->execute([$meses]);
$movimientos_mes = $movimientos_mes->fetchAll();

// Totales del período
$total_entradas = array_sum(array_column($movimientos_mes, 'total_entradas'));
$total_salidas = array_sum(array_column($movimientos_mes, 'total_salidas'));
$promedio_mensual = count($movimientos_mes) > 0 ? ($total_entradas + $total_salidas) / count($movimientos_mes) : 0;
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📈 Reporte de Movimientos por Período</h1>
            <p style="color: var(--gray-600);">Análisis de tendencias de entradas y salidas</p>
        </div>
        <div>
            <a href="reportes.php" class="btn btn-outline">← Volver a Reportes</a>
        </div>
    </div>

    <!-- Selector de período -->
    <div class="card">
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <div class="form-group">
                <label>Mostrar últimos</label>
                <select name="meses" class="form-control" onchange="this.form.submit()">
                    <option value="3" <?= $meses == 3 ? 'selected' : '' ?>>3 meses</option>
                    <option value="6" <?= $meses == 6 ? 'selected' : '' ?>>6 meses</option>
                    <option value="12" <?= $meses == 12 ? 'selected' : '' ?>>12 meses</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-value"><?= $total_entradas ?></div>
            <div class="stat-label">Total Entradas</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-value"><?= $total_salidas ?></div>
            <div class="stat-label">Total Salidas</div>
        </div>
        <div class="stat-card info">
            <div class="stat-value"><?= round($promedio_mensual) ?></div>
            <div class="stat-label">Promedio Mensual</div>
        </div>
    </div>

    <!-- Gráfico de barras -->
    <div class="card">
        <h3>📊 Tendencia de Movimientos</h3>
        <div style="display: flex; align-items: flex-end; gap: var(--spacing-md); min-height: 300px; margin-top: var(--spacing-xl);">
            <?php 
            $max = max(array_merge(array_column($movimientos_mes, 'total_entradas'), array_column($movimientos_mes, 'total_salidas'))) ?: 1;
            foreach ($movimientos_mes as $m): 
                $altura_entradas = ($m['total_entradas'] / $max) * 200;
                $altura_salidas = ($m['total_salidas'] / $max) * 200;
            ?>
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                    <div style="display: flex; gap: 4px; width: 100%; justify-content: center; align-items: flex-end; height: 220px;">
                        <div style="width: 30px; height: <?= $altura_entradas ?>px; background: #27ae60; border-radius: var(--radius-sm) var(--radius-sm) 0 0;" title="Entradas: <?= $m['total_entradas'] ?>"></div>
                        <div style="width: 30px; height: <?= $altura_salidas ?>px; background: #e67e22; border-radius: var(--radius-sm) var(--radius-sm) 0 0;" title="Salidas: <?= $m['total_salidas'] ?>"></div>
                    </div>
                    <div style="margin-top: var(--spacing-sm); font-weight: bold; text-align: center;">
                        <?= date('M Y', strtotime($m['mes'] . '-01')) ?>
                    </div>
                    <div style="font-size: var(--font-size-xs); color: var(--gray-600);">
                        E:<?= $m['total_entradas'] ?> / S:<?= $m['total_salidas'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="display: flex; gap: var(--spacing-lg); justify-content: center; margin-top: var(--spacing-lg);">
            <div><span style="display: inline-block; width: 20px; height: 20px; background: #27ae60; border-radius: 4px;"></span> Entradas</div>
            <div><span style="display: inline-block; width: 20px; height: 20px; background: #e67e22; border-radius: 4px;"></span> Salidas</div>
        </div>
    </div>

    <!-- Tabla detallada -->
    <div class="card">
        <h3>📋 Detalle por Mes</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th>Entradas (uds)</th>
                        <th>Salidas (uds)</th>
                        <th>Total Movimientos</th>
                        <th># Entradas</th>
                        <th># Salidas</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimientos_mes as $m): 
                        $balance = $m['total_entradas'] - $m['total_salidas'];
                        $balance_class = $balance > 0 ? 'success' : ($balance < 0 ? 'danger' : '');
                    ?>
                        <tr>
                            <td><strong><?= $m['mes_nombre'] ?></strong></td>
                            <td><?= $m['total_entradas'] ?></td>
                            <td><?= $m['total_salidas'] ?></td>
                            <td><?= $m['total_movimientos'] ?></td>
                            <td><?= $m['num_entradas'] ?></td>
                            <td><?= $m['num_salidas'] ?></td>
                            <td class="text-<?= $balance_class ?>">
                                <?= $balance > 0 ? '+' : '' ?><?= $balance ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
