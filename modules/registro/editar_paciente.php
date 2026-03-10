<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id = ?");
$stmt->execute([$id]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header("Location: pacientes.php?error=Paciente no encontrado");
    exit;
}
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Paciente</h1>
            <p style="color: var(--gray-600);">Modificando: <strong><?= htmlspecialchars($paciente['nombre']) ?></strong></p>
        </div>
        <a href="pacientes.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_paciente.php">
            <input type="hidden" name="id" value="<?= $paciente['id'] ?>">
            
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="required">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" required 
                           value="<?= htmlspecialchars($paciente['nombre']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="required">CURP</label>
                    <input type="text" name="curp" class="form-control" required 
                           value="<?= htmlspecialchars($paciente['curp']) ?>"
                           maxlength="18" style="text-transform:uppercase;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" 
                           value="<?= $paciente['fecha_nacimiento'] ?>">
                </div>
                
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="M" <?= $paciente['sexo'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                        <option value="F" <?= $paciente['sexo'] == 'F' ? 'selected' : '' ?>>Femenino</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" 
                           value="<?= htmlspecialchars($paciente['telefono'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($paciente['email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" class="form-control" rows="2"><?= htmlspecialchars($paciente['direccion'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="activo" class="form-control">
                    <option value="1" <?= $paciente['activo'] ? 'selected' : '' ?>>✅ Activo</option>
                    <option value="0" <?= !$paciente['activo'] ? 'selected' : '' ?>>❌ Inactivo</option>
                </select>
            </div>

            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 Actualizar Paciente
                </button>
                <a href="pacientes.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>