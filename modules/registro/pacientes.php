<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$busqueda = $_GET['buscar'] ?? '';
$pagina = (int)($_GET['pagina'] ?? 1);
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta
$sql_where = "WHERE 1=1";
$params = [];

if (!empty($busqueda)) {
    $sql_where .= " AND (nombre LIKE ? OR curp LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

// Contar total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes $sql_where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_paginas = ceil($total / $por_pagina);

// Obtener pacientes
$sql = "SELECT id, nombre, curp, fecha_nacimiento, sexo, telefono, activo, fecha_registro 
        FROM pacientes 
        $sql_where 
        ORDER BY nombre ASC 
        LIMIT $offset, $por_pagina";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pacientes = $stmt->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📋 Lista de Pacientes</h1>
            <p style="color: var(--gray-600);">Total: <strong><?= $total ?></strong> pacientes registrados</p>
        </div>
        <a href="nuevo_paciente.php" class="btn btn-success">
            <span>➕</span> Nuevo Paciente
        </a>
    </div>

    <!-- Buscador -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <form method="GET" class="form-row" style="align-items: flex-end;">
            <div class="form-group" style="flex: 1;">
                <label>Buscar paciente</label>
                <input type="text" name="buscar" class="form-control" 
                       value="<?= htmlspecialchars($busqueda) ?>" 
                       placeholder="Nombre o CURP...">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                <?php if ($busqueda): ?>
                    <a href="pacientes.php" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de pacientes -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>CURP</th>
                        <th>Fecha Nac.</th>
                        <th>Sexo</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pacientes as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($p['curp']) ?></td>
                        <td><?= $p['fecha_nacimiento'] ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '-' ?></td>
                        <td><?= $p['sexo'] == 'M' ? '👨 Masculino' : '👩 Femenino' ?></td>
                        <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                        <td>
                            <?php if ($p['activo']): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($p['fecha_registro'])) ?></td>
                        <td>
                            <div style="display: flex; gap: var(--spacing-xs);">
                                <a href="editar_paciente.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Editar">
                                    ✏️
                                </a>
                                <a href="ver_expediente.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" title="Expediente">
                                    📋
                                </a>
                                <?php if ($p['activo']): ?>
                                    <a href="cambiar_estado.php?id=<?= $p['id'] ?>&estado=0" 
                                       class="btn btn-sm btn-outline" 
                                       style="border-color: var(--warning); color: var(--warning);"
                                       onclick="return confirm('¿Desactivar paciente?')">
                                        ⭕
                                    </a>
                                <?php else: ?>
                                    <a href="cambiar_estado.php?id=<?= $p['id'] ?>&estado=1" 
                                       class="btn btn-sm btn-outline"
                                       style="border-color: var(--success); color: var(--success);">
                                        ✅
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="pagination" style="margin-top: var(--spacing-lg);">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?><?= $busqueda ? '&buscar='.urlencode($busqueda) : '' ?>" 
                   class="btn btn-sm <?= $i == $pagina ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>