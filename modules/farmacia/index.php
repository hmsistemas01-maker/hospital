<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';



// Obtener estadísticas
$stats = [];

try {
    // Productos con stock
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT p.id) 
        FROM productos p
        INNER JOIN lote l ON p.id = l.producto_id
        WHERE l.activo = 1 AND l.cantidad_actual > 0
    ");
    $stats['productos_con_stock'] = $stmt->fetchColumn() ?: 0;

    // Productos con stock bajo
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT p.id, 
                   COALESCE((SELECT SUM(cantidad_actual) FROM lote WHERE producto_id = p.id AND activo = 1), 0) as stock_total,
                   p.stock_minimo
            FROM productos p
            WHERE p.activo = 1
            HAVING stock_total <= p.stock_minimo AND stock_total > 0
        ) as bajos
    ");
    $stats['stock_bajo'] = $stmt->fetchColumn() ?: 0;

    // Requisiciones pendientes
    $stmt = $pdo->query("SELECT COUNT(*) FROM requisiciones WHERE estado = 'pendiente'");
    $stats['requisiciones_pendientes'] = $stmt->fetchColumn() ?: 0;

    // Recetas pendientes TOTALES (no solo de hoy)
    $stmt = $pdo->query("SELECT COUNT(*) FROM recetas WHERE estado = 'pendiente'");
    $stats['recetas_pendientes'] = $stmt->fetchColumn() ?: 0;

    // Últimas entradas
    $entradas = $pdo->query("
        SELECT 
            DATE_FORMAT(m.fecha_movimiento, '%d/%m/%Y') as fecha,
            p.nombre as producto,
            m.cantidad,
            u.usuario
        FROM movimientos_inventario m
        JOIN productos p ON m.producto_id = p.id
        JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.tipo_movimiento = 'entrada'
        ORDER BY m.fecha_movimiento DESC
        LIMIT 5
    ")->fetchAll();

    // ===== NUEVO: OBTENER RECETAS PENDIENTES =====
    $recetas_pendientes = $pdo->query("
        SELECT r.id, r.fecha, 
               p.nombre as paciente, 
               d.nombre as doctor,
               COUNT(rd.id) as total_medicamentos
        FROM recetas r
        LEFT JOIN pacientes p ON r.id_paciente = p.id
        LEFT JOIN doctores d ON r.id_doctor = d.id
        LEFT JOIN receta_detalles rd ON r.id = rd.id_receta
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
    $recetas_pendientes = [];
}
?>

<div class="fade-in">
    <h1 class="page-title">💊 Módulo de Farmacia</h1>

    <!-- Stats - 4 cuadros estilo dashboard -->
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

    <!-- Accesos rápidos -->
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
        
        <a href="reportes.php" class="farmacia-grid-item">
            <div class="farmacia-grid-icon">📊</div>
            <div class="farmacia-grid-text">Reportes</div>
        </a>
    </div>

    <!-- ===== NUEVA SECCIÓN: RECETAS PENDIENTES ===== -->
    <div class="farmacia-card">
        <div class="farmacia-card-header">
            <h3>
                <span>📋</span> Recetas Pendientes
            </h3>
            <a href="despacho.php" class="btn-link">
                Ver todas <span>→</span>
            </a>
        </div>
        
        <?php if (empty($recetas_pendientes)): ?>
            <div class="farmacia-empty">
                No hay recetas pendientes
            </div>
        <?php else: ?>
            <div class="recetas-lista">
                <?php foreach ($recetas_pendientes as $r): ?>
                <div class="receta-card pendiente">
                    <div class="receta-header">
                        <span class="receta-folio">#<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></span>
                        <span class="receta-fecha"><?= date('d/m/Y', strtotime($r['fecha'])) ?></span>
                    </div>
                    <div class="receta-info">
                        <div class="receta-info-item">
                            <span class="receta-info-label">Paciente</span>
                            <span class="receta-info-value"><?= htmlspecialchars($r['paciente'] ?? 'Sin paciente') ?></span>
                        </div>
                        <div class="receta-info-item">
                            <span class="receta-info-label">Doctor</span>
                            <span class="receta-info-value"><?= htmlspecialchars($r['doctor'] ?? 'Sin doctor') ?></span>
                        </div>
                        <div class="receta-info-item">
                            <span class="receta-info-label">Medicamentos</span>
                            <span class="receta-info-value"><?= $r['total_medicamentos'] ?> items</span>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <a href="despacho.php?receta=<?= $r['id'] ?>" class="btn btn-sm btn-success">Atender</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Últimas entradas -->
    <div class="farmacia-card">
        <div class="farmacia-card-header">
            <h3>
                <span>📦</span> Últimas Entradas
            </h3>
            <a href="entrada.php" class="btn-link">
                Ver todas <span>→</span>
            </a>
        </div>
        
        <?php if (empty($entradas)): ?>
            <div class="farmacia-empty">
                No hay movimientos recientes
            </div>
        <?php else: ?>
            <div class="farmacia-tabla-container">
                <table class="farmacia-tabla">
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