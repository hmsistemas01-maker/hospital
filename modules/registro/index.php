<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener estadísticas del módulo
$stats = [];

// Total de pacientes
$stmt = $pdo->query("SELECT COUNT(*) FROM pacientes WHERE activo = 1");
$stats['total_pacientes'] = $stmt->fetchColumn();

// Pacientes inactivos
$stmt = $pdo->query("SELECT COUNT(*) FROM pacientes WHERE activo = 0");
$stats['pacientes_inactivos'] = $stmt->fetchColumn();

// Pacientes registrados hoy
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE DATE(fecha_registro) = CURDATE()");
$stmt->execute();
$stats['registrados_hoy'] = $stmt->fetchColumn();

// Últimos pacientes registrados
$ultimos_pacientes = $pdo->query("
    SELECT id, nombre, curp, fecha_registro 
    FROM pacientes 
    WHERE activo = 1 
    ORDER BY fecha_registro DESC 
    LIMIT 5
")->fetchAll();
?>

<div class="fade-in">
    <!-- Header del módulo -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📋 Módulo de Registro</h1>
            <p style="color: var(--gray-600);">Gestión de pacientes y expedientes clínicos</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="nuevo_paciente.php" class="btn btn-success">
                <span>➕</span> Nuevo Paciente
            </a>
            <a href="pacientes.php" class="btn btn-primary">
                <span>📋</span> Ver Todos
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
        <div class="stat-card primary">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">👥</div>
            <div class="stat-value"><?= $stats['total_pacientes'] ?></div>
            <div class="stat-label">Pacientes Activos</div>
        </div>
        
        <div class="stat-card <?= $stats['pacientes_inactivos'] > 0 ? 'warning' : 'success' ?>">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">⭕</div>
            <div class="stat-value"><?= $stats['pacientes_inactivos'] ?></div>
            <div class="stat-label">Pacientes Inactivos</div>
        </div>
        
        <div class="stat-card info">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📅</div>
            <div class="stat-value"><?= $stats['registrados_hoy'] ?></div>
            <div class="stat-label">Registrados Hoy</div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <h2 style="margin-bottom: var(--spacing-lg);">Accesos Rápidos</h2>
    <div class="grid-modulos" style="margin-bottom: var(--spacing-xl);">
        <a href="pacientes.php" class="card-modulo">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📋</div>
            <h3>Lista de Pacientes</h3>
            <p style="font-size: var(--font-size-sm); opacity: 0.8;">Ver y gestionar todos los pacientes</p>
        </a>
        
        <a href="nuevo_paciente.php" class="card-modulo">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">➕</div>
            <h3>Registrar Paciente</h3>
            <p style="font-size: var(--font-size-sm); opacity: 0.8;">Agregar un nuevo paciente al sistema</p>
        </a>
        
        <a href="buscar_paciente.php" class="card-modulo">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">🔍</div>
            <h3>Buscar Paciente</h3>
            <p style="font-size: var(--font-size-sm); opacity: 0.8;">Búsqueda por nombre o CURP</p>
        </a>
        
        <a href="reportes.php" class="card-modulo">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📊</div>
            <h3>Reportes</h3>
            <p style="font-size: var(--font-size-sm); opacity: 0.8;">Estadísticas y análisis</p>
        </a>
    </div>

    <!-- Últimos pacientes registrados -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>🆕 Últimos Pacientes Registrados</h3>
            <a href="pacientes.php" class="btn btn-sm btn-outline">Ver todos →</a>
        </div>
        
        <?php if (empty($ultimos_pacientes)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">📭 No hay pacientes registrados</p>
                <a href="nuevo_paciente.php" class="btn btn-success mt-3">Registrar primer paciente</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>CURP</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pacientes as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($p['curp']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['fecha_registro'])) ?></td>
                            <td>
                                <a href="editar_paciente.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️ Editar</a>
                                <a href="ver_expediente.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">📋 Expediente</a>
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