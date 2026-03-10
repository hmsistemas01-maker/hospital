<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener estadísticas
$stats = [];

// Citas de hoy
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE fecha = CURDATE() AND estado = 'pendiente'
");
$stmt->execute();
$stats['citas_hoy'] = $stmt->fetchColumn();

// Citas de mañana
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND estado = 'pendiente'
");
$stmt->execute();
$stats['citas_manana'] = $stmt->fetchColumn();

// Citas atendidas hoy
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE fecha = CURDATE() AND estado = 'atendida'
");
$stmt->execute();
$stats['atendidas_hoy'] = $stmt->fetchColumn();

// Total citas pendientes
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM citas 
    WHERE fecha >= CURDATE() AND estado = 'pendiente'
");
$stmt->execute();
$stats['pendientes'] = $stmt->fetchColumn();

// Próximas citas (siguientes 10)
$proximas_citas = $pdo->query("
    SELECT c.*, p.nombre as paciente_nombre, d.nombre as doctor_nombre
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    WHERE c.fecha >= CURDATE() AND c.estado = 'pendiente'
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 10
")->fetchAll();

// Doctores disponibles
$doctores = $pdo->query("
    SELECT d.*, COUNT(c.id) as citas_hoy
    FROM doctores d
    LEFT JOIN citas c ON d.id = c.doctor_id AND c.fecha = CURDATE()
    WHERE d.activo = 1
    GROUP BY d.id
    ORDER BY d.nombre
")->fetchAll();

// Obtener el primer día del mes actual
$primer_dia_mes = date('Y-m-01');
$ultimo_dia_mes = date('Y-m-t');

// Citas del mes agrupadas por día para el calendario
$citas_mes = $pdo->prepare("
    SELECT DATE(fecha) as fecha, COUNT(*) as total
    FROM citas
    WHERE fecha BETWEEN ? AND ?
    GROUP BY DATE(fecha)
");
$citas_mes->execute([$primer_dia_mes, $ultimo_dia_mes]);
$citas_por_dia = [];
while ($row = $citas_mes->fetch()) {
    $citas_por_dia[$row['fecha']] = $row['total'];
}
?>

<div class="fade-in">
    <!-- Header del módulo -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📅 Módulo de Citas</h1>
            <p style="color: var(--gray-600);">Agendamiento y control de citas médicas</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="nueva.php" class="btn btn-success">
                <span>➕</span> Nueva Cita
            </a>
            <a href="calendario.php" class="btn btn-primary">
                <span>📅</span> Calendario
            </a>
            <a href="lista.php" class="btn btn-outline">
                <span>📋</span> Lista
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
        <div class="stat-card primary">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📅</div>
            <div class="stat-value"><?= $stats['citas_hoy'] ?></div>
            <div class="stat-label">Citas para Hoy</div>
            <?php if ($stats['citas_hoy'] > 0): ?>
                <small>Pendientes de atender</small>
            <?php endif; ?>
        </div>
        
        <div class="stat-card info">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">📆</div>
            <div class="stat-value"><?= $stats['citas_manana'] ?></div>
            <div class="stat-label">Citas para Mañana</div>
        </div>
        
        <div class="stat-card success">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">✅</div>
            <div class="stat-value"><?= $stats['atendidas_hoy'] ?></div>
            <div class="stat-label">Atendidas Hoy</div>
        </div>
        
        <div class="stat-card warning">
            <div style="font-size: 2.5rem; margin-bottom: var(--spacing-sm);">⏳</div>
            <div class="stat-value"><?= $stats['pendientes'] ?></div>
            <div class="stat-label">Pendientes Total</div>
        </div>
    </div>

    <!-- Dos columnas: Próximas citas y Doctores -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
        <!-- Próximas citas -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
                <h3>⏰ Próximas Citas</h3>
                <a href="lista.php" class="btn btn-sm btn-outline">Ver todas →</a>
            </div>
            
            <?php if (empty($proximas_citas)): ?>
                <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                    <p style="font-size: 1.2rem;">📭 No hay citas programadas</p>
                    <a href="nueva.php" class="btn btn-success mt-3">Agendar primera cita</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Doctor</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximas_citas as $c): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                                <td><strong><?= $c['hora'] ?></strong></td>
                                <td><?= htmlspecialchars($c['paciente_nombre']) ?></td>
                                <td><?= htmlspecialchars($c['doctor_nombre']) ?></td>
                                <td>
                                    <div style="display: flex; gap: var(--spacing-xs);">
                                        <a href="detalle.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="Ver detalles">
                                            👁️
                                        </a>
                                        <?php if ($c['fecha'] == date('Y-m-d')): ?>
                                            <a href="atender.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-success" title="Atender">
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
            <?php endif; ?>
        </div>

        <!-- Doctores y sus citas hoy -->
        <div class="card">
            <h3>👨‍⚕️ Doctores - Citas Hoy</h3>
            <div style="margin-top: var(--spacing-md);">
                <?php foreach ($doctores as $d): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md); margin-bottom: var(--spacing-sm);">
                        <div>
                            <strong><?= htmlspecialchars($d['nombre']) ?></strong>
                            <br>
                            <small><?= htmlspecialchars($d['especialidad'] ?? 'General') ?></small>
                        </div>
                        <div>
                            <span class="badge badge-primary"><?= $d['citas_hoy'] ?> citas</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: var(--spacing-md); text-align: center;">
                <a href="calendario.php" class="btn btn-outline btn-sm">Ver calendario completo</a>
            </div>
        </div>
    </div>

    <!-- Mini calendario del mes actual -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg);">
            <h3>📆 Calendario de <?= date('F Y') ?></h3>
            <a href="calendario.php" class="btn btn-sm btn-primary">Ver mes completo</a>
        </div>
        
        <?php
        // Generar mini calendario
        $dia_actual = date('j');
        $primer_dia = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $dias_en_mes = date('t');
        $dia_semana_inicio = date('w', $primer_dia);
        $dia_semana_inicio = $dia_semana_inicio == 0 ? 6 : $dia_semana_inicio - 1;
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center;">
            <div style="font-weight: bold; padding: 8px;">L</div>
            <div style="font-weight: bold; padding: 8px;">M</div>
            <div style="font-weight: bold; padding: 8px;">M</div>
            <div style="font-weight: bold; padding: 8px;">J</div>
            <div style="font-weight: bold; padding: 8px;">V</div>
            <div style="font-weight: bold; padding: 8px;">S</div>
            <div style="font-weight: bold; padding: 8px;">D</div>
            
            <?php for ($i = 0; $i < $dia_semana_inicio; $i++): ?>
                <div style="padding: 8px;"></div>
            <?php endfor; ?>
            
            <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++): 
                $fecha_actual = date('Y-m') . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                $tiene_citas = isset($citas_por_dia[$fecha_actual]);
                $es_hoy = ($dia == $dia_actual);
            ?>
                <div style="padding: 8px; background: <?= $es_hoy ? 'var(--primary-soft)' : 'transparent' ?>; border-radius: var(--radius-sm);">
                    <strong><?= $dia ?></strong>
                    <?php if ($tiene_citas): ?>
                        <div style="width: 6px; height: 6px; background: var(--primary); border-radius: 50%; margin: 2px auto;"></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>