<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_GET['id'];

// Obtener datos de la cita con joins
$stmt = $pdo->prepare("
    SELECT c.*, 
           p.id as paciente_id,
           p.nombre as paciente_nombre, 
           p.curp, 
           p.fecha_nacimiento as paciente_fecha_nac,
           p.sexo as paciente_sexo, 
           p.telefono as paciente_telefono, 
           p.direccion as paciente_direccion,
           d.id as doctor_id,
           d.nombre as doctor_nombre, 
           d.especialidad,
           u.nombre as creado_por_nombre
    FROM citas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN doctores d ON c.doctor_id = d.id
    LEFT JOIN usuarios u ON c.created_by = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$cita = $stmt->fetch();

if (!$cita) {
    $_SESSION['error'] = "Cita no encontrada";
    header("Location: lista.php");
    exit;
}

require_once '../../includes/header.php';
?>

<div class="fade-in">
    <!-- Header con acciones -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>👁️ Detalle de Cita</h1>
            <p style="color: var(--gray-600);">Información completa de la cita #<?= $id ?></p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="lista.php" class="btn btn-outline">
                <span>←</span> Volver
            </a>
            <?php if ($cita['estado'] == 'pendiente'): ?>
                <a href="editar.php?id=<?= $id ?>" class="btn btn-primary">✏️ Editar</a>
                <a href="cancelar.php?id=<?= $id ?>" class="btn btn-danger" onclick="return confirm('¿Cancelar esta cita?')">❌ Cancelar</a>
                <?php if ($cita['fecha'] == date('Y-m-d')): ?>
                    <a href="../doctor/atender_cita.php?id=<?= $id ?>" class="btn btn-success">✅ Atender</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensajes de éxito/error -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Información en tarjetas -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-lg);">
        <!-- Tarjeta 1: Datos de la cita -->
        <div class="card">
            <h3 style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <span>📅</span> Datos de la Cita
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; font-weight: bold; width: 40%; border-bottom: 1px solid var(--gray-200);">Fecha:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= date('d/m/Y', strtotime($cita['fecha'])) ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Hora:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><strong><?= substr($cita['hora'], 0, 5) ?></strong></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Estado:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
                        <?php
                        $estado_class = '';
                        $icono = '';
                        switch($cita['estado']) {
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
                            <?= $icono ?> <?= ucfirst($cita['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php if (!empty($cita['motivo'])): ?>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Motivo:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= nl2br(htmlspecialchars($cita['motivo'])) ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($cita['observaciones'])): ?>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Observaciones:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= nl2br(htmlspecialchars($cita['observaciones'])) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Registrada por:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
                        <?= htmlspecialchars($cita['creado_por_nombre'] ?? 'Sistema') ?>
                        <?php if (!empty($cita['created_at'])): ?>
                            <br><small><?= date('d/m/Y H:i', strtotime($cita['created_at'])) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tarjeta 2: Datos del paciente -->
        <div class="card">
            <h3 style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <span>👤</span> Datos del Paciente
            </h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 12px; font-weight: bold; width: 40%; border-bottom: 1px solid var(--gray-200);">Nombre:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= htmlspecialchars($cita['paciente_nombre']) ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">CURP:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= htmlspecialchars($cita['curp'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Fecha Nac.:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
                        <?= $cita['paciente_fecha_nac'] ? date('d/m/Y', strtotime($cita['paciente_fecha_nac'])) : 'N/A' ?>
                        <?php if ($cita['paciente_fecha_nac']): ?>
                            (<?= date('Y') - date('Y', strtotime($cita['paciente_fecha_nac'])) ?> años)
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Sexo:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);">
                        <?= $cita['paciente_sexo'] == 'M' ? '👨 Masculino' : ($cita['paciente_sexo'] == 'F' ? '👩 Femenino' : 'N/A') ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Teléfono:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= htmlspecialchars($cita['paciente_telefono'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td style="padding: 12px; font-weight: bold; border-bottom: 1px solid var(--gray-200);">Dirección:</td>
                    <td style="padding: 12px; border-bottom: 1px solid var(--gray-200);"><?= htmlspecialchars($cita['paciente_direccion'] ?? 'N/A') ?></td>
                </tr>
            </table>
            <div style="margin-top: var(--spacing-md); text-align: right;">
                <a href="../registro/editar_paciente.php?id=<?= $cita['paciente_id'] ?>" class="btn btn-sm btn-outline">
                    📋 Ver Historial Completo
                </a>
            </div>
        </div>

        <!-- Tarjeta 3: Datos del doctor (ocupa 2 columnas) -->
        <div class="card" style="grid-column: span 2;">
            <h3 style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <span>👨‍⚕️</span> Datos del Doctor
            </h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-md);">
                <div>
                    <strong>Nombre:</strong><br>
                    <?= htmlspecialchars($cita['doctor_nombre']) ?>
                </div>
                <div>
                    <strong>Especialidad:</strong><br>
                    <?= htmlspecialchars($cita['especialidad'] ?? 'General') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>