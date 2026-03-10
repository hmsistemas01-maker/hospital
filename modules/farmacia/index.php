<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// ============================================
// OBTENER ESTADÍSTICAS
// ============================================
$stats = [];

try {
    // Total de productos en farmacia con stock
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) 
        FROM productos p 
        INNER JOIN farmacia_lotes l ON p.id = l.producto_id 
        WHERE p.departamento = 'farmacia' AND l.activo = 1 AND l.cantidad_actual > 0
    ");
    $stats['productos_con_stock'] = $stmt->fetchColumn() ?: 0;

    // Productos con stock bajo (menor al mínimo)
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT p.id, 
                   COALESCE(SUM(l.cantidad_actual), 0) as stock_total,
                   p.stock_minimo
            FROM productos p
            LEFT JOIN farmacia_lotes l ON p.id = l.producto_id AND l.activo = 1
            WHERE p.departamento = 'farmacia' AND p.activo = 1
            GROUP BY p.id
            HAVING stock_total < p.stock_minimo
        ) as bajos
    ");
    $stats['stock_bajo'] = $stmt->fetchColumn() ?: 0;

    // Requisiciones pendientes
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM requisiciones 
        WHERE departamento = 'farmacia' AND estado = 'pendiente'
    ");
    $stats['requisiciones_pendientes'] = $stmt->fetchColumn() ?: 0;

    // ===== NUEVO: RECETAS PENDIENTES =====
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM recetas 
        WHERE estado = 'pendiente'
    ");
    $stats['recetas_pendientes'] = $stmt->fetchColumn() ?: 0;

    // Últimas entradas
    $entradas = $pdo->query("
        SELECT 
            DATE_FORMAT(m.fecha_movimiento, '%d/%m/%Y') as fecha,
            p.nombre as producto,
            m.cantidad,
            u.nombre as usuario
        FROM movimientos_inventario m
        JOIN productos p ON m.producto_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.departamento = 'farmacia' AND m.tipo_movimiento = 'entrada'
        ORDER BY m.fecha_movimiento DESC
        LIMIT 5
    ")->fetchAll();

    // ===== NUEVO: RECETAS PENDIENTES PARA EL DASHBOARD =====
    $recetas_hoy = $pdo->query("
        SELECT COUNT(*) as total 
        FROM recetas 
        WHERE DATE(fecha) = CURDATE() AND estado = 'pendiente'
    ")->fetchColumn();

    $primeras_recetas = $pdo->query("
        SELECT r.id, r.fecha, p.nombre as paciente, 
               COUNT(rd.id) as medicamentos
        FROM recetas r
        JOIN pacientes p ON r.paciente_id = p.id
        LEFT JOIN receta_detalles rd ON r.id = rd.receta_id
        WHERE r.estado = 'pendiente'
        GROUP BY r.id
        ORDER BY r.fecha ASC
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    error_log("Error en farmacia/index.php: " . $e->getMessage());
    $stats = [
        'productos_con_stock' => 0,
        'stock_bajo' => 0,
        'requisiciones_pendientes' => 0,
        'recetas_pendientes' => 0
    ];
    $entradas = [];
    $primeras_recetas = [];
    $recetas_hoy = 0;
}
?>

<div class="fade-in">
    <h1 class="page-title">💊 Módulo de Farmacia</h1>

    <!-- ============================================ -->
    <!-- ESTADÍSTICAS - 4 CUADROS -->
    <!-- ============================================ -->
    <div class="farmacia-stats">
        <div class="farmacia-stat-card primary">
            <div class="farmacia-stat-value"><?= $stats['productos_con_stock'] ?></div>
            <div class="farmacia-stat-label">Productos en Stock</div>
        </div>
        
        <div class="farmacia-stat-card <?= $stats['stock_bajo'] > 0 ? 'warning' : 'success' ?>">
            <div class="farmacia-stat-value"><?= $stats['stock_bajo'] ?></div>
            <div class="farmacia-stat-label">Stock Bajo</div>
        </div>
        
        <div class="farmacia-stat-card <?= $stats['requisiciones_pendientes'] > 0 ? 'warning' : 'success' ?>">
            <div class="farmacia-stat-value"><?= $stats['requisiciones_pendientes'] ?></div>
            <div class="farmacia-stat-label">Req. Pendientes</div>
        </div>
        
        <div class="farmacia-stat-card <?= $stats['recetas_pendientes'] > 0 ? 'info' : 'success' ?>">
            <div class="farmacia-stat-value"><?= $stats['recetas_pendientes'] ?></div>
            <div class="farmacia-stat-label">Recetas Pendientes</div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- ACCESOS RÁPIDOS - 6 BOTONES -->
    <!-- ============================================ -->
    <div class="farmacia-grid">
        <a href="productos.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">📦</div>
            <div class="farmacia-grid-text">Productos</div>
        </a>

        <a href="entrada.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">⬇️</div>
            <div class="farmacia-grid-text">Entradas</div>
        </a>

        <a href="despacho.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">💊</div>
            <div class="farmacia-grid-text">Despacho</div>
        </a>

        <a href="lotes.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">🏷️</div>
            <div class="farmacia-grid-text">Lotes</div>
        </a>

        <a href="requisiciones.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">📝</div>
            <div class="farmacia-grid-text">Requisiciones</div>
        </a>

        <a href="reportes.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">📊</div>
            <div class="farmacia-grid-text">Reportes</div>
        </a>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN DE RECETAS PENDIENTES (NUEVO) -->
    <!-- ============================================ -->
    <div class="card" style="margin-top: var(--spacing-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>📋 Recetas Pendientes</h3>
            <div>
                <span class="badge badge-warning" style="margin-right: 10px;">Hoy: <?= $recetas_hoy ?></span>
                <span class="badge badge-info">Total: <?= $stats['recetas_pendientes'] ?></span>
            </div>
        </div>
        
        <?php if (empty($primeras_recetas)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">📭 No hay recetas pendientes</p>
                <p style="margin-top: var(--spacing-sm);">Las recetas creadas por los doctores aparecerán aquí automáticamente</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Medicamentos</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($primeras_recetas as $r): ?>
                            <tr>
                                <td><strong>#<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($r['fecha'])) ?></td>
                                <td><?= htmlspecialchars($r['paciente']) ?></td>
                                <td><?= $r['medicamentos'] ?> medicamentos</td>
                                <td>
                                    <a href="despacho.php?receta=<?= $r['id'] ?>" 
                                       class="btn btn-sm btn-success">
                                        💊 Despachar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($stats['recetas_pendientes'] > 5): ?>
                <div style="text-align: right; margin-top: var(--spacing-md);">
                    <a href="despacho.php">Ver todas las recetas pendientes (<?= $stats['recetas_pendientes'] ?>) →</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ============================================ -->
    <!-- ÚLTIMAS ENTRADAS -->
    <!-- ============================================ -->
    <div class="card" style="margin-top: var(--spacing-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>📦 Últimas Entradas</h3>
            <a href="entrada.php" class="btn btn-sm btn-outline">Nueva Entrada</a>
        </div>

        <?php if (empty($entradas)): ?>
            <p class="text-muted" style="text-align: center; padding: var(--spacing-lg);">
                No hay movimientos recientes
            </p>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entradas as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['fecha']) ?></td>
                                <td><?= htmlspecialchars($e['producto']) ?></td>
                                <td><?= $e['cantidad'] ?></td>
                                <td><?= htmlspecialchars($e['usuario']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>