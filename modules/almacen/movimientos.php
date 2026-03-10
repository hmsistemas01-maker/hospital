<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo = $_GET['tipo'] ?? '';
$producto_id = $_GET['producto'] ?? '';

// Construir consulta - SIN proveedores porque no están en movimientos_inventario
$sql = "
    SELECT 
        m.id,
        m.producto_id,
        m.tipo_movimiento,
        m.cantidad,
        m.motivo,
        m.fecha_movimiento,
        p.nombre as producto_nombre,
        p.codigo as producto_codigo,
        c.nombre as categoria_nombre,
        u.nombre as usuario_nombre
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    JOIN categorias_productos c ON p.categoria_id = c.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha_movimiento) BETWEEN :desde AND :hasta
";

$params = [
    ':desde' => $fecha_desde,
    ':hasta' => $fecha_hasta
];

if (!empty($tipo)) {
    $sql .= " AND m.tipo_movimiento = :tipo";
    $params[':tipo'] = $tipo;
}

if (!empty($producto_id)) {
    $sql .= " AND m.producto_id = :producto";
    $params[':producto'] = $producto_id;
}

$sql .= " ORDER BY m.fecha_movimiento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

// Obtener productos para filtro
$productos = $pdo->query("
    SELECT id, codigo, nombre 
    FROM productos 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Calcular totales
$total_entradas = 0;
$total_salidas = 0;
foreach ($movimientos as $m) {
    if ($m['tipo_movimiento'] == 'entrada') $total_entradas += $m['cantidad'];
    if ($m['tipo_movimiento'] == 'salida') $total_salidas += $m['cantidad'];
}
?>

<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl); flex-wrap: wrap;">
        <div>
            <h1>📋 Historial de Movimientos</h1>
            <p style="color: var(--gray-600);">Registro completo de entradas y salidas de almacén</p>
        </div>
        <div>
            <span class="badge badge-success">Entradas: <?= $total_entradas ?></span>
            <span class="badge badge-warning">Salidas: <?= $total_salidas ?></span>
            <span class="badge badge-info">Total: <?= count($movimientos) ?></span>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card">
        <h3>🔍 Filtrar movimientos</h3>
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <div class="form-group">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?= $fecha_desde ?>">
            </div>
            
            <div class="form-group">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= $fecha_hasta ?>">
            </div>
            
            <div class="form-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $tipo == 'entrada' ? 'selected' : '' ?>>⬆️ Entradas</option>
                    <option value="salida" <?= $tipo == 'salida' ? 'selected' : '' ?>>⬇️ Salidas</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Producto</label>
                <select name="producto_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $producto_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="movimientos.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Tabla de movimientos -->
    <div class="card">
        <h3>📋 Listado de movimientos</h3>
        
        <?php if (empty($movimientos)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">📭 No hay movimientos en el período seleccionado</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) ?></td>
                            <td>
                                <?php if ($m['tipo_movimiento'] == 'entrada'): ?>
                                    <span class="badge badge-success">⬆️ Entrada</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">⬇️ Salida</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($m['producto_codigo']) ?></strong>
                                <br>
                                <small><?= htmlspecialchars($m['producto_nombre']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($m['categoria_nombre']) ?></td>
                            <td style="font-weight: bold; <?= $m['tipo_movimiento'] == 'entrada' ? 'color: var(--success);' : 'color: var(--warning);' ?>">
                                <?= $m['tipo_movimiento'] == 'entrada' ? '+' : '-' ?><?= $m['cantidad'] ?>
                            </td>
                            <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                            <td><?= htmlspecialchars($m['motivo'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Exportar a CSV -->
            <div style="margin-top: var(--spacing-lg); text-align: right;">
                <button onclick="exportarCSV()" class="btn btn-sm btn-outline">
                    📥 Exportar a CSV
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportarCSV() {
    // Obtener datos de la tabla
    const filas = document.querySelectorAll('table tbody tr');
    if (filas.length === 0) return;
    
    let csv = [];
    
    // Encabezados
    csv.push('Fecha,Hora,Tipo,Producto,Categoría,Cantidad,Usuario,Motivo');
    
    // Datos
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        const fecha = celdas[0]?.innerText || '';
        const tipo = celdas[1]?.innerText.trim() || '';
        const producto = celdas[2]?.innerText.replace(/\n/g, ' ') || '';
        const categoria = celdas[3]?.innerText || '';
        const cantidad = celdas[4]?.innerText || '';
        const usuario = celdas[5]?.innerText || '';
        const motivo = celdas[6]?.innerText || '';
        
        csv.push(`"${fecha}","${tipo}","${producto}","${categoria}","${cantidad}","${usuario}","${motivo}"`);
    });
    
    // Descargar
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'movimientos_<?= $fecha_desde ?>_<?= $fecha_hasta ?>.csv';
    a.click();
}
</script>

<?php require_once '../../includes/footer.php'; ?>