<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$estado = $_GET['estado'] ?? '';
$doctor_id = $_GET['doctor_id'] ?? '';
$paciente_id = $_GET['paciente_id'] ?? '';

// Construir consulta
$sql = "
    SELECT c.*, 
           p.nombre as paciente_nombre, p.curp, p.telefono as paciente_telefono,
           d.nombre as doctor_nombre, d.especialidad
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    WHERE c.fecha BETWEEN ? AND ?
";
$params = [$fecha_desde, $fecha_hasta];

if (!empty($estado)) {
    $sql .= " AND c.estado = ?";
    $params[] = $estado;
}

if (!empty($doctor_id)) {
    $sql .= " AND c.doctor_id = ?";
    $params[] = $doctor_id;
}

if (!empty($paciente_id)) {
    $sql .= " AND c.paciente_id = ?";
    $params[] = $paciente_id;
}

$sql .= " ORDER BY c.fecha DESC, c.hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$citas = $stmt->fetchAll();

// Obtener listados para filtros
$doctores = $pdo->query("SELECT id, nombre FROM doctores WHERE activo = 1 ORDER BY nombre")->fetchAll();
$pacientes = $pdo->query("SELECT id, nombre FROM pacientes WHERE activo = 1 ORDER BY nombre LIMIT 100")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📋 Lista de Citas</h1>
            <p style="color: var(--gray-600);">Total: <strong><?= count($citas) ?></strong> citas en el período</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="nueva.php" class="btn btn-success">
                <span>➕</span> Nueva Cita
            </a>
            <a href="calendario.php" class="btn btn-outline">
                <span>📅</span> Calendario
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>🔍 Filtros</h3>
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
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Todos</option>
                    <option value="pendiente" <?= $estado == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="atendida" <?= $estado == 'atendida' ? 'selected' : '' ?>>Atendida</option>
                    <option value="cancelada" <?= $estado == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Doctor</label>
                <select name="doctor_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $doctor_id == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Paciente</label>
                <select name="paciente_id" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $paciente_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="lista.php" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Tabla de citas -->
    <div class="card">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Doctor</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $c): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                        <td><strong><?= $c['hora'] ?></strong></td>
                        <td>
                            <strong><?= htmlspecialchars($c['paciente_nombre']) ?></strong>
                            <br>
                            <small><?= htmlspecialchars($c['curp']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($c['doctor_nombre']) ?>
                            <br>
                            <small><?= htmlspecialchars($c['especialidad'] ?? '') ?></small>
                        </td>
                        <td>
                            <?php
                            $estado_class = '';
                            switch($c['estado']) {
                                case 'pendiente':
                                    $estado_class = 'badge-warning';
                                    $icono = '⏳';
                                    break;
                                case 'atendida':
                                    $estado_class = 'badge-success';
                                    $icono = '✅';
                                    break;
                                case 'cancelada':
                                    $estado_class = 'badge-danger';
                                    $icono = '❌';
                                    break;
                            }
                            ?>
                            <span class="badge <?= $estado_class ?>">
                                <?= $icono ?> <?= ucfirst($c['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: var(--spacing-xs);">
                                <a href="detalle.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="Ver detalles">
                                    👁️
                                </a>
                                
                                <?php if ($c['estado'] == 'pendiente'): ?>
                                    <?php if ($c['fecha'] == date('Y-m-d')): ?>
                                        <a href="atender.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-success" title="Atender">
                                            ✅
                                        </a>
                                    <?php endif; ?>
                                    <a href="cancelar.php?id=<?= $c['id'] ?>" 
                                       class="btn btn-sm btn-outline" 
                                       style="border-color: var(--danger); color: var(--danger);"
                                       onclick="return confirm('¿Cancelar esta cita?')"
                                       title="Cancelar">
                                        ❌
                                    </a>
                                    <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="Editar">
                                        ✏️
                                    </a>
                                <?php endif; ?>
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