<?php
require_once '../../config/config.php';
$modulo_requerido = 'farmacia';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener recetas pendientes con sus medicamentos
$recetas = $pdo->query("
    SELECT r.*, h.paciente_id, p.nombre as paciente_nombre, d.nombre as doctor_nombre,
           GROUP_CONCAT(CONCAT(m.nombre, ' (', rm.cantidad, ')') SEPARATOR ', ') as medicamentos
    FROM recetas r
    JOIN historial_clinico h ON r.historial_id = h.id
    JOIN pacientes p ON h.paciente_id = p.id
    JOIN doctores d ON h.doctor_id = d.id
    LEFT JOIN receta_medicamentos rm ON r.id = rm.receta_id
    LEFT JOIN medicamentos m ON rm.medicamento_id = m.id
    WHERE r.estado IS NULL OR r.estado != 'dispensada'
    GROUP BY r.id
    ORDER BY r.fecha DESC
")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📋 Recetas Pendientes</h1>
            <p style="color: var(--gray-600);">Dispensación de medicamentos</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <div class="card">
        <?php if (empty($recetas)): ?>
            <div class="alert alert-success" style="text-align: center; padding: var(--spacing-xl);">
                <p style="font-size: 1.2rem;">✅ No hay recetas pendientes</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Paciente</th>
                            <th>Doctor</th>
                            <th>Medicamentos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recetas as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['fecha'])) ?></td>
                            <td><strong><?= htmlspecialchars($r['paciente_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($r['doctor_nombre']) ?></td>
                            <td><?= htmlspecialchars($r['medicamentos'] ?? 'Sin medicamentos') ?></td>
                            <td>
                                <a href="dispensar.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success">
                                    💊 Dispensar
                                </a>
                                <a href="ver_receta.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">
                                    👁️ Ver
                                </a>
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