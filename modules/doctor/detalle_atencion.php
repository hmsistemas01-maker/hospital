<?php
require_once '../../config/config.php';
$modulo_requerido = 'doctor';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$historial_id = (int) ($_GET['historial_id'] ?? 0);

if (!$historial_id) {
    header("Location: index.php");
    exit;
}

// Obtener datos del historial
$stmt = $pdo->prepare("
    SELECT h.*, 
           p.nombre as paciente_nombre, p.curp, p.fecha_nacimiento, p.telefono,
           d.nombre as doctor_nombre, d.especialidad,
           c.fecha as cita_fecha, c.hora as cita_hora
    FROM historial_clinico h
    JOIN pacientes p ON h.paciente_id = p.id
    JOIN doctores d ON h.doctor_id = d.id
    LEFT JOIN citas c ON c.paciente_id = h.paciente_id 
        AND c.doctor_id = h.doctor_id 
        AND DATE(c.fecha) = DATE(h.fecha)
    WHERE h.id = ?
");
$stmt->execute([$historial_id]);
$historial = $stmt->fetch();

if (!$historial) {
    header("Location: index.php?error=Registro no encontrado");
    exit;
}

// Obtener receta si existe
$stmt = $pdo->prepare("SELECT * FROM recetas WHERE historial_id = ?");
$stmt->execute([$historial_id]);
$receta = $stmt->fetch();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📋 Detalle de Atención</h1>
            <p style="color: var(--gray-600);">Registro de consulta médica</p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="index.php" class="btn btn-outline">
                <span>←</span> Volver
            </a>
            <?php if ($receta): ?>
                <a href="../farmacia/ver_receta.php?id=<?= $receta['id'] ?>" class="btn btn-primary">
                    💊 Ver Receta
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>📅 Información de la Consulta</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md);">
            <div>
                <strong>Fecha:</strong><br>
                <?= date('d/m/Y H:i', strtotime($historial['fecha'])) ?>
            </div>
            <div>
                <strong>Doctor:</strong><br>
                <?= htmlspecialchars($historial['doctor_nombre']) ?>
            </div>
            <div>
                <strong>Especialidad:</strong><br>
                <?= htmlspecialchars($historial['especialidad'] ?? 'General') ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>👤 Paciente</h3>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--spacing-md);">
            <div>
                <strong>Nombre:</strong><br>
                <?= htmlspecialchars($historial['paciente_nombre']) ?>
            </div>
            <div>
                <strong>CURP:</strong><br>
                <?= htmlspecialchars($historial['curp'] ?? 'N/A') ?>
            </div>
            <div>
                <strong>Teléfono:</strong><br>
                <?= htmlspecialchars($historial['telefono'] ?? 'N/A') ?>
            </div>
        </div>
    </div>

    <?php if ($historial['presion_arterial'] || $historial['temperatura'] || $historial['peso'] || $historial['altura']): ?>
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>📊 Signos Vitales</h3>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-md);">
            <?php if ($historial['presion_arterial']): ?>
            <div>
                <strong>Presión Arterial:</strong><br>
                <?= htmlspecialchars($historial['presion_arterial']) ?>
            </div>
            <?php endif; ?>
            <?php if ($historial['temperatura']): ?>
            <div>
                <strong>Temperatura:</strong><br>
                <?= htmlspecialchars($historial['temperatura']) ?> °C
            </div>
            <?php endif; ?>
            <?php if ($historial['peso']): ?>
            <div>
                <strong>Peso:</strong><br>
                <?= htmlspecialchars($historial['peso']) ?> kg
            </div>
            <?php endif; ?>
            <?php if ($historial['altura']): ?>
            <div>
                <strong>Altura:</strong><br>
                <?= htmlspecialchars($historial['altura']) ?> m
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>🔍 Diagnóstico</h3>
        <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <?= nl2br(htmlspecialchars($historial['diagnostico'])) ?>
        </div>
    </div>

    <?php if (!empty($historial['alergias'])): ?>
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>⚠️ Alergias</h3>
        <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <?= nl2br(htmlspecialchars($historial['alergias'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($historial['enfermedades_cronicas'])): ?>
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>🏥 Enfermedades Crónicas</h3>
        <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <?= nl2br(htmlspecialchars($historial['enfermedades_cronicas'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($historial['notas'])): ?>
    <div class="card" style="margin-bottom: var(--spacing-lg);">
        <h3>📝 Notas Adicionales</h3>
        <div style="background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md);">
            <?= nl2br(htmlspecialchars($historial['notas'])) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>