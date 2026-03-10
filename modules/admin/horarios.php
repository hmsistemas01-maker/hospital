<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// CORRECCIÓN: TODAS las validaciones y redirecciones van ANTES de incluir el header
$doctor_id = (int) ($_GET['doctor_id'] ?? 0);

if (!$doctor_id) {
    $_SESSION['error'] = "ID de doctor no válido";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Obtener datos del doctor
$stmt = $pdo->prepare("SELECT * FROM doctores WHERE id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    $_SESSION['error'] = "Doctor no encontrado";
    header("Location: usuarios.php?rol=doctor");
    exit;
}

// Obtener horarios actuales
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

$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

// AHORA incluimos el header (después de todas las validaciones)
require_once '../../includes/header.php';
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>⏰ Horarios de Doctor</h1>
            <p style="color: var(--gray-600);">
                <strong><?= htmlspecialchars($doctor['nombre']) ?></strong> 
                (<?= htmlspecialchars($doctor['especialidad'] ?? 'Sin especialidad') ?>)
            </p>
        </div>
        <a href="usuarios.php?rol=doctor" class="btn btn-outline">
            <span>←</span> Volver a Doctores
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- Formulario para nuevo horario -->
    <div class="card" style="margin-bottom: var(--spacing-xl);">
        <h3>➕ Agregar Horario</h3>
        <form method="POST" action="guardar_horario.php" class="form-row" style="align-items: flex-end;">
            <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
            
            <div class="form-group">
                <label>Día</label>
                <select name="dia" class="form-control" required>
                    <option value="">Seleccionar...</option>
                    <?php foreach ($dias_semana as $dia): ?>
                        <option value="<?= $dia ?>"><?= $dia ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Hora inicio</label>
                <input type="time" name="hora_inicio" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Hora fin</label>
                <input type="time" name="hora_fin" class="form-control" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Guardar Horario</button>
            </div>
        </form>
    </div>

    <!-- Tabla de horarios -->
    <div class="card">
        <h3>📋 Horarios Actuales</h3>
        
        <?php if (empty($horarios)): ?>
            <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                <p>No hay horarios configurados para este doctor</p>
                <p style="margin-top: var(--spacing-sm);">Use el formulario superior para agregar horarios</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td><strong><?= $h['dia'] ?></strong></td>
                            <td><?= substr($h['hora_inicio'], 0, 5) ?></td>
                            <td><?= substr($h['hora_fin'], 0, 5) ?></td>
                            <td>
                                <a href="eliminar_horario.php?id=<?= $h['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('¿Eliminar este horario?')">
                                    🗑️ Eliminar
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