<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// ========== VALIDACIONES PRIMERO ==========
$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$action = $_GET['action'] ?? 'ver';

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
    ORDER BY FIELD(dia, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado')
");
$stmt->execute([$doctor_id]);
$horarios = $stmt->fetchAll();

// Obtener excepciones
$stmt = $pdo->prepare("
    SELECT * FROM doctor_excepciones 
    WHERE doctor_id = ? 
    ORDER BY 
        CASE 
            WHEN fecha_inicio >= CURDATE() AND activo = 1 THEN 1
            WHEN fecha_inicio < CURDATE() AND fecha_fin >= CURDATE() AND activo = 1 THEN 2
            ELSE 3
        END,
        fecha_inicio DESC
");
$stmt->execute([$doctor_id]);
$excepciones = $stmt->fetchAll();

$dias_semana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

// Mensajes de sesión
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// ========== AHORA INCLUIMOS EL HEADER ==========
require_once '../../includes/header.php';
?>

<div class="fade-in">
    <!-- Header con tabs -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>📅 Gestión de Horarios</h1>
            <p style="color: var(--gray-600);">
                <strong><?= htmlspecialchars($doctor['nombre']) ?></strong> 
                (<?= htmlspecialchars($doctor['especialidad'] ?? 'Sin especialidad') ?>)
            </p>
        </div>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="usuarios.php?rol=doctor" class="btn btn-outline">
                <span>←</span> Volver a Doctores
            </a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tabs de navegación -->
    <div style="display: flex; gap: var(--spacing-sm); margin-bottom: var(--spacing-lg); border-bottom: 2px solid var(--gray-200);">
        <a href="?doctor_id=<?= $doctor_id ?>&action=ver" 
           class="btn <?= $action == 'ver' ? 'btn-primary' : 'btn-outline' ?>" 
           style="border-radius: 0; border-bottom: none;">
            📋 Horarios Regulares
        </a>
        <a href="?doctor_id=<?= $doctor_id ?>&action=excepciones" 
           class="btn <?= $action == 'excepciones' ? 'btn-primary' : 'btn-outline' ?>" 
           style="border-radius: 0; border-bottom: none;">
            ⚠️ Excepciones (Vacaciones/Permisos)
        </a>
    </div>

    <?php if ($action == 'ver'): ?>
        <!-- ========== SECCIÓN DE HORARIOS REGULARES ========== -->
        
        <!-- Formulario para nuevo horario -->
        <div class="card" style="margin-bottom: var(--spacing-xl);">
            <h3>➕ Agregar Horario Regular</h3>
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

        <!-- Tabla de horarios regulares -->
        <div class="card">
            <h3>📋 Horarios Regulares</h3>
            
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
                                            Eliminar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- ========== SECCIÓN DE EXCEPCIONES ========== -->
        
        <!-- Formulario para nueva excepción -->
        <div class="card" style="margin-bottom: var(--spacing-xl); border-left: 4px solid var(--warning);">
            <h3 style="color: var(--warning);">➕ Agregar Excepción</h3>
            <form method="POST" action="guardar_excepcion.php" class="form-row" style="align-items: flex-end;">
                <input type="hidden" name="doctor_id" value="<?= $doctor_id ?>">
                
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="tipo" class="form-control" required id="tipoExcepcion">
                        <option value="">Seleccionar...</option>
                        <option value="vacaciones">🏖️ Vacaciones</option>
                        <option value="permiso">📋 Permiso</option>
                        <option value="capacitacion">📚 Capacitación</option>
                        <option value="festivo">🎉 Día Festivo</option>
                        <option value="horario_especial">⏰ Horario Especial</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>Fecha Fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                
                <!-- Campos para horario especial -->
                <div class="form-group horario-especial-field" style="display: none;">
                    <label>Hora entrada (especial)</label>
                    <input type="time" name="hora_entrada" class="form-control">
                </div>
                
                <div class="form-group horario-especial-field" style="display: none;">
                    <label>Hora salida (especial)</label>
                    <input type="time" name="hora_salida" class="form-control">
                </div>
                
                <div class="form-group" style="flex: 2;">
                    <label>Motivo *</label>
                    <input type="text" name="motivo" class="form-control" required 
                           placeholder="Ej: Vacaciones anuales, Permiso personal, Capacitación, etc.">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-warning">Guardar Excepción</button>
                </div>
            </form>
        </div>

        <!-- Tabla de excepciones -->
        <div class="card">
            <h3>📋 Excepciones Programadas</h3>
            
            <?php if (empty($excepciones)): ?>
                <div class="alert alert-info" style="text-align: center; padding: var(--spacing-xl);">
                    <p>No hay excepciones registradas para este doctor</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Motivo</th>
                                <th>Horario Especial</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $hoy = date('Y-m-d');
                            foreach ($excepciones as $e): 
                                $activa = ($e['activo'] && $e['fecha_inicio'] <= $hoy && $e['fecha_fin'] >= $hoy);
                                $proxima = ($e['activo'] && $e['fecha_inicio'] > $hoy);
                                $pasada = ($e['fecha_fin'] < $hoy);
                            ?>
                                <tr class="<?= $activa ? 'warning' : ($proxima ? 'info' : ($pasada ? 'secondary' : '')) ?>">
                                    <td>
                                        <?php 
                                        $iconos = [
                                            'vacaciones' => '🏖️',
                                            'permiso' => '📋',
                                            'capacitacion' => '📚',
                                            'festivo' => '🎉',
                                            'horario_especial' => '⏰'
                                        ];
                                        echo $iconos[$e['tipo']] ?? '📌';
                                        ?>
                                        <?= ucfirst($e['tipo']) ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($e['fecha_inicio'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($e['fecha_fin'])) ?></td>
                                    <td><?= htmlspecialchars($e['motivo']) ?></td>
                                    <td>
                                        <?php if ($e['tipo'] == 'horario_especial' && $e['hora_entrada']): ?>
                                            <?= substr($e['hora_entrada'], 0, 5) ?> - <?= substr($e['hora_salida'], 0, 5) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($activa): ?>
                                            <span class="badge badge-warning">En curso</span>
                                        <?php elseif ($proxima): ?>
                                            <span class="badge badge-info">Próxima</span>
                                        <?php elseif (!$e['activo']): ?>
                                            <span class="badge badge-danger">Cancelada</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Finalizada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($e['activo'] && ($proxima || $activa)): ?>
                                            <a href="cancelar_excepcion.php?id=<?= $e['id'] ?>" 
                                               class="btn btn-sm btn-outline"
                                               style="border-color: var(--danger); color: var(--danger);"
                                               onclick="return confirm('¿Cancelar esta excepción?')">
                                                Cancelar
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Leyenda de colores -->
                <div style="display: flex; gap: var(--spacing-lg); margin-top: var(--spacing-md); padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md); flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
                        <div style="width: 20px; height: 20px; background: var(--warning-light); border-radius: var(--radius-sm);"></div>
                        <span>En curso (activa ahora)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
                        <div style="width: 20px; height: 20px; background: var(--info-light); border-radius: var(--radius-sm);"></div>
                        <span>Próxima (futura)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: var(--spacing-xs);">
                        <div style="width: 20px; height: 20px; background: var(--gray-200); border-radius: var(--radius-sm);"></div>
                        <span>Finalizada</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Mostrar/ocultar campos de horario especial
document.getElementById('tipoExcepcion')?.addEventListener('change', function() {
    const horarioFields = document.querySelectorAll('.horario-especial-field');
    if (this.value === 'horario_especial') {
        horarioFields.forEach(field => field.style.display = 'block');
    } else {
        horarioFields.forEach(field => field.style.display = 'none');
    }
});

// Validar que fecha fin sea mayor o igual a fecha inicio
document.querySelector('input[name="fecha_fin"]')?.addEventListener('change', function() {
    const inicio = document.querySelector('input[name="fecha_inicio"]').value;
    const fin = this.value;
    if (fin < inicio) {
        alert('La fecha fin debe ser mayor o igual a la fecha inicio');
        this.value = inicio;
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>