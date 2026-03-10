<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';


// Filtros
$estado = $_GET['estado'] ?? 'pendiente';
$proveedor_id = $_GET['proveedor'] ?? '';

// Construir consulta
$sql = "
    SELECT 
        ep.*,
        p.nombre as producto_nombre,
        p.codigo as producto_codigo,
        p.unidad_medida,
        prov.nombre as proveedor_nombre,
        u.nombre as usuario_nombre,
        DATEDIFF(ep.fecha_programada, CURDATE()) as dias_restantes
    FROM entradas_programadas ep
    JOIN productos p ON ep.producto_id = p.id
    LEFT JOIN proveedores prov ON ep.proveedor_id = prov.id
    JOIN usuarios u ON ep.usuario_id = u.id
    WHERE 1=1
";

$params = [];

if ($estado != 'todos') {
    $sql .= " AND ep.estado = ?";
    $params[] = $estado;
}

if ($proveedor_id) {
    $sql .= " AND ep.proveedor_id = ?";
    $params[] = $proveedor_id;
}

$sql .= " ORDER BY 
    CASE ep.estado
        WHEN 'pendiente' THEN 1
        WHEN 'recibido' THEN 2
        ELSE 3
    END,
    ep.fecha_programada ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$programaciones = $stmt->fetchAll();

// Obtener proveedores para filtro
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Totales
$pendientes = array_filter($programaciones, fn($p) => $p['estado'] == 'pendiente');
$recibidas = array_filter($programaciones, fn($p) => $p['estado'] == 'recibido');
?>


<div class="fade-in">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📅 Programaciones de Entrada</h1>
            <p style="color: var(--gray-600);">Gestión de compras y recepciones programadas</p>
        </div>
        <div>
            <a href="programar_entrada.php" class="btn btn-success">
                <span>➕</span> Nueva Programación
            </a>
            <a href="index.php" class="btn btn-outline">← Volver</a>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-lg);">
        <div class="stat-card warning">
            <div class="stat-value"><?= count($pendientes) ?></div>
            <div class="stat-label">Pendientes</div>
        </div>
        <div class="stat-card success">
            <div class="stat-value"><?= count($recibidas) ?></div>
            <div class="stat-label">Recibidas</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card">
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="pendiente" <?= $estado == 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                    <option value="recibido" <?= $estado == 'recibido' ? 'selected' : '' ?>>Recibidas</option>
                    <option value="cancelado" <?= $estado == 'cancelado' ? 'selected' : '' ?>>Canceladas</option>
                    <option value="todos" <?= $estado == 'todos' ? 'selected' : '' ?>>Todos</option>
                </select>
            </div>
            <div class="form-group">
                <label>Proveedor</label>
                <select name="proveedor" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= $prov['id'] ?>" <?= $proveedor_id == $prov['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="programaciones.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Tabla de programaciones -->
    <div class="card">
        <h3>📋 Listado de Programaciones</h3>
        
        <?php if (empty($programaciones)): ?>
            <div class="alert alert-info">No hay programaciones para mostrar</div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha Prog.</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th>Días</th>
                            <th>Registró</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programaciones as $p): 
                            $clase_estado = $p['estado'] == 'pendiente' ? 'warning' : ($p['estado'] == 'recibido' ? 'success' : 'danger');
                            $clase_dias = $p['dias_restantes'] <= 2 ? 'danger' : ($p['dias_restantes'] <= 5 ? 'warning' : 'info');
                        ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($p['fecha_programada'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($p['producto_codigo']) ?></strong>
                                <br>
                                <small><?= htmlspecialchars($p['producto_nombre']) ?></small>
                            </td>
                            <td><?= $p['cantidad'] ?> <?= $p['unidad_medida'] ?></td>
                            <td><?= htmlspecialchars($p['proveedor_nombre'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-<?= $clase_estado ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['estado'] == 'pendiente'): ?>
                                    <span class="badge badge-<?= $clase_dias ?>">
                                        <?= $p['dias_restantes'] ?> días
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['usuario_nombre']) ?></td>
                            <td>
                                <?php if ($p['estado'] == 'pendiente'): ?>
                                    <a href="marcar_recibido.php?id=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-success"
                                       onclick="return confirm('¿Marcar como recibida?')">
                                        ✓ Recibido
                                    </a>
                                    <a href="editar_programacion.php?id=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-outline">
                                        ✏️
                                    </a>
                                    <a href="cancelar_programacion.php?id=<?= $p['id'] ?>" 
                                       class="btn btn-sm btn-outline"
                                       style="border-color: var(--danger); color: var(--danger);"
                                       onclick="return confirm('¿Cancelar esta programación?')">
                                        ✗
                                    </a>
                                <?php endif; ?>
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