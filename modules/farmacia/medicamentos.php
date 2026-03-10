<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$busqueda = $_GET['buscar'] ?? '';
$stock = $_GET['stock'] ?? '';

$sql = "SELECT * FROM medicamentos WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$busqueda%";
}

if ($stock == 'bajo') {
    $sql .= " AND stock < 10";
} elseif ($stock == 'agotado') {
    $sql .= " AND stock = 0";
}

$sql .= " ORDER BY nombre ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicamentos = $stmt->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>💊 Catálogo de Medicamentos</h1>
            <p style="color: var(--gray-600);">Gestión de medicamentos y existencias</p>
        </div>
        <a href="nuevo_medicamento.php" class="btn btn-success">
            <span>➕</span> Nuevo Medicamento
        </a>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label>Buscar medicamento</label>
                <input type="text" name="buscar" class="form-control" 
                       value="<?= htmlspecialchars($busqueda) ?>" 
                       placeholder="Nombre...">
            </div>
            
            <div class="form-group">
                <label>Filtrar por stock</label>
                <select name="stock" class="form-control">
                    <option value="">Todos</option>
                    <option value="bajo" <?= $stock == 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                    <option value="agotado" <?= $stock == 'agotado' ? 'selected' : '' ?>>Agotados</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="medicamentos.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Tabla de medicamentos -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Presentación</th>
                        <th>Stock</th>
                        <th>Precio</th>
                        <th>Laboratorio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicamentos as $m): 
                        $stock_class = '';
                        if ($m['stock'] == 0) $stock_class = 'danger';
                        elseif ($m['stock'] < 10) $stock_class = 'warning';
                        else $stock_class = 'success';
                    ?>
                    <tr class="<?= $stock_class ?>">
                        <td><strong><?= htmlspecialchars($m['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($m['presentacion'] ?? 'N/A') ?></td>
                        <td style="font-weight: bold;"><?= $m['stock'] ?></td>
                        <td>$<?= number_format($m['precio'] ?? 0, 2) ?></td>
                        <td><?= htmlspecialchars($m['laboratorio'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($m['activo'] ?? true): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: var(--spacing-xs);">
                                <a href="editar_medicamento.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                                <a href="ajustar_stock.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline">📦</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>