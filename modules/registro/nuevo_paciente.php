<?php
require_once '../../config/config.php';
$modulo_requerido = 'registro';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>➕ Nuevo Paciente</h1>
            <p style="color: var(--gray-600);">Registrar un nuevo paciente en el sistema</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver al Dashboard
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="guardar.php" id="formPaciente">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="required">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" required 
                           placeholder="Ej: Juan Pérez González" autofocus>
                </div>
                
                <div class="form-group">
                    <label class="required">CURP</label>
                    <input type="text" name="curp" class="form-control" required 
                           maxlength="18" style="text-transform:uppercase;" 
                           placeholder="18 caracteres">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" 
                           max="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="sexo" class="form-control">
                        <option value="">Seleccione</option>
                        <option value="M">Masculino</option>
                        <option value="F">Femenino</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" 
                           placeholder="Ej: 555-123-4567">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="paciente@email.com">
                </div>
            </div>

            <div class="form-group">
                <label>Dirección</label>
                <textarea name="direccion" class="form-control" rows="2" 
                          placeholder="Calle, número, colonia, ciudad"></textarea>
            </div>

            <div class="form-group">
                <label>Contacto de emergencia</label>
                <input type="text" name="numero_emergencia" class="form-control" 
                       placeholder="Nombre y teléfono">
            </div>

            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    💾 Guardar Paciente
                </button>
                <button type="reset" class="btn btn-outline">🗑️ Limpiar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Validar CURP (formato básico)
document.querySelector('input[name="curp"]').addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});

// Confirmar antes de enviar
document.getElementById('formPaciente').addEventListener('submit', function(e) {
    if (!confirm('¿Guardar este paciente?')) {
        e.preventDefault();
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>