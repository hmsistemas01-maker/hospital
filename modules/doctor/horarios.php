<?php
require_once '../../config/config.php';
$modulo_requerido = 'doctor';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$doctor_id = (int) ($_GET['doctor_id'] ?? 0);

if (!$doctor_id) {
    header("Location: index.php?error=ID de doctor no válido");
    exit;
}

// Obtener horarios del doctor
$stmt = $pdo->prepare("
    SELECT * FROM doctor_horarios 
    WHERE doctor_id = ? 
    ORDER BY 
        CASE dia
            WHEN 'Lunes' THEN 1
            WHEN 'Martes' THEN 2
            WHEN 'Miercoles' THEN 3
            WHEN 'Jueves' THEN 4
            WHEN 'Viernes' THEN 5
            WHEN 'Sabado' THEN 6
            ELSE 7
        END
");
$stmt->execute([$doctor_id]);
$horarios = $stmt->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>⏰ Mis Horarios de Atención</h1>
            <p style="color: var(--gray-600);">Horarios configurados para consulta</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if (empty($horarios)): ?>
        <div class="alert alert-warning">
            <strong>⚠️ No tienes horarios configurados.</strong>
            <p style="margin-top: var(--spacing-sm);">Contacta al administrador para configurar tus horarios de atención.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td><strong><?= $h['dia'] ?></strong></td>
                            <td><?= substr($h['hora_inicio'], 0, 5) ?></td>
                            <td><?= substr($h['hora_fin'], 0, 5) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>