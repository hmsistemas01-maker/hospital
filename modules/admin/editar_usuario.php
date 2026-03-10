<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

$id = (int) $_GET['id'];

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: usuarios.php?error=Usuario no encontrado");
    exit;
}

// Obtener módulos del usuario
$stmt = $pdo->prepare("SELECT modulo_id FROM usuario_modulos WHERE usuario_id = ?");
$stmt->execute([$id]);
$modulos_usuario = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener todos los módulos
$modulos = $pdo->query("SELECT * FROM modulos ORDER BY nombre")->fetchAll();

// Si es doctor, obtener datos de la tabla doctores
$doctor_data = null;
if ($usuario['rol'] == 'doctor') {
    $stmt = $pdo->prepare("SELECT * FROM doctores WHERE nombre LIKE ?");
    $stmt->execute(["%{$usuario['nombre']}%"]);
    $doctor_data = $stmt->fetch();
}

require_once '../../includes/header.php';
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Usuario</h1>
            <p style="color: var(--gray-600);">Modificando: <strong><?= htmlspecialchars($usuario['nombre']) ?></strong></p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <?php if ($usuario['rol'] == 'doctor' && $doctor_data): ?>
                <a href="horarios.php?doctor_id=<?= $doctor_data['id'] ?>" class="btn btn-outline">
                    ⏰ Ver Horarios
                </a>
            <?php endif; ?>
            <a href="usuarios.php" class="btn btn-outline">
                <span>←</span> Volver
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['success']) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_usuario.php" id="formEditarUsuario">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
            
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="required">Nombre completo</label>
                    <input type="text" name="nombre" class="form-control" required 
                           value="<?= htmlspecialchars($usuario['nombre']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="required">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required 
                           value="<?= htmlspecialchars($usuario['usuario']) ?>"
                           readonly style="background: var(--gray-100);">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nueva contraseña</label>
                    <input type="password" name="password" class="form-control" 
                           id="password" placeholder="Dejar en blanco para mantener">
                </div>
                
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="confirm_password" class="form-control" 
                           id="confirm_password">
                    <small id="passwordMatch" style="color: var(--gray-500);"></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="required">Rol principal</label>
                    <select name="rol" class="form-control" required id="rol">
                        <option value="admin" <?= $usuario['rol'] == 'admin' ? 'selected' : '' ?>>👑 Administrador</option>
                        <option value="doctor" <?= $usuario['rol'] == 'doctor' ? 'selected' : '' ?>>🩺 Doctor</option>
                        <option value="farmacia" <?= $usuario['rol'] == 'farmacia' ? 'selected' : '' ?>>💊 Farmacia</option>
                        <option value="registro" <?= $usuario['rol'] == 'registro' ? 'selected' : '' ?>>📋 Registro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Estado</label>
                    <select name="activo" class="form-control">
                        <option value="1" <?= $usuario['activo'] ? 'selected' : '' ?>>✅ Activo</option>
                        <option value="0" <?= !$usuario['activo'] ? 'selected' : '' ?>>❌ Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Campos específicos para doctor -->
            <div id="doctor_fields" style="display: <?= $usuario['rol'] == 'doctor' ? 'block' : 'none' ?>; background: var(--gray-100); padding: var(--spacing-lg); border-radius: var(--radius-md); margin: var(--spacing-lg) 0;">
                <h4 style="margin-bottom: var(--spacing-md);">🩺 Información del Doctor</h4>
                
                <div class="form-group">
                    <label>Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" 
                           value="<?= htmlspecialchars($doctor_data['especialidad'] ?? '') ?>"
                           placeholder="Ej: Cardiología, Pediatría...">
                </div>
                
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono_doctor" class="form-control" 
                           value="<?= htmlspecialchars($doctor_data['telefono'] ?? '') ?>"
                           placeholder="Ej: 555-123-4567">
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email_doctor" class="form-control" 
                           value="<?= htmlspecialchars($doctor_data['email'] ?? '') ?>"
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
                            border: 2px solid <?= in_array($m['id'], $modulos_usuario) ? 'var(--primary)' : 'transparent' ?>;
                            background: <?= in_array($m['id'], $modulos_usuario) ? 'var(--primary-soft)' : 'var(--gray-100)' ?>;
                        ">
                            <input type="checkbox" name="modulos[]" value="<?= $m['id'] ?>" 
                                   style="width: 18px; height: 18px;"
                                   <?= in_array($m['id'], $modulos_usuario) ? 'checked' : '' ?>>
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
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 Actualizar Usuario
                </button>
                <a href="usuarios.php" class="btn btn-outline">Cancelar</a>
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

// Validar contraseñas (solo si se está cambiando)
document.getElementById('confirm_password').addEventListener('keyup', function() {
    var password = document.getElementById('password').value;
    var confirm = this.value;
    var matchMsg = document.getElementById('passwordMatch');
    
    if (password === '' && confirm === '') {
        matchMsg.innerHTML = '';
        this.style.borderColor = '';
    } else if (password === confirm) {
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
    document.querySelectorAll('.checkbox-card').forEach(card => {
        card.style.background = 'var(--primary-soft)';
        card.style.borderColor = 'var(--primary)';
    });
}

// Deseleccionar todos
function uncheckAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('.checkbox-card').forEach(card => {
        card.style.background = 'var(--gray-100)';
        card.style.borderColor = 'transparent';
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>