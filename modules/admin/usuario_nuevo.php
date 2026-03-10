<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener módulos disponibles
$modulos = $pdo->query("SELECT * FROM modulos ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>➕ Nuevo Usuario</h1>
            <p style="color: var(--gray-600);">Registrar un nuevo usuario en el sistema</p>
        </div>
        <a href="usuarios.php" class="btn btn-outline">
            <span>←</span> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="guardar_usuario.php" id="formUsuario">
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="required">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" required 
                           placeholder="Ej: Dr. Juan Pérez" autofocus>
                </div>
                
                <div class="form-group">
                    <label class="required">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required 
                           placeholder="jperez" pattern="[a-zA-Z0-9_]+"
                           title="Solo letras, números y guión bajo">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="required">Contraseña</label>
                    <input type="password" name="password" class="form-control" required 
                           id="password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label class="required">Confirmar contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           id="confirm_password">
                    <small id="passwordMatch" style="color: var(--gray-500);"></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="required">Rol principal</label>
                    <select name="rol" class="form-control" required id="rol">
                        <option value="">Seleccionar...</option>
                        <option value="admin">👑 Administrador</option>
                        <option value="doctor">🩺 Doctor</option>
                        <option value="farmacia">💊 Farmacia</option>
                        <option value="registro">📋 Registro</option>
                        <option value="citas">📅 Citas</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="activo" class="form-control">
                        <option value="1">✅ Activo</option>
                        <option value="0">❌ Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Campos específicos para doctor (se muestran con JS) -->
            <div id="doctor_fields" style="display: none; background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md); margin: var(--spacing-lg) 0;">
                <h4 style="margin-bottom: var(--spacing-md);">🩺 Información del Doctor</h4>
                
                <div class="form-group">
                    <label>Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" 
                           placeholder="Ej: Cardiología, Pediatría...">
                </div>
                
                <div class="form-group">
                    <label>Teléfono (opcional)</label>
                    <input type="text" name="telefono_doctor" class="form-control" 
                           placeholder="Ej: 555-123-4567">
                </div>
                
                <div class="form-group">
                    <label>Email (opcional)</label>
                    <input type="email" name="email_doctor" class="form-control" 
                           placeholder="doctor@hospital.com">
                </div>
            </div>

            <!-- Permisos por módulo -->
            <div style="margin-top: var(--spacing-lg); border-top: 2px solid var(--gray-200); padding-top: var(--spacing-lg);">
                <h4 style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                    <span>🔐</span> Permisos de Acceso por Módulo
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <?php foreach ($modulos as $m): ?>
                        <label class="checkbox-card" style="
                            display: flex;
                            align-items: center;
                            gap: var(--spacing-sm);
                            padding: var(--spacing-sm) var(--spacing-md);
                            background: var(--gray-100);
                            border-radius: var(--radius-md);
                            cursor: pointer;
                            transition: all var(--transition-fast);
                            border: 2px solid transparent;
                        ">
                            <input type="checkbox" name="modulos[]" value="<?= $m['id'] ?>" style="width: 18px; height: 18px;">
                            <span style="font-weight: 500;"><?= htmlspecialchars($m['nombre']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: var(--spacing-md);">
                    <button type="button" class="btn btn-sm btn-outline" onclick="checkAll()">✓ Seleccionar Todos</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="uncheckAll()">✗ Deseleccionar Todos</button>
                </div>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    💾 Guardar Usuario
                </button>
                <button type="reset" class="btn btn-outline">🗑️ Limpiar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Mostrar/ocultar campos de doctor según el rol seleccionado
document.getElementById('rol').addEventListener('change', function() {
    const doctorFields = document.getElementById('doctor_fields');
    if (this.value === 'doctor') {
        doctorFields.style.display = 'block';
    } else {
        doctorFields.style.display = 'none';
    }
});

// Validar contraseñas
document.getElementById('confirm_password').addEventListener('keyup', function() {
    var password = document.getElementById('password').value;
    var confirm = this.value;
    var matchMsg = document.getElementById('passwordMatch');
    
    if (password === confirm) {
        matchMsg.innerHTML = '✓ Las contraseñas coinciden';
        matchMsg.style.color = 'var(--success)';
        this.style.borderColor = 'var(--success)';
    } else {
        matchMsg.innerHTML = '✗ Las contraseñas no coinciden';
        matchMsg.style.color = 'var(--danger)';
        this.style.borderColor = 'var(--danger)';
    }
});

// Seleccionar todos los checkboxes
function checkAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = true);
}

// Deseleccionar todos los checkboxes
function uncheckAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = false);
}

// Validar formulario antes de enviar
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>