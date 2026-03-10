<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// CORREGIDO: Mover toda la lógica de verificación ANTES de incluir el header
$id = (int) $_GET['id'];

// Obtener datos de la cita
$stmt = $pdo->prepare("SELECT * FROM citas WHERE id = ? AND estado = 'pendiente'");
$stmt->execute([$id]);
$cita = $stmt->fetch();

if (!$cita) {
    header("Location: lista.php?error=Cita no encontrada o ya no es editable");
    exit;
}

// AHORA incluimos el header (después de todas las validaciones)
require_once '../../includes/header.php';

// Obtener listados
$pacientes = $pdo->query("SELECT id, nombre FROM pacientes WHERE activo = 1 ORDER BY nombre")->fetchAll();
$doctores = $pdo->query("SELECT id, nombre, especialidad FROM doctores WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Cita</h1>
            <p style="color: var(--gray-600);">Modificando cita #<?= $id ?> para <strong><?= htmlspecialchars($cita['paciente_id']) ?></strong></p>
        </div>
        <a href="detalle.php?id=<?= $id ?>" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="form-group">
                <label class="required">Paciente</label>
                <select name="paciente_id" class="form-control" required>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $cita['paciente_id'] == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="required">Doctor</label>
                <select name="doctor_id" class="form-control" required>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $cita['doctor_id'] == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?> 
                            <?= $d['especialidad'] ? '(' . htmlspecialchars($d['especialidad']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="required">Fecha</label>
                    <input type="date" name="fecha" class="form-control" 
                           required min="<?= date('Y-m-d') ?>" 
                           value="<?= $cita['fecha'] ?>">
                </div>
                
                <div class="form-group">
                    <label class="required">Hora</label>
                    <input type="time" name="hora" class="form-control" 
                           required value="<?= $cita['hora'] ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Motivo de la consulta</label>
                <textarea name="motivo" class="form-control" rows="3"><?= htmlspecialchars($cita['motivo'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 Actualizar Cita
                </button>
                <a href="detalle.php?id=<?= $id ?>" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>