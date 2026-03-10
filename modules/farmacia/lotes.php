<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener filtros
$producto_id = $_GET['producto'] ?? '';
$estado = $_GET['estado'] ?? 'activos';
$busqueda = $_GET['busqueda'] ?? '';

// Construir consulta base
$sql = "
    SELECT 
        l.*,
        p.nombre as producto,
        p.codigo,
        p.categoria_id,
        c.nombre as categoria,
        DATEDIFF(l.fecha_vencimiento, CURDATE()) as dias_restantes
    FROM lote l
    JOIN productos p ON l.producto_id = p.id
    LEFT JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE l.activo = 1
";

$params = [];

// Aplicar filtros según estado
switch ($estado) {
    case 'activos':
        $sql .= " AND (l.fecha_vencimiento IS NULL OR l.fecha_vencimiento > CURDATE())";
        break;
        
    case 'proximos_6_meses':
        $sql .= " AND l.fecha_vencimiento IS NOT NULL 
                  AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)";
        break;
        
    case 'proximos_3_meses':
        $sql .= " AND l.fecha_vencimiento IS NOT NULL 
                  AND l.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
        break;
        
    case 'vencidos':
        $sql .= " AND l.fecha_vencimiento IS NOT NULL 
                  AND l.fecha_vencimiento < CURDATE()";
        break;
        
    case 'todos':
        // Sin filtro de fecha
        break;
}

// Filtro por producto
if (!empty($producto_id)) {
    $sql .= " AND l.producto_id = ?";
    $params[] = $producto_id;
}

// Filtro de búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre LIKE ? OR l.numero_lote LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY 
            CASE 
                WHEN l.fecha_vencimiento IS NULL THEN 3
                WHEN l.fecha_vencimiento < CURDATE() THEN 1
                WHEN l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) THEN 2
                WHEN l.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH) THEN 3
                ELSE 4
            END,
            l.fecha_vencimiento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lotes = $stmt->fetchAll();

// Productos para el filtro
$productos = $pdo->query("
    SELECT id, nombre, codigo 
    FROM productos 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Estadísticas para los contadores
$stats = [];

// Total de lotes activos
$stmt = $pdo->query("SELECT COUNT(*) FROM lote WHERE activo = 1");
$stats['total'] = $stmt->fetchColumn();

// Lotes próximos a 3 meses
$stmt = $pdo->query("
    SELECT COUNT(*) FROM lote 
    WHERE activo = 1 
    AND fecha_vencimiento IS NOT NULL
    AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
");
$stats['proximos_3'] = $stmt->fetchColumn();

// Lotes próximos a 6 meses
$stmt = $pdo->query("
    SELECT COUNT(*) FROM lote 
    WHERE activo = 1 
    AND fecha_vencimiento IS NOT NULL
    AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
");
$stats['proximos_6'] = $stmt->fetchColumn();

// Lotes vencidos
$stmt = $pdo->query("
    SELECT COUNT(*) FROM lote 
    WHERE activo = 1 
    AND fecha_vencimiento IS NOT NULL
    AND fecha_vencimiento < CURDATE()
");
$stats['vencidos'] = $stmt->fetchColumn();
?>

<div class="fade-in">
    <!-- HEADER CON BOTÓN VOLVER -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>🏷️ Control de Lotes</h1>
            <p style="color: var(--gray-600);">Gestión de lotes y fechas de vencimiento</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-lg);">
        <div class="stat-card primary">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Lotes</div>
        </div>
        
        <div class="stat-card <?= $stats['proximos_3'] > 0 ? 'danger' : 'success' ?>">
            <div class="stat-value"><?= $stats['proximos_3'] ?></div>
            <div class="stat-label">≤ 3 meses 🔴</div>
        </div>
        
        <div class="stat-card <?= $stats['proximos_6'] > 0 ? 'warning' : 'success' ?>">
            <div class="stat-value"><?= $stats['proximos_6'] ?></div>
            <div class="stat-label">≤ 6 meses 🟡</div>
        </div>
        
        <div class="stat-card <?= $stats['vencidos'] > 0 ? 'danger' : 'success' ?>">
            <div class="stat-value"><?= $stats['vencidos'] ?></div>
            <div class="stat-label">Vencidos ⚠️</div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>🔍 Filtros</h3>
        <form method="GET" class="form-row">
            <div class="form-group" style="flex: 1;">
                <label>Producto</label>
                <select name="producto" class="form-control">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $producto_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?> (<?= $p['codigo'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activos" <?= $estado == 'activos' ? 'selected' : '' ?>>Lotes activos</option>
                    <option value="proximos_6_meses" <?= $estado == 'proximos_6_meses' ? 'selected' : '' ?>>Próximos ≤ 6 meses 🟡</option>
                    <option value="proximos_3_meses" <?= $estado == 'proximos_3_meses' ? 'selected' : '' ?>>Próximos ≤ 3 meses 🔴</option>
                    <option value="vencidos" <?= $estado == 'vencidos' ? 'selected' : '' ?>>Vencidos ⚠️</option>
                    <option value="todos" <?= $estado == 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1.5;">
                <label>Buscar</label>
                <input type="text" name="busqueda" class="form-control" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Producto o lote...">
            </div>
            
            <div class="form-group" style="align-self: flex-end;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="lotes.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- TABLA DE LOTES -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th>F. Vencimiento</th>
                        <th>Stock Actual</th>
                        <th>Stock Inicial</th>
                        <th>Estado</th>
                        <th>Días</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lotes)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: var(--spacing-xl); color: var(--gray-500);">
                                No hay lotes para mostrar
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lotes as $l): 
                            $vencido = $l['fecha_vencimiento'] && strtotime($l['fecha_vencimiento']) < time();
                            $proximo_3 = $l['dias_restantes'] <= 90 && $l['dias_restantes'] > 0;
                            $proximo_6 = $l['dias_restantes'] <= 180 && $l['dias_restantes'] > 90;
                        ?>
                        <tr class="<?= $vencido ? 'danger' : ($proximo_3 ? 'danger' : ($proximo_6 ? 'warning' : '')) ?>">
                            <td>
                                <strong><?= htmlspecialchars($l['producto']) ?></strong>
                                <br><small><?= $l['codigo'] ?></small>
                            </td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($l['numero_lote']) ?></span></td>
                            <td><?= $l['fecha_vencimiento'] ? date('d/m/Y', strtotime($l['fecha_vencimiento'])) : 'N/A' ?></td>
                            <td style="font-weight: bold;"><?= $l['cantidad_actual'] ?></td>
                            <td><?= $l['cantidad_inicial'] ?></td>
                            <td>
                                <?php if ($vencido): ?>
                                    <span class="badge badge-danger">VENCIDO</span>
                                <?php elseif ($proximo_3): ?>
                                    <span class="badge badge-danger">≤ 3 MESES 🔴</span>
                                <?php elseif ($proximo_6): ?>
                                    <span class="badge badge-warning">≤ 6 MESES 🟡</span>
                                <?php else: ?>
                                    <span class="badge badge-success">VIGENTE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($l['fecha_vencimiento']): ?>
                                    <?php if ($vencido): ?>
                                        Vencido hace <?= abs($l['dias_restantes']) ?> días
                                    <?php else: ?>
                                        <?= $l['dias_restantes'] ?> días
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>