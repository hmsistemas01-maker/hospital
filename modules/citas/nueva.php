<?php
require_once '../../config/config.php';
$modulo_requerido = 'citas';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener listados para selects
$pacientes = $pdo->query("
    SELECT id, nombre FROM pacientes 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

$doctores = $pdo->query("
    SELECT id, nombre, especialidad FROM doctores 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Si viene una fecha pre-seleccionada
$fecha_preseleccionada = $_GET['fecha'] ?? date('Y-m-d');
$doctor_preseleccionado = $_GET['doctor_id'] ?? '';
$paciente_preseleccionado = $_GET['paciente_id'] ?? '';

// Mensajes de sesión
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<div class="fade-in">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>➕ Nueva Cita</h1>
            <p style="color: var(--gray-600);">Agendar una nueva cita médica</p>
        </div>
        <a href="index.php" class="btn btn-outline">
            <span>←</span> Volver al Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="guardar.php" id="formCita">
            <div class="form-group">
                <label class="required">Paciente</label>
                <select name="paciente_id" class="form-control" required id="paciente_id">
                    <option value="">-- Seleccione un paciente --</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $paciente_preseleccionado == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--gray-500);">
                    <a href="../registro/nuevo_paciente.php" target="_blank">➕ Registrar nuevo paciente</a>
                </small>
            </div>
            
            <div class="form-group">
                <label class="required">Doctor</label>
                <select name="doctor_id" class="form-control" required id="doctor_id">
                    <option value="">-- Seleccione un doctor --</option>
                    <?php foreach ($doctores as $d): ?>
                        <option value="<?= $d['id'] ?>" 
                                <?= $doctor_preseleccionado == $d['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre']) ?> 
                            (<?= htmlspecialchars($d['especialidad'] ?? 'General') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="required">Fecha</label>
                    <input type="date" name="fecha" class="form-control" 
                           required min="<?= date('Y-m-d') ?>" 
                           value="<?= $fecha_preseleccionada ?>"
                           id="fecha">
                </div>
                
                <div class="form-group">
                    <label class="required">Hora</label>
                    <input type="time" name="hora" class="form-control" required id="hora">
                </div>
            </div>

            <!-- Horarios disponibles (se actualiza vía AJAX) -->
            <div id="horarios_disponibles" style="display: none; margin-top: var(--spacing-md);">
                <label>Horarios disponibles:</label>
                <div id="lista_horarios" style="display: flex; flex-wrap: wrap; gap: var(--spacing-sm); margin-top: var(--spacing-sm);"></div>
            </div>

            <!-- Nuevos campos -->
            <div class="form-group">
                <label>Motivo de la consulta</label>
                <textarea name="motivo" class="form-control" rows="2" 
                          placeholder="¿Por qué motivo solicita la consulta?"></textarea>
            </div>
            
            <div class="form-group">
                <label>Observaciones adicionales</label>
                <textarea name="observaciones" class="form-control" rows="2" 
                          placeholder="Información adicional relevante..."></textarea>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-success" style="flex: 1;">
                    💾 Guardar Cita
                </button>
                <button type="reset" class="btn btn-outline">🗑️ Limpiar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Cargar horarios disponibles cuando se selecciona doctor y fecha
document.getElementById('doctor_id').addEventListener('change', cargarHorarios);
document.getElementById('fecha').addEventListener('change', cargarHorarios);

function cargarHorarios() {
    const doctorId = document.getElementById('doctor_id').value;
    const fecha = document.getElementById('fecha').value;
    const horariosDiv = document.getElementById('horarios_disponibles');
    const listaHorarios = document.getElementById('lista_horarios');
    
    if (!doctorId || !fecha) {
        horariosDiv.style.display = 'none';
        return;
    }
    
    // Llamada AJAX para obtener horarios disponibles
    fetch(`ajax/horarios_disponibles.php?doctor_id=${doctorId}&fecha=${fecha}`)
        .then(response => response.json())
        .then(data => {
            listaHorarios.innerHTML = '';
            if (data.length > 0) {
                data.forEach(hora => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-outline';
                    btn.textContent = hora;
                    btn.onclick = function() {
                        document.getElementById('hora').value = hora;
                        document.querySelectorAll('#lista_horarios button').forEach(b => 
                            b.classList.remove('btn-primary'));
                        this.classList.add('btn-primary');
                    };
                    listaHorarios.appendChild(btn);
                });
                horariosDiv.style.display = 'block';
            } else {
                listaHorarios.innerHTML = '<p class="text-muted">No hay horarios disponibles para este día</p>';
                horariosDiv.style.display = 'block';
            }
        });
}

// Validar que la hora esté entre 8 AM y 8 PM (ajusta según necesidades)
document.getElementById('hora').addEventListener('change', function() {
    const hora = this.value;
    if (hora < '08:00' || hora > '20:00') {
        alert('La hora debe estar entre 8:00 AM y 8:00 PM');
        this.value = '';
    }
});

// Confirmar antes de enviar
document.getElementById('formCita').addEventListener('submit', function(e) {
    if (!document.getElementById('hora').value) {
        e.preventDefault();
        alert('Debe seleccionar una hora');
        return false;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>